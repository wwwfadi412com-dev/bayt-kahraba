<?php
/**
 * Single source of truth for catalog filtering.
 *
 * The same filter definition drives:
 *   1) the main WP query (crawlable, no-JS, shareable filtered URLs), and
 *   2) the REST endpoint used by the Alpine.js instant filter.
 *
 * URL parameters: power, amp_max, brand_filter, cat_filter, min_price,
 * max_price, orderby, in_stock.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize filter parameters from any source ($_GET or REST params).
 *
 * @return array{power: string, amp_max: float, brand_filter: string, cat_filter: string, min_price: float, max_price: float, orderby: string, in_stock: bool}
 */
function bk_catalog_filters( array $source ): array {
	$power = sanitize_key( (string) ( $source['power'] ?? '' ) );
	return array(
		'power'        => ( 'solar' === $power || isset( bk_power_classes()[ $power ] ) ) ? $power : '',
		'amp_max'      => max( 0, (float) ( $source['amp_max'] ?? 0 ) ),
		'brand_filter' => sanitize_title( (string) ( $source['brand_filter'] ?? '' ) ),
		'cat_filter'   => sanitize_title( (string) ( $source['cat_filter'] ?? '' ) ),
		'min_price'    => max( 0, (float) ( $source['min_price'] ?? 0 ) ),
		'max_price'    => max( 0, (float) ( $source['max_price'] ?? 0 ) ),
		'orderby'      => sanitize_key( (string) ( $source['orderby'] ?? '' ) ),
		'in_stock'     => ! empty( $source['in_stock'] ),
	);
}

function bk_catalog_has_active_filters( array $filters ): bool {
	return (bool) ( $filters['power'] || $filters['amp_max'] || $filters['brand_filter'] || $filters['cat_filter'] || $filters['min_price'] || $filters['max_price'] || $filters['in_stock'] );
}

/**
 * Translate filters into tax_query / meta_query fragments.
 *
 * @return array{tax_query: array, meta_query: array}
 */
function bk_catalog_clauses( array $filters ): array {
	$tax  = array();
	$meta = array();

	if ( 'solar' === $filters['power'] ) {
		// "Runs on battery / inverter / solar" = solar_ready + low_amp.
		$meta[] = array( 'key' => '_solar_inverter_friendly', 'value' => array( 'solar_ready', 'low_amp' ), 'compare' => 'IN' );
	} elseif ( $filters['power'] ) {
		$meta[] = array( 'key' => '_solar_inverter_friendly', 'value' => $filters['power'] );
	}
	if ( $filters['amp_max'] ) {
		$meta[] = array( 'key' => '_ampere_draw', 'value' => $filters['amp_max'], 'compare' => '<=', 'type' => 'DECIMAL(10,2)' );
		$meta[] = array( 'key' => '_ampere_draw', 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL(10,2)' );
	}
	if ( $filters['in_stock'] ) {
		$meta[] = array( 'key' => '_stock_status', 'value' => 'instock' );
	}
	if ( $filters['brand_filter'] ) {
		$tax[] = array( 'taxonomy' => BK_BRAND_TAX, 'field' => 'slug', 'terms' => $filters['brand_filter'] );
	}
	if ( $filters['cat_filter'] ) {
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $filters['cat_filter'], 'include_children' => true );
	}
	return array( 'tax_query' => $tax, 'meta_query' => $meta );
}

/**
 * Apply filters to the main product archive query.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$is_catalog = $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) || $query->get( 'bk_silo' );
		if ( ! $is_catalog ) {
			return;
		}
		$filters = bk_catalog_filters( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$clauses = bk_catalog_clauses( $filters );

		if ( $clauses['tax_query'] ) {
			$query->set( 'tax_query', array_merge( (array) $query->get( 'tax_query' ), $clauses['tax_query'] ) );
		}
		if ( $clauses['meta_query'] ) {
			$query->set( 'meta_query', array_merge( (array) $query->get( 'meta_query' ), $clauses['meta_query'] ) );
		}
		$query->set( 'posts_per_page', (int) bk_option( 'products_per_page', 24 ) );
	},
	20
);

add_filter( 'loop_shop_per_page', static fn() => (int) bk_option( 'products_per_page', 24 ), 20 );

/**
 * Price range restriction via the indexed WC lookup table (REST queries).
 */
