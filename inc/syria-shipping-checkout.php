<?php
/**
 * Syrian logistics & conversion-first checkout.
 *
 *  Zone 1  دمشق وريفها القريب      → FREE door delivery
 *  Zone 2  ريف دمشق الأبعد          → symbolic flat fee per area
 *  Zone 3  باقي المحافظات           → courier fee by governorate × weight class (small / heavy)
 *
 * Checkout is reduced to 4 required inputs (name, WhatsApp phone, governorate +
 * area, detailed address). Payment: COD (Damascus & Rif only) or approved
 * money-transfer companies. WhatsApp ordering is available on product, cart
 * and checkout with a fully itemized message.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Geography & configuration
 * ---------------------------------------------------------------------- */

function bk_syria_states(): array {
	return array(
		'DI' => 'دمشق',
		'RD' => 'ريف دمشق',
		'HL' => 'حلب',
		'HI' => 'حمص',
		'HM' => 'حماة',
		'LA' => 'اللاذقية',
		'TA' => 'طرطوس',
		'ID' => 'إدلب',
		'DR' => 'درعا',
		'SU' => 'السويداء',
		'QU' => 'القنيطرة',
		'DY' => 'دير الزور',
		'RA' => 'الرقة',
		'HA' => 'الحسكة',
	);
}

add_filter(
	'woocommerce_states',
	static function ( array $states ): array {
		$states['SY'] = bk_syria_states();
		return $states;
	}
);

function bk_shipping_defaults(): array {
	return array(
		'zone1_areas'   => implode(
			"\n",
			array(
				'المزة | DI', 'المزة 86 | DI', 'كفرسوسة | DI', 'الميدان | DI', 'مشروع دمر | DI', 'دمر | DI', 'المالكي | DI', 'أبو رمانة | DI',
				'الشعلان | DI', 'المهاجرين | DI', 'الصالحية | DI', 'ركن الدين | DI', 'برزة | DI', 'القصاع | DI', 'باب توما | DI', 'الشاغور | DI',
				'البرامكة | DI', 'المزرعة | DI', 'الزاهرة | DI', 'القدم | DI', 'العباسيين | DI', 'التجارة | DI', 'الدويلعة | DI', 'القابون | DI',
				'جرمانا | RD', 'صحنايا | RD', 'أشرفية صحنايا | RD', 'قدسيا | RD', 'ضاحية قدسيا | RD', 'الهامة | RD',
			)
		),
		'zone2_areas'   => implode(
			"\n",
			array(
				'التل | 25000', 'حرستا | 25000', 'داريا | 25000', 'معضمية الشام | 25000', 'عربين | 25000', 'زملكا | 25000', 'دوما | 30000',
				'الكسوة | 30000', 'سقبا | 30000', 'كفربطنا | 30000', 'جديدة عرطوز | 30000', 'الصبورة | 30000', 'قطنا | 35000', 'يعفور | 35000',
				'عدرا | 40000', 'الديماس | 40000', 'الزبداني | 50000', 'الضمير | 50000', 'بلودان | 55000', 'مضايا | 55000', 'القطيفة | 60000',
				'يبرود | 75000', 'النبك | 75000', 'دير عطية | 75000',
			)
		),
		'zone2_default' => 40000,
		'zone3_fees'    => implode(
			"\n",
			array(
				'HI | 40000 | 125000', 'DR | 40000 | 125000', 'SU | 40000 | 125000', 'QU | 40000 | 125000', 'HM | 50000 | 150000',
				'LA | 60000 | 175000', 'TA | 60000 | 175000', 'HL | 60000 | 200000', 'ID | 75000 | 250000', 'DY | 100000 | 300000',
				'RA | 100000 | 300000', 'HA | 100000 | 300000',
			)
		),
		'couriers'      => 'القدموس، الفؤاد',
		'eta_zone1'     => 'خلال 24 ساعة عمل',
		'eta_zone2'     => 'خلال 1 - 2 يوم عمل',
		'eta_zone3'     => 'خلال 2 - 4 أيام عمل',
		'transfer_info' => "الهرم: باسم صاحب المحل — رقم الهاتف 0911000000\nالفؤاد: باسم صاحب المحل — رقم الهاتف 0911000000\nسيريتل كاش: 0933000000\nشام كاش: رقم المحفظة 000000",
	);
}

function bk_shipping_settings(): array {
	return wp_parse_args( (array) get_option( 'bk_shipping', array() ), bk_shipping_defaults() );
}

/**
 * Parsed, structured shipping configuration (cached per request).
 *
 * @return array{areas: array<int, array{name: string, state: string, zone: int, fee: float}>, gov: array<string, array{small: float, heavy: float}>, zone2_default: float, couriers: string, eta: array}
 */
