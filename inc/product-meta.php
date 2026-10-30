<?php
/**
 * Product data: electrical profile (watt / ampere / solar), specs, highlights,
 * FAQ, real-shop video, USD base price, bundle tier.
 *
 * Fields live in a dedicated "الكهرباء والمواصفات" tab and are fully supported
 * by WooCommerce's native CSV importer / exporter.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field registry: meta key => [label, type, csv column, extra].
 */
function bk_product_fields(): array {
	return array(
		'_bk_model_code'           => array( __( 'رقم الموديل', 'baytkahraba' ), 'text', 'bk_model_code', array( 'placeholder' => 'HF-RF16NF-INV' ) ),
		'_wattage_consumption'     => array( __( 'الاستطاعة (واط)', 'baytkahraba' ), 'number', 'bk_wattage', array( 'placeholder' => '350', 'step' => '1' ) ),
		'_ampere_draw'             => array( __( 'السحب بالأمبير', 'baytkahraba' ), 'number', 'bk_ampere', array( 'placeholder' => '1.5', 'step' => '0.01', 'desc' => __( 'يُحسب تلقائياً (الواط ÷ 220) إن تُرك فارغاً.', 'baytkahraba' ) ) ),
		'_solar_inverter_friendly' => array( __( 'التوافق مع الأمبيرات والطاقة الشمسية', 'baytkahraba' ), 'select', 'bk_power_class', array() ),
		'_bk_capacity'             => array( __( 'السعة / الحجم', 'baytkahraba' ), 'text', 'bk_capacity', array( 'placeholder' => '16 قدم - 450 لتر' ) ),
		'_bk_dimensions'           => array( __( 'الأبعاد', 'baytkahraba' ), 'text', 'bk_dimensions', array( 'placeholder' => '70 × 72 × 180 سم' ) ),
		'_bk_origin'               => array( __( 'المنشأ / التجميع', 'baytkahraba' ), 'text', 'bk_origin', array( 'placeholder' => 'تجميع محلي بتقنية كورية' ) ),
		'_bk_warranty_override'    => array( __( 'كفالة خاصة بهذا المنتج', 'baytkahraba' ), 'text', 'bk_warranty', array( 'desc' => __( 'اتركه فارغاً لاستخدام كفالة الماركة.', 'baytkahraba' ) ) ),
		'_bk_highlights'           => array( __( 'أبرز المميزات (سطر لكل ميزة)', 'baytkahraba' ), 'textarea', 'bk_highlights', array() ),
		'_bk_faq'                  => array( __( 'أسئلة شائعة إضافية (السؤال | الجواب)', 'baytkahraba' ), 'textarea', 'bk_faq', array( 'desc' => __( 'تُدمج مع الأسئلة المولّدة تلقائياً من بيانات المنتج.', 'baytkahraba' ) ) ),
		'_bk_shop_video'           => array( __( 'فيديو معاينة من المحل (15 ثانية عمودي)', 'baytkahraba' ), 'video', 'bk_shop_video', array( 'desc' => __( 'MP4/WebM من المكتبة أو رابط خارجي. يظهر كفقاعة "ستوري" في صفحة المنتج.', 'baytkahraba' ) ) ),
		'_bk_shop_video_poster'    => array( __( 'صورة غلاف الفيديو', 'baytkahraba' ), 'image', 'bk_shop_video_poster', array() ),
		'_bk_bundle_tier'          => array( __( 'باقة تجهيز العرسان', 'baytkahraba' ), 'select', 'bk_bundle_tier', array() ),
	);
}

function bk_bundle_tiers(): array {
	return array(
		'economic' => __( 'الباقة الاقتصادية', 'baytkahraba' ),
		'smart'    => __( 'باقة التوفير الذكي - الأكثر طلباً', 'baytkahraba' ),
		'premium'  => __( 'الباقة الفاخرة الكاملة', 'baytkahraba' ),
	);
}

