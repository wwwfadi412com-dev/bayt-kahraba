<?php
/**
 * One-click store bootstrap + rich demo data.
 *
 * Configures WooCommerce for Syria (SYP, governorates, classic checkout,
 * shipping zone, COD + transfer gateways), then seeds the four launch brands,
 * a full 3-level category tree and 26 realistic products with Arabic copy,
 * electrical specs, FAQ and bundle tiers. Idempotent (safe to run twice) and
 * fully reversible: everything created is tagged `_bk_demo`.
 *
 * Admin: بيت الكهرباء ← تهيئة المتجر  |  CLI: wp bk seed [--remove]
 *
 * IMPORTANT: brand warranty terms, service centers, model codes and prices are
 * SAMPLE values — confirm them with each official agent before going live.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Data
 * ---------------------------------------------------------------------- */

function bk_seed_brands(): array {
	return array(
		'alhafez' => array(
			'name'   => 'الحافظ',
			'agent'  => 'وكيل رسمي ومعتمد في سوريا - كفالة حقيقية',
			'text'   => 'كفالة شاملة لمدة سنتين صيانة وقطع تبديل',
			'months' => 24,
			'color'  => '#1E3A8A',
			'centers' => "دمشق | المزة - أوتوستراد المزة | 0112000001\nحلب | الفرقان | 0212000001\nحمص | شارع الحضارة | 0312000001",
			'faq'    => "هل أجهزة الحافظ تعمل على الطاقة الشمسية؟ | معظم برادات وشاشات الحافظ الحديثة بضاغط أو تقنية إنفرتر وتعمل على المنظومات المنزلية، وتجد شارة خضراء على كل جهاز متوافق.\nهل قطع تبديل الحافظ متوفرة؟ | نعم، قطع التبديل متوفرة لدى الوكيل في دمشق والمحافظات طوال فترة الكفالة وبعدها.",
		),
		'alaraby' => array(
			'name'   => 'العربي',
			'agent'  => 'وكيل رسمي ومعتمد في سوريا - كفالة حقيقية',
			'text'   => 'كفالة سنتين على الجهاز وخمس سنوات على الضاغط',
			'months' => 24,
			'color'  => '#B91C1C',
			'centers' => "دمشق | البرامكة - جانب الجسر | 0112000002\nاللاذقية | شارع 8 آذار | 0412000002",
			'faq'    => 'ما الذي تشمله كفالة العربي؟ | تشمل الكفالة الصيانة وقطع التبديل خلال سنتين، مع كفالة ممتدة على الضاغط لمدة خمس سنوات.',
		),
		'hendico' => array(
			'name'   => 'هنديكو',
			'agent'  => 'وكيل رسمي ومعتمد في سوريا - كفالة حقيقية',
			'text'   => 'كفالة سنة كاملة شاملة الصيانة وقطع التبديل',
			'months' => 12,
			'color'  => '#0F766E',
			'centers' => "دمشق | الزاهرة الجديدة | 0112000003\nحماة | ساحة العاصي | 0332000003",
			'faq'    => 'هل هنديكو مناسبة للميزانية المحدودة؟ | نعم، هنديكو من أفضل الخيارات الاقتصادية مع كفالة حقيقية، وتحديداً غسالات الحوضين ومراوح الشحن.',
		),
		'ramco'   => array(
			'name'   => 'رامكو',
			'agent'  => 'وكيل رسمي ومعتمد في سوريا - كفالة حقيقية',
			'text'   => 'كفالة سنتين مع صيانة مجانية في كل المحافظات',
			'months' => 24,
			'color'  => '#7C3AED',
			'centers' => "دمشق | ركن الدين | 0112000004\nطرطوس | الكورنيش البحري | 0432000004",
			'faq'    => 'هل صيانة رامكو متوفرة خارج دمشق؟ | نعم، لدى رامكو مراكز صيانة معتمدة في عدة محافظات والصيانة مجانية خلال فترة الكفالة.',
		),
	);
}

/**
 * slug => [name, parent slug, weight class, keyword profile].
 */
