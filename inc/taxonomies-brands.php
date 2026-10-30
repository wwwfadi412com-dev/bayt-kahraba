<?php
/**
 * `product_brand` taxonomy (official agents) with native term-meta UI.
 *
 * WooCommerce ≥ 9.6 ships its own `product_brand` taxonomy. When it exists we
 * EXTEND it (meta fields, admin columns) instead of registering a duplicate,
 * so the store keeps working whether core Brands is enabled or not.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

const BK_BRAND_TAX = 'product_brand';

add_action(
	'init',
	static function (): void {
		if ( ! taxonomy_exists( BK_BRAND_TAX ) ) {
			register_taxonomy(
				BK_BRAND_TAX,
				array( 'product' ),
				array(
					'labels'            => array(
						'name'          => __( 'الماركات', 'baytkahraba' ),
						'singular_name' => __( 'ماركة', 'baytkahraba' ),
						'menu_name'     => __( 'الماركات والوكلاء', 'baytkahraba' ),
						'all_items'     => __( 'كل الماركات', 'baytkahraba' ),
						'edit_item'     => __( 'تعديل الماركة', 'baytkahraba' ),
						'add_new_item'  => __( 'إضافة ماركة / وكيل جديد', 'baytkahraba' ),
						'search_items'  => __( 'بحث في الماركات', 'baytkahraba' ),
						'parent_item'   => __( 'الماركة الأم', 'baytkahraba' ),
						'not_found'     => __( 'لا توجد ماركات', 'baytkahraba' ),
					),
					'hierarchical'      => true,
					'public'            => true,
					'show_ui'           => true,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'show_in_nav_menus' => true,
					'query_var'         => BK_BRAND_TAX,
					'rewrite'           => array(
						'slug'         => 'brand',
						'with_front'   => false,
						'hierarchical' => false,
					),
					'capabilities'      => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
				)
			);
		}

		foreach ( bk_brand_meta_schema() as $key => $field ) {
			register_term_meta(
				BK_BRAND_TAX,
				$key,
				array(
					'type'              => 'integer' === $field['type'] ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $field['sanitize'],
				)
			);
		}
	},
	20
);

/**
 * Brand term-meta schema (key => label, field type, sanitizer, help).
 */
