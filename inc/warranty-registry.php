<?php
/**
 * Digital Warranty & Authenticity Lookup.
 *
 *  - Warranty terms per brand (read live from the brand taxonomy — any future
 *    brand appears automatically).
 *  - Customers register their order/invoice number + phone to create a digital
 *    warranty record (start date = delivery/completion date).
 *  - Records are also created automatically when an order is completed.
 *  - Shortcode: [bk_warranty]  (page template not required).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

const BK_WARRANTY_CPT = 'bk_warranty';

add_action(
	'init',
	static function (): void {
		register_post_type(
			BK_WARRANTY_CPT,
			array(
				'labels'          => array(
					'name'          => __( 'سجل الكفالات', 'baytkahraba' ),
					'singular_name' => __( 'كفالة', 'baytkahraba' ),
					'search_items'  => __( 'بحث برقم الهاتف أو الطلب', 'baytkahraba' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => BK_ADMIN_SLUG,
				'supports'        => array( 'title' ),
				'capability_type' => 'shop_order',
				'map_meta_cap'    => true,
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			)
		);
	}
);

/**
 * Create warranty records for an order (idempotent).
 *
 * @return int[] Record IDs.
 */
function bk_warranty_register_order( WC_Order $order ): array {
	$existing = get_posts(
		array(
			'post_type'      => BK_WARRANTY_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'meta_key'       => '_bk_order_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $order->get_id(), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	if ( $existing ) {
		return array_map( 'intval', $existing );
	}

	$start = $order->get_date_completed() ?: $order->get_date_created();
	$start = $start ? $start->getTimestamp() : time();
	$ids   = array();

	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		$product_id = $item->get_product_id();
		$brand      = bk_get_product_brand( $product_id );
		$months     = $brand ? bk_brand_meta( $brand )['warranty_months'] : 12;
		$id         = wp_insert_post(
			array(
				'post_type'   => BK_WARRANTY_CPT,
				'post_status' => 'publish',
				'post_title'  => sprintf( '#%s — %s', $order->get_order_number(), $item->get_name() ),
				'meta_input'  => array(
					'_bk_order_id'   => $order->get_id(),
					'_bk_order_no'   => (string) $order->get_order_number(),
					'_bk_phone'      => bk_normalize_phone( (string) $order->get_billing_phone() ),
					'_bk_product_id' => $product_id,
					'_bk_brand_id'   => $brand ? $brand->term_id : 0,
					'_bk_start'      => $start,
					'_bk_end'        => strtotime( "+{$months} months", $start ),
				),
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			$ids[] = (int) $id;
		}
	}
	return $ids;
}

add_action(
	'woocommerce_order_status_completed',
	static function ( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			bk_warranty_register_order( $order );
		}
	}
);

/**
 * Public representation of warranty records.
 */
function bk_warranty_records( array $ids ): array {
	$out = array();
	foreach ( $ids as $id ) {
		$end     = (int) get_post_meta( $id, '_bk_end', true );
		$start   = (int) get_post_meta( $id, '_bk_start', true );
		$product = wc_get_product( (int) get_post_meta( $id, '_bk_product_id', true ) );
		$brand   = (int) get_post_meta( $id, '_bk_brand_id', true );
		$meta    = $brand ? bk_brand_meta( $brand ) : null;
		$days    = (int) floor( ( $end - time() ) / DAY_IN_SECONDS );
		$out[]   = array(
			'product'  => $product ? $product->get_name() : get_the_title( $id ),
			'url'      => $product ? $product->get_permalink() : '',
			'brand'    => $meta['name'] ?? '',
			'terms'    => $meta['warranty_text'] ?? '',
			'centers'  => $meta['service_centers'] ?? array(),
			'start'    => wp_date( 'Y-m-d', $start ),
			'end'      => wp_date( 'Y-m-d', $end ),
			'active'   => $days >= 0,
			'daysLeft' => max( 0, $days ),
			'order'    => (string) get_post_meta( $id, '_bk_order_no', true ),
		);
	}
	return $out;
}

/**
 * Simple per-IP throttle against enumeration (10 attempts / 10 minutes).
 */
