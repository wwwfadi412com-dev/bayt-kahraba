<?php
/**
 * Shared helpers: settings access, versioned caching, formatting, icons,
 * phone / WhatsApp utilities and the power (ampere / solar) classification.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

/**
 * Default store settings. Everything here is editable from
 * "بيت الكهرباء ← الإعدادات العامة" without touching code.
 */
function bk_settings_defaults(): array {
	return array(
		'store_name'          => get_bloginfo( 'name' ) ?: 'بيت الكهرباء',
		'store_tagline'       => 'الوكيل المعتمد للأدوات الكهربائية والمنزلية',
		'phone'               => '0911000000',
		'whatsapp'            => '963911000000',
		'email'               => get_bloginfo( 'admin_email' ),
		'address'             => 'دمشق - شارع الثورة - مقابل سوق الكهرباء',
		'city'                => 'دمشق',
		'geo_lat'             => '33.5138',
		'geo_lng'             => '36.2765',
		'opening_hours'       => 'Sa-Th 09:00-21:00',
		'opening_hours_label' => 'السبت - الخميس: 9 صباحاً حتى 9 مساءً',
		'facebook'            => '',
		'instagram'           => '',
		'telegram'            => '',
		'price_range'         => '$$',
		'return_days'         => 7,
		'schema_types'        => 'Store,HardwareStore,ElectronicsStore',
		'top_bar_message'     => 'توصيل مجاني لباب البيت داخل دمشق وريفها القريب — وبنفس سعر المحل',
		'compare_max'         => 4,
		'products_per_page'   => 24,
		'hero_title'          => 'كل أجهزة بيتك بسعر المحل… مع كفالة الوكيل الحقيقية',
		'hero_subtitle'       => 'برادات، غسالات، شاشات، مكيفات إنفرتر وأدوات مطبخ من الحافظ، العربي، هنديكو، رامكو وغيرها — توصيل مجاني في دمشق وريفها وشحن رمزي لكل المحافظات.',
		'hero_image_id'       => 0,
	);
}

/**
 * Read a single setting (with defaults).
 *
 * @param string $key     Setting key.
 * @param mixed  $fallback Value when the key is empty.
 */
function bk_option( string $key, $fallback = null ) {
	static $settings = null;
	if ( null === $settings ) {
		$settings = wp_parse_args( (array) get_option( 'bk_settings', array() ), bk_settings_defaults() );
	}
	$value = $settings[ $key ] ?? null;
	if ( null === $value || '' === $value ) {
		return $fallback ?? ( bk_settings_defaults()[ $key ] ?? null );
	}
	return $value;
}

/* -------------------------------------------------------------------------
 * Versioned cache
 * Keys embed a global version number; bumping it invalidates every cached
 * fragment (mega menu, brand hubs, matrices, llms.txt) in O(1) without
 * wildcard deletes. Uses the object cache when present, transients otherwise.
 * ---------------------------------------------------------------------- */

function bk_cache_version(): int {
	$version = wp_cache_get( 'bk_cache_version', 'bk' );
	if ( false === $version ) {
		$version = (int) get_option( 'bk_cache_version', 1 );
		wp_cache_set( 'bk_cache_version', $version, 'bk' );
	}
	return (int) $version;
}

/**
 * Invalidate every cached fragment.
 *
 * Each call moves the in-request version forward (so anything computed after a
 * change uses fresh keys); the final version is written to the database once
 * at shutdown, keeping bulk edits / CSV imports cheap.
 */
function bk_cache_bump(): void {
	static $hooked = false;
	wp_cache_set( 'bk_cache_version', bk_cache_version() + 1, 'bk' );
	if ( ! $hooked ) {
		$hooked = true;
		add_action( 'shutdown', 'bk_cache_persist_version', 1 );
	}
}

function bk_cache_persist_version(): void {
	$version = bk_cache_version();
	update_option( 'bk_cache_version', $version, true );
	do_action( 'bk_cache_flushed', $version );
}

/**
 * Get-or-compute a cached value.
 *
 * @param string   $key      Logical key (version is appended automatically).
 * @param callable $callback Producer.
 * @param int      $ttl      Seconds.
 */
function bk_remember( string $key, callable $callback, int $ttl = 12 * HOUR_IN_SECONDS ) {
	$full_key = 'bk_' . md5( $key ) . '_v' . bk_cache_version();
	$cached   = wp_using_ext_object_cache() ? wp_cache_get( $full_key, 'bk' ) : get_transient( $full_key );
	if ( false !== $cached ) {
		return $cached;
	}
	$value = $callback();
	if ( wp_using_ext_object_cache() ) {
		wp_cache_set( $full_key, $value, 'bk', $ttl );
	} else {
		set_transient( $full_key, $value, $ttl );
	}
	return $value;
}

/**
 * Cache rendered HTML of a template part.
 */
