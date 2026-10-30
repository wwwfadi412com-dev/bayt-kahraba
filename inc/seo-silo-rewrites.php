<?php
/**
 * Brand × Category intersection silos: /brand/{brand-slug}/{category-slug}/
 *
 * - Works for ANY existing or future brand + parent/child/sub-child category.
 * - Resolves to a real main query (product_brand + product_cat), so paging,
 *   sorting and WooCommerce price filters keep working natively.
 * - Empty combinations return a proper 404 (no thin pages).
 * - If the second segment is a child BRAND (hierarchical brands) the request
 *   gracefully falls back to that brand's archive.
 * - All valid intersections are published in a dedicated XML sitemap.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

const BK_REWRITE_VERSION = '3';

function bk_brand_base(): string {
	$tax  = get_taxonomy( BK_BRAND_TAX );
	$slug = $tax && ! empty( $tax->rewrite['slug'] ) ? $tax->rewrite['slug'] : 'brand';
	return trim( (string) $slug, '/' );
}

add_action(
	'init',
	static function (): void {
		$base = preg_quote( bk_brand_base(), '#' );
		add_rewrite_rule( "^{$base}/([^/]+)/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?post_type=product&bk_silo=1&product_brand=$matches[1]&bk_silo_cat=$matches[2]&paged=$matches[3]', 'top' );
		add_rewrite_rule( "^{$base}/([^/]+)/([^/]+)/?$", 'index.php?post_type=product&bk_silo=1&product_brand=$matches[1]&bk_silo_cat=$matches[2]', 'top' );

		// Flush once when rules change (theme update / first activation) — never on every request.
		if ( get_option( 'bk_rewrite_version' ) !== BK_REWRITE_VERSION ) {
			add_action(
				'wp_loaded',
				static function () {
					flush_rewrite_rules( false );
					update_option( 'bk_rewrite_version', BK_REWRITE_VERSION, true );
				}
			);
		}
	},
	25 // After taxonomies so the brand base is known.
);

add_action( 'after_switch_theme', static fn() => delete_option( 'bk_rewrite_version' ) );

add_filter(
	'query_vars',
	static function ( array $vars ): array {
		return array_merge( $vars, array( 'bk_silo', 'bk_silo_cat' ) );
	}
);

/**
 * Translate the silo request into a native taxonomy query.
 */
add_filter(
	'request',
	static function ( array $vars ): array {
		if ( empty( $vars['bk_silo'] ) || empty( $vars['bk_silo_cat'] ) ) {
			return $vars;
		}
		$second = sanitize_title( $vars['bk_silo_cat'] );

		if ( term_exists( $second, 'product_cat' ) ) {
			$vars['product_cat'] = $second;
			return $vars;
		}
		// Hierarchical brand path (/brand/parent/child/) → child brand archive.
		if ( term_exists( $second, BK_BRAND_TAX ) ) {
			unset( $vars['bk_silo'], $vars['bk_silo_cat'], $vars['post_type'] );
			$vars[ BK_BRAND_TAX ] = $second;
			return $vars;
		}
		return array( 'error' => '404' );
	}
);

function bk_is_silo(): bool {
	return (bool) get_query_var( 'bk_silo' ) && ! is_404();
}

/**
 * Current silo context (cached per request).
 *
 * @return array{brand: ?WP_Term, cat: ?WP_Term, count: int}
 */
function bk_get_silo_context(): array {
	static $context = null;
	if ( null !== $context ) {
		return $context;
	}
	$brand   = get_term_by( 'slug', (string) get_query_var( BK_BRAND_TAX ), BK_BRAND_TAX );
	$cat     = get_term_by( 'slug', (string) get_query_var( 'product_cat' ), 'product_cat' );
	$context = array(
		'brand' => $brand instanceof WP_Term ? $brand : null,
		'cat'   => $cat instanceof WP_Term ? $cat : null,
		'count' => ( $brand && $cat ) ? bk_intersection_count( $brand->term_id, $cat->term_id ) : 0,
	);
	return $context;
}