function bk_field_options( string $key ): array {
	if ( '_solar_inverter_friendly' === $key ) {
		return array( '' => __( '— غير محدد —', 'baytkahraba' ) ) + array_map( static fn( $c ) => $c['label'], bk_power_classes() );
	}
	if ( '_bk_bundle_tier' === $key ) {
		return array( '' => __( 'تلقائي حسب السعر', 'baytkahraba' ) ) + bk_bundle_tiers();
	}
	return array();
}

/* -------------------------------------------------------------------------
 * Admin: product data tab
 * ---------------------------------------------------------------------- */

add_filter(
	'woocommerce_product_data_tabs',
	static function ( array $tabs ): array {
		$tabs['bk_power'] = array(
			'label'    => __( 'الكهرباء والمواصفات', 'baytkahraba' ),
			'target'   => 'bk_power_data',
			'class'    => array(),
			'priority' => 15,
		);
		return $tabs;
	}
);

add_action(
	'woocommerce_product_data_panels',
	static function (): void {
		global $post;
		echo '<div id="bk_power_data" class="panel woocommerce_options_panel hidden">';
		wp_nonce_field( 'bk_product_meta', 'bk_product_meta_nonce' );
		foreach ( bk_product_fields() as $key => $field ) {
			list( $label, $type, , $extra ) = $field;
			$value = get_post_meta( $post->ID, $key, true );
			echo '<div class="options_group">';
			switch ( $type ) {
				case 'select':
					woocommerce_wp_select( array( 'id' => $key, 'label' => $label, 'options' => bk_field_options( $key ), 'value' => $value ) );
					break;
				case 'textarea':
					woocommerce_wp_textarea_input( array( 'id' => $key, 'label' => $label, 'value' => $value, 'description' => $extra['desc'] ?? '', 'desc_tip' => false, 'rows' => 5, 'style' => 'min-height:110px' ) );
					break;
				case 'number':
					woocommerce_wp_text_input(
						array(
							'id'                => $key,
							'label'             => $label,
							'value'             => $value,
							'type'              => 'number',
							'placeholder'       => $extra['placeholder'] ?? '',
							'description'       => $extra['desc'] ?? '',
							'custom_attributes' => array( 'step' => $extra['step'] ?? 'any', 'min' => '0' ),
						)
					);
					break;
				case 'video':
				case 'image':
					echo '<p class="form-field"><label>' . esc_html( $label ) . '</label>';
					echo '<span class="bk-media-field" data-bk-media data-bk-media-type="' . esc_attr( $type ) . '">';
					printf( '<input type="text" class="short" name="%s" value="%s" data-bk-media-input placeholder="ID / URL" dir="ltr"> ', esc_attr( $key ), esc_attr( (string) $value ) );
					printf( '<button type="button" class="button" data-bk-media-select>%s</button>', esc_html__( 'اختيار من المكتبة', 'baytkahraba' ) );
					echo '</span>';
					if ( ! empty( $extra['desc'] ) ) {
						echo '<span class="description" style="display:block;clear:both">' . esc_html( $extra['desc'] ) . '</span>';
					}
					echo '</p>';
					break;
				default:
					woocommerce_wp_text_input( array( 'id' => $key, 'label' => $label, 'value' => $value, 'placeholder' => $extra['placeholder'] ?? '', 'description' => $extra['desc'] ?? '' ) );
			}
			echo '</div>';
		}
		echo '</div>';
	}
);

// USD base price beside the native SYP price fields (General tab).
add_action(
	'woocommerce_product_options_pricing',
	static function (): void {
		echo '<div class="bk-usd-box">';
		woocommerce_wp_text_input(
			array(
				'id'                => '_base_usd_price',
				'label'             => __( 'السعر المرجعي بالدولار ($)', 'baytkahraba' ),
				'data_type'         => 'price',
				'description'       => __( 'مخفي عن الزبون — يُستخدم لإعادة حساب السعر بالليرة عند تحديث سعر الصرف.', 'baytkahraba' ),
				'desc_tip'          => true,
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => '_base_usd_sale_price',
				'label'       => __( 'سعر العرض المرجعي ($)', 'baytkahraba' ),
				'data_type'   => 'price',
				'description' => __( 'اختياري.', 'baytkahraba' ),
				'desc_tip'    => true,
			)
		);
		echo '</div>';
	}
);