function bk_seed_categories(): array {
	return array(
		'large-appliances'             => array( 'الأجهزة المنزلية الكبيرة', '', 'heavy', '' ),
		'refrigerators-freezers'       => array( 'برادات وفريزرات', 'large-appliances', '', 'fridge' ),
		'double-door-inverter-fridges' => array( 'برادات بابين (إنفرتر)', 'refrigerators-freezers', '', '' ),
		'chest-upright-freezers'       => array( 'فريزرات أفقية وعمودية', 'refrigerators-freezers', '', 'freezer' ),
		'mini-bar-fridges'             => array( 'برادات مكتبية وميني بار', 'refrigerators-freezers', 'small', '' ),
		'washing-machines'             => array( 'غسالات أوتوماتيك وحوضين', 'large-appliances', '', 'washer' ),
		'automatic-washers'            => array( 'غسالات أوتوماتيك', 'washing-machines', '', '' ),
		'twin-tub-washers'             => array( 'غسالات حوضين', 'washing-machines', '', '' ),
		'dishwashers'                  => array( 'جلايات صحون', 'large-appliances', '', 'dishwasher' ),
		'cookers-ovens'                => array( 'طباخات وأفران غاز', 'large-appliances', '', 'cooker' ),
		'kitchen-appliances'           => array( 'أجهزة المطبخ والتحضير', '', 'small', '' ),
		'microwaves'                   => array( 'مايكروويف', 'kitchen-appliances', '', 'microwave' ),
		'air-fryers'                   => array( 'قلايات هوائية', 'kitchen-appliances', '', 'airfryer' ),
		'blenders-food-processors'     => array( 'خلاطات ومحضرات طعام', 'kitchen-appliances', '', 'kitchen' ),
		'tvs-entertainment'            => array( 'الشاشات والترفيه', '', 'heavy', '' ),
		'tvs'                          => array( 'شاشات وتلفزيونات', 'tvs-entertainment', '', 'tv' ),
		'smart-android-tvs'            => array( 'شاشات سمارت أندرويد 4K', 'tvs', '', '' ),
		'tvs-built-in-receiver'        => array( 'شاشات مع رسيفر داخلي', 'tvs', '', '' ),
		'cooling-heating'              => array( 'التكييف والتبريد والتدفئة', '', 'heavy', '' ),
		'air-conditioners'             => array( 'مكيفات', 'cooling-heating', '', 'ac' ),
		'inverter-air-conditioners'    => array( 'مكيفات إنفرتر', 'air-conditioners', '', '' ),
		'fans'                         => array( 'مراوح', 'cooling-heating', 'small', 'fan' ),
		'rechargeable-fans'            => array( 'مراوح شحن بطارية ليثيوم', 'fans', '', '' ),
		'heaters'                      => array( 'مدافئ وسخانات', 'cooling-heating', 'small', 'heater' ),
		'home-garment-care'            => array( 'العناية بالمنزل والملابس', '', 'small', '' ),
		'vacuum-cleaners'              => array( 'مكانس كهربائية', 'home-garment-care', '', 'vacuum' ),
		'irons'                        => array( 'مكاوي', 'home-garment-care', '', 'iron' ),
	);
}

/**
 * [name, brand, category, usd, usd_sale, watt, amp, power class, capacity, dimensions, model, weight kg, bundle tier, lead copy].
 */
