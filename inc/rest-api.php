<?php
/**
 * Catalog REST API (namespace bk/v1) for the Alpine.js front end:
 *   GET /products      instant filtering & AJAX pagination (server-rendered cards)
 *   GET /search        live search with Syrian dialect synonyms + SKU / model match
 *   GET /quick-view/ID quick-view modal markup
 *   GET /compare       side-by-side comparison data
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

		register_rest_route(
			BK_REST_NS,
			'/quick-view/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => 'bk_rest_quick_view',
			)
		);

		register_rest_route(
			BK_REST_NS,
			'/compare',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => 'bk_rest_compare',
				'args'                => array(
					'ids' => array( 'type' => 'string', 'required' => true ),
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
		'search:' . bk_normalize_arabic( $q ),
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
					'price' => $product->get_price_html(),
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

function bk_rest_quick_view( WP_REST_Request $request ) {
	$product = wc_get_product( (int) $request['id'] );
	if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
		return new WP_Error( 'bk_not_found', __( 'المنتج غير متوفر', 'baytkahraba' ), array( 'status' => 404 ) );
	}
	return rest_ensure_response(
		array(
			'id'   => $product->get_id(),
			'html' => bk_part( 'template-parts/product/quick-view', array( 'product' => $product ), true ),
		)
	);
}

/**
 * Normalized comparison payload for one product.
 */
function bk_compare_payload( WC_Product $product ): array {
	$id    = $product->get_id();
	$power = bk_get_product_power( $id );
	$brand = bk_get_product_brand( $id );
	$cat   = bk_product_primary_category( $id );
	$specs = bk_product_specs( $product );

	return array(
		'id'          => $id,
		'name'        => $product->get_name(),
		'url'         => $product->get_permalink(),
		'image'       => (string) ( wp_get_attachment_image_url( $product->get_image_id(), 'bk-card' ) ?: bk_placeholder_src() ),
		'price'       => (float) $product->get_price(),
		'price_html'  => $product->get_price_html(),
		'brand'       => $brand ? $brand->name : '—',
		'category'    => $cat ? implode( ' › ', wp_list_pluck( bk_category_chain( $cat->term_id ), 'name' ) ) : '—',
		'warranty'    => bk_product_warranty( $product ),
		'wattage'     => $power['wattage'],
		'ampere'      => $power['ampere'],
		'power'       => $power['label'] ?: '—',
		'power_class' => $power['class'],
		'capacity'    => $specs['capacity']['value'] ?? '—',
		'dimensions'  => $specs['dims']['value'] ?? '—',
		'model'       => $specs['model']['value'] ?? '—',
		'in_stock'    => $product->is_in_stock(),
		'stock'       => $product->is_in_stock() ? __( 'متوفر في المحل', 'baytkahraba' ) : __( 'غير متوفر حالياً', 'baytkahraba' ),
		'purchasable' => $product->is_purchasable() && $product->is_type( 'simple' ),
	);
}

function bk_rest_compare( WP_REST_Request $request ): WP_REST_Response {
	$max = max( 2, (int) bk_option( 'compare_max', 4 ) );
	$ids = array_slice( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $request['ids'] ) ) ) ), 0, $max );
	$out = array();
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product && 'publish' === $product->get_status() ) {
			$out[] = bk_compare_payload( $product );
		}
	}
	return rest_ensure_response( array( 'products' => $out ) );
}
