<?php
/**
 * Shop page and product search results.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

$bk_ctx       = bk_seo_context();
$bk_is_search = is_search();
$bk_matrix    = bk_get_catalog_matrix();
$bk_brands    = array_map( static fn( $b ) => array( 'term' => $b, 'count' => (int) ( $bk_matrix['brand_totals'][ $b->term_id ] ?? 0 ) ), bk_get_brands() );
$bk_parents   = array_map( static fn( $n ) => array( 'term' => get_term( $n['id'], 'product_cat' ), 'count' => $n['count'] ), bk_get_category_tree() );

bk_part(
	'template-parts/archive/catalog',
	array(
		'context'      => $bk_is_search ? array( 's' => get_search_query( false ) ) : array(),
		'h1'           => $bk_is_search
			/* translators: %s search query */
			? sprintf( __( 'نتائج البحث عن: %s', 'baytkahraba' ), get_search_query( false ) )
			: __( 'كل الأجهزة الكهربائية والمنزلية — أسعار اليوم بسعر المحل', 'baytkahraba' ),
		'intro_html'   => $bk_is_search ? '' : bk_seo_intro_html( $bk_ctx ),
		'child_cats'   => $bk_is_search ? array() : bk_get_category_tree(),
		'brand_filter' => $bk_brands,
		'cat_filter'   => $bk_parents,
		'faqs'         => $bk_is_search ? array() : bk_seo_faqs( $bk_ctx ),
	)
);

get_footer();
