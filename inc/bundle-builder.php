<?php
/**
 * "حاسبة تجهيز العرسان والبيت الكامل" — Bride & Home Setup Bundle Builder.
 *
 * Steps are mapped to product categories from the dashboard (any parent/child
 * category, present or future). For each step the engine proposes products for
 * three curated tiers — explicitly tagged products first (`_bk_bundle_tier`),
 * otherwise picked by price position (cheapest / median / premium). Users can
 * swap any item; the full bundle is added to the cart in one request with an
 * automatic bundle discount, or sent as one WhatsApp order.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

function bk_bundle_settings(): array {
	return wp_parse_args(
		(array) get_option( 'bk_bundle', array() ),
		array(
			'steps'     => implode(
				"\n",
				array(
					'البراد | refrigerators-freezers | 0 | fridge',
					'الغسالة | washing-machines | 0 | washer',
					'الشاشة | tvs | 0 | tv',
					'الطباخ / الميكروويف | cookers-ovens,microwaves | 0 | cooker',
					'الأدوات الصغيرة: خلاط، مكنسة، مكواة | blenders-food-processors,vacuum-cleaners,irons | 1 | kitchen',
				)
			),
			'discount'  => 5,
			'min_items' => 3,
			'label'     => 'خصم باقة تجهيز البيت',
		)
	);
}

/**
 * @return array<int, array{key: string, label: string, cats: string[], multi: bool, icon: string}>
 */
function bk_bundle_steps(): array {
	$steps = array();
	foreach ( preg_split( '/\R/u', (string) bk_bundle_settings()['steps'] ) as $i => $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( count( $p ) < 2 || '' === $p[0] ) {
			continue;
		}
		$cats = array_values( array_filter( array_map( 'sanitize_title', explode( ',', $p[1] ) ), static fn( $slug ) => term_exists( $slug, 'product_cat' ) ) );
		if ( $cats ) {
			$steps[] = array( 'key' => 's' . $i, 'label' => $p[0], 'cats' => $cats, 'multi' => ! empty( $p[2] ), 'icon' => $p[3] ?? 'bolt' );
		}
	}
	return $steps;
}

/**
 * Purchasable candidates for a step, cheapest first.
 */
function bk_bundle_candidates( array $step ): array {
	return (array) bk_remember(
		'bundle-candidates:' . implode( ',', $step['cats'] ),
		static function () use ( $step ) {
			$out = array();
			foreach ( $step['cats'] as $slug ) {
				$ids = wc_get_products(
					array(
						'status'       => 'publish',
						'type'         => 'simple',
						'stock_status' => 'instock',
						'category'     => array( $slug ),
						'limit'        => 24,
						'orderby'      => 'price',
						'order'        => 'ASC',
						'return'       => 'ids',
					)
				);
				foreach ( $ids as $id ) {
					$product = wc_get_product( $id );
					if ( ! $product || ! $product->is_purchasable() || ! $product->get_price() ) {
						continue;
					}
					$brand = bk_get_product_brand( $id );
					$out[] = array(
						'id'    => $id,
						'name'  => $product->get_name(),
						'price' => (float) $product->get_price(),
						'image' => (string) ( wp_get_attachment_image_url( $product->get_image_id(), 'bk-thumb' ) ?: bk_placeholder_src() ),
						'brand' => $brand ? $brand->name : '',
						'tier'  => (string) $product->get_meta( '_bk_bundle_tier' ),
						'power' => bk_get_product_power( $id )['short'],
						'url'   => $product->get_permalink(),
						'cat'   => $slug,
					);
				}
			}
			usort( $out, static fn( $a, $b ) => $a['price'] <=> $b['price'] );
			return $out;
		}
	);
}

/**
 * Pick product IDs for a tier.
 *
 * @return int[]
 */
