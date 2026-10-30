<?php
/**
 * Front-end assets: one purged Tailwind stylesheet, one deferred Alpine.js
 * bundle, preloaded Arabic fonts and the runtime config object.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

function bk_asset_version( string $relative ): string {
	$path = BK_DIR . '/' . ltrim( $relative, '/' );
	return file_exists( $path ) ? (string) filemtime( $path ) : BK_VERSION;
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style( 'bk-main', BK_URI . '/assets/css/dist/main.css', array(), bk_asset_version( 'assets/css/dist/main.css' ) );

		wp_enqueue_script( 'bk-app', BK_URI . '/assets/js/dist/app.js', array(), bk_asset_version( 'assets/js/dist/app.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script( 'bk-app', 'BK', bk_frontend_config() );
	},
	5
);

/**
 * Runtime config consumed by Alpine components.
 */
function bk_frontend_config(): array {
	if ( ! function_exists( 'WC' ) ) {
		return array( 'rest' => esc_url_raw( rest_url( BK_REST_NS . '/' ) ), 'i18n' => array() );
	}
	$decimals = wc_get_price_decimals();
	return array(
		'rest'          => esc_url_raw( rest_url( BK_REST_NS . '/' ) ),
		'storeApi'      => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
		'nonce'         => wp_create_nonce( 'wp_rest' ),
		'storeNonce'    => wp_create_nonce( 'wc_store_api' ),
		'cartUrl'       => wc_get_cart_url(),
		'checkoutUrl'   => wc_get_checkout_url(),
		'whatsapp'      => bk_phone_international( (string) bk_option( 'whatsapp' ) ),
		'storeName'     => (string) bk_option( 'store_name' ),
		'compareMax'    => (int) bk_option( 'compare_max', 4 ),
		'placeholder'   => bk_placeholder_src(),
		'currency'      => array(
			'symbol'   => html_entity_decode( get_woocommerce_currency_symbol() ),
			'decimals' => $decimals,
			'code'     => get_woocommerce_currency(),
		),
		'shipping'      => function_exists( 'bk_shipping_public_config' ) ? bk_shipping_public_config() : array(),
		'i18n'          => array(
			'added'        => __( 'تمت الإضافة إلى السلة', 'baytkahraba' ),
			'error'        => __( 'حدث خطأ، حاول مرة أخرى أو اطلب عبر واتساب', 'baytkahraba' ),
			'compareFull'  => __( 'يمكنك مقارنة 4 منتجات كحد أقصى', 'baytkahraba' ),
			'free'         => __( 'مجاني', 'baytkahraba' ),
			'noResults'    => __( 'لا توجد نتائج مطابقة — جرّب كلمة أخرى مثل "براد" أو "شاشة"', 'baytkahraba' ),
			'loading'      => __( 'جارِ التحميل…', 'baytkahraba' ),
		),
	);
}

/**
 * Preload the two critical Arabic font weights + the LCP hero image.
 */
add_action(
	'wp_head',
	static function (): void {
		foreach ( array( 400, 700 ) as $weight ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( BK_URI . "/assets/fonts/ibm-plex-sans-arabic-arabic-{$weight}.woff2" )
			);
		}
		if ( is_front_page() && ( $hero = (int) bk_option( 'hero_image_id', 0 ) ) ) {
			$src    = wp_get_attachment_image_url( $hero, 'bk-banner' );
			$srcset = wp_get_attachment_image_srcset( $hero, 'bk-banner' );
			if ( $src ) {
				printf( '<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="100vw" fetchpriority="high">' . "\n", esc_url( $src ), esc_attr( (string) $srcset ) );
			}
		}
		echo '<meta name="theme-color" content="#0F172A">' . "\n";
	},
	1
);