// Variations get their own USD base.
add_action(
	'woocommerce_variation_options_pricing',
	static function ( int $loop, array $variation_data, WP_Post $variation ): void {
		woocommerce_wp_text_input(
			array(
				'id'            => "bk_variable_base_usd_price_{$loop}",
				'name'          => "bk_variable_base_usd_price[{$loop}]",
				'value'         => get_post_meta( $variation->ID, '_base_usd_price', true ),
				'label'         => __( 'السعر المرجعي ($)', 'baytkahraba' ),
				'data_type'     => 'price',
				'wrapper_class' => 'form-row form-row-first',
			)
		);
	},
	10,
	3
);

add_action(
	'woocommerce_save_product_variation',
	static function ( int $variation_id, int $loop ): void {
		check_ajax_referer( 'save-variations', 'security' );
		if ( isset( $_POST['bk_variable_base_usd_price'][ $loop ] ) ) {
			$value = wc_format_decimal( wp_unslash( $_POST['bk_variable_base_usd_price'][ $loop ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'' === $value ? delete_post_meta( $variation_id, '_base_usd_price' ) : update_post_meta( $variation_id, '_base_usd_price', $value );
		}
	},
	10,
	2
);

/**
 * Sanitize one field value by type.
 */
function bk_sanitize_product_field( string $key, $raw ) {
	$type = bk_product_fields()[ $key ][1] ?? 'text';
	switch ( $type ) {
		case 'number':
			$raw = preg_replace( '/[^\d.]/', '', (string) $raw );
			return '' === $raw ? '' : wc_format_decimal( $raw );
		case 'select':
			return array_key_exists( (string) $raw, bk_field_options( $key ) ) ? (string) $raw : '';
		case 'textarea':
			return sanitize_textarea_field( (string) $raw );
		case 'video':
		case 'image':
			return is_numeric( $raw ) ? (string) absint( $raw ) : esc_url_raw( (string) $raw );
		default:
			return sanitize_text_field( (string) $raw );
	}
}

add_action(
	'woocommerce_admin_process_product_object',
	static function ( WC_Product $product ): void {
		if ( ! isset( $_POST['bk_product_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bk_product_meta_nonce'] ) ), 'bk_product_meta' ) ) {
			return;
		}
		foreach ( array_keys( bk_product_fields() ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$product->update_meta_data( $key, bk_sanitize_product_field( $key, wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}
		}
		foreach ( array( '_base_usd_price', '_base_usd_sale_price' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$product->update_meta_data( $key, wc_format_decimal( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) );
			}
		}
	}
);

/* -------------------------------------------------------------------------
 * CSV import / export (WooCommerce native tools)
 * ---------------------------------------------------------------------- */

function bk_csv_columns(): array {
	$columns = array(
		'bk_base_usd_price'      => array( '_base_usd_price', __( 'السعر المرجعي بالدولار', 'baytkahraba' ) ),
		'bk_base_usd_sale_price' => array( '_base_usd_sale_price', __( 'سعر العرض المرجعي بالدولار', 'baytkahraba' ) ),
	);
	foreach ( bk_product_fields() as $key => $field ) {
		$columns[ $field[2] ] = array( $key, $field[0] );
	}
	return $columns;
}

add_filter(
	'woocommerce_product_export_column_names',
	static function ( array $columns ): array {
		foreach ( bk_csv_columns() as $column => $def ) {
			$columns[ $column ] = $def[1];
		}
		return $columns;
	}
);
add_filter( 'woocommerce_product_export_product_default_columns', static fn( $columns ) => array_merge( $columns, array_combine( array_keys( bk_csv_columns() ), array_column( bk_csv_columns(), 1 ) ) ) );

add_action(
	'init',
	static function (): void {
		foreach ( bk_csv_columns() as $column => $def ) {
			add_filter(
				"woocommerce_product_export_product_column_{$column}",
				static fn( $value, $product ) => $product->get_meta( $def[0], true ),
				10,
				2
			);
		}
	}
);

add_filter(
	'woocommerce_csv_product_import_mapping_options',
	static function ( array $options ): array {
		foreach ( bk_csv_columns() as $column => $def ) {
			$options[ $column ] = $def[1];
		}
		return $options;
	}
);
add_filter(
	'woocommerce_csv_product_import_mapping_default_columns',
	static function ( array $columns ): array {
		foreach ( bk_csv_columns() as $column => $def ) {
			$columns[ $def[1] ] = $column;
			$columns[ $column ] = $column;
		}
		return $columns;
	}
);
add_filter(
	'woocommerce_product_import_pre_insert_product_object',
	static function ( WC_Product $product, array $data ): WC_Product {
		foreach ( bk_csv_columns() as $column => $def ) {
			if ( isset( $data[ $column ] ) ) {
				$value = str_starts_with( $def[0], '_base_usd' ) ? wc_format_decimal( $data[ $column ] ) : bk_sanitize_product_field( $def[0], $data[ $column ] );
				$product->update_meta_data( $def[0], $value );
			}
		}
		return $product;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Read API used by templates, schema and REST
 * ---------------------------------------------------------------------- */

/**
 * Warranty text for a product (override → brand → default).
 */
function bk_product_warranty( WC_Product $product ): string {
	$override = (string) $product->get_meta( '_bk_warranty_override' );
	if ( $override ) {
		return $override;
	}
	$brand = bk_get_product_brand( $product->get_id() );
	return $brand ? bk_brand_meta( $brand )['warranty_text'] : __( 'كفالة الوكيل الرسمية', 'baytkahraba' );
}

/**
 * Ordered technical spec rows.
 *
 * @return array<string, array{label: string, value: string}>
 */
function bk_product_specs( WC_Product $product ): array {
	$id    = $product->get_id();
	$power = bk_get_product_power( $id );
	$brand = bk_get_product_brand( $id );
	$cat   = bk_product_primary_category( $id );
	$dims  = (string) $product->get_meta( '_bk_dimensions' );
	if ( ! $dims && $product->has_dimensions() ) {
		$dims = wc_format_dimensions( $product->get_dimensions( false ) );
	}

	$rows = array(
		'model'    => array( __( 'رقم الموديل', 'baytkahraba' ), (string) $product->get_meta( '_bk_model_code' ) ?: $product->get_sku() ),
		'brand'    => array( __( 'الماركة', 'baytkahraba' ), $brand ? $brand->name : '' ),
		'category' => array( __( 'القسم', 'baytkahraba' ), $cat ? implode( ' › ', wp_list_pluck( bk_category_chain( $cat->term_id ), 'name' ) ) : '' ),
		'capacity' => array( __( 'السعة / الحجم', 'baytkahraba' ), (string) $product->get_meta( '_bk_capacity' ) ),
		'wattage'  => array( __( 'الاستطاعة بالواط', 'baytkahraba' ), bk_format_watt( $power['wattage'] ) ),
		'ampere'   => array( __( 'السحب بالأمبير', 'baytkahraba' ), bk_format_ampere( $power['ampere'] ) ),
		'power'    => array( __( 'التوافق مع الانفرتر / الطاقة الشمسية', 'baytkahraba' ), $power['label'] ),
		'origin'   => array( __( 'المنشأ / الوكيل', 'baytkahraba' ), trim( (string) $product->get_meta( '_bk_origin' ) . ( $brand ? ' — ' . bk_brand_meta( $brand )['agent'] : '' ), ' —' ) ),
		'dims'     => array( __( 'الأبعاد', 'baytkahraba' ), $dims ),
		'weight'   => array( __( 'الوزن', 'baytkahraba' ), $product->has_weight() ? wc_format_weight( $product->get_weight() ) : '' ),
		'warranty' => array( __( 'مدة الكفالة', 'baytkahraba' ), bk_product_warranty( $product ) ),
	);

	// Visible WooCommerce attributes are appended automatically.
	foreach ( $product->get_attributes() as $attribute ) {
		if ( $attribute->get_visible() && ! $attribute->get_variation() ) {
			$rows[ 'attr_' . $attribute->get_name() ] = array( wc_attribute_label( $attribute->get_name() ), wp_strip_all_tags( $product->get_attribute( $attribute->get_name() ) ) );
		}
	}

	$out = array();
	foreach ( apply_filters( 'bk_product_specs', $rows, $product ) as $key => $row ) {
		if ( '' !== trim( (string) $row[1] ) ) {
			$out[ $key ] = array( 'label' => $row[0], 'value' => $row[1] );
		}
	}
	return $out;
}

/**
 * Highlight bullets — manual lines, or generated from real product data.
 *
 * @return string[]
 */
function bk_product_highlights( WC_Product $product ): array {
	$manual = array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $product->get_meta( '_bk_highlights' ) ) ) );
	if ( $manual ) {
		return array_values( $manual );
	}
	$power = bk_get_product_power( $product->get_id() );
	$brand = bk_get_product_brand( $product->get_id() );
	$auto  = array();
	if ( $capacity = (string) $product->get_meta( '_bk_capacity' ) ) {
		$auto[] = sprintf( __( 'سعة عملية: %s تناسب احتياجات العائلة السورية.', 'baytkahraba' ), $capacity );
	}
	if ( $power['wattage'] ) {
		$auto[] = sprintf( __( 'استهلاك %1$s فقط (حوالي %2$s) — حسابها سهل على اشتراك الأمبير.', 'baytkahraba' ), bk_format_watt( $power['wattage'] ), bk_format_ampere( $power['ampere'] ) );
	}
	if ( $power['class'] ) {
		$auto[] = $power['label'] . '.';
	}
	$auto[] = sprintf( __( '%s مع بطاقة كفالة الوكيل داخل الكرتونة.', 'baytkahraba' ), bk_product_warranty( $product ) );
	$auto[] = $brand ? sprintf( __( 'منتج أصلي من %s عبر الوكيل المعتمد — نفس سعر المحل بدون زيادات.', 'baytkahraba' ), $brand->name ) : __( 'منتج أصلي بنفس سعر المحل بدون زيادات.', 'baytkahraba' );
	return $auto;
}

/**
 * Real-shop video source.
 *
 * @return array{src: string, type: string, poster: string}|null
 */
function bk_product_video( WC_Product $product ): ?array {
	$raw = (string) $product->get_meta( '_bk_shop_video' );
	if ( '' === $raw ) {
		return null;
	}
	$src = is_numeric( $raw ) ? (string) wp_get_attachment_url( (int) $raw ) : $raw;
	if ( ! $src ) {
		return null;
	}
	$poster_raw = (string) $product->get_meta( '_bk_shop_video_poster' );
	$poster     = is_numeric( $poster_raw ) ? (string) wp_get_attachment_image_url( (int) $poster_raw, 'bk-thumb' ) : $poster_raw;
	if ( ! $poster ) {
		$poster = (string) wp_get_attachment_image_url( $product->get_image_id(), 'bk-thumb' );
	}
	return array(
		'src'    => $src,
		'type'   => str_ends_with( strtolower( wp_parse_url( $src, PHP_URL_PATH ) ?? '' ), '.webm' ) ? 'video/webm' : 'video/mp4',
		'poster' => $poster,
	);
}
