<?php
/**
 * Theme supports, menus, image sizes, locale and the <html> attributes.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		load_theme_textdomain( 'baytkahraba', BK_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 96,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);

		// WooCommerce: we render our own gallery (no jQuery zoom / lightbox / slider).
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 400,
				'single_image_width'    => 800,
				'product_grid'          => array(
					'default_columns' => 4,
					'min_columns'     => 2,
					'max_columns'     => 5,
				),
			)
		);

		register_nav_menus(
			array(
				'utility' => __( 'روابط الشريط العلوي', 'baytkahraba' ),
				'footer'  => __( 'روابط التذييل (خدمة العملاء)', 'baytkahraba' ),
			)
		);

		add_image_size( 'bk-card', 480, 480, false );
		add_image_size( 'bk-thumb', 160, 160, false );
		add_image_size( 'bk-banner', 1600, 560, true );
	}
);

// Arabic (Syria) document language + RTL regardless of the WP admin locale.
add_filter(
	'language_attributes',
	static function (): string {
		return 'dir="rtl" lang="ar-SY"';
	}
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'bk-theme';
		if ( function_exists( 'bk_is_silo' ) && bk_is_silo() ) {
			$classes[] = 'bk-silo';
		}
		return $classes;
	}
);

// Page templates (bundle builder) and menus feed cached fragments too.
add_action( 'save_post_page', static fn() => bk_cache_bump() );
add_action( 'wp_update_nav_menu', static fn() => bk_cache_bump() );

add_filter( 'excerpt_length', static fn() => 28 );
add_filter( 'excerpt_more', static fn() => '…' );

/**
 * Automatic WebP output for uploaded JPEG/PNG (keeps originals; srcset is generated natively).
 * AVIF is used instead when the server's image editor supports it and BK_PREFER_AVIF is true.
 */
add_filter(
	'image_editor_output_format',
	static function ( array $formats ): array {
		$target = ( defined( 'BK_PREFER_AVIF' ) && BK_PREFER_AVIF && wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) ) ) ? 'image/avif' : 'image/webp';
		if ( ! wp_image_editor_supports( array( 'mime_type' => $target ) ) ) {
			return $formats;
		}
		$formats['image/jpeg'] = $target;
		$formats['image/png']  = $target;
		return $formats;
	}
);