function bk_remember_html( string $key, callable $render, int $ttl = 12 * HOUR_IN_SECONDS ): string {
	$produce = static function () use ( $render ) {
		ob_start();
		$render();
		return (string) ob_get_clean();
	};
	// Template edits must show immediately while developing; theme updates get a fresh key.
	if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( function_exists( 'bk_is_production' ) && ! bk_is_production() ) ) {
		return $produce();
	}
	return (string) bk_remember( 'html:' . BK_VERSION . ':' . $key, $produce, $ttl );
}

/* -------------------------------------------------------------------------
 * Formatting
 * ---------------------------------------------------------------------- */

/**
 * Format an amount in Syrian pounds with tabular numerals.
 */
function bk_format_syp( $amount, bool $html = true ): string {
	$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0;
	$number   = number_format( (float) $amount, $decimals, '.', ',' );
	$symbol   = 'ل.س';
	if ( ! $html ) {
		return $number . ' ' . $symbol;
	}
	return '<span class="bk-num">' . esc_html( $number ) . '</span>&nbsp;<span class="text-[.8em] font-medium">' . esc_html( $symbol ) . '</span>';
}

/**
 * Normalise Arabic text for matching (hamza forms, taa marbuta, alef maqsura, tashkeel).
 */
function bk_normalize_arabic( string $text ): string {
	$text = preg_replace( '/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text ); // tashkeel + tatweel.
	$text = strtr(
		$text,
		array(
			'أ' => 'ا',
			'إ' => 'ا',
			'آ' => 'ا',
			'ٱ' => 'ا',
			'ة' => 'ه',
			'ى' => 'ي',
			'ؤ' => 'و',
			'ئ' => 'ي',
		)
	);
	return trim( mb_strtolower( (string) $text ) );
}

/* -------------------------------------------------------------------------
 * Phone & WhatsApp
 * ---------------------------------------------------------------------- */

/**
 * Normalise a Syrian mobile number to 09XXXXXXXX. Returns '' when invalid.
 * Accepts: 09xxxxxxxx, 9xxxxxxxx, +9639xxxxxxxx, 009639xxxxxxxx, Arabic-Indic digits.
 */
function bk_normalize_phone( string $phone ): string {
	$phone = strtr( $phone, array_combine( array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ), range( 0, 9 ) ) );
	$phone = preg_replace( '/\D+/', '', $phone );
	if ( preg_match( '/^(?:00963|963|0)?(9\d{8})$/', $phone, $m ) ) {
		return '0' . $m[1];
	}
	return '';
}

/**
 * International (wa.me) form of a Syrian number: 9639XXXXXXXX.
 */
function bk_phone_international( string $phone ): string {
	$local = bk_normalize_phone( $phone );
	return $local ? '963' . substr( $local, 1 ) : preg_replace( '/\D+/', '', $phone );
}

function bk_whatsapp_url( string $message = '', string $number = '' ): string {
	$number = $number ?: (string) bk_option( 'whatsapp' );
	$number = bk_phone_international( $number );
	$url    = 'https://wa.me/' . rawurlencode( $number );
	return $message ? $url . '?text=' . rawurlencode( $message ) : $url;
}

/* -------------------------------------------------------------------------
 * Power / Solar classification (Feature 1)
 * ---------------------------------------------------------------------- */

/**
 * The three power-compatibility classes stored in `_solar_inverter_friendly`.
 */
function bk_power_classes(): array {
	return array(
		'solar_ready'   => array(
			'label' => __( 'يعمل على الانفرتر المنزلي والطاقة الشمسية', 'baytkahraba' ),
			'short' => __( 'يعمل على الطاقة الشمسية', 'baytkahraba' ),
			'badge' => 'bk-badge-emerald',
			'icon'  => 'sun',
		),
		'low_amp'       => array(
			'label' => __( 'اقتصادي جداً - يعمل على أمبير منخفض', 'baytkahraba' ),
			'short' => __( 'اقتصادي - أمبير منخفض', 'baytkahraba' ),
			'badge' => 'bk-badge-emerald-soft',
			'icon'  => 'bolt',
		),
		'standard_grid' => array(
			'label' => __( 'يحتاج كهرباء نظامية أو أمبير عالي', 'baytkahraba' ),
			'short' => __( 'كهرباء نظامية', 'baytkahraba' ),
			'badge' => 'bk-badge-slate',
			'icon'  => 'plug',
		),
	);
}

/**
 * Electrical profile of a product.
 *
 * @return array{wattage: float, ampere: float, class: string, label: string, short: string, badge: string, icon: string}
 */
