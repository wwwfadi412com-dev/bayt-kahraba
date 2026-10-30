<?php
/**
 * Brand Experience Hub: /brand/{brand-slug}/
 * Discovers the brand's categories from the catalog matrix and renders a live
 * tab bar of brand × category silos above the instant catalog.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

$bk_ctx   = bk_seo_context();
$bk_brand = $bk_ctx['brand'];

bk_part( 'template-parts/archive/brand-hub-header', array( 'brand' => $bk_brand ) );

bk_part(
	'template-parts/archive/catalog',
	array(
		'context'    => array( 'brand' => $bk_brand->slug ),
		'h1'         => bk_seo_h1( $bk_ctx ),
		'intro_html' => bk_seo_intro_html( $bk_ctx ),
		'guide_html' => bk_seo_guide_html( $bk_ctx ),
		'faqs'       => bk_seo_faqs( $bk_ctx ),
		'cat_filter' => bk_get_brand_categories( $bk_brand->term_id, 1 ),
	)
);

get_footer();