function bk_warranty_throttle(): ?WP_Error {
	$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$key = 'bk_wt_' . md5( $ip );
	$hit = (int) get_transient( $key );
	if ( $hit >= 10 ) {
		return new WP_Error( 'bk_throttled', __( 'محاولات كثيرة، حاول بعد قليل أو تواصل معنا عبر واتساب.', 'baytkahraba' ), array( 'status' => 429 ) );
	}
	set_transient( $key, $hit + 1, 10 * MINUTE_IN_SECONDS );
	return null;
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			BK_REST_NS,
			'/warranty',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'args'                => array(
					'order' => array( 'type' => 'string', 'required' => true ),
					'phone' => array( 'type' => 'string', 'required' => true ),
				),
				'callback'            => static function ( WP_REST_Request $request ) {
					if ( $error = bk_warranty_throttle() ) {
						return $error;
					}
					$phone    = bk_normalize_phone( (string) $request['phone'] );
					$order_no = preg_replace( '/\D+/', '', (string) $request['order'] );
					$order    = $order_no ? wc_get_order( (int) $order_no ) : null;
					$generic  = new WP_Error( 'bk_not_found', __( 'لم نجد طلباً بهذا الرقم مرتبطاً بهذا الموبايل. تأكد من رقم الفاتورة أو تواصل معنا.', 'baytkahraba' ), array( 'status' => 404 ) );

					if ( ! $phone || ! $order instanceof WC_Order || bk_normalize_phone( (string) $order->get_billing_phone() ) !== $phone ) {
						return $generic;
					}
					if ( ! $order->has_status( array( 'completed', 'processing' ) ) ) {
						return new WP_Error( 'bk_not_delivered', __( 'تُفعّل الكفالة الإلكترونية بعد تسليم الطلب. إن استلمت جهازك تواصل معنا لتأكيد التسليم.', 'baytkahraba' ), array( 'status' => 409 ) );
					}
					return rest_ensure_response( array( 'records' => bk_warranty_records( bk_warranty_register_order( $order ) ) ) );
				},
			)
		);
	}
);

/* -------------------------------------------------------------------------
 * Shortcode + admin columns
 * ---------------------------------------------------------------------- */

add_shortcode(
	'bk_warranty',
	static function (): string {
		return (string) bk_part( 'template-parts/warranty/lookup', array(), true );
	}
);

add_filter(
	'manage_' . BK_WARRANTY_CPT . '_posts_columns',
	static fn( $cols ) => array(
		'cb'       => $cols['cb'] ?? '',
		'title'    => __( 'الجهاز', 'baytkahraba' ),
		'bk_phone' => __( 'الموبايل', 'baytkahraba' ),
		'bk_brand' => __( 'الماركة', 'baytkahraba' ),
		'bk_end'   => __( 'تنتهي في', 'baytkahraba' ),
	)
);

add_action(
	'manage_' . BK_WARRANTY_CPT . '_posts_custom_column',
	static function ( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'bk_phone':
				echo esc_html( (string) get_post_meta( $post_id, '_bk_phone', true ) );
				break;
			case 'bk_brand':
				$term = get_term( (int) get_post_meta( $post_id, '_bk_brand_id', true ), BK_BRAND_TAX );
				echo esc_html( $term instanceof WP_Term ? $term->name : '—' );
				break;
			case 'bk_end':
				$end = (int) get_post_meta( $post_id, '_bk_end', true );
				echo '<span style="color:' . ( $end > time() ? '#059669' : '#dc2626' ) . '">' . esc_html( wp_date( 'Y-m-d', $end ) ) . '</span>';
				break;
		}
	},
	10,
	2
);

// Admin search by phone / order number.
add_filter(
	'posts_search',
	static function ( string $search, WP_Query $query ): string {
		if ( ! is_admin() || ! $query->is_main_query() || BK_WARRANTY_CPT !== $query->get( 'post_type' ) || ! $query->get( 's' ) ) {
			return $search;
		}
		global $wpdb;
		$like = '%' . $wpdb->esc_like( (string) $query->get( 's' ) ) . '%';
		return $wpdb->prepare( " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_bk_phone','_bk_order_no') AND meta_value LIKE %s)) ", $like, $like );
	},
	10,
	2
);