add_filter(
	'posts_clauses',
	static function ( array $clauses, WP_Query $query ): array {
		$min = (float) $query->get( 'bk_min_price' );
		$max = (float) $query->get( 'bk_max_price' );
		if ( ! $min && ! $max ) {
			return $clauses;
		}
		global $wpdb;
		if ( ! str_contains( $clauses['join'], 'bk_price_lookup' ) ) {
			$clauses['join'] .= " INNER JOIN {$wpdb->wc_product_meta_lookup} bk_price_lookup ON {$wpdb->posts}.ID = bk_price_lookup.product_id ";
		}
		if ( $min ) {
			$clauses['where'] .= $wpdb->prepare( ' AND bk_price_lookup.max_price >= %f ', $min );
		}
		if ( $max ) {
			$clauses['where'] .= $wpdb->prepare( ' AND bk_price_lookup.min_price <= %f ', $max );
		}
		return $clauses;
	},
	10,
	2
);

/**
 * Build WP_Query args for REST / secondary catalog queries.
 *
 * @param array $filters Output of bk_catalog_filters().
 * @param array $context { brand: slug, cat: slug, s: string, ids: int[], exclude: int[] }.
 * @param int   $page    1-based page.
 * @param int   $per_page Items per page.
 */
function bk_catalog_query_args( array $filters, array $context = array(), int $page = 1, int $per_page = 0 ): array {
	$clauses = bk_catalog_clauses( $filters );
	$tax     = WC()->query->get_tax_query( $clauses['tax_query'], true ); // Adds visibility + hide-out-of-stock rules.
	$meta    = $clauses['meta_query'];

	if ( ! empty( $context['brand'] ) ) {
		$tax[] = array( 'taxonomy' => BK_BRAND_TAX, 'field' => 'slug', 'terms' => sanitize_title( $context['brand'] ) );
	}
	if ( ! empty( $context['cat'] ) ) {
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => sanitize_title( $context['cat'] ), 'include_children' => true );
	}

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $per_page ?: (int) bk_option( 'products_per_page', 24 ),
		'paged'               => max( 1, $page ),
		'tax_query'           => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_query'          => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
		'ignore_sticky_posts' => true,
		'bk_min_price'        => $filters['min_price'],
		'bk_max_price'        => $filters['max_price'],
	);
	if ( ! empty( $context['s'] ) ) {
		$args['s'] = sanitize_text_field( $context['s'] );
	}
	if ( ! empty( $context['exclude'] ) ) {
		$args['post__not_in'] = array_map( 'intval', (array) $context['exclude'] );
	}

	$ordering = WC()->query->get_catalog_ordering_args( $filters['orderby'] ?: '', '' );
	$args     = array_merge( $args, array_filter( $ordering, static fn( $v ) => '' !== $v && null !== $v ) );

	return apply_filters( 'bk_catalog_query_args', $args, $filters, $context );
}

/**
 * Run a catalog query and return rendered cards + pagination.
 *
 * @return array{html: string, total: int, pages: int, page: int, pagination: string}
 */
function bk_catalog_render( array $filters, array $context, int $page, string $base_url ): array {
	$args  = bk_catalog_query_args( $filters, $context, $page );
	$query = new WP_Query( $args );
	WC()->query->remove_ordering_args();

	$html = '';
	if ( $query->have_posts() ) {
		$position = 0;
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= (string) bk_part( 'template-parts/product/card', array( 'product' => wc_get_product( get_the_ID() ), 'position' => $position++ ), true );
		}
		wp_reset_postdata();
	}

	return array(
		'html'       => $html,
		'total'      => (int) $query->found_posts,
		'pages'      => (int) $query->max_num_pages,
		'page'       => max( 1, $page ),
		'pagination' => bk_catalog_pagination( (int) $query->max_num_pages, max( 1, $page ), $base_url, $filters, ! empty( $context['s'] ) ? array( 's' => $context['s'], 'post_type' => 'product' ) : array() ),
	);
}

/**
 * Pagination links that preserve active filters.
 */
