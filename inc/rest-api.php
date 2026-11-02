<?php
/**
 * Catalog REST API (namespace bk/v1):
 *   GET /products      filtered catalog pages (server-rendered cards)
 *   GET /search        live search with Syrian dialect synonyms + SKU / model match
 *
 * Read endpoints are public and cache-friendly (no nonce required).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			BK_REST_NS,
			'/products',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => 'bk_rest_products',
				'args'                => array(
					'page'     => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
					'brand'    => array( 'type' => 'string', 'default' => '' ),
					'cat'      => array( 'type' => 'string', 'default' => '' ),
					's'        => array( 'type' => 'string', 'default' => '' ),
					'base_url' => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);

		register_rest_route(
			BK_REST_NS,
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => 'bk_rest_search',
				'args'                => array(
					'q' => array( 'type' => 'string', 'required' => true ),
				),
			)
		);

	}
);

/**
 * Only same-site URLs are accepted as pagination base.
 */
function bk_rest_safe_base_url( string $url ): string {
	$url = esc_url_raw( $url );
	return ( $url && wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) ? $url : wc_get_page_permalink( 'shop' );
}

function bk_rest_products( WP_REST_Request $request ): WP_REST_Response {
	$filters = bk_catalog_filters( $request->get_params() );
	$context = array(
		'brand' => (string) $request['brand'],
		'cat'   => (string) $request['cat'],
		's'     => (string) $request['s'],
	);
	$result = bk_catalog_render( $filters, $context, (int) $request['page'], bk_rest_safe_base_url( (string) $request['base_url'] ) );

	$response = rest_ensure_response( $result );
	$response->header( 'Cache-Control', 'public, max-age=120' );
	return $response;
}

function bk_rest_search( WP_REST_Request $request ): WP_REST_Response {
	$q = trim( sanitize_text_field( (string) $request['q'] ) );
	if ( mb_strlen( $q ) < 2 ) {
		return rest_ensure_response( array( 'products' => array(), 'categories' => array(), 'brands' => array(), 'total' => 0 ) );
	}

	$data = bk_remember(
		'search2:' . bk_normalize_arabic( $q ),
		static function () use ( $q ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					's'              => $q,
					'posts_per_page' => 8,
					'no_found_rows'  => false,
					'tax_query'      => WC()->query->get_tax_query( array(), true ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			$products = array();
			foreach ( $query->posts as $post ) {
				$product = wc_get_product( $post );
				if ( ! $product ) {
					continue;
				}
				$brand      = bk_get_product_brand( $product->get_id() );
				$products[] = array(
					'id'    => $product->get_id(),
					'name'  => $product->get_name(),
					'url'   => $product->get_permalink(),
					'price' => function_exists( 'bk_price_html' ) ? bk_price_html( $product, 'text-[15px]' ) : $product->get_price_html(),
					'image' => (string) ( wp_get_attachment_image_url( $product->get_image_id(), 'bk-thumb' ) ?: bk_placeholder_src() ),
					'brand' => $brand ? $brand->name : '',
					'model' => (string) $product->get_meta( '_bk_model_code' ),
					'power' => bk_get_product_power( $product->get_id() )['short'],
				);
			}

			$needle     = bk_normalize_arabic( $q );
			$expanded   = array_map( 'bk_normalize_arabic', bk_search_expand_terms( $q ) );
			$categories = array();
			$walk       = static function ( array $nodes ) use ( &$walk, &$categories, $expanded ) {
				foreach ( $nodes as $node ) {
					$name = bk_normalize_arabic( $node['name'] );
					foreach ( $expanded as $term ) {
						if ( $term && str_contains( $name, $term ) ) {
							$categories[] = array( 'name' => $node['name'], 'url' => $node['url'], 'count' => $node['count'] );
							break;
						}
					}
					$walk( $node['children'] );
				}
			};
			$walk( bk_get_category_tree() );

			$brands = array();
			foreach ( bk_get_brands() as $brand ) {
				if ( str_contains( bk_normalize_arabic( $brand->name ), $needle ) || str_contains( $brand->slug, sanitize_title( $q ) ) ) {
					$brands[] = array( 'name' => $brand->name, 'url' => get_term_link( $brand ) );
				}
			}

			return array(
				'products'   => $products,
				'categories' => array_slice( $categories, 0, 4 ),
				'brands'     => array_slice( $brands, 0, 3 ),
				'total'      => (int) $query->found_posts,
				'all_url'    => add_query_arg( array( 's' => $q, 'post_type' => 'product' ), home_url( '/' ) ),
			);
		},
		30 * MINUTE_IN_SECONDS
	);

	$response = rest_ensure_response( $data );
	$response->header( 'Cache-Control', 'public, max-age=300' );
	return $response;
}
