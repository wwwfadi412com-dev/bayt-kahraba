<?php
/**
 * Brand × Category intersection landing page: /brand/{brand}/{category}/
 * e.g. "أسعار ومواصفات غسالات هنديكو الأصلية في دمشق وسوريا مع الكفالة".
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

$bk_ctx   = bk_seo_context();
$bk_brand = $bk_ctx['brand'];
$bk_cat   = $bk_ctx['cat'];

// Drill-down: child categories of this category that contain this brand → deeper silos.
$bk_children = array();
foreach ( bk_get_child_categories( $bk_cat->term_id ) as $bk_node ) {
	$bk_count = bk_intersection_count( $bk_brand->term_id, $bk_node['id'] );
	if ( $bk_count > 0 ) {
		$bk_children[] = array_merge(
			$bk_node,
			array(
				'name'  => $bk_node['name'] . ' ' . $bk_brand->name,
				'url'   => bk_silo_url( $bk_brand, $bk_node['id'] ),
				'count' => $bk_count,
			)
		);
	}
}

bk_part( 'template-parts/archive/brand-hub-header', array( 'brand' => $bk_brand, 'active_cat' => $bk_cat, 'compact' => true ) );

bk_part(
	'template-parts/archive/catalog',
	array(
		'context'    => array( 'brand' => $bk_brand->slug, 'cat' => $bk_cat->slug ),
		'h1'         => bk_seo_h1( $bk_ctx ),
		'intro_html' => bk_seo_intro_html( $bk_ctx ),
		'guide_html' => bk_seo_guide_html( $bk_ctx ),
		'faqs'       => bk_seo_faqs( $bk_ctx ),
		'child_cats' => $bk_children,
	)
);

get_footer();
