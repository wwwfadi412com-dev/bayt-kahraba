<?php
/**
 * Admin hub "بيت الكهرباء": top-level menu, general store settings and a
 * small reusable field renderer used by every module's settings screen.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

const BK_ADMIN_SLUG = 'bk-dashboard';

add_action(
	'admin_menu',
	static function (): void {
		add_menu_page(
			__( 'بيت الكهرباء', 'baytkahraba' ),
			__( 'بيت الكهرباء', 'baytkahraba' ),
			'manage_woocommerce',
			BK_ADMIN_SLUG,
			'bk_render_general_settings_page',
			'dashicons-lightbulb',
			56
		);
		add_submenu_page( BK_ADMIN_SLUG, __( 'الإعدادات العامة', 'baytkahraba' ), __( 'الإعدادات العامة', 'baytkahraba' ), 'manage_woocommerce', BK_ADMIN_SLUG, 'bk_render_general_settings_page' );
	},
	9
);

add_action(
	'admin_init',
	static function (): void {
		register_setting(
			'bk_settings_group',
			'bk_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'bk_sanitize_settings',
				'default'           => array(),
			)
		);
	}
);

/**
 * General settings schema: section => [ key => [label, type, help] ].
 */
function bk_general_settings_schema(): array {
	return array(
		__( 'هوية المتجر', 'baytkahraba' )          => array(
			'store_name'    => array( __( 'اسم المتجر', 'baytkahraba' ), 'text' ),
			'store_tagline' => array( __( 'الشعار الفرعي تحت الاسم', 'baytkahraba' ), 'text' ),
			'top_bar_message' => array( __( 'رسالة الشريط العلوي', 'baytkahraba' ), 'text' ),
			'hero_title'    => array( __( 'عنوان الواجهة الرئيسية', 'baytkahraba' ), 'text' ),
			'hero_subtitle' => array( __( 'وصف الواجهة الرئيسية', 'baytkahraba' ), 'textarea' ),
			'hero_image_id' => array( __( 'صورة الواجهة (LCP)', 'baytkahraba' ), 'image', __( 'يُفضّل 1600×900 بصيغة WebP.', 'baytkahraba' ) ),
		),
		__( 'التواصل والموقع (يُستخدم في Schema والواتساب)', 'baytkahraba' ) => array(
			'phone'               => array( __( 'رقم الهاتف', 'baytkahraba' ), 'text' ),
			'whatsapp'            => array( __( 'رقم واتساب الطلبات', 'baytkahraba' ), 'text', __( 'مثال: 0933123456 أو 963933123456', 'baytkahraba' ) ),
			'email'               => array( __( 'البريد الإلكتروني', 'baytkahraba' ), 'text' ),
			'address'             => array( __( 'عنوان المحل', 'baytkahraba' ), 'text' ),
			'city'                => array( __( 'المدينة', 'baytkahraba' ), 'text' ),
			'geo_lat'             => array( __( 'خط العرض', 'baytkahraba' ), 'text' ),
			'geo_lng'             => array( __( 'خط الطول', 'baytkahraba' ), 'text' ),
			'opening_hours'       => array( __( 'ساعات العمل (صيغة Schema)', 'baytkahraba' ), 'text', 'Sa-Th 09:00-21:00' ),
			'opening_hours_label' => array( __( 'ساعات العمل (للعرض)', 'baytkahraba' ), 'text' ),
			'facebook'            => array( 'Facebook URL', 'url' ),
			'instagram'           => array( 'Instagram URL', 'url' ),
			'telegram'            => array( 'Telegram URL', 'url' ),
		),
		__( 'المتجر و SEO', 'baytkahraba' )         => array(
			'products_per_page' => array( __( 'عدد المنتجات في الصفحة', 'baytkahraba' ), 'number' ),
			'compare_max'       => array( __( 'الحد الأقصى للمقارنة', 'baytkahraba' ), 'number' ),
			'return_days'       => array( __( 'مدة الاسترجاع/الاستبدال (أيام)', 'baytkahraba' ), 'number' ),
			'price_range'       => array( __( 'فئة الأسعار (Schema priceRange)', 'baytkahraba' ), 'text' ),
			'schema_types'      => array( __( 'أنواع Schema للمتجر', 'baytkahraba' ), 'text', 'Store,HardwareStore,ElectronicsStore' ),
		),
	);
}

function bk_sanitize_settings( $input ): array {
	$input  = is_array( $input ) ? $input : array();
	$clean  = array();
	foreach ( bk_general_settings_schema() as $fields ) {
		foreach ( $fields as $key => $field ) {
			$raw = $input[ $key ] ?? '';
			switch ( $field[1] ) {
				case 'textarea':
					$clean[ $key ] = sanitize_textarea_field( $raw );
					break;
				case 'number':
				case 'image':
					$clean[ $key ] = absint( $raw );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( $raw );
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $raw );
			}
		}
	}
	if ( ! empty( $clean['whatsapp'] ) ) {
		$clean['whatsapp'] = bk_phone_international( $clean['whatsapp'] );
	}
	bk_cache_bump();
	return $clean;
}