function bk_brand_meta_schema(): array {
	return array(
		'brand_logo'             => array(
			'label'    => __( 'شعار الماركة الرسمي', 'baytkahraba' ),
			'type'     => 'image',
			'sanitize' => 'absint',
			'help'     => __( 'PNG/SVG بخلفية شفافة. إن تُرك فارغاً يُستخدم حقل الصورة المصغرة الخاص بووكومرس.', 'baytkahraba' ),
		),
		'brand_banner'           => array(
			'label'    => __( 'بانر صفحة الماركة (Hub)', 'baytkahraba' ),
			'type'     => 'image',
			'sanitize' => 'absint',
			'help'     => __( 'مقاس مقترح 1600×560.', 'baytkahraba' ),
		),
		'brand_badge_color'      => array(
			'label'    => __( 'لون تمييز الماركة', 'baytkahraba' ),
			'type'     => 'color',
			'sanitize' => 'bk_sanitize_hex',
			'help'     => __( 'اختياري — يلوّن ترويسة صفحة الماركة.', 'baytkahraba' ),
		),
		'brand_origin_and_agent' => array(
			'label'    => __( 'المنشأ والوكالة', 'baytkahraba' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
			'help'     => __( 'مثال: وكيل رسمي ومعتمد في سوريا - كفالة حقيقية', 'baytkahraba' ),
		),
		'brand_warranty_years'   => array(
			'label'    => __( 'نص الكفالة', 'baytkahraba' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
			'help'     => __( 'مثال: كفالة شاملة لمدة سنتين صيانة وقطع تبديل', 'baytkahraba' ),
		),
		'brand_warranty_months'  => array(
			'label'    => __( 'مدة الكفالة بالأشهر (رقم)', 'baytkahraba' ),
			'type'     => 'integer',
			'sanitize' => 'absint',
			'help'     => __( 'تُستخدم لحساب تاريخ انتهاء الكفالة الإلكترونية تلقائياً.', 'baytkahraba' ),
		),
		'brand_service_centers'  => array(
			'label'    => __( 'مراكز الصيانة', 'baytkahraba' ),
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
			'help'     => __( 'سطر لكل مركز: المدينة | العنوان | الهاتف', 'baytkahraba' ),
		),
		'brand_faq_json'         => array(
			'label'    => __( 'أسئلة شائعة خاصة بالماركة', 'baytkahraba' ),
			'type'     => 'textarea',
			'sanitize' => 'bk_sanitize_faq_input',
			'help'     => __( 'سطر لكل سؤال: السؤال | الجواب — أو JSON بالشكل [{"q":"…","a":"…"}]. تُحقن تلقائياً في FAQPage Schema.', 'baytkahraba' ),
		),
		'brand_seo_guide'        => array(
			'label'    => __( 'دليل شراء مخصص (HTML)', 'baytkahraba' ),
			'type'     => 'textarea',
			'sanitize' => 'wp_kses_post',
			'help'     => __( 'اختياري — إن تُرك فارغاً يولّد المحرك دليل شراء غنياً بالكلمات المفتاحية تلقائياً.', 'baytkahraba' ),
		),
	);
}

function bk_sanitize_hex( $value ): string {
	return (string) sanitize_hex_color( (string) $value );
}

/**
 * Accepts JSON or "question | answer" lines and stores normalized JSON.
 */
function bk_sanitize_faq_input( $value ): string {
	$items = bk_parse_faq( (string) $value );
	return $items ? (string) wp_json_encode( $items, JSON_UNESCAPED_UNICODE ) : '';
}

/**
 * @return array<int, array{q: string, a: string}>
 */
function bk_parse_faq( string $raw ): array {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return array();
	}
	$decoded = json_decode( $raw, true );
	$items   = array();
	if ( is_array( $decoded ) ) {
		foreach ( $decoded as $row ) {
			if ( ! empty( $row['q'] ) && ! empty( $row['a'] ) ) {
				$items[] = array( 'q' => sanitize_text_field( $row['q'] ), 'a' => sanitize_textarea_field( $row['a'] ) );
			}
		}
		return $items;
	}
	foreach ( preg_split( '/\R/u', $raw ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( 2 === count( $parts ) && $parts[0] && $parts[1] ) {
			$items[] = array( 'q' => sanitize_text_field( $parts[0] ), 'a' => sanitize_textarea_field( $parts[1] ) );
		}
	}
	return $items;
}

/* -------------------------------------------------------------------------
 * Admin UI (add / edit term screens)
 * ---------------------------------------------------------------------- */

add_action( BK_BRAND_TAX . '_add_form_fields', 'bk_brand_add_fields' );
add_action( BK_BRAND_TAX . '_edit_form_fields', 'bk_brand_edit_fields' );
add_action( 'created_' . BK_BRAND_TAX, 'bk_brand_save_fields' );
add_action( 'edited_' . BK_BRAND_TAX, 'bk_brand_save_fields' );

function bk_brand_field_value_for_display( string $key, $value ): string {
	if ( 'brand_faq_json' === $key && $value ) {
		$lines = array();
		foreach ( bk_parse_faq( (string) $value ) as $faq ) {
			$lines[] = $faq['q'] . ' | ' . $faq['a'];
		}
		return implode( "\n", $lines );
	}
	return (string) $value;
}

function bk_brand_add_fields(): void {
	wp_nonce_field( 'bk_brand_meta', 'bk_brand_meta_nonce' );
	foreach ( bk_brand_meta_schema() as $key => $field ) {
		echo '<div class="form-field term-' . esc_attr( $key ) . '-wrap">';
		echo '<label>' . esc_html( $field['label'] ) . '</label>';
		bk_admin_field( $key, 'integer' === $field['type'] ? 'number' : $field['type'], '', array( 'help' => $field['help'], 'rows' => 5 ) );
		echo '</div>';
	}
}

function bk_brand_edit_fields( WP_Term $term ): void {
	wp_nonce_field( 'bk_brand_meta', 'bk_brand_meta_nonce' );
	foreach ( bk_brand_meta_schema() as $key => $field ) {
		$value = bk_brand_field_value_for_display( $key, get_term_meta( $term->term_id, $key, true ) );
		echo '<tr class="form-field term-' . esc_attr( $key ) . '-wrap"><th scope="row"><label>' . esc_html( $field['label'] ) . '</label></th><td>';
		bk_admin_field( $key, 'integer' === $field['type'] ? 'number' : $field['type'], $value, array( 'help' => $field['help'], 'rows' => 6 ) );
		echo '</td></tr>';
	}
}

function bk_brand_save_fields( int $term_id ): void {
	if ( ! isset( $_POST['bk_brand_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bk_brand_meta_nonce'] ) ), 'bk_brand_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	foreach ( bk_brand_meta_schema() as $key => $field ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = call_user_func( $field['sanitize'], wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $value || 0 === $value ) {
			delete_term_meta( $term_id, $key );
		} else {
			update_term_meta( $term_id, $key, $value );
		}
	}
}

