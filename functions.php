<?php
/**
 * Bayt Al-Kahraba theme bootstrap.
 *
 * Every feature lives in its own module under /inc. Modules are loaded in
 * dependency order; WooCommerce-dependent modules are skipped (with an admin
 * notice) when WooCommerce is not active so the site never fatals.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

define( 'BK_VERSION', '1.0.1' );
define( 'BK_DIR', get_template_directory() );
define( 'BK_URI', get_template_directory_uri() );
define( 'BK_REST_NS', 'bk/v1' );

/**
 * Core modules: no WooCommerce dependency.
 */
$bk_core_modules = array(
	'helpers',
	'environment',
	'setup',
	'performance',
	'admin-settings',
	'assets',
);

/**
 * Commerce modules: require WooCommerce.
 */
$bk_commerce_modules = array(
	'taxonomies-brands',
	'category-hierarchy-engine',
	'seo-silo-rewrites',
	'product-meta',
	'catalog-query',
	'rest-api',
	'syria-pricing-sync',
	'syria-shipping-checkout',
	'seo-content-templates',
	'seo-geo-engine',
	'bundle-builder',
	'warranty-registry',
	'seed-demo-data',
);

foreach ( $bk_core_modules as $bk_module ) {
	require_once BK_DIR . '/inc/' . $bk_module . '.php';
}

if ( class_exists( 'WooCommerce' ) ) {
	foreach ( $bk_commerce_modules as $bk_module ) {
		require_once BK_DIR . '/inc/' . $bk_module . '.php';
	}
} else {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>بيت الكهرباء:</strong> ';
			echo esc_html__( 'هذا القالب يحتاج إضافة WooCommerce مفعّلة لتشغيل المتجر (الماركات، التسعير، التوصيل، SEO).', 'baytkahraba' );
			echo '</p></div>';
		}
	);
}

unset( $bk_core_modules, $bk_commerce_modules, $bk_module );
