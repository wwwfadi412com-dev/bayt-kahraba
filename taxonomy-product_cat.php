<?php
/**
 * Parent / child / sub-child category archive.
 * Parent pages open with visual child-category cards, then the instant
 * catalog, then the generated buying guide and FAQ.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

$bk_ctx  = bk_seo_context();
$bk_term = $bk_ctx['cat'];

bk_part(
	'template-parts/archive/catalog',
	array(
		'context'      => array( 'cat' => $bk_term->slug ),
		'h1'           => bk_seo_h1( $bk_ctx ),
		'intro_html'   => bk_seo_intro_html( $bk_ctx ),
		'guide_html'   => bk_seo_guide_html( $bk_ctx ),
		'faqs'         => bk_seo_faqs( $bk_ctx ),
		'child_cats'   => bk_get_child_categories( $bk_term->term_id ),
		'brand_filter' => bk_get_category_brands( $bk_term->term_id ),
	)
);

get_footer();