/**
 * 404 for unknown or empty combinations; make the brand the queried object.
 */
add_action(
	'wp',
	static function ( WP $wp ): void {
		if ( ! get_query_var( 'bk_silo' ) ) {
			return;
		}
		$ctx = bk_get_silo_context();
		if ( ! $ctx['brand'] || ! $ctx['cat'] || $ctx['count'] < 1 ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}
);

// The intersection URL is canonical by itself; never redirect it to a single term archive.
add_filter( 'redirect_canonical', static fn( $redirect ) => get_query_var( 'bk_silo' ) ? false : $redirect );

/**
 * Load the dedicated intersection landing template (after WooCommerce's loader).
 */
add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( bk_is_silo() ) {
			$silo = locate_template( 'templates/brand-intersection.php' );
			if ( $silo ) {
				return $silo;
			}
		}
		return $template;
	},
	99
);

/**
 * Public URL of an intersection page.
 *
 * @param WP_Term|int $brand Brand.
 * @param WP_Term|int $cat   Category.
 */
function bk_silo_url( $brand, $cat ): string {
	$brand = $brand instanceof WP_Term ? $brand : get_term( (int) $brand, BK_BRAND_TAX );
	$cat   = $cat instanceof WP_Term ? $cat : get_term( (int) $cat, 'product_cat' );
	if ( ! $brand instanceof WP_Term || ! $cat instanceof WP_Term ) {
		return '';
	}
	if ( ! get_option( 'permalink_structure' ) ) {
		return add_query_arg( array( 'bk_silo' => 1, BK_BRAND_TAX => $brand->slug, 'bk_silo_cat' => $cat->slug, 'post_type' => 'product' ), home_url( '/' ) );
	}
	return user_trailingslashit( home_url( bk_brand_base() . '/' . $brand->slug . '/' . $cat->slug ) );
}

/**
 * Every valid intersection (used by the sitemap and llms-full.txt).
 *
 * @return array<int, array{brand_id:int, cat_id:int, count:int}>
 */
function bk_all_silos( int $min_products = 1 ): array {
	$out = array();
	foreach ( bk_get_catalog_matrix()['by_brand'] as $brand_id => $cats ) {
		foreach ( $cats as $cat_id => $count ) {
			if ( $count >= $min_products && (int) get_option( 'default_product_cat' ) !== (int) $cat_id ) {
				$out[] = array( 'brand_id' => (int) $brand_id, 'cat_id' => (int) $cat_id, 'count' => (int) $count );
			}
		}
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * XML sitemap provider: wp-sitemap-bksilo-1.xml
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	static function (): void {
		if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) {
			return;
		}

		/**
		 * Sitemap of brand × category landing pages.
		 */
		final class BK_Silo_Sitemap_Provider extends WP_Sitemaps_Provider {
			public function __construct() {
				$this->name        = 'bksilo';
				$this->object_type = 'bksilo';
			}

			public function get_url_list( $page_num, $object_subtype = '' ) {
				$per   = wp_sitemaps_get_max_urls( $this->object_type );
				$slice = array_slice( bk_all_silos(), ( $page_num - 1 ) * $per, $per );
				return array_values(
					array_filter(
						array_map(
							static fn( $silo ) => array( 'loc' => bk_silo_url( $silo['brand_id'], $silo['cat_id'] ) ),
							$slice
						),
						static fn( $row ) => '' !== $row['loc']
					)
				);
			}

			public function get_max_num_pages( $object_subtype = '' ) {
				return (int) ceil( count( bk_all_silos() ) / wp_sitemaps_get_max_urls( $this->object_type ) );
			}
		}

		wp_register_sitemap_provider( 'bksilo', new BK_Silo_Sitemap_Provider() );
	},
	30
);