function bk_seed_products(): array {
	return array(
		array( 'براد الحافظ بابين 16 قدم نوفروست إنفرتر', 'alhafez', 'double-door-inverter-fridges', 520, 0, 180, 0.8, 'solar_ready', '16 قدم - 450 لتر', '70 × 72 × 178 سم', 'HF-RF16NF-INV', 68, 'smart', 'براد عائلي هادئ بضاغط إنفرتر يحافظ على برودة ثابتة ويسحب أقل من أمبير واحد بعد الاستقرار، ليبقى أكلك طازجاً حتى في ساعات التقنين.' ),
		array( 'براد العربي بابين 18 قدم إنفرتر ديجيتال', 'alaraby', 'double-door-inverter-fridges', 640, 610, 190, 0.9, 'solar_ready', '18 قدم - 510 لتر', '74 × 75 × 185 سم', 'AR-RF18DI-INV', 75, 'premium', 'شاشة تحكم ديجيتال خارجية، رفوف زجاج مقسّى ودرج خضار مستقل للرطوبة — براد بابين واسع للعائلات الكبيرة ومن يحب تخزين المونة.' ),
		array( 'براد هنديكو بابين 14 قدم ديفروست', 'hendico', 'double-door-inverter-fridges', 380, 0, 150, 1.1, 'low_amp', '14 قدم - 380 لتر', '60 × 65 × 160 سم', 'HD-RF14DF', 58, 'economic', 'الخيار الاقتصادي الأذكى لبيت العرسان: سعة عملية، استهلاك منخفض، وكفالة حقيقية بسعر يناسب الميزانية.' ),
		array( 'براد رامكو 21 قدم نوفروست إنفرتر مع موزع ماء', 'ramco', 'double-door-inverter-fridges', 780, 0, 210, 1.0, 'solar_ready', '21 قدم - 600 لتر', '80 × 76 × 190 سم', 'RM-RF21NF-WD', 85, 'premium', 'موزع ماء بارد خارجي، إضاءة LED داخلية وتبريد متعدد الاتجاهات — براد فاخر بسعة كبيرة وسحب كهرباء منخفض بفضل ضاغط الإنفرتر.' ),
		array( 'فريزر الحافظ أفقي (صندوق) 300 لتر', 'alhafez', 'chest-upright-freezers', 340, 0, 120, 0.6, 'solar_ready', '300 لتر', '110 × 60 × 85 سم', 'HF-CF300', 45, '', 'فريزر صندوق يحفظ البرودة لساعات طويلة عند انقطاع الكهرباء — مثالي لمونة اللحمة والخضار ولمن يعتمد على الطاقة الشمسية.' ),
		array( 'فريزر العربي عمودي 7 جوارير نوفروست', 'alaraby', 'chest-upright-freezers', 560, 0, 160, 0.9, 'low_amp', '7 جوارير - 260 لتر', '60 × 65 × 175 سم', 'AR-UF7NF', 62, '', 'سبعة جوارير شفافة لترتيب المونة بسهولة، بدون تكوّن ثلج، مع تجميد سريع للحوم.' ),
		array( 'براد ميني بار هنديكو 90 لتر', 'hendico', 'mini-bar-fridges', 140, 125, 70, 0.35, 'solar_ready', '90 لتر', '48 × 45 × 85 سم', 'HD-MB90', 20, '', 'براد صغير للمكتب أو غرفة النوم أو المحل، يعمل بسهولة على البطارية والانفرتر المنزلي.' ),
		array( 'غسالة الحافظ أوتوماتيك 8 كغ إنفرتر دفع مباشر', 'alhafez', 'automatic-washers', 470, 0, 2000, 9.1, 'standard_grid', '8 كغ', '60 × 56 × 85 سم', 'HF-WM8DD', 70, 'smart', 'محرك إنفرتر بالدفع المباشر أهدأ وأطول عمراً، 15 برنامج غسيل بينها الغسيل البارد الموفّر للكهرباء.' ),
		array( 'غسالة العربي أوتوماتيك 10 كغ تنشيف كامل', 'alaraby', 'automatic-washers', 690, 650, 2200, 10, 'standard_grid', '10 كغ غسيل - 7 كغ تنشيف', '60 × 62 × 85 سم', 'AR-WD10', 78, 'premium', 'غسيل وتنشيف كامل في جهاز واحد — وداعاً لنشر الغسيل في الشتاء، مع برنامج خاص للحرامات والأقمشة الحساسة.' ),
		array( 'غسالة هنديكو حوضين 10 كغ نصف أوتوماتيك', 'hendico', 'twin-tub-washers', 190, 0, 420, 1.9, 'low_amp', '10 كغ', '82 × 48 × 95 سم', 'HD-TT10', 30, 'economic', 'غسالة الحوضين الأكثر تحملاً لانقطاع الكهرباء وضعف ضغط الماء، وتعمل على اشتراك أمبيرين.' ),
		array( 'غسالة رامكو أوتوماتيك 7 كغ', 'ramco', 'automatic-washers', 360, 0, 1800, 8.2, 'standard_grid', '7 كغ', '60 × 52 × 85 سم', 'RM-WM7', 63, 'economic', 'غسالة أوتوماتيك عملية لعائلة من 4 أشخاص بسعر منافس وكفالة صيانة في كل المحافظات.' ),
		array( 'جلاية الحافظ 12 مكان 6 برامج', 'alhafez', 'dishwashers', 450, 0, 1800, 8.2, 'standard_grid', '12 مكان', '60 × 60 × 85 سم', 'HF-DW12', 48, '', 'جلاية توفر الماء والوقت مع برنامج إيكو منخفض الاستهلاك وبرنامج سريع خلال 30 دقيقة.' ),
		array( 'طباخ غاز العربي 5 عيون 90×60 مع إشعال ذاتي', 'alaraby', 'cookers-ovens', 420, 0, 15, 0.1, 'solar_ready', '5 عيون - فرن 100 لتر', '90 × 60 × 85 سم', 'AR-GC5090', 55, 'smart', 'طباخ كبير بخمس عيون منها عين وسطية للتسوية السريعة، فرن واسع بشواية، وإشعال ذاتي بالكهرباء لا يحتاج سوى سحب بسيط جداً.' ),
		array( 'طباخ غاز هنديكو 4 عيون 60×60', 'hendico', 'cookers-ovens', 230, 0, 10, 0.05, 'solar_ready', '4 عيون - فرن 60 لتر', '60 × 60 × 85 سم', 'HD-GC4060', 38, 'economic', 'طباخ عملي للمطابخ الصغيرة، يعمل بالغاز ولا يتأثر بانقطاع الكهرباء.' ),
		array( 'شاشة الحافظ 43 إنش سمارت مع رسيفر داخلي', 'alhafez', 'tvs-built-in-receiver', 230, 210, 75, 0.35, 'solar_ready', '43 إنش - Full HD', '97 × 57 × 8 سم', 'HF-43SR', 9, 'economic', 'شاشة سمارت برسيفر داخلي توفر عليك جهاز الرسيفر وسحبه الكهربائي، وتعمل بسهولة على البطارية.' ),
		array( 'شاشة العربي 55 إنش سمارت أندرويد 4K', 'alaraby', 'smart-android-tvs', 420, 0, 120, 0.55, 'solar_ready', '55 إنش - 4K UHD', '124 × 72 × 9 سم', 'AR-55AND4K', 15, 'smart', 'دقة 4K مع نظام أندرويد ومتجر تطبيقات كامل: يوتيوب، نتفليكس وشاهد مباشرة، مع رسيفر داخلي.' ),
		array( 'شاشة رامكو 65 إنش أندرويد 4K HDR', 'ramco', 'smart-android-tvs', 690, 0, 160, 0.75, 'solar_ready', '65 إنش - 4K HDR', '145 × 84 × 9 سم', 'RM-65AND4K', 22, 'premium', 'تجربة سينما في الصالون: شاشة 65 إنش بتقنية HDR وصوت محيطي، واستهلاك أقل من أمبير.' ),
		array( 'مكيف الحافظ إنفرتر 12000 BTU', 'alhafez', 'inverter-air-conditioners', 520, 0, 1100, 4.5, 'low_amp', '12000 BTU', 'داخلي 80 سم / خارجي 70 سم', 'HF-AC12INV', 38, '', 'مكيف إنفرتر تبريد وتدفئة يبدأ بسحب منخفض ويحافظ على الحرارة دون فصل ووصل — مناسب للغرف حتى 25 متر مربع.' ),
		array( 'مكيف العربي إنفرتر 18000 BTU يعمل على الطاقة الشمسية', 'alaraby', 'inverter-air-conditioners', 760, 0, 1600, 6.8, 'low_amp', '18000 BTU', 'داخلي 95 سم / خارجي 80 سم', 'AR-AC18INV', 52, '', 'مكيف إنفرتر للصالونات الكبيرة، مجهز للعمل على منظومات الطاقة الشمسية المتوسطة مع وضع توفير ليلي.' ),
		array( 'مروحة شحن هنديكو 16 إنش بطارية ليثيوم', 'hendico', 'rechargeable-fans', 75, 65, 30, 0.15, 'solar_ready', '16 إنش - بطارية 8 ساعات', 'ارتفاع 120 سم', 'HD-RF16LI', 6, '', 'رفيقة ساعات التقنين: بطارية ليثيوم تعمل حتى 8 ساعات، شحن من الكهرباء أو اللوح الشمسي، وإضاءة LED مدمجة.' ),
		array( 'مايكروويف رامكو 25 لتر مع شواية', 'ramco', 'microwaves', 140, 0, 1000, 4.5, 'standard_grid', '25 لتر', '48 × 38 × 28 سم', 'RM-MW25G', 14, 'smart', 'تسخين، فك تجميد وشوي في جهاز واحد مع 8 برامج طبخ تلقائية.' ),
		array( 'قلاية هوائية الحافظ 5.5 لتر ديجيتال', 'alhafez', 'air-fryers', 95, 85, 1700, 7.7, 'standard_grid', '5.5 لتر', '30 × 35 × 32 سم', 'HF-AF55', 5, '', 'بطاطا مقرمشة ودجاج مشوي بدون زيت تقريباً، مع شاشة ديجيتال و8 برامج جاهزة.' ),
		array( 'خلاط ومحضرة طعام العربي 600 واط', 'alaraby', 'blenders-food-processors', 85, 0, 600, 2.7, 'low_amp', 'إبريق 1.5 لتر', '22 × 22 × 40 سم', 'AR-FP600', 4, 'economic', 'خلاط وفرامة ومحضرة طعام بشفرات ستانلس ستيل، تعمل على الأمبير لتحضير الكبة والعصائر بسهولة.' ),
		array( 'مكنسة كهربائية رامكو برميل 2000 واط', 'ramco', 'vacuum-cleaners', 110, 0, 2000, 9, 'standard_grid', 'خزان 20 لتر', '38 × 38 × 52 سم', 'RM-VC2000', 9, 'smart', 'مكنسة برميل قوية للسجاد والموكيت مع منظم قوة الشفط وفلتر قابل للغسل.' ),
		array( 'مكواة بخار هنديكو 2200 واط', 'hendico', 'irons', 35, 0, 2200, 10, 'standard_grid', 'خزان 300 مل', '28 × 12 × 15 سم', 'HD-SI2200', 2, 'economic', 'مكواة بخار بقاعدة سيراميك منزلقة وبخّة قوية لإزالة التجاعيد الصعبة.' ),
		array( 'مدفأة زيت العربي 11 ريشة مع ترموستات', 'alaraby', 'heaters', 120, 0, 2500, 11.4, 'standard_grid', '11 ريشة', '62 × 25 × 65 سم', 'AR-OH11', 14, '', 'دفء آمن وصامت لغرفة حتى 25 متر مربع مع ترموستات يوفر الاستهلاك.' ),
	);
}