// Logo column in the brands list.
add_filter(
	'manage_edit-' . BK_BRAND_TAX . '_columns',
	static function ( array $columns ): array {
		return array_slice( $columns, 0, 1, true ) + array( 'bk_logo' => __( 'الشعار', 'baytkahraba' ) ) + array_slice( $columns, 1, null, true );
	}
);
add_filter(
	'manage_' . BK_BRAND_TAX . '_custom_column',
	static function ( string $out, string $column, int $term_id ): string {
		if ( 'bk_logo' === $column ) {
			$logo = bk_brand_meta( $term_id )['logo_id'];
			return $logo ? wp_get_attachment_image( $logo, array( 48, 48 ), false, array( 'style' => 'object-fit:contain;max-height:40px;width:auto' ) ) : '—';
		}
		return $out;
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Public API
 * ---------------------------------------------------------------------- */

/**
 * Normalised brand profile.
 *
 * @param int|WP_Term $term Brand.
 * @return array{id:int,name:string,slug:string,url:string,logo_id:int,banner_id:int,color:string,agent:string,warranty_text:string,warranty_months:int,service_centers:array,faqs:array,guide:string,description:string,count:int}
 */
function bk_brand_meta( $term ): array {
	$term = $term instanceof WP_Term ? $term : get_term( (int) $term, BK_BRAND_TAX );
	if ( ! $term instanceof WP_Term ) {
		return array( 'id' => 0, 'name' => '', 'slug' => '', 'url' => '', 'logo_id' => 0, 'banner_id' => 0, 'color' => '', 'agent' => '', 'warranty_text' => '', 'warranty_months' => 0, 'service_centers' => array(), 'faqs' => array(), 'guide' => '', 'description' => '', 'count' => 0 );
	}
	$id      = $term->term_id;
	$centers = array();
	foreach ( preg_split( '/\R/u', (string) get_term_meta( $id, 'brand_service_centers', true ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( ! empty( $parts[0] ) ) {
			$centers[] = array( 'city' => $parts[0], 'address' => $parts[1] ?? '', 'phone' => $parts[2] ?? '' );
		}
	}
	$months = (int) get_term_meta( $id, 'brand_warranty_months', true );

	return array(
		'id'              => $id,
		'name'            => $term->name,
		'slug'            => $term->slug,
		'url'             => (string) get_term_link( $term ),
		'logo_id'         => (int) get_term_meta( $id, 'brand_logo', true ) ?: (int) get_term_meta( $id, 'thumbnail_id', true ),
		'banner_id'       => (int) get_term_meta( $id, 'brand_banner', true ),
		'color'           => (string) get_term_meta( $id, 'brand_badge_color', true ),
		'agent'           => (string) get_term_meta( $id, 'brand_origin_and_agent', true ) ?: __( 'وكيل رسمي ومعتمد في سوريا - كفالة حقيقية', 'baytkahraba' ),
		'warranty_text'   => (string) get_term_meta( $id, 'brand_warranty_years', true ) ?: __( 'كفالة الوكيل الرسمية', 'baytkahraba' ),
		'warranty_months' => $months ?: 12,
		'service_centers' => $centers,
		'faqs'            => bk_parse_faq( (string) get_term_meta( $id, 'brand_faq_json', true ) ),
		'guide'           => (string) get_term_meta( $id, 'brand_seo_guide', true ),
		'description'     => (string) term_description( $term ),
		'count'           => (int) $term->count,
	);
}

/**
 * All brands that have products (cached, auto-invalidated).
 *
 * @return WP_Term[]
 */
function bk_get_brands( bool $hide_empty = true ): array {
	if ( ! taxonomy_exists( BK_BRAND_TAX ) ) {
		return array();
	}
	$ids = bk_remember(
		'brands:' . (int) $hide_empty,
		static function () use ( $hide_empty ) {
			$terms = get_terms(
				array(
					'taxonomy'   => BK_BRAND_TAX,
					'hide_empty' => $hide_empty,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'fields'     => 'ids',
				)
			);
			return is_wp_error( $terms ) ? array() : array_map( 'intval', $terms );
		}
	);
	return array_values( array_filter( array_map( static fn( $id ) => get_term( $id, BK_BRAND_TAX ), (array) $ids ), static fn( $t ) => $t instanceof WP_Term ) );
}

/**
 * Primary brand of a product.
 */
function bk_get_product_brand( int $product_id ): ?WP_Term {
	$product_id = wp_get_post_parent_id( $product_id ) ?: $product_id; // Variations inherit.
	$terms      = get_the_terms( $product_id, BK_BRAND_TAX );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Brand logo markup.
 */
function bk_brand_logo( $term, string $class = 'h-10 w-auto object-contain', string $size = 'medium' ): string {
	$meta = bk_brand_meta( $term );
	if ( $meta['logo_id'] ) {
		return wp_get_attachment_image( $meta['logo_id'], $size, false, array( 'class' => $class, 'alt' => $meta['name'] ) );
	}
	return '<span class="inline-grid place-items-center rounded-lg bg-brand-royal-50 px-3 font-bold text-brand-royal ' . esc_attr( $class ) . '">' . esc_html( $meta['name'] ) . '</span>';
}
