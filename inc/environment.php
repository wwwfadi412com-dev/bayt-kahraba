<?php
/**
 * Development-to-production portability.
 *
 * - All theme data stores attachment IDs / slugs, never absolute URLs, so a
 *   domain change cannot break serialized options.
 * - Non-production environments (WP_ENVIRONMENT_TYPE = local|development|staging)
 *   are automatically noindexed and flagged in the admin bar.
 * - BK_LEGACY_URLS (wp-config) rewrites hard-coded old-domain links in content
 *   on the fly as a safety net after migration (run `wp search-replace` too).
 *
 * See docs/DEPLOYMENT.md for the wp-config snippet.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

function bk_is_production(): bool {
	return 'production' === wp_get_environment_type();
}

// Never let a staging copy compete with production in Google.
add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( ! bk_is_production() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	},
	99
);

add_filter(
	'robots_txt',
	static function ( string $output ): string {
		if ( ! bk_is_production() ) {
			return "User-agent: *\nDisallow: /\n";
		}
		return $output;
	},
	99
);

// Environment pill in the admin bar.
add_action(
	'admin_bar_menu',
	static function ( WP_Admin_Bar $bar ): void {
		if ( bk_is_production() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'bk-env',
				'title' => '<span style="background:#F59E0B;color:#0F172A;padding:2px 8px;border-radius:99px;font-weight:700">' . esc_html( strtoupper( wp_get_environment_type() ) ) . '</span>',
				'meta'  => array( 'title' => __( 'بيئة غير إنتاجية: الأرشفة معطّلة تلقائياً', 'baytkahraba' ) ),
			)
		);
	},
	1
);

/**
 * Rewrite legacy absolute URLs (e.g. the free staging host) to the current home URL.
 * Define in wp-config.php: define( 'BK_LEGACY_URLS', 'https://old.example.net,http://localhost:8080' );
 */
function bk_rewrite_legacy_urls( $content ) {
	if ( ! defined( 'BK_LEGACY_URLS' ) || ! is_string( $content ) || '' === $content ) {
		return $content;
	}
	$home = untrailingslashit( home_url() );
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) BK_LEGACY_URLS ) ) ) as $legacy ) {
		$content = str_replace( untrailingslashit( $legacy ), $home, $content );
	}
	return $content;
}
add_filter( 'the_content', 'bk_rewrite_legacy_urls', 1 );
add_filter( 'term_description', 'bk_rewrite_legacy_urls', 1 );
add_filter( 'woocommerce_short_description', 'bk_rewrite_legacy_urls', 1 );

// Don't email real customers from a staging copy.
add_filter(
	'pre_wp_mail',
	static function ( $short_circuit, array $atts ) {
		if ( defined( 'BK_BLOCK_EMAILS' ) && BK_BLOCK_EMAILS && ! bk_is_production() ) {
			return true;
		}
		return $short_circuit;
	},
	10,
	2
);