/* -------------------------------------------------------------------------
 * Seeder
 * ---------------------------------------------------------------------- */

function bk_seed_configure_woocommerce(): void {
	$options = array(
		'woocommerce_default_country'            => 'SY:DI',
		'woocommerce_currency'                   => 'SYP',
		'woocommerce_currency_pos'               => 'right_space',
		'woocommerce_price_num_decimals'         => 0,
		'woocommerce_price_thousand_sep'         => ',',
		'woocommerce_price_decimal_sep'          => '.',
		'woocommerce_allowed_countries'          => 'specific',
		'woocommerce_specific_allowed_countries' => array( 'SY' ),
		'woocommerce_ship_to_countries'          => 'specific',
		'woocommerce_specific_ship_to_countries' => array( 'SY' ),
		'woocommerce_enable_guest_checkout'      => 'yes',
		'woocommerce_enable_checkout_login_reminder' => 'no',
		'woocommerce_calc_taxes'                 => 'no',
		'woocommerce_weight_unit'                => 'kg',
		'woocommerce_dimension_unit'             => 'cm',
		'woocommerce_enable_reviews'             => 'yes',
		'woocommerce_shipping_cost_requires_address' => 'no',
		// WooCommerce 9.1+ starts new stores in "Coming soon" mode, hiding every shop page from visitors.
		'woocommerce_coming_soon'                => 'no',
		'woocommerce_store_pages_only'           => 'no',
	);
	foreach ( $options as $key => $value ) {
		update_option( $key, $value );
	}

	update_option(
		'woocommerce_cod_settings',
		array_merge(
			(array) get_option( 'woocommerce_cod_settings', array() ),
			array(
				'enabled'      => 'yes',
				'title'        => 'الدفع عند الاستلام (دمشق وريفها)',
				'description'  => 'ادفع نقداً عند استلام الجهاز وفحصه أمام مندوب التوصيل.',
				'instructions' => 'سيتواصل معك فريقنا عبر واتساب لتأكيد موعد التوصيل.',
			)
		)
	);
	update_option( 'woocommerce_bk_transfer_settings', array_merge( (array) get_option( 'woocommerce_bk_transfer_settings', array() ), array( 'enabled' => 'yes' ) ) );

	// Shipping zone "سوريا" with the unified Syrian method.
	$exists = false;
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'bk_syria' === $method->id ) {
				$exists = true;
			}
		}
	}
	if ( ! $exists ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'سوريا' );
		$zone->set_zone_order( 1 );
		$zone->add_location( 'SY', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'bk_syria' );
	}

	// Fresh installs that skipped the WooCommerce wizard have no shop/cart/checkout pages yet.
	if ( wc_get_page_id( 'shop' ) < 1 || wc_get_page_id( 'checkout' ) < 1 ) {
		WC_Install::create_pages();
	}
	bk_ensure_classic_checkout();
}

