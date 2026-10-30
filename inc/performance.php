<?php
/**
 * Core Web Vitals hardening: strip WordPress & WooCommerce front-end bloat.
 *
 * WooCommerce scripts (jQuery based) are kept ONLY where they are required:
 * cart, checkout, my-account and variable-product pages.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/* Emojis, oEmbed, generator noise ------------------------------------------ */
add_action(
	'init',
	static function (): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );

		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		add_filter( 'embed_oembed_discover', '__return_false' );
	}
);

add_filter(
	'tiny_mce_plugins',
	static fn( $plugins ) => is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array()
);

add_action(
	'wp_footer',
	static function (): void {
		wp_dequeue_script( 'wp-embed' );
	}
);

/* WooCommerce default stylesheets: replaced by our compiled Tailwind CSS. */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Does the current request need WooCommerce's jQuery scripts?
 */
function bk_needs_wc_scripts(): bool {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}
	if ( is_cart() || is_checkout() || is_account_page() ) {
		return true;
	}
	if ( is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		return $product && ! $product->is_type( 'simple' ); // Variable/grouped forms rely on WC JS.
	}
	return false;
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		// Block-editor front-end CSS (we don't render blocks in shop templates).
		foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'wc-blocks-style', 'wc-blocks-vendors-style', 'wc-all-blocks-style' ) as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}

		if ( ! bk_needs_wc_scripts() ) {
			foreach ( array( 'wc-cart-fragments', 'woocommerce', 'wc-add-to-cart', 'wc-add-to-cart-variation', 'wc-single-product', 'sourcebuster-js', 'wc-order-attribution', 'jquery-blockui', 'js-cookie', 'wc-jquery-blockui', 'wc-js-cookie', 'zoom', 'flexslider', 'photoswipe', 'photoswipe-ui-default' ) as $handle ) {
				wp_dequeue_script( $handle );
			}
		}
	},
	100
);

// Remove jquery-migrate everywhere (jQuery itself only loads as a WC dependency on checkout pages).
add_action(
	'wp_default_scripts',
	static function ( WP_Scripts $scripts ): void {
		if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
			return;
		}
		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}
);

// WooCommerce block-based inline CSS / SVG definitions we don't use.
add_action(
	'after_setup_theme',
	static function (): void {
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	}
);

// Generator tag from WooCommerce.
add_action(
	'get_header',
	static function (): void {
		remove_action( 'wp_head', 'wc_generator_tag' );
	}
);

/* Images ------------------------------------------------------------------ */

// Hint the browser about the LCP image: first image in loop & hero are eager + high priority.
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attr ): array {
		if ( ! empty( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) {
			$attr['loading']  = 'eager';
			$attr['decoding'] = 'async';
		} elseif ( empty( $attr['loading'] ) ) {
			$attr['loading'] = 'lazy';
		}
		$attr['decoding'] = $attr['decoding'] ?? 'async';
		return $attr;
	},
	20
);

// Keep heartbeat away from the storefront.
add_action(
	'init',
	static function (): void {
		if ( ! is_admin() ) {
			wp_deregister_script( 'heartbeat' );
		}
	},
	1
);

// DNS hints for WhatsApp deep links.
add_filter(
	'wp_resource_hints',
	static function ( array $urls, string $relation ): array {
		if ( 'dns-prefetch' === $relation ) {
			$urls[] = 'https://wa.me';
		}
		return $urls;
	},
	10,
	2
);