function bk_get_product_power( int $product_id ): array {
	$wattage = (float) get_post_meta( $product_id, '_wattage_consumption', true );
	$ampere  = (float) get_post_meta( $product_id, '_ampere_draw', true );
	$class   = (string) get_post_meta( $product_id, '_solar_inverter_friendly', true );

	if ( ! $ampere && $wattage ) {
		$ampere = round( $wattage / 220, 2 ); // Syrian grid nominal 220V.
	}
	$classes = bk_power_classes();
	if ( ! isset( $classes[ $class ] ) ) {
		$class = '';
	}
	$meta = $class ? $classes[ $class ] : array( 'label' => '', 'short' => '', 'badge' => '', 'icon' => '' );

	return array(
		'wattage' => $wattage,
		'ampere'  => $ampere,
		'class'   => $class,
		'label'   => $meta['label'],
		'short'   => $meta['short'],
		'badge'   => $meta['badge'],
		'icon'    => $meta['icon'],
	);
}

function bk_format_watt( float $watt ): string {
	return $watt ? number_format_i18n( $watt ) . ' ' . __( 'واط', 'baytkahraba' ) : '';
}

function bk_format_ampere( float $amp ): string {
	return $amp ? rtrim( rtrim( number_format( $amp, 2, '.', '' ), '0' ), '.' ) . ' ' . __( 'أمبير', 'baytkahraba' ) : '';
}

/* -------------------------------------------------------------------------
 * Templates & media
 * ---------------------------------------------------------------------- */

/**
 * get_template_part() wrapper that returns markup when $return is true.
 */
function bk_part( string $slug, array $args = array(), bool $return = false ) {
	if ( $return ) {
		ob_start();
		get_template_part( $slug, null, $args );
		return (string) ob_get_clean();
	}
	get_template_part( $slug, null, $args );
	return null;
}

/**
 * Resolve a term image (brand logo / category thumbnail).
 */
function bk_term_image_id( int $term_id, string $meta_key = 'thumbnail_id' ): int {
	return (int) get_term_meta( $term_id, $meta_key, true );
}

/**
 * Neutral appliance placeholder (inline SVG data URI — no request).
 */
function bk_placeholder_src(): string {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><rect width="400" height="400" fill="#F1F5F9"/><g fill="none" stroke="#CBD5E1" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"><rect x="130" y="90" width="140" height="220" rx="16"/><path d="M130 170h140M160 120v25M160 200v40"/></g><path d="M212 228l-18 28h14l-6 22 20-30h-14l4-20z" fill="#F59E0B"/></svg>';
	return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/**
 * Inline SVG icon set (stroke icons, 24px grid, currentColor).
 */
function bk_icon( string $name, string $class = 'size-5' ): string {
	static $paths = array(
		'cart'      => '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'bolt'      => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
		'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'plug'      => '<path d="M9 2v6M15 2v6M6 8h12v3a6 6 0 0 1-12 0V8ZM12 17v5"/>',
		'truck'     => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
		'shield'    => '<path d="M12 3 4 6v5c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
		'store'     => '<path d="M3 9 4.5 4h15L21 9M3 9v11h18V9M3 9h18M9 20v-6h6v6"/>',
		'compare'   => '<path d="M7 4v16M17 4v16M3 8h8M13 16h8M3 8l2-2M3 8l2 2M21 16l-2-2M21 16l-2 2"/>',
		'chevron'   => '<path d="m15 6-6 6 6 6"/>',
		'chevron-d' => '<path d="m6 9 6 6 6-6"/>',
		'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
		'menu'      => '<path d="M4 6h16M4 12h16M4 18h10"/>',
		'play'      => '<path d="M8 5v14l11-7L8 5Z"/>',
		'check'     => '<path d="m5 12 5 5 9-10"/>',
		'phone'     => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2Z"/>',
		'map'       => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'star'      => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9L12 3Z"/>',
		'gift'      => '<path d="M4 11h16v10H4zM3 7h18v4H3zM12 7v14M12 7S10.5 3 8 3.5 7.5 7 12 7Zm0 0s1.5-4 4-3.5S16.5 7 12 7Z"/>',
		'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'filter'    => '<path d="M4 5h16l-6 8v6l-4-2v-4L4 5Z"/>',
		'trash'     => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
		'cash'      => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
		'home'      => '<path d="M3 11 12 4l9 7M5 10v10h14V10"/>',
		'wrench'    => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6Z"/>',
		'minus'     => '<path d="M5 12h14"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'whatsapp'  => '',
	);

	if ( 'whatsapp' === $name ) {
		return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 2.5 1 3 .8 3.6.7.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.2-.3-.3-.6-.4ZM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Z"/></svg>';
	}
	$path = $paths[ $name ] ?? $paths['bolt'];
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * Echo-safe icon output (SVG markup is generated internally).
 */
function bk_the_icon( string $name, string $class = 'size-5' ): void {
	echo bk_icon( $name, $class ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * ID of the first published page using a page template (0 when none).
 */
function bk_page_by_template( string $template ): int {
	return (int) bk_remember(
		'page-tpl:' . $template,
		static function () use ( $template ) {
			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			return $pages ? (int) $pages[0] : 0;
		}
	);
}

/**
 * Current year in site timezone (used by SEO copy).
 */
function bk_year(): string {
	return wp_date( 'Y' );
}