function bk_bundle_pick( array $candidates, string $tier, array $step ): array {
	$pick_one = static function ( array $pool ) use ( $tier ): ?int {
		if ( ! $pool ) {
			return null;
		}
		$tagged = array_values( array_filter( $pool, static fn( $c ) => $c['tier'] === $tier ) );
		if ( $tagged ) {
			return (int) $tagged[0]['id'];
		}
		$pool = array_values( $pool ); // Already sorted by price.
		if ( 'economic' === $tier ) {
			return (int) $pool[0]['id'];
		}
		if ( 'premium' === $tier ) {
			return (int) $pool[ count( $pool ) - 1 ]['id'];
		}
		return (int) $pool[ intdiv( count( $pool ), 2 ) ]['id'];
	};

	if ( ! $step['multi'] ) {
		$id = $pick_one( $candidates );
		return $id ? array( $id ) : array();
	}
	$ids = array();
	foreach ( $step['cats'] as $slug ) {
		$id = $pick_one( array_filter( $candidates, static fn( $c ) => $c['cat'] === $slug ) );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

function bk_bundle_payload(): array {
	$steps = array();
	foreach ( bk_bundle_steps() as $step ) {
		$candidates = bk_bundle_candidates( $step );
		if ( ! $candidates ) {
			continue;
		}
		$picks = array();
		foreach ( array_keys( bk_bundle_tiers() ) as $tier ) {
			$picks[ $tier ] = bk_bundle_pick( $candidates, $tier, $step );
		}
		$steps[] = array(
			'key'        => $step['key'],
			'label'      => $step['label'],
			'multi'      => $step['multi'],
			'icon'       => $step['icon'],
			'candidates' => $candidates,
			'picks'      => $picks,
		);
	}
	$settings = bk_bundle_settings();
	return array(
		'steps'    => $steps,
		'tiers'    => bk_bundle_tiers(),
		'discount' => (float) $settings['discount'],
		'minItems' => (int) $settings['min_items'],
		'label'    => (string) $settings['label'],
	);
}

/**
 * Validate that IDs belong to the builder's candidate pool.
 *
 * @return int[]
 */
function bk_bundle_validate_ids( array $ids ): array {
	$allowed = array();
	foreach ( bk_bundle_steps() as $step ) {
		foreach ( bk_bundle_candidates( $step ) as $candidate ) {
			$allowed[ $candidate['id'] ] = true;
		}
	}
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ), static fn( $id ) => isset( $allowed[ $id ] ) ) ) );
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			BK_REST_NS,
			'/bundle',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static fn() => rest_ensure_response( bk_bundle_payload() ),
			)
		);

		register_rest_route(
			BK_REST_NS,
			'/bundle/add',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'args'                => array( 'ids' => array( 'type' => 'array', 'required' => true, 'items' => array( 'type' => 'integer' ) ) ),
				'callback'            => static function ( WP_REST_Request $request ) {
					$ids = bk_bundle_validate_ids( (array) $request['ids'] );
					if ( ! $ids ) {
						return new WP_Error( 'bk_bundle_empty', __( 'اختر منتجاً واحداً على الأقل', 'baytkahraba' ), array( 'status' => 400 ) );
					}
					wc_load_cart();
					$bundle_id = 'bk' . wp_generate_password( 8, false );
					$added     = 0;
					foreach ( $ids as $id ) {
						if ( WC()->cart->add_to_cart( $id, 1, 0, array(), array( 'bk_bundle' => $bundle_id ) ) ) {
							++$added;
						}
					}
					WC()->cart->calculate_totals();
					return rest_ensure_response(
						array(
							'added'       => $added,
							'count'       => WC()->cart->get_cart_contents_count(),
							'cartUrl'     => wc_get_cart_url(),
							'checkoutUrl' => wc_get_checkout_url(),
						)
					);
				},
			)
		);

		register_rest_route(
			BK_REST_NS,
			'/bundle/whatsapp',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					$ids   = bk_bundle_validate_ids( (array) $request['ids'] );
					$lines = array();
					foreach ( $ids as $id ) {
						$product = wc_get_product( $id );
						if ( $product ) {
							$lines[] = array( 'product' => $product, 'qty' => 1 );
						}
					}
					if ( ! $lines ) {
						return new WP_Error( 'bk_bundle_empty', __( 'اختر منتجاً واحداً على الأقل', 'baytkahraba' ), array( 'status' => 400 ) );
					}
					$settings = bk_bundle_settings();
					$subtotal = array_sum( array_map( static fn( $l ) => (float) $l['product']->get_price(), $lines ) );
					$discount = count( $lines ) >= (int) $settings['min_items'] ? round( $subtotal * (float) $settings['discount'] / 100 ) : 0;
					$tier     = bk_bundle_tiers()[ sanitize_key( (string) $request['tier'] ) ] ?? '';
					$message  = bk_whatsapp_order_message(
						$lines,
						array(
							'state'          => array_key_exists( (string) $request['state'], bk_syria_states() ) ? (string) $request['state'] : '',
							'area'           => sanitize_text_field( (string) $request['area'] ),
							'discount'       => $discount,
							'discount_label' => $settings['label'] . ( $tier ? ' (' . $tier . '): ' : ': ' ),
						)
					);
					return rest_ensure_response( array( 'message' => $message, 'url' => bk_whatsapp_url( $message ) ) );
				},
			)
		);
	}
);