function bk_catalog_pagination( int $pages, int $current, string $base_url, array $filters = array(), array $extra_args = array() ): string {
	if ( $pages < 2 ) {
		return '';
	}
	$base_url = preg_replace( '#/page/\d+/?#', '/', strtok( $base_url, '?' ) );
	$query    = array_filter(
		$extra_args + array(
			'power'        => $filters['power'] ?? '',
			'amp_max'      => ! empty( $filters['amp_max'] ) ? $filters['amp_max'] : '',
			'brand_filter' => $filters['brand_filter'] ?? '',
			'cat_filter'   => $filters['cat_filter'] ?? '',
			'min_price'    => ! empty( $filters['min_price'] ) ? $filters['min_price'] : '',
			'max_price'    => ! empty( $filters['max_price'] ) ? $filters['max_price'] : '',
			'orderby'      => $filters['orderby'] ?? '',
			'in_stock'     => ! empty( $filters['in_stock'] ) ? 1 : '',
		)
	);
	$links = paginate_links(
		array(
			'base'      => trailingslashit( $base_url ) . 'page/%#%/',
			'format'    => '',
			'current'   => $current,
			'total'     => $pages,
			'type'      => 'array',
			'add_args'  => $query,
			'prev_text' => __( 'السابق', 'baytkahraba' ),
			'next_text' => __( 'التالي', 'baytkahraba' ),
			'mid_size'  => 1,
		)
	);
	if ( ! $links ) {
		return '';
	}
	$out = '<nav class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="' . esc_attr__( 'صفحات المنتجات', 'baytkahraba' ) . '" data-bk-pagination>';
	foreach ( $links as $link ) {
		$is_current = str_contains( $link, 'current' );
		$classes    = $is_current ? 'bg-brand-navy text-white border-brand-navy' : 'bg-white text-brand-ink border-brand-line hover:border-brand-royal';
		$out       .= preg_replace( '/class="([^"]*)"/', 'class="$1 inline-grid min-w-10 h-10 place-items-center rounded-xl border px-3 text-sm font-semibold bk-num ' . $classes . '"', $link, 1 );
	}
	return $out . '</nav>';
}

/* -------------------------------------------------------------------------
 * Related-product engines (internal linking mesh)
 * ---------------------------------------------------------------------- */

/**
 * Lightweight ID query (no counts, no meta/term cache priming).
 *
 * @return WC_Product[]
 */
function bk_query_products( array $args, int $limit ): array {
	$ids = get_posts(
		array_merge(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => WC()->query->get_tax_query( array(), true ), // phpcs:ignore WordPress.DB.SlowDBQuery
			),
			$args
		)
	);
	return array_values( array_filter( array_map( 'wc_get_product', $ids ) ) );
}

/**
 * Same deepest category, price within ±30%, closest price first.
 *
 * @return WC_Product[]
 */
function bk_similar_price_products( WC_Product $product, int $limit = 10 ): array {
	$cat = bk_product_primary_category( $product->get_id() );
	if ( ! $cat ) {
		return array();
	}
	$price = (float) $product->get_price();
	$ids   = bk_remember(
		'similar-price:' . $product->get_id(),
		static function () use ( $product, $cat, $price, $limit ) {
			$args = array(
				'post__not_in' => array( $product->get_id() ),
				'tax_query'    => array_merge( WC()->query->get_tax_query( array(), true ), array( array( 'taxonomy' => 'product_cat', 'terms' => $cat->term_id ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			);
			if ( $price > 0 ) {
				$args['meta_query'] = array( array( 'key' => '_price', 'value' => array( $price * 0.7, $price * 1.3 ), 'compare' => 'BETWEEN', 'type' => 'DECIMAL(20,2)' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			}
			$found = bk_query_products( $args, $limit * 2 );
			usort( $found, static fn( $a, $b ) => abs( (float) $a->get_price() - $price ) <=> abs( (float) $b->get_price() - $price ) );
			return array_map( static fn( $p ) => $p->get_id(), array_slice( $found, 0, $limit ) );
		}
	);
	return array_values( array_filter( array_map( 'wc_get_product', (array) $ids ) ) );
}

/**
 * Inverter / solar-friendly products with a similar wattage (±35%).
 *
 * @return WC_Product[]
 */
function bk_similar_power_products( WC_Product $product, int $limit = 10 ): array {
	$power = bk_get_product_power( $product->get_id() );
	$ids   = bk_remember(
		'similar-power:' . $product->get_id(),
		static function () use ( $product, $power, $limit ) {
			$meta = array(
				'relation' => 'AND',
				array( 'key' => '_solar_inverter_friendly', 'value' => array( 'solar_ready', 'low_amp' ), 'compare' => 'IN' ),
			);
			if ( $power['wattage'] ) {
				$meta[] = array( 'key' => '_wattage_consumption', 'value' => array( $power['wattage'] * 0.65, $power['wattage'] * 1.35 ), 'compare' => 'BETWEEN', 'type' => 'DECIMAL(10,2)' );
			}
			$found = bk_query_products( array( 'post__not_in' => array( $product->get_id() ), 'meta_query' => $meta ), $limit ); // phpcs:ignore WordPress.DB.SlowDBQuery
			return array_map( static fn( $p ) => $p->get_id(), $found );
		}
	);
	return array_values( array_filter( array_map( 'wc_get_product', (array) $ids ) ) );
}

/**
 * Filtered URLs are for humans — keep them out of the index (canonical stays clean).
 */
add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( is_search() || bk_catalog_has_active_filters( bk_catalog_filters( wp_unslash( $_GET ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);