/**
 * Reusable field renderer.
 *
 * @param string $name  Input name attribute.
 * @param string $type  text|textarea|number|url|image|select|checkbox.
 * @param mixed  $value Current value.
 * @param array  $extra { options: array, help: string, rows: int, class: string }.
 */
function bk_admin_field( string $name, string $type, $value, array $extra = array() ): void {
	$id   = sanitize_html_class( str_replace( array( '[', ']' ), '_', $name ) );
	$help = $extra['help'] ?? '';
	switch ( $type ) {
		case 'textarea':
			printf( '<textarea class="large-text code" rows="%d" id="%s" name="%s">%s</textarea>', (int) ( $extra['rows'] ?? 4 ), esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
			break;
		case 'select':
			printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
			foreach ( (array) ( $extra['options'] ?? array() ) as $opt_value => $opt_label ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $opt_value ), selected( (string) $value, (string) $opt_value, false ), esc_html( $opt_label ) );
			}
			echo '</select>';
			break;
		case 'checkbox':
			printf( '<label><input type="checkbox" id="%s" name="%s" value="1" %s> %s</label>', esc_attr( $id ), esc_attr( $name ), checked( (bool) $value, true, false ), esc_html( $extra['label'] ?? '' ) );
			break;
		case 'image':
			$value = absint( $value );
			$src   = $value ? wp_get_attachment_image_url( $value, 'thumbnail' ) : '';
			echo '<div class="bk-media-field" data-bk-media>';
			printf( '<input type="hidden" name="%s" value="%d" data-bk-media-input>', esc_attr( $name ), (int) $value );
			printf( '<img src="%s" alt="" class="bk-media-preview" data-bk-media-preview %s>', esc_url( (string) $src ), $src ? '' : 'hidden' );
			printf( '<button type="button" class="button" data-bk-media-select>%s</button> ', esc_html__( 'اختيار صورة', 'baytkahraba' ) );
			printf( '<button type="button" class="button-link-delete" data-bk-media-remove %s>%s</button>', $value ? '' : 'hidden', esc_html__( 'إزالة', 'baytkahraba' ) );
			echo '</div>';
			break;
		default:
			// 'color' stays a text input (a native color input can't be left empty).
			printf(
				'<input type="%s" class="%s" id="%s" name="%s" value="%s" dir="auto"%s>',
				esc_attr( in_array( $type, array( 'number', 'url' ), true ) ? $type : 'text' ),
				esc_attr( $extra['class'] ?? ( 'color' === $type ? 'bk-color-field' : 'regular-text' ) ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				'color' === $type ? ' placeholder="#1E3A8A" pattern="#[0-9A-Fa-f]{6}"' : ''
			);
	}
	if ( $help ) {
		printf( '<p class="description">%s</p>', esc_html( $help ) );
	}
}

function bk_render_general_settings_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$values = wp_parse_args( (array) get_option( 'bk_settings', array() ), bk_settings_defaults() );
	?>
	<div class="wrap bk-admin">
		<h1><?php esc_html_e( 'بيت الكهرباء — الإعدادات العامة', 'baytkahraba' ); ?></h1>
		<p class="bk-admin-lead"><?php esc_html_e( 'كل ما يظهر في الواجهة، الواتساب، و Schema يُدار من هنا. الماركات والتصنيفات تُدار من المنتجات ← الماركات / التصنيفات.', 'baytkahraba' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'bk_settings_group' ); ?>
			<?php foreach ( bk_general_settings_schema() as $section => $fields ) : ?>
				<div class="bk-admin-card">
					<h2><?php echo esc_html( $section ); ?></h2>
					<table class="form-table" role="presentation">
						<?php foreach ( $fields as $key => $field ) : ?>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( 'bk_settings_' . $key . '_' ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
								<td><?php bk_admin_field( 'bk_settings[' . $key . ']', $field[1], $values[ $key ] ?? '', array( 'help' => $field[2] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			<?php endforeach; ?>
			<?php submit_button( __( 'حفظ الإعدادات', 'baytkahraba' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Admin assets on our screens, product edit and taxonomy screens.
 */
add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		$screen = get_current_screen();
		$ours   = str_contains( $hook, 'bk-' )
			|| ( $screen && in_array( $screen->id, array( 'product', 'edit-product_brand', 'edit-product_cat' ), true ) )
			|| ( $screen && in_array( $screen->taxonomy ?? '', array( 'product_brand', 'product_cat' ), true ) );
		if ( ! $ours ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'bk-admin', BK_URI . '/assets/css/admin.css', array(), BK_VERSION );
		wp_enqueue_script( 'bk-admin', BK_URI . '/assets/js/dist/admin.js', array(), BK_VERSION, true );
		wp_localize_script(
			'bk-admin',
			'BKAdmin',
			array(
				'chooseImage' => __( 'اختر صورة', 'baytkahraba' ),
				'chooseVideo' => __( 'اختر فيديو (MP4 / WebM)', 'baytkahraba' ),
				'useThis'     => __( 'استخدام', 'baytkahraba' ),
			)
		);
	}
);