function bk_seed_terms(): array {
	$ids = array( 'brand' => array(), 'cat' => array() );

	foreach ( bk_seed_brands() as $slug => $brand ) {
		$term = term_exists( $slug, BK_BRAND_TAX );
		if ( ! $term ) {
			$term = wp_insert_term( $brand['name'], BK_BRAND_TAX, array( 'slug' => $slug ) );
			if ( is_wp_error( $term ) ) {
				continue;
			}
			update_term_meta( (int) $term['term_id'], '_bk_demo', 1 );
		}
		$id = (int) $term['term_id'];
		update_term_meta( $id, 'brand_origin_and_agent', $brand['agent'] );
		update_term_meta( $id, 'brand_warranty_years', $brand['text'] );
		update_term_meta( $id, 'brand_warranty_months', $brand['months'] );
		update_term_meta( $id, 'brand_badge_color', $brand['color'] );
		update_term_meta( $id, 'brand_service_centers', $brand['centers'] );
		update_term_meta( $id, 'brand_faq_json', bk_sanitize_faq_input( $brand['faq'] ) );
		$ids['brand'][ $slug ] = $id;
	}

	$order = 0;
	foreach ( bk_seed_categories() as $slug => $cat ) {
		$term = term_exists( $slug, 'product_cat' );
		if ( ! $term ) {
			$parent = $cat[1] ? (int) ( $ids['cat'][ $cat[1] ] ?? 0 ) : 0;
			$term   = wp_insert_term( $cat[0], 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
			if ( is_wp_error( $term ) ) {
				continue;
			}
			update_term_meta( (int) $term['term_id'], '_bk_demo', 1 );
		}
		$id = (int) $term['term_id'];
		update_term_meta( $id, 'order', $order++ );
		if ( $cat[2] ) {
			update_term_meta( $id, 'bk_weight_class', $cat[2] );
		}
		if ( $cat[3] ) {
			update_term_meta( $id, 'bk_keyword_profile', $cat[3] );
		}
		$ids['cat'][ $slug ] = $id;
	}
	return $ids;
}

/**
 * Persuasive, keyword-rich product description generated from real specs.
 */
function bk_seed_description( array $p, string $brand_name, array $brand ): string {
	list( $name, , $cat_slug, , , $watt, $amp, $class, $capacity ) = $p;
	$profile = bk_category_profile( get_term_by( 'slug', $cat_slug, 'product_cat' ) ?: null );
	$label   = bk_power_classes()[ $class ]['label'] ?? '';

	$html  = '<p>' . esc_html( $p[13] ) . '</p>';
	$html .= '<h3>' . esc_html( "لماذا {$name}؟" ) . '</h3><ul>';
	$html .= '<li>' . esc_html( "سعة {$capacity} مدروسة لاحتياج البيت السوري." ) . '</li>';
	$html .= '<li>' . esc_html( "منتج أصلي من {$brand_name} عبر الوكيل المعتمد — {$brand['text']}." ) . '</li>';
	$html .= '<li>' . esc_html( 'نفس سعر المحل بدون أي زيادة على الطلب أونلاين.' ) . '</li></ul>';
	$html .= '<h3>' . esc_html( 'الاستهلاك الكهربائي والتوافق مع الأمبير والطاقة الشمسية' ) . '</h3>';
	$html .= '<p>' . esc_html( sprintf( 'استطاعة الجهاز %s ويسحب حوالي %s. %s. %s', bk_format_watt( (float) $watt ), bk_format_ampere( (float) $amp ), $label, $profile['power'] ) ) . '</p>';
	$html .= '<h3>' . esc_html( 'الكفالة وخدمة ما بعد البيع' ) . '</h3>';
	$html .= '<p>' . esc_html( "يصلك {$name} مع فاتورة وبطاقة كفالة {$brand_name} الرسمية ({$brand['text']})، ومراكز صيانة معتمدة في دمشق والمحافظات." ) . '</p>';
	$html .= '<h3>' . esc_html( 'التوصيل في دمشق والشحن للمحافظات' ) . '</h3>';
	$html .= '<p>' . esc_html( 'توصيل مجاني لباب البيت داخل دمشق وريفها القريب مع فحص الجهاز عند الاستلام، وشحن بسعر رمزي وتغليف آمن لكل المحافظات السورية.' ) . '</p>';
	return $html;
}

function bk_seed_products_run( array $ids ): int {
	$rate     = (float) bk_pricing_settings()['rate'];
	$step     = (int) bk_pricing_settings()['step'];
	$brands   = bk_seed_brands();
	$created  = 0;

	foreach ( bk_seed_products() as $i => $p ) {
		list( $name, $brand_slug, $cat_slug, $usd, $usd_sale, $watt, $amp, $class, $capacity, $dims, $model, $weight, $tier ) = $p;
		if ( wc_get_product_id_by_sku( $model ) ) {
			continue;
		}
		$brand = $brands[ $brand_slug ];

		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_sku( $model );
		$product->set_short_description( $p[13] );
		$product->set_description( bk_seed_description( $p, $brand['name'], $brand ) );
		$product->set_regular_price( (string) bk_pricing_round( $usd * $rate, $step ) );
		if ( $usd_sale ) {
			$product->set_sale_price( (string) bk_pricing_round( $usd_sale * $rate, $step ) );
		}
		$product->set_manage_stock( true );
		$product->set_stock_quantity( wp_rand( 3, 25 ) );
		$product->set_weight( (string) $weight );
		$product->set_reviews_allowed( true );
		$product->set_category_ids( array_filter( array( $ids['cat'][ $cat_slug ] ?? 0 ) ) );
		$meta = array(
			'_base_usd_price'          => $usd,
			'_base_usd_sale_price'     => $usd_sale ?: '',
			'_bk_model_code'           => $model,
			'_wattage_consumption'     => $watt,
			'_ampere_draw'             => $amp,
			'_solar_inverter_friendly' => $class,
			'_bk_capacity'             => $capacity,
			'_bk_dimensions'           => $dims,
			'_bk_origin'               => 'تجميع معتمد',
			'_bk_bundle_tier'          => $tier,
			'_bk_faq'                  => "هل يمكن تقسيط {$name}؟ | تواصل معنا عبر واتساب لمعرفة عروض الدفع المتاحة حالياً في المحل.",
			'_bk_demo'                 => 1,
		);
		foreach ( $meta as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}
		$product_id = $product->save();

		if ( ! empty( $ids['brand'][ $brand_slug ] ) ) {
			wp_set_object_terms( $product_id, array( (int) $ids['brand'][ $brand_slug ] ), BK_BRAND_TAX );
		}
		update_post_meta( $product_id, 'total_sales', 200 - $i * 7 );
		++$created;
	}
	return $created;
}

function bk_seed_pages(): void {
	// Short Latin slugs: WordPress truncates long percent-encoded Arabic slugs at 200 bytes.
	$pages = array(
		'bundle'   => array( 'حاسبة تجهيز العرسان والبيت الكامل', '<!-- wp:paragraph --><p>جهّز بيتك بالكامل بخطوات بسيطة: اختر البراد والغسالة والشاشة والطباخ والأدوات الصغيرة ضمن باقة اقتصادية أو ذكية أو فاخرة، واحصل على خصم إضافي عند طلب الباقة كاملة مع توصيل مجاني داخل دمشق وريفها.</p><!-- /wp:paragraph -->', 'page-bundle-builder.php' ),
		'warranty' => array( 'التحقق من الكفالة والفاتورة الإلكترونية', '<!-- wp:paragraph --><p>اقرأ شروط كفالة كل وكيل، وسجّل رقم طلبك وموبايلك لحفظ تاريخ بدء كفالتك إلكترونياً.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[bk_warranty]<!-- /wp:shortcode -->', '' ),
		'shipping' => array( 'سياسة التوصيل والشحن', '<!-- wp:heading --><h2>توصيل مجاني داخل دمشق وريفها القريب</h2><!-- /wp:heading --><!-- wp:paragraph --><p>نوصل مجاناً لباب البيت في المزة، الميدان، مشروع دمر، كفرسوسة، جرمانا، صحنايا، قدسيا وضاحية قدسيا وغيرها، مع فحص الجهاز عند الاستلام.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>ريف دمشق الأبعد</h2><!-- /wp:heading --><!-- wp:paragraph --><p>التل، قطنا، الكسوة، دوما، حرستا، الزبداني، يبرود، النبك وغيرها: رسم توصيل رمزي يظهر تلقائياً عند اختيار المنطقة.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>باقي المحافظات السورية</h2><!-- /wp:heading --><!-- wp:paragraph --><p>نشحن إلى حلب، حمص، حماة، اللاذقية، طرطوس، درعا، السويداء وباقي المحافظات عبر شركات شحن موثوقة بتغليف آمن، والتكلفة حسب حجم الجهاز.</p><!-- /wp:paragraph -->', '' ),
	);
	$slugs = array( 'bundle' => 'bundle-builder', 'warranty' => 'warranty', 'shipping' => 'shipping-policy' );
	$ids   = array();
	foreach ( $pages as $key => $page ) {
		$existing = get_page_by_path( $slugs[ $key ] );
		$id       = $existing ? $existing->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slugs[ $key ],
				'post_title'   => $page[0],
				'post_content' => $page[1],
				'meta_input'   => array( '_bk_demo' => 1 ),
			)
		);
		if ( $page[2] && $id ) {
			update_post_meta( $id, '_wp_page_template', $page[2] );
		}
		$ids[ $key ] = (int) $id;
	}
	update_option( 'bk_warranty_page_id', $ids['warranty'] );

	// Footer "customer service" menu.
	$menu = wp_get_nav_menu_object( 'خدمة العملاء' );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( 'خدمة العملاء' );
		if ( ! is_wp_error( $menu_id ) ) {
			$links = array( $ids['warranty'], $ids['bundle'], $ids['shipping'], wc_get_page_id( 'myaccount' ) );
			foreach ( array_filter( $links, static fn( $id ) => $id > 0 ) as $page_id ) {
				wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-object' => 'page', 'menu-item-object-id' => $page_id, 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
			}
			$locations           = (array) get_theme_mod( 'nav_menu_locations', array() );
			$locations['footer'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
}

/**
 * Run the full seed.
 *
 * @return array{products: int}
 */
function bk_seed_run(): array {
	wp_raise_memory_limit( 'admin' );
	bk_seed_configure_woocommerce();
	$ids     = bk_seed_terms();
	$created = bk_seed_products_run( $ids );
	bk_seed_pages();
	delete_option( 'bk_rewrite_version' ); // Flush rewrites on next load.
	bk_cache_bump();
	return array( 'products' => $created );
}

/**
 * Remove everything tagged as demo (products are trashed, not hard-deleted).
 */
function bk_seed_remove(): array {
	$posts = get_posts( array( 'post_type' => array( 'product', 'page' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_bk_demo', 'meta_value' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $posts as $id ) {
		wp_trash_post( $id );
	}
	$terms = get_terms( array( 'taxonomy' => array( 'product_cat', BK_BRAND_TAX ), 'hide_empty' => false, 'meta_key' => '_bk_demo', 'meta_value' => 1, 'fields' => 'all' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		wp_delete_term( $term->term_id, $term->taxonomy );
	}
	bk_cache_bump();
	return array( 'posts' => count( $posts ), 'terms' => is_wp_error( $terms ) ? 0 : count( $terms ) );
}

/* -------------------------------------------------------------------------
 * Admin + CLI
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page( BK_ADMIN_SLUG, __( 'تهيئة المتجر', 'baytkahraba' ), __( 'تهيئة المتجر والبيانات التجريبية', 'baytkahraba' ), 'manage_options', 'bk-seed', 'bk_render_seed_page' );
	},
	30
);

add_action(
	'admin_post_bk_seed',
	static function (): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '', 403 );
		}
		check_admin_referer( 'bk_seed' );
		$result = isset( $_POST['remove'] ) ? bk_seed_remove() : bk_seed_run();
		set_transient( 'bk_seed_result_' . get_current_user_id(), $result, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=bk-seed' ) );
		exit;
	}
);

function bk_render_seed_page(): void {
	$result = get_transient( 'bk_seed_result_' . get_current_user_id() );
	delete_transient( 'bk_seed_result_' . get_current_user_id() );
	?>
	<div class="wrap bk-admin">
		<h1><?php esc_html_e( 'تهيئة المتجر والبيانات التجريبية', 'baytkahraba' ); ?></h1>
		<?php if ( $result ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( isset( $result['products'] ) ? sprintf( __( 'تمت التهيئة: %d منتج جديد + الماركات والتصنيفات والصفحات ومنطقة الشحن.', 'baytkahraba' ), $result['products'] ) : sprintf( __( 'تم نقل %1$d عنصر إلى سلة المهملات وحذف %2$d تصنيف/ماركة تجريبية.', 'baytkahraba' ), $result['posts'], $result['terms'] ) ); ?></p></div>
		<?php endif; ?>
		<div class="bk-admin-card">
			<h2><?php esc_html_e( 'ماذا يفعل زر التهيئة؟', 'baytkahraba' ); ?></h2>
			<ul style="list-style:disc;padding-inline-start:20px">
				<li><?php esc_html_e( 'يضبط ووكومرس لسوريا: الليرة السورية، المحافظات، صفحة الدفع الكلاسيكية المختصرة، منطقة شحن "سوريا"، الدفع عند الاستلام والحوالات.', 'baytkahraba' ); ?></li>
				<li><?php esc_html_e( 'ينشئ الماركات: الحافظ، العربي، هنديكو، رامكو مع الكفالة ومراكز الصيانة والأسئلة الشائعة.', 'baytkahraba' ); ?></li>
				<li><?php esc_html_e( 'ينشئ شجرة تصنيفات من 3 مستويات (أب ← ابن ← فرعي) مع فئة الوزن وملف الكلمات المفتاحية.', 'baytkahraba' ); ?></li>
				<li><?php esc_html_e( 'ينشئ 26 منتجاً بمواصفات كهربائية كاملة وأسعار مرجعية بالدولار وأوصاف تسويقية.', 'baytkahraba' ); ?></li>
				<li><?php esc_html_e( 'ينشئ صفحات: حاسبة تجهيز العرسان، التحقق من الكفالة، سياسة التوصيل + قائمة التذييل.', 'baytkahraba' ); ?></li>
			</ul>
			<p><strong style="color:#B45309"><?php esc_html_e( 'تنبيه: نصوص الكفالة ومراكز الصيانة وأرقام الموديلات والأسعار قيم تجريبية — راجعها مع كل وكيل قبل الإطلاق.', 'baytkahraba' ); ?></strong></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bk-actions">
				<?php wp_nonce_field( 'bk_seed' ); ?>
				<input type="hidden" name="action" value="bk_seed">
				<button class="button button-primary button-hero bk-apply" name="seed" value="1"><?php esc_html_e( 'تهيئة المتجر وتثبيت البيانات التجريبية', 'baytkahraba' ); ?></button>
				<button class="button button-secondary button-hero" name="remove" value="1" onclick="return confirm('<?php echo esc_js( __( 'نقل كل المنتجات والصفحات التجريبية إلى سلة المهملات وحذف التصنيفات التجريبية؟', 'baytkahraba' ) ); ?>')"><?php esc_html_e( 'إزالة البيانات التجريبية', 'baytkahraba' ); ?></button>
			</form>
		</div>
	</div>
	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bk seed',
		static function ( array $args, array $assoc ): void {
			$result = isset( $assoc['remove'] ) ? bk_seed_remove() : bk_seed_run();
			WP_CLI::success( wp_json_encode( $result ) );
		}
	);
}