function bk_shipping_config(): array {
	static $config = null;
	if ( null !== $config ) {
		return $config;
	}
	$s     = bk_shipping_settings();
	$areas = array();
	foreach ( preg_split( '/\R/u', (string) $s['zone1_areas'] ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( ! empty( $p[0] ) ) {
			$areas[] = array( 'name' => $p[0], 'state' => strtoupper( $p[1] ?? 'DI' ), 'zone' => 1, 'fee' => 0.0 );
		}
	}
	foreach ( preg_split( '/\R/u', (string) $s['zone2_areas'] ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( ! empty( $p[0] ) ) {
			$areas[] = array( 'name' => $p[0], 'state' => 'RD', 'zone' => 2, 'fee' => (float) ( $p[1] ?? $s['zone2_default'] ) );
		}
	}
	$gov = array();
	foreach ( preg_split( '/\R/u', (string) $s['zone3_fees'] ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( ! empty( $p[0] ) ) {
			$gov[ strtoupper( $p[0] ) ] = array( 'small' => (float) ( $p[1] ?? 0 ), 'heavy' => (float) ( $p[2] ?? $p[1] ?? 0 ) );
		}
	}
	$config = array(
		'areas'         => $areas,
		'gov'           => $gov,
		'zone2_default' => (float) $s['zone2_default'],
		'couriers'      => (string) $s['couriers'],
		'eta'           => array( 1 => $s['eta_zone1'], 2 => $s['eta_zone2'], 3 => $s['eta_zone3'] ),
	);
	return $config;
}

/**
 * Compact config for the front end (product delivery estimator, checkout, drawer).
 */
function bk_shipping_public_config(): array {
	$c = bk_shipping_config();
	return array(
		'states'   => bk_syria_states(),
		'areas'    => array_map( static fn( $a ) => array( $a['name'], $a['state'], $a['zone'], $a['fee'] ), $c['areas'] ),
		'gov'      => $c['gov'],
		'zone2Def' => $c['zone2_default'],
		'couriers' => $c['couriers'],
		'eta'      => $c['eta'],
		// Same wording as bk_shipping_quote() so the estimator never disagrees with checkout.
		'labels'   => array(
			'z1'    => __( 'توصيل مجاني لباب البيت وبنفس سعر المحل', 'baytkahraba' ),
			'z2'    => __( 'توصيل بسعر رمزي مخفض — %s', 'baytkahraba' ),
			'z2def' => __( 'ريف دمشق', 'baytkahraba' ),
			'z3'    => sprintf( __( 'شحن للمحافظات بسعر رمزي عبر %s', 'baytkahraba' ), $c['couriers'] ),
		),
	);
}

/**
 * Expose each cart item's weight class through the Store API
 * (item.extensions.bk.weight) so the side cart can quote delivery live.
 */
add_action(
	'init',
	static function (): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'cart-item',
				'namespace'       => 'bk',
				'data_callback'   => static function ( $cart_item ) {
					$cat = bk_product_primary_category( (int) $cart_item['product_id'] );
					return array( 'weight' => $cat ? bk_category_weight_class( $cat->term_id ) : 'heavy' );
				},
				'schema_callback' => static fn() => array(
					'weight' => array(
						'description' => 'Shipping weight class (small|heavy).',
						'type'        => 'string',
						'readonly'    => true,
					),
				),
				'schema_type'     => ARRAY_A,
			)
		);
	}
);

/**
 * Quote a delivery.
 *
 * @param string $state       Governorate code.
 * @param string $area        Area name (Damascus / Rif).
 * @param array  $weight_qty  ['small' => qty, 'heavy' => qty].
 * @return array{zone: int, cost: float, label: string, eta: string}
 */
function bk_shipping_quote( string $state, string $area, array $weight_qty = array( 'heavy' => 1 ) ): array {
	$c     = bk_shipping_config();
	$state = strtoupper( $state ?: 'DI' );

	if ( in_array( $state, array( 'DI', 'RD' ), true ) ) {
		$match = null;
		foreach ( $c['areas'] as $row ) {
			if ( bk_normalize_arabic( $row['name'] ) === bk_normalize_arabic( $area ) ) {
				$match = $row;
				break;
			}
		}
		if ( ( $match && 1 === $match['zone'] ) || ( ! $match && 'DI' === $state ) ) {
			return array( 'zone' => 1, 'cost' => 0.0, 'label' => __( 'توصيل مجاني لباب البيت وبنفس سعر المحل', 'baytkahraba' ), 'eta' => (string) $c['eta'][1] );
		}
		$fee = $match ? (float) $match['fee'] : (float) $c['zone2_default'];
		return array(
			'zone'  => 2,
			'cost'  => (float) apply_filters( 'bk_zone2_fee', $fee, $area, $weight_qty ),
			/* translators: %s area name */
			'label' => sprintf( __( 'توصيل بسعر رمزي مخفض — %s', 'baytkahraba' ), $area ?: __( 'ريف دمشق', 'baytkahraba' ) ),
			'eta'   => (string) $c['eta'][2],
		);
	}

	$fees  = $c['gov'][ $state ] ?? array( 'small' => 0.0, 'heavy' => 0.0 );
	$heavy = (int) ( $weight_qty['heavy'] ?? 0 );
	$small = (int) ( $weight_qty['small'] ?? 0 );
	// Heavy appliances ship per unit; small appliances travel together for one fee.
	$cost = $heavy > 0 ? $fees['heavy'] * $heavy : ( $small > 0 ? $fees['small'] : 0 );

	return array(
		'zone'  => 3,
		'cost'  => (float) apply_filters( 'bk_zone3_fee', $cost, $state, $weight_qty ),
		/* translators: %s courier names */
		'label' => sprintf( __( 'شحن للمحافظات بسعر رمزي عبر %s', 'baytkahraba' ), $c['couriers'] ),
		'eta'   => (string) $c['eta'][3],
	);
}

