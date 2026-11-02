<?php
/**
 * Front-end assets: one purged Tailwind stylesheet, one tiny deferred script
 * (no framework), preloaded Arabic fonts and the runtime config object.
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
 * Runtime config consumed by app.js.
 */
function bk_frontend_config(): array {
	return array(
		'rest' => esc_url_raw( rest_url( BK_REST_NS . '/' ) ),
		'i18n' => array(
			'noResults' => __( 'ما لقينا نتائج — جرّب كلمة تانية مثل "براد" أو "شاشة"', 'baytkahraba' ),
			'all'       => __( 'كل النتائج', 'baytkahraba' ),
		),
	);
}

/**
 * Preload the two critical Arabic font files (body + headline).
 */
add_action(
	'wp_head',
	static function (): void {
		foreach ( array( 'ibm-plex-sans-arabic-arabic-400', 'alexandria-arabic-600' ) as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( BK_URI . "/assets/fonts/{$font}.woff2" )
			);
		}
		echo '<meta name="theme-color" content="#FAF9F6">' . "\n";
	},
	1
);