// Candidate pools are cached: refresh them when stock availability changes.
add_action( 'woocommerce_product_set_stock_status', 'bk_cache_bump' );
add_action( 'woocommerce_variation_set_stock_status', 'bk_cache_bump' );

/* -------------------------------------------------------------------------
 * Cart: bundle discount + item label
 * ---------------------------------------------------------------------- */

add_action(
	'woocommerce_cart_calculate_fees',
	static function ( WC_Cart $cart ): void {
		$settings = bk_bundle_settings();
		$pct      = (float) $settings['discount'];
		if ( $pct <= 0 ) {
			return;
		}
		$groups = array();
		foreach ( $cart->get_cart() as $item ) {
			if ( ! empty( $item['bk_bundle'] ) ) {
				$groups[ $item['bk_bundle'] ]['ids'][ $item['product_id'] ] = true;
				$groups[ $item['bk_bundle'] ]['total']                      = ( $groups[ $item['bk_bundle'] ]['total'] ?? 0 ) + (float) $item['line_subtotal'];
			}
		}
		foreach ( $groups as $group ) {
			if ( count( $group['ids'] ) >= (int) $settings['min_items'] ) {
				$cart->add_fee( (string) $settings['label'] . ' (' . wc_format_localized_decimal( (string) $pct ) . '%)', - round( $group['total'] * $pct / 100 ), false );
			}
		}
	}
);

add_filter(
	'woocommerce_get_item_data',
	static function ( array $data, array $item ): array {
		if ( ! empty( $item['bk_bundle'] ) ) {
			$data[] = array( 'name' => __( 'الباقة', 'baytkahraba' ), 'value' => __( 'ضمن باقة تجهيز البيت', 'baytkahraba' ) );
		}
		return $data;
	},
	10,
	2
);

add_action(
	'woocommerce_checkout_create_order_line_item',
	static function ( WC_Order_Item_Product $item, string $key, array $values ): void {
		if ( ! empty( $values['bk_bundle'] ) ) {
			$item->add_meta_data( __( 'الباقة', 'baytkahraba' ), __( 'باقة تجهيز البيت', 'baytkahraba' ) . ' #' . $values['bk_bundle'] );
		}
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Admin
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page( BK_ADMIN_SLUG, __( 'حاسبة تجهيز العرسان', 'baytkahraba' ), __( 'حاسبة تجهيز العرسان', 'baytkahraba' ), 'manage_woocommerce', 'bk-bundle', 'bk_render_bundle_page' );
	},
	22
);

add_action(
	'admin_init',
	static function (): void {
		register_setting(
			'bk_bundle_group',
			'bk_bundle',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $input ) {
					bk_cache_bump();
					return array(
						'steps'     => sanitize_textarea_field( (string) ( $input['steps'] ?? '' ) ),
						'discount'  => min( 50, max( 0, (float) ( $input['discount'] ?? 0 ) ) ),
						'min_items' => max( 1, absint( $input['min_items'] ?? 3 ) ),
						'label'     => sanitize_text_field( (string) ( $input['label'] ?? '' ) ),
					);
				},
			)
		);
	}
);

function bk_render_bundle_page(): void {
	$s = bk_bundle_settings();
	?>
	<div class="wrap bk-admin">
		<h1><?php esc_html_e( 'حاسبة تجهيز العرسان والبيت الكامل', 'baytkahraba' ); ?></h1>
		<p class="bk-admin-lead"><?php esc_html_e( 'كل سطر خطوة: العنوان | روابط التصنيفات (slug) مفصولة بفاصلة | 1 للسماح باختيار عدة منتجات | أيقونة. لتثبيت منتج في باقة معيّنة اختر "باقة تجهيز العرسان" من تبويب الكهرباء والمواصفات داخل المنتج.', 'baytkahraba' ); ?></p>
		<form method="post" action="options.php" class="bk-admin-card">
			<?php settings_fields( 'bk_bundle_group' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'الخطوات', 'baytkahraba' ); ?></th><td><?php bk_admin_field( 'bk_bundle[steps]', 'textarea', $s['steps'], array( 'rows' => 8 ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'نسبة خصم الباقة %', 'baytkahraba' ); ?></th><td><?php bk_admin_field( 'bk_bundle[discount]', 'number', $s['discount'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'الحد الأدنى لعدد الأجهزة لتفعيل الخصم', 'baytkahraba' ); ?></th><td><?php bk_admin_field( 'bk_bundle[min_items]', 'number', $s['min_items'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'اسم الخصم في السلة', 'baytkahraba' ); ?></th><td><?php bk_admin_field( 'bk_bundle[label]', 'text', $s['label'] ); ?></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