/**
 * Weight-class quantities for a list of [product_id => qty].
 */
function bk_weight_quantities( array $lines ): array {
	$out = array( 'small' => 0, 'heavy' => 0 );
	foreach ( $lines as $product_id => $qty ) {
		$parent = wp_get_post_parent_id( $product_id ) ?: $product_id;
		$cat    = bk_product_primary_category( $parent );
		$class  = $cat ? bk_category_weight_class( $cat->term_id ) : 'heavy';
		$out[ $class ] += max( 1, (int) $qty );
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Shipping method
 * ---------------------------------------------------------------------- */

/**
 * "توصيل بيت الكهرباء" — single method that resolves all three zones.
 */
class BK_Syria_Shipping_Method extends WC_Shipping_Method {

	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'bk_syria';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'توصيل سوريا (دمشق، الريف، المحافظات)', 'baytkahraba' );
		$this->method_description = __( 'مجاني داخل دمشق وريفها القريب، رسم رمزي لريف دمشق الأبعد، وشحن للمحافظات حسب فئة الوزن. الأسعار تُدار من بيت الكهرباء ← التوصيل والشحن.', 'baytkahraba' );
		$this->supports           = array( 'shipping-zones', 'instance-settings' );
		$this->instance_form_fields = array(
			'title' => array(
				'title'   => __( 'العنوان', 'baytkahraba' ),
				'type'    => 'text',
				'default' => __( 'التوصيل', 'baytkahraba' ),
			),
		);
		$this->init_settings();
		$this->title = $this->get_option( 'title', __( 'التوصيل', 'baytkahraba' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function calculate_shipping( $package = array() ) {
		$lines = array();
		foreach ( $package['contents'] ?? array() as $item ) {
			$id           = (int) ( $item['variation_id'] ?: $item['product_id'] );
			$lines[ $id ] = ( $lines[ $id ] ?? 0 ) + (int) $item['quantity'];
		}
		// The checkout only collects billing fields: never let an empty shipping state default to free Damascus delivery.
		$state = (string) ( $package['destination']['state'] ?? '' );
		if ( '' === $state && WC()->customer ) {
			$state = (string) WC()->customer->get_billing_state();
		}
		$quote = bk_shipping_quote(
			$state,
			(string) ( $package['destination']['bk_area'] ?? '' ),
			bk_weight_quantities( $lines )
		);
		$this->add_rate(
			array(
				'id'        => $this->get_rate_id(),
				'label'     => $quote['label'],
				'cost'      => $quote['cost'],
				'package'   => $package,
				'meta_data' => array( 'zone' => $quote['zone'], 'eta' => $quote['eta'] ),
			)
		);
	}
}

add_filter(
	'woocommerce_shipping_methods',
	static function ( array $methods ): array {
		$methods['bk_syria'] = 'BK_Syria_Shipping_Method';
		return $methods;
	}
);

// Area becomes part of the package hash so rates recalculate when it changes.
add_filter(
	'woocommerce_cart_shipping_packages',
	static function ( array $packages ): array {
		$area = WC()->session ? (string) WC()->session->get( 'bk_area', '' ) : '';
		foreach ( $packages as &$package ) {
			$package['destination']['bk_area'] = $area;
			$package['destination']['country'] = $package['destination']['country'] ?: 'SY';
		}
		return $packages;
	}
);

// Show "free" explicitly instead of hiding the price.
add_filter(
	'woocommerce_cart_shipping_method_full_label',
	static function ( string $label, WC_Shipping_Rate $rate ): string {
		if ( 'bk_syria' === $rate->get_method_id() ) {
			$meta = $rate->get_meta_data();
			if ( 0.0 === (float) $rate->get_cost() ) {
				$label .= ' — <strong style="color:#059669">' . esc_html__( 'مجاني', 'baytkahraba' ) . '</strong>';
			}
			if ( ! empty( $meta['eta'] ) ) {
				$label .= '<br><small>' . esc_html( $meta['eta'] ) . '</small>';
			}
		}
		return $label;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * 4-field checkout
 * ---------------------------------------------------------------------- */

add_filter(
	'woocommerce_checkout_fields',
	static function ( array $fields ): array {
		$areas = array( '' => __( 'اختر المنطقة…', 'baytkahraba' ) );
		foreach ( bk_shipping_config()['areas'] as $area ) {
			$areas[ $area['name'] ] = $area['name'];
		}
		$areas['__other'] = __( 'منطقة أخرى (اكتبها في العنوان)', 'baytkahraba' );

		$billing = $fields['billing'];
		$fields['billing'] = array(
			'billing_first_name' => array_merge(
				$billing['billing_first_name'],
				array( 'label' => __( 'الاسم الكامل', 'baytkahraba' ), 'placeholder' => __( 'مثال: محمد أحمد', 'baytkahraba' ), 'class' => array( 'form-row-wide' ), 'priority' => 10, 'required' => true, 'autocomplete' => 'name' )
			),
			'billing_phone'      => array_merge(
				$billing['billing_phone'],
				array( 'label' => __( 'رقم الموبايل المتصل بالواتساب', 'baytkahraba' ), 'placeholder' => '09xxxxxxxx', 'class' => array( 'form-row-wide' ), 'priority' => 20, 'required' => true, 'custom_attributes' => array( 'dir' => 'ltr', 'inputmode' => 'tel', 'pattern' => '[0-9+ ]{9,16}' ) )
			),
			'billing_country'    => array_merge( $billing['billing_country'], array( 'class' => array( 'form-row-wide', 'bk-hidden-country' ), 'priority' => 25 ) ),
			'billing_state'      => array_merge(
				$billing['billing_state'],
				array( 'label' => __( 'المحافظة', 'baytkahraba' ), 'class' => array( 'form-row-first' ), 'priority' => 30, 'required' => true, 'country' => 'SY' )
			),
			'billing_bk_area'    => array(
				'type'     => 'select',
				'label'    => __( 'المنطقة', 'baytkahraba' ),
				'options'  => $areas,
				'class'    => array( 'form-row-last', 'update_totals_on_change' ),
				'priority' => 35,
				'required' => false, // Required only for Damascus & Rif — validated below.
			),
			'billing_address_1'  => array_merge(
				$billing['billing_address_1'],
				array( 'label' => __( 'العنوان التفصيلي وعلامة مميزة قريبة', 'baytkahraba' ), 'placeholder' => __( 'الحي، الشارع، البناء، الطابق — وأقرب علامة مميزة (جامع، صيدلية، فرن…)', 'baytkahraba' ), 'class' => array( 'form-row-wide' ), 'priority' => 40, 'required' => true )
			),
		);
		$fields['shipping'] = array();
		unset( $fields['order']['order_comments'] );
		return $fields;
	},
	20
);

// Billing address is the delivery address (no separate shipping form).
add_filter( 'option_woocommerce_ship_to_destination', static fn() => 'billing_only' );
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
add_filter( 'woocommerce_checkout_registration_enabled', '__return_false' );

add_filter(
	'woocommerce_get_country_locale',
	static function ( array $locale ): array {
		$locale['SY'] = array(
			'postcode'  => array( 'required' => false, 'hidden' => true ),
			'city'      => array( 'required' => false, 'hidden' => true ),
			'address_2' => array( 'required' => false, 'hidden' => true ),
			'last_name' => array( 'required' => false, 'hidden' => true ),
			'state'     => array( 'required' => true, 'label' => __( 'المحافظة', 'baytkahraba' ) ),
		);
		return $locale;
	}
);

add_filter(
	'woocommerce_localisation_address_formats',
	static function ( array $formats ): array {
		$formats['SY'] = "{first_name}\n{state}\n{address_1}";
		return $formats;
	}
);

add_filter( 'default_checkout_billing_country', static fn() => 'SY' );

// Normalize the phone before validation & saving.
add_filter(
	'woocommerce_checkout_posted_data',
	static function ( array $data ): array {
		$normalized = bk_normalize_phone( (string) ( $data['billing_phone'] ?? '' ) );
		if ( $normalized ) {
			$data['billing_phone'] = $normalized;
		}
		$data['billing_country'] = 'SY';
		if ( '__other' === ( $data['billing_bk_area'] ?? '' ) ) {
			$data['billing_bk_area'] = __( 'منطقة أخرى', 'baytkahraba' );
		}
		return $data;
	}
);

add_action(
	'woocommerce_after_checkout_validation',
	static function ( array $data, WP_Error $errors ): void {
		if ( ! bk_normalize_phone( (string) ( $data['billing_phone'] ?? '' ) ) ) {
			$errors->add( 'billing_phone_validation', __( 'رقم الموبايل غير صحيح — اكتبه بالشكل 09xxxxxxxx', 'baytkahraba' ), array( 'id' => 'billing_phone' ) );
		}
		if ( in_array( $data['billing_state'] ?? '', array( 'DI', 'RD' ), true ) && empty( $data['billing_bk_area'] ) ) {
			$errors->add( 'billing_bk_area_required', __( 'اختر المنطقة لحساب التوصيل المجاني أو الرمزي', 'baytkahraba' ), array( 'id' => 'billing_bk_area' ) );
		}
	},
	10,
	2
);

// Keep the chosen area in session so shipping updates live.
add_action(
	'woocommerce_checkout_update_order_review',
	static function ( string $post_data ): void {
		parse_str( $post_data, $data );
		if ( isset( $data['billing_bk_area'] ) && WC()->session ) {
			WC()->session->set( 'bk_area', sanitize_text_field( wp_unslash( (string) $data['billing_bk_area'] ) ) );
		}
	}
);

// Final submit (also covers browsers without JS): trust the posted area before totals are recalculated.
add_action(
	'woocommerce_checkout_process',
	static function (): void {
		if ( isset( $_POST['billing_bk_area'] ) && WC()->session ) { // phpcs:ignore WordPress.Security.NonceVerification -- WC verified the checkout nonce.
			WC()->session->set( 'bk_area', sanitize_text_field( wp_unslash( $_POST['billing_bk_area'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
);

// Area in admin, emails and formatted addresses.
add_filter(
	'woocommerce_order_formatted_billing_address',
	static function ( $address, WC_Order $order ) {
		$area = (string) $order->get_meta( '_billing_bk_area' );
		if ( $area && is_array( $address ) ) {
			$address['address_1'] = $area . ' — ' . ( $address['address_1'] ?? '' );
		}
		return $address;
	},
	10,
	2
);

add_action(
	'woocommerce_admin_order_data_after_billing_address',
	static function ( WC_Order $order ): void {
		$area = (string) $order->get_meta( '_billing_bk_area' );
		$wa   = bk_whatsapp_url( '', (string) $order->get_billing_phone() );
		echo '<p><strong>' . esc_html__( 'المنطقة:', 'baytkahraba' ) . '</strong> ' . esc_html( $area ?: '—' ) . '<br>';
		echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url( $wa ) . '">' . esc_html__( 'مراسلة الزبون عبر واتساب', 'baytkahraba' ) . '</a></p>';
	}
);

// Checkout behaviour: area options follow the governorate, searchable dropdown.
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! is_checkout() ) {
			return;
		}
		$areas = array();
		foreach ( bk_shipping_config()['areas'] as $area ) {
			$areas[ $area['state'] ][] = $area['name'];
		}
		$script = 'jQuery(function($){var A=' . wp_json_encode( $areas ) . ',other=' . wp_json_encode( __( 'منطقة أخرى (اكتبها في العنوان)', 'baytkahraba' ) ) . ',ph=' . wp_json_encode( __( 'اختر المنطقة…', 'baytkahraba' ) ) . ';'
			. 'var $s=$("#billing_state"),$a=$("#billing_bk_area"),$w=$("#billing_bk_area_field");'
			. 'function sync(){var st=$s.val(),list=A[st]||[],cur=$a.val();if(st!=="DI"&&st!=="RD"){$w.hide();$a.val("");return;}$w.show();'
			. 'var html="<option value=\'\'>"+ph+"</option>";list.forEach(function(n){html+="<option"+(n===cur?" selected":"")+">"+$("<i>").text(n).html()+"</option>";});html+="<option value=\'__other\'>"+other+"</option>";$a.html(html);if($.fn.selectWoo){$a.selectWoo({dir:"rtl",width:"100%"});}}'
			. '$s.on("change",function(){sync();$(document.body).trigger("update_checkout");});$a.on("change",function(){$(document.body).trigger("update_checkout");});sync();});';
		wp_add_inline_script( 'wc-checkout', $script );
		wp_add_inline_style( 'bk-main', '.bk-hidden-country{display:none!important}' );
	},
	30
);

/* -------------------------------------------------------------------------
 * Payment: COD only for Damascus & Rif + approved money-transfer gateway
 * ---------------------------------------------------------------------- */

add_filter(
	'woocommerce_available_payment_gateways',
	static function ( array $gateways ): array {
		if ( is_admin() || ! WC()->customer ) {
			return $gateways;
		}
		$state = WC()->customer->get_billing_state();
		if ( $state && ! in_array( $state, array( 'DI', 'RD' ), true ) ) {
			unset( $gateways['cod'] );
		}
		return $gateways;
	}
);

/**
 * Approved money-transfer companies (الهرم، الفؤاد، سيريتل كاش، شام كاش).
 */
class BK_Gateway_Transfer extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'bk_transfer';
		$this->method_title       = __( 'الحوالات المالية المعتمدة', 'baytkahraba' );
		$this->method_description = __( 'الدفع عبر الهرم، الفؤاد، سيريتل كاش، شام كاش — مناسب لطلبات المحافظات. يُحجز الطلب "قيد الانتظار" حتى تأكيد الحوالة.', 'baytkahraba' );
		$this->has_fields         = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	public function companies(): array {
		return array_filter( array_map( 'trim', explode( ',', (string) $this->get_option( 'companies', 'الهرم,الفؤاد,سيريتل كاش,شام كاش' ) ) ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array( 'title' => __( 'تفعيل', 'baytkahraba' ), 'type' => 'checkbox', 'default' => 'yes' ),
			'title'        => array( 'title' => __( 'العنوان', 'baytkahraba' ), 'type' => 'text', 'default' => __( 'الدفع عبر الحوالات المالية المعتمدة', 'baytkahraba' ) ),
			'description'  => array( 'title' => __( 'الوصف', 'baytkahraba' ), 'type' => 'textarea', 'default' => __( 'حوّل قيمة الطلب عبر الهرم أو الفؤاد أو سيريتل كاش أو شام كاش، وأرسل صورة الإشعار على واتساب ليتم تجهيز طلبك وشحنه فوراً.', 'baytkahraba' ) ),
			'companies'    => array( 'title' => __( 'الشركات (مفصولة بفاصلة)', 'baytkahraba' ), 'type' => 'text', 'default' => 'الهرم,الفؤاد,سيريتل كاش,شام كاش' ),
			'instructions' => array( 'title' => __( 'بيانات التحويل (تظهر بعد الطلب وفي الإيميل)', 'baytkahraba' ), 'type' => 'textarea', 'default' => bk_shipping_defaults()['transfer_info'] ),
		);
	}

	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}
		echo '<p class="form-row form-row-wide"><label for="bk_transfer_company">' . esc_html__( 'شركة التحويل', 'baytkahraba' ) . '</label><select name="bk_transfer_company" id="bk_transfer_company">';
		foreach ( $this->companies() as $company ) {
			echo '<option value="' . esc_attr( $company ) . '">' . esc_html( $company ) . '</option>';
		}
		echo '</select></p>';
		echo '<p class="form-row form-row-wide"><label for="bk_transfer_ref">' . esc_html__( 'رقم الحوالة (اختياري — يمكن إرساله لاحقاً)', 'baytkahraba' ) . '</label><input type="text" class="input-text" name="bk_transfer_ref" id="bk_transfer_ref" dir="ltr"></p>';
	}

	public function process_payment( $order_id ) {
		$order   = wc_get_order( $order_id );
		$company = sanitize_text_field( wp_unslash( $_POST['bk_transfer_company'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$ref     = sanitize_text_field( wp_unslash( $_POST['bk_transfer_ref'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$order->update_meta_data( '_bk_transfer_company', in_array( $company, $this->companies(), true ) ? $company : '' );
		$order->update_meta_data( '_bk_transfer_ref', $ref );
		$order->update_status( 'on-hold', sprintf( __( 'بانتظار تأكيد الحوالة عبر %1$s %2$s', 'baytkahraba' ), $company, $ref ? '(#' . $ref . ')' : '' ) );
		wc_reduce_stock_levels( $order_id );
		WC()->cart->empty_cart();
		return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
	}

	public function thankyou( $order_id ) {
		$order = wc_get_order( $order_id );
		echo '<section class="bk-card p-5 my-6"><h2 class="text-lg mb-2">' . esc_html__( 'بيانات التحويل', 'baytkahraba' ) . '</h2>';
		echo wp_kses_post( wpautop( esc_html( (string) $this->get_option( 'instructions' ) ) ) );
		if ( $order ) {
			$msg = sprintf( __( 'مرحباً، أرسلت حوالة للطلب رقم %1$s بقيمة %2$s', 'baytkahraba' ), $order->get_order_number(), wp_strip_all_tags( wc_price( $order->get_total() ) ) );
			echo '<a class="bk-btn-emerald mt-3" target="_blank" rel="noopener" href="' . esc_url( bk_whatsapp_url( html_entity_decode( $msg ) ) ) . '">' . bk_icon( 'whatsapp', 'size-5' ) . esc_html__( 'أرسل إشعار الحوالة على واتساب', 'baytkahraba' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</section>';
	}

	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( ! $sent_to_admin && $order instanceof WC_Order && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			echo $plain_text ? esc_html( (string) $this->get_option( 'instructions' ) ) . PHP_EOL : wp_kses_post( wpautop( esc_html( (string) $this->get_option( 'instructions' ) ) ) );
		}
	}
}

add_filter(
	'woocommerce_payment_gateways',
	static function ( array $gateways ): array {
		$gateways[] = 'BK_Gateway_Transfer';
		return $gateways;
	}
);

/* -------------------------------------------------------------------------
 * WhatsApp ordering
 * ---------------------------------------------------------------------- */

/**
 * Build an itemized WhatsApp order message.
 *
 * @param array $lines   [ ['product' => WC_Product, 'qty' => int, 'total' => float] ].
 * @param array $address { name, state, area, address }.
 */
function bk_whatsapp_order_message( array $lines, array $address = array() ): string {
	$state_names = bk_syria_states();
	$state       = (string) ( $address['state'] ?? '' );
	$area        = (string) ( $address['area'] ?? '' );
	$weights     = array();
	$subtotal    = 0.0;
	$out         = array( sprintf( __( '🛒 طلب جديد من %s', 'baytkahraba' ), (string) bk_option( 'store_name' ) ), '' );
	$i           = 1;

	foreach ( $lines as $line ) {
		/** @var WC_Product $product */
		$product = $line['product'];
		$qty     = max( 1, (int) $line['qty'] );
		$total   = isset( $line['total'] ) ? (float) $line['total'] : (float) $product->get_price() * $qty;
		$brand   = bk_get_product_brand( $product->get_id() );
		$model   = (string) ( $product->get_meta( '_bk_model_code' ) ?: $product->get_sku() );

		$out[]   = $i++ . ') ' . wp_strip_all_tags( $product->get_name() );
		$details = array();
		if ( $brand ) {
			$details[] = __( 'الماركة: ', 'baytkahraba' ) . $brand->name;
		}
		if ( $model ) {
			$details[] = __( 'الموديل: ', 'baytkahraba' ) . $model;
		}
		$details[] = __( 'الكمية: ', 'baytkahraba' ) . $qty;
		$details[] = __( 'السعر: ', 'baytkahraba' ) . bk_format_syp( $total, false );
		$out[]     = '   ' . implode( ' | ', $details );

		$weights[ $product->get_id() ] = $qty;
		$subtotal                     += $total;
	}

	$out[] = '';
	$out[] = __( 'المجموع الفرعي: ', 'baytkahraba' ) . bk_format_syp( $subtotal, false );
	if ( ! empty( $address['discount'] ) && (float) $address['discount'] > 0 ) {
		$out[]     = ( $address['discount_label'] ?? __( 'الخصم: ', 'baytkahraba' ) ) . '-' . bk_format_syp( (float) $address['discount'], false );
		$subtotal -= (float) $address['discount'];
	}

	if ( $state ) {
		$quote = bk_shipping_quote( $state, $area, bk_weight_quantities( $weights ) );
		$out[] = __( 'المحافظة / المنطقة: ', 'baytkahraba' ) . ( $state_names[ $state ] ?? $state ) . ( $area ? ' - ' . $area : '' );
		$out[] = __( 'التوصيل: ', 'baytkahraba' ) . ( $quote['cost'] > 0 ? bk_format_syp( $quote['cost'], false ) : __( 'مجاني', 'baytkahraba' ) ) . ' (' . $quote['eta'] . ')';
		$out[] = __( 'الإجمالي النهائي: ', 'baytkahraba' ) . bk_format_syp( $subtotal + $quote['cost'], false );
	}
	if ( ! empty( $address['name'] ) ) {
		$out[] = __( 'الاسم: ', 'baytkahraba' ) . $address['name'];
	}
	if ( ! empty( $address['address'] ) ) {
		$out[] = __( 'العنوان: ', 'baytkahraba' ) . $address['address'];
	}
	$out[] = '';
	$out[] = __( 'أرجو تأكيد الطلب وموعد التوصيل، شكراً.', 'baytkahraba' );

	return (string) apply_filters( 'bk_whatsapp_order_message', implode( "\n", $out ), $lines, $address );
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			BK_REST_NS,
			'/whatsapp/cart',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					if ( function_exists( 'wc_load_cart' ) ) {
						wc_load_cart();
					}
					$lines = array();
					foreach ( WC()->cart ? WC()->cart->get_cart() : array() as $item ) {
						$lines[] = array( 'product' => $item['data'], 'qty' => (int) $item['quantity'], 'total' => (float) $item['line_total'] + (float) $item['line_tax'] );
					}
					if ( ! $lines ) {
						return new WP_Error( 'bk_empty_cart', __( 'السلة فارغة', 'baytkahraba' ), array( 'status' => 400 ) );
					}
					$address = array(
						'name'    => sanitize_text_field( (string) $request['name'] ),
						'state'   => array_key_exists( (string) $request['state'], bk_syria_states() ) ? (string) $request['state'] : '',
						'area'    => sanitize_text_field( (string) $request['area'] ),
						'address' => sanitize_text_field( (string) $request['address'] ),
					);
					$message = bk_whatsapp_order_message( $lines, $address );
					return rest_ensure_response( array( 'message' => $message, 'url' => bk_whatsapp_url( $message ) ) );
				},
			)
		);

		register_rest_route(
			BK_REST_NS,
			'/shipping/quote',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					$product_id = absint( $request['product_id'] );
					$weights    = $product_id ? bk_weight_quantities( array( $product_id => max( 1, absint( $request['qty'] ) ) ) ) : array( 'heavy' => 1 );
					return rest_ensure_response( bk_shipping_quote( sanitize_key( (string) $request['state'] ), sanitize_text_field( (string) $request['area'] ), $weights ) + array( 'weights' => $weights ) );
				},
			)
		);
	}
);

// "Order via WhatsApp instead" on checkout.
add_action(
	'woocommerce_review_order_after_submit',
	static function (): void {
		echo '<button type="button" class="bk-btn-emerald bk-btn-lg mt-3 w-full" x-data x-on:click="$store.cart.whatsappFromCheckout()">' . bk_icon( 'whatsapp', 'size-5' ) . esc_html__( 'أو أرسل الطلب كاملاً عبر واتساب', 'baytkahraba' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
);

/* -------------------------------------------------------------------------
 * Classic (shortcode) checkout is required for the 4-field flow.
 * ---------------------------------------------------------------------- */

function bk_ensure_classic_checkout(): void {
	foreach ( array( 'checkout' => '[woocommerce_checkout]', 'cart' => '[woocommerce_cart]' ) as $page => $shortcode ) {
		$page_id = wc_get_page_id( $page );
		if ( $page_id > 0 && has_block( "woocommerce/{$page}", $page_id ) ) {
			wp_update_post( array( 'ID' => $page_id, 'post_content' => '<!-- wp:shortcode -->' . $shortcode . '<!-- /wp:shortcode -->' ) );
		}
	}
}

add_action(
	'admin_notices',
	static function (): void {
		$checkout = wc_get_page_id( 'checkout' );
		if ( ! current_user_can( 'manage_woocommerce' ) || $checkout < 1 || ! has_block( 'woocommerce/checkout', $checkout ) ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=bk_classic_checkout' ), 'bk_classic_checkout' );
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'صفحة الدفع تستخدم "Checkout Block". صفحة الدفع السورية المختصرة (4 حقول + واتساب) تحتاج الصفحة الكلاسيكية.', 'baytkahraba' ) . ' <a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'تحويل بنقرة واحدة', 'baytkahraba' ) . '</a></p></div>';
	}
);

add_action(
	'admin_post_bk_classic_checkout',
	static function (): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( '', 403 );
		}
		check_admin_referer( 'bk_classic_checkout' );
		bk_ensure_classic_checkout();
		wp_safe_redirect( admin_url() );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Admin: delivery settings screen
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page( BK_ADMIN_SLUG, __( 'التوصيل والشحن', 'baytkahraba' ), __( 'التوصيل والشحن', 'baytkahraba' ), 'manage_woocommerce', 'bk-shipping', 'bk_render_shipping_page' );
	},
	21
);

add_action(
	'admin_init',
	static function (): void {
		register_setting(
			'bk_shipping_group',
			'bk_shipping',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $input ) {
					$clean = array();
					foreach ( bk_shipping_defaults() as $key => $default ) {
						$raw           = $input[ $key ] ?? $default;
						$clean[ $key ] = is_numeric( $default ) ? (float) $raw : sanitize_textarea_field( (string) $raw );
					}
					bk_cache_bump();
					return $clean;
				},
			)
		);
	}
);

function bk_render_shipping_page(): void {
	$s      = bk_shipping_settings();
	$labels = array(
		'zone1_areas'   => array( __( 'المنطقة 1 — توصيل مجاني (المنطقة | DI أو RD)', 'baytkahraba' ), 'textarea' ),
		'zone2_areas'   => array( __( 'المنطقة 2 — ريف دمشق الأبعد (المنطقة | الرسم)', 'baytkahraba' ), 'textarea' ),
		'zone2_default' => array( __( 'الرسم الافتراضي لمنطقة غير مدرجة في الريف', 'baytkahraba' ), 'number' ),
		'zone3_fees'    => array( __( 'المنطقة 3 — المحافظات (رمز المحافظة | أجهزة صغيرة | أجهزة كبيرة لكل قطعة)', 'baytkahraba' ), 'textarea' ),
		'couriers'      => array( __( 'شركات الشحن', 'baytkahraba' ), 'text' ),
		'eta_zone1'     => array( __( 'مدة التوصيل — المنطقة 1', 'baytkahraba' ), 'text' ),
		'eta_zone2'     => array( __( 'مدة التوصيل — المنطقة 2', 'baytkahraba' ), 'text' ),
		'eta_zone3'     => array( __( 'مدة التوصيل — المنطقة 3', 'baytkahraba' ), 'text' ),
	);
	?>
	<div class="wrap bk-admin">
		<h1><?php esc_html_e( 'التوصيل والشحن — دمشق، الريف، المحافظات', 'baytkahraba' ); ?></h1>
		<p class="bk-admin-lead"><?php echo esc_html( sprintf( __( 'رموز المحافظات: %s', 'baytkahraba' ), implode( '، ', array_map( static fn( $code, $name ) => "{$name} = {$code}", array_keys( bk_syria_states() ), bk_syria_states() ) ) ) ); ?></p>
		<form method="post" action="options.php" class="bk-admin-card">
			<?php settings_fields( 'bk_shipping_group' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $labels as $key => $field ) : ?>
					<tr><th><?php echo esc_html( $field[0] ); ?></th><td><?php bk_admin_field( "bk_shipping[{$key}]", $field[1], $s[ $key ], array( 'rows' => 10 ) ); ?></td></tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
