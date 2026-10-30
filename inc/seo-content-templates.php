<?php
/**
 * Dynamic Arabic SEO copywriting engine.
 *
 * Builds titles, meta descriptions, H1s, above-the-fold intros, below-the-grid
 * buying guides and FAQs for categories, brands, brand × category silos and
 * products — from REAL catalog data (counts, price ranges, brands, ampere
 * draw, warranty) woven with the Syrian keyword matrix (local dialect +
 * Modern Standard Arabic + power/solar modifiers).
 *
 * Every generated block can be overridden per term from the dashboard.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Keyword matrix
 * ---------------------------------------------------------------------- */

/**
 * Appliance keyword profiles (dialect + MSA synonyms, buying tips, power tips).
 */
function bk_keyword_profiles(): array {
	$profiles = array(
		'fridge'     => array(
			'label'    => 'برادات وثلاجات',
			'match'    => array( 'براد', 'ثلاج', 'refrigerator', 'fridge' ),
			'singular' => 'البراد',
			'plural'   => 'البرادات',
			'synonyms' => array( 'ثلاجة', 'ثلاجات', 'براد بابين', 'براد إنفرتر موفر للطاقة', 'براد نوفروست' ),
			'power'    => 'البرادات بضاغط إنفرتر تسحب عادة بين 0.6 و1.2 أمبير بعد استقرار التبريد، لذلك تعمل براحة على اشتراك 2 أمبير أو على منظومة طاقة شمسية منزلية متوسطة. انتبه لتيار الإقلاع في البرادات التقليدية (غير الإنفرتر) لأنه قد يصل إلى 3 أضعاف السحب العادي.',
			'tips'     => array(
				'اختيار السعة المناسبة' => 'للعائلة الصغيرة (2-3 أشخاص) يكفي براد 12 إلى 14 قدم، وللعائلة المتوسطة 16 إلى 18 قدم، أما العائلات الكبيرة ومن يخزّن المونة فالأفضل 20 قدم فما فوق أو براد بابين مع فريزر مستقل.',
				'نوفروست أم ديفروست؟'   => 'براد النوفروست لا يتكوّن فيه الثلج ويوزع البرودة بالتساوي، بينما الديفروست أرخص سعراً وأقل استهلاكاً قليلاً لكنه يحتاج إذابة يدوية للثلج كل فترة.',
			),
		),
		'freezer'    => array(
			'label'    => 'فريزرات',
			'match'    => array( 'فريزر', 'مجمد', 'freezer' ),
			'singular' => 'الفريزر',
			'plural'   => 'الفريزرات',
			'synonyms' => array( 'فريزر أفقي (صندوق)', 'فريزر عمودي (جوارير)', 'ديب فريزر', 'مجمدة' ),
			'power'    => 'الفريزر الأفقي (الصندوق) يحافظ على البرودة لساعات طويلة عند انقطاع الكهرباء، وهو الخيار الأذكى لمن يعتمد على الأمبير أو الطاقة الشمسية لساعات محددة.',
			'tips'     => array(
				'أفقي أم عمودي؟' => 'الأفقي أوفر في الكهرباء ويحفظ البرودة أطول، والعمودي بالجوارير أسهل في الترتيب والوصول للمونة.',
			),
		),
		'washer'     => array(
			'label'    => 'غسالات',
			'match'    => array( 'غسال', 'washing', 'washer' ),
			'singular' => 'الغسالة',
			'plural'   => 'الغسالات',
			'synonyms' => array( 'غسالة أوتوماتيك', 'غسالة حوضين (نصف أوتوماتيك)', 'غسالة إنفرتر دفع مباشر', 'غسالة تنشيف كامل' ),
			'power'    => 'الغسالة الأوتوماتيك تسحب أعلى قيمة لها عند تسخين الماء (قد تتجاوز 8 أمبير)، لذلك اختر برامج الغسيل على الماء البارد أو 30 درجة عند التشغيل على الأمبير أو الإنفرتر. غسالات المحرك الإنفرتر (الدفع المباشر) أهدأ وأقل سحباً أثناء العصر.',
			'tips'     => array(
				'أوتوماتيك أم حوضين؟' => 'الأوتوماتيك توفر الماء والجهد وتناسب الكهرباء المستقرة، أما غسالة الحوضين فهي الأكثر تحملاً لانقطاع الكهرباء وضعف ضغط الماء وأقل سعراً.',
				'سعة الغسيل'           => 'سعة 7 كغ مناسبة لعائلة من 4 أشخاص، و9-10 كغ للعائلات الكبيرة وغسيل الحرامات.',
			),
		),
		'dishwasher' => array(
			'label'    => 'جلايات صحون',
			'match'    => array( 'جلاي', 'غسالة صحون', 'dishwasher' ),
			'singular' => 'الجلاية',
			'plural'   => 'الجلايات',
			'synonyms' => array( 'غسالة صحون', 'جلاية 12 مكان', 'جلاية مدمجة' ),
			'power'    => 'الجلاية تستهلك معظم طاقتها لتسخين الماء؛ برنامج "إيكو" يخفض السحب بشكل واضح ويجعلها مناسبة لساعات الكهرباء النظامية.',
			'tips'     => array(),
		),
		'tv'         => array(
			'label'    => 'شاشات وتلفزيونات',
			'match'    => array( 'شاش', 'تلفزيون', 'تلفاز', 'tv', 'screen' ),
			'singular' => 'الشاشة',
			'plural'   => 'الشاشات',
			'synonyms' => array( 'تلفزيون سمارت', 'شاشة سمارت', 'شاشة أندرويد 4K', 'شاشة مع رسيفر داخلي' ),
			'power'    => 'شاشات LED الحديثة اقتصادية جداً: شاشة 43 إنش تسحب حوالي 0.3 إلى 0.5 أمبير فقط، لذلك تعمل بسهولة على البطارية والإنفرتر المنزلي والطاقة الشمسية.',
			'tips'     => array(
				'اختيار القياس المناسب' => 'قِس المسافة بين الكنبة والشاشة: 2 متر تناسبها شاشة 43 إنش، و2.5 متر شاشة 50-55 إنش، وأكثر من 3 أمتار شاشة 65 إنش.',
				'سمارت مع رسيفر داخلي' => 'الشاشة مع رسيفر داخلي توفر عليك جهاز الرسيفر وسحبه الكهربائي، والسمارت أندرويد تفتح لك يوتيوب ونتفليكس مباشرة.',
			),
		),
		'ac'         => array(
			'label'    => 'مكيفات',
			'match'    => array( 'مكيف', 'تكييف', 'سبليت', 'كونديشن', 'air condition' ),
			'singular' => 'المكيف',
			'plural'   => 'المكيفات',
			'synonyms' => array( 'مكيف إنفرتر', 'مكيف يعمل على الأمبير والطاقة الشمسية', 'مكيف سبليت', 'كونديشن' ),
			'power'    => 'المكيف الإنفرتر يبدأ بسحب منخفض ويحافظ على الحرارة دون فصل ووصل مستمر، فمكيف 12000 BTU إنفرتر قد يعمل على 3 إلى 5 أمبير بعد الاستقرار، بينما المكيف التقليدي يحتاج تيار إقلاع أعلى بكثير.',
			'tips'     => array(
				'حساب قدرة المكيف (BTU)' => 'غرفة حتى 16 متر مربع يكفيها 9000 BTU، وحتى 25 متر 12000 BTU، والصالونات الكبيرة 18000 BTU فما فوق.',
			),
		),
		'fan'        => array(
			'label'    => 'مراوح',
			'match'    => array( 'مروح', 'مراوح', 'fan' ),
			'singular' => 'المروحة',
			'plural'   => 'المراوح',
			'synonyms' => array( 'مروحة شحن بطارية ليثيوم', 'مروحة عامودية', 'مروحة سقفية', 'مروحة جدارية', 'مروحة تعمل على 12 فولت' ),
			'power'    => 'مراوح الشحن ببطارية الليثيوم ومراوح 12 فولت هي الحل الأمثل لساعات التقنين: تسحب أقل من 0.2 أمبير وتعمل لساعات طويلة على البطارية.',
			'tips'     => array(),
		),
		'heater'     => array(
			'label'    => 'تدفئة وسخانات',
			'match'    => array( 'مدفا', 'دفاي', 'سخان', 'تدفئ', 'صوب', 'heater' ),
			'singular' => 'المدفأة',
			'plural'   => 'أجهزة التدفئة',
			'synonyms' => array( 'دفاية كهربائية', 'صوبيا', 'سخان ماء كهربائي', 'مدفأة زيت' ),
			'power'    => 'أجهزة التدفئة الكهربائية من أعلى الأجهزة سحباً (من 4 إلى 9 أمبير)، لذلك لا ننصح بتشغيلها على الإنفرتر المنزلي؛ اختر الموديلات ذات منظم الحرارة (ترموستات) لتوفير الاستهلاك.',
			'tips'     => array(),
		),
		'cooker'     => array(
			'label'    => 'طباخات وأفران',
			'match'    => array( 'طباخ', 'فرن', 'غاز', 'cooker', 'oven' ),
			'singular' => 'الطباخ',
			'plural'   => 'الطباخات والأفران',
			'synonyms' => array( 'طباخ غاز', 'فرن غاز', 'طباخ 5 عيون', 'فرن كهربائي' ),
			'power'    => 'طباخات الغاز لا تحتاج كهرباء إلا للإشعال الذاتي والإنارة، لذلك هي الخيار الأوثق في ظل التقنين، بينما الأفران الكهربائية تحتاج كهرباء نظامية.',
			'tips'     => array(
				'عدد العيون والقياس' => 'طباخ 60×60 بأربع عيون يناسب المطابخ الصغيرة، و90×60 بخمس عيون للعائلات الكبيرة ومحبي الطبخ.',
			),
		),
		'microwave'  => array(
			'label'    => 'مايكروويف',
			'match'    => array( 'مايكرو', 'ميكرو', 'microwave' ),
			'singular' => 'المايكروويف',
			'plural'   => 'أجهزة المايكروويف',
			'synonyms' => array( 'ميكروويف', 'مايكروويف مع شواية', 'فرن مايكروويف' ),
			'power'    => 'المايكروويف يسحب 4 إلى 6 أمبير لكن لدقائق قليلة فقط، فيمكن تشغيله على الأمبير بشكل متقطع.',
			'tips'     => array(),
		),
		'airfryer'   => array(
			'label'    => 'قلايات هوائية',
			'match'    => array( 'قلاي', 'air fryer', 'اير فراير', 'إير فراير' ),
			'singular' => 'القلاية الهوائية',
			'plural'   => 'القلايات الهوائية',
			'synonyms' => array( 'قلاية بدون زيت', 'Air Fryer', 'اير فراير' ),
			'power'    => 'القلاية الهوائية تسحب بين 6 و8 أمبير أثناء التسخين، لذلك تحتاج اشتراك أمبير مناسب أو كهرباء نظامية.',
			'tips'     => array(),
		),
		'kitchen'    => array(
			'label'    => 'أدوات المطبخ الصغيرة',
			'match'    => array( 'خلاط', 'فرام', 'محضر', 'عجان', 'كبة', 'blender' ),
			'singular' => 'الخلاط',
			'plural'   => 'أدوات المطبخ الكهربائية',
			'synonyms' => array( 'خلاط كهربائي وفرامة', 'محضرة طعام متكاملة', 'ماكينة كبة', 'عجانة' ),
			'power'    => 'معظم الخلاطات ومحضرات الطعام تسحب بين 1.5 و4 أمبير لفترات قصيرة، فتعمل جيداً على الأمبير والإنفرتر المنزلي.',
			'tips'     => array(),
		),
		'vacuum'     => array(
			'label'    => 'مكانس كهربائية',
			'match'    => array( 'مكنس', 'مكانس', 'vacuum' ),
			'singular' => 'المكنسة الكهربائية',
			'plural'   => 'المكانس الكهربائية',
			'synonyms' => array( 'مكنسة برميل', 'مكنسة كيس', 'مكنسة بدون كيس', 'مكنسة شحن' ),
			'power'    => 'المكنسة 2000 واط تسحب حوالي 9 أمبير؛ إن كنت على الأمبير اختر موديلاً بمنظم قوة أو مكنسة شحن لاسلكية.',
			'tips'     => array(),
		),
		'iron'       => array(
			'label'    => 'مكاوي',
			'match'    => array( 'مكوا', 'مكاوي', 'مكواة', 'iron' ),
			'singular' => 'المكواة',
			'plural'   => 'المكاوي',
			'synonyms' => array( 'مكواة بخار', 'مكواة جافة', 'مكواة بخار عمودية' ),
			'power'    => 'المكواة تسحب بين 6 و10 أمبير أثناء التسخين لذلك يفضّل استخدامها على كهرباء الدولة.',
			'tips'     => array(),
		),
		'generic'    => array(
			'label'    => 'أجهزة كهربائية ومنزلية',
			'match'    => array(),
			'singular' => 'الجهاز',
			'plural'   => 'الأجهزة الكهربائية والمنزلية',
			'synonyms' => array( 'أدوات كهربائية', 'أجهزة منزلية', 'كهربائيات' ),
			'power'    => 'راجع خانة "السحب بالأمبير" في جدول مواصفات كل جهاز، واستخدم فلتر "يعمل على الانفرتر / الطاقة الشمسية" لعرض الأجهزة المناسبة لاشتراكك.',
			'tips'     => array(),
		),
	);
	return apply_filters( 'bk_keyword_profiles', $profiles );
}

/**
 * Local commercial-intent modifiers.
 */
function bk_local_modifiers(): array {
	return array(
		'price_city'  => 'أسعار %s في دمشق',
		'price_today' => 'أسعار %s في سوريا اليوم',
		'shop'        => 'بسعر المحل والجملة',
		'warranty'    => 'مع كفالة الوكيل الحقيقية',
		'delivery'    => 'توصيل مجاني في دمشق وريفها',
		'power'       => array( 'يعمل على الأمبير', 'يعمل على الانفرتر المنزلي', 'متوافق مع الطاقة الشمسية', 'سحب كهرباء منخفض' ),
	);
}

/**
 * Resolve the keyword profile of a category (explicit → name match → ancestors → generic).
 */
function bk_category_profile( ?WP_Term $cat ): array {
	$profiles = bk_keyword_profiles();
	if ( ! $cat ) {
		return $profiles['generic'] + array( 'key' => 'generic' );
	}
	$chain = array_reverse( bk_category_chain( $cat->term_id ) ); // Self first, then ancestors.
	foreach ( $chain as $term ) {
		$explicit = (string) get_term_meta( $term->term_id, 'bk_keyword_profile', true );
		if ( $explicit && isset( $profiles[ $explicit ] ) ) {
			return $profiles[ $explicit ] + array( 'key' => $explicit );
		}
		$name = bk_normalize_arabic( $term->name . ' ' . urldecode( $term->slug ) );
		foreach ( $profiles as $key => $profile ) {
			foreach ( $profile['match'] as $needle ) {
				if ( str_contains( $name, bk_normalize_arabic( $needle ) ) ) {
					return $profile + array( 'key' => $key );
				}
			}
		}
	}
	return $profiles['generic'] + array( 'key' => 'generic' );
}

/**
 * Line icon for a category: its own profile, else the first specific descendant's.
 */
function bk_category_icon( int $term_id ): string {
	$map  = array(
		'fridge'     => 'fridge',
		'freezer'    => 'fridge',
		'washer'     => 'washer',
		'dishwasher' => 'washer',
		'tv'         => 'tv',
		'ac'         => 'ac',
		'fan'        => 'fan',
		'heater'     => 'sun',
		'cooker'     => 'pot',
		'microwave'  => 'pot',
		'airfryer'   => 'pot',
		'kitchen'    => 'pot',
		'vacuum'     => 'vacuum',
		'iron'       => 'bolt',
	);
	$term = get_term( $term_id, 'product_cat' );
	$key  = $term instanceof WP_Term ? bk_category_profile( $term )['key'] : 'generic';
	if ( 'generic' === $key ) {
		foreach ( bk_get_child_categories( $term_id ) as $child ) {
			$icon = bk_category_icon( $child['id'] );
			if ( 'bolt' !== $icon ) {
				return $icon;
			}
		}
	}
	return $map[ $key ] ?? 'bolt';
}

/* -------------------------------------------------------------------------
 * Search synonym expansion (براد ↔ ثلاجة، شاشة ↔ تلفزيون …)
 * ---------------------------------------------------------------------- */

function bk_search_synonym_groups(): array {
	return apply_filters(
		'bk_search_synonym_groups',
		array(
			array( 'براد', 'برادات', 'ثلاجة', 'ثلاجات', 'تلاجة', 'refrigerator', 'fridge' ),
			array( 'فريزر', 'فريزرات', 'مجمدة', 'ديب فريزر', 'freezer' ),
			array( 'غسالة', 'غسالات', 'washing' ),
			array( 'جلاية', 'جلايات', 'غسالة صحون', 'dishwasher' ),
			array( 'شاشة', 'شاشات', 'تلفزيون', 'تلفزيونات', 'تلفاز', 'تيفي', 'tv', 'smart' ),
			array( 'مكيف', 'مكيفات', 'كونديشن', 'سبليت', 'تكييف', 'ac' ),
			array( 'مروحة', 'مراوح', 'fan' ),
			array( 'مدفأة', 'مدفاة', 'دفاية', 'صوبيا', 'heater' ),
			array( 'سخان', 'سخانات', 'شوفاج' ),
			array( 'طباخ', 'طباخات', 'فرن', 'افران', 'غاز', 'cooker', 'oven' ),
			array( 'مايكروويف', 'ميكروويف', 'مايكرويف', 'microwave' ),
			array( 'قلاية', 'قلاية هوائية', 'اير فراير', 'airfryer', 'air fryer' ),
			array( 'خلاط', 'خلاطات', 'فرامة', 'محضرة', 'blender' ),
			array( 'مكنسة', 'مكانس', 'vacuum' ),
			array( 'مكواة', 'مكوى', 'مكاوي', 'iron' ),
			array( 'انفرتر', 'إنفرتر', 'inverter', 'طاقة شمسية', 'سولار', 'solar' ),
		)
	);
}

/**
 * Expand a free-text query into synonyms (normalized comparison).
 *
 * @return string[] Unique search terms including the originals.
 */
function bk_search_expand_terms( string $query ): array {
	$query = trim( $query );
	$terms = array( $query );
	$words = preg_split( '/\s+/u', $query ) ?: array();
	foreach ( bk_search_synonym_groups() as $group ) {
		$normalized_group = array_map( 'bk_normalize_arabic', $group );
		foreach ( $words as $word ) {
			$nw = bk_normalize_arabic( $word );
			if ( mb_strlen( $nw ) < 2 ) {
				continue;
			}
			// Prefix match tolerates Arabic suffixes (ال، ات، ة).
			foreach ( $normalized_group as $candidate ) {
				$stem = preg_replace( '/^ال/u', '', $nw );
				if ( $candidate === $nw || $candidate === $stem || ( mb_strlen( $stem ) >= 3 && str_starts_with( $candidate, $stem ) ) ) {
					foreach ( $group as $syn ) {
						$terms[] = trim( str_replace( $word, $syn, $query ) );
						$terms[] = $syn;
					}
					break 2;
				}
			}
		}
	}
	return array_values( array_unique( array_filter( $terms ) ) );
}

/* -------------------------------------------------------------------------
 * Context
 * ---------------------------------------------------------------------- */

/**
 * SEO context of the current request.
 *
 * @return array{type: string, cat: ?WP_Term, brand: ?WP_Term, product: ?WC_Product, count: int, min: float, max: float, profile: array}
 */
function bk_seo_context(): array {
	static $ctx = null;
	if ( null !== $ctx ) {
		return $ctx;
	}
	$ctx = array( 'type' => 'other', 'cat' => null, 'brand' => null, 'product' => null, 'count' => 0, 'min' => 0.0, 'max' => 0.0, 'profile' => array() );

	if ( function_exists( 'bk_is_silo' ) && bk_is_silo() ) {
		$silo         = bk_get_silo_context();
		$ctx['type']  = 'silo';
		$ctx['cat']   = $silo['cat'];
		$ctx['brand'] = $silo['brand'];
		$ctx['count'] = $silo['count'];
	} elseif ( is_product() ) {
		$ctx['type']    = 'product';
		$ctx['product'] = wc_get_product( get_queried_object_id() );
		$ctx['cat']     = $ctx['product'] ? bk_product_primary_category( $ctx['product']->get_id() ) : null;
		$ctx['brand']   = $ctx['product'] ? bk_get_product_brand( $ctx['product']->get_id() ) : null;
	} elseif ( is_tax( 'product_cat' ) ) {
		$ctx['type']  = 'category';
		$ctx['cat']   = get_queried_object();
		$ctx['count'] = bk_category_count( $ctx['cat']->term_id );
	} elseif ( is_tax( BK_BRAND_TAX ) ) {
		$ctx['type']  = 'brand';
		$ctx['brand'] = get_queried_object();
		$ctx['count'] = (int) ( bk_get_catalog_matrix()['brand_totals'][ $ctx['brand']->term_id ] ?? 0 );
	} elseif ( is_front_page() ) {
		$ctx['type'] = 'home';
	} elseif ( is_search() ) {
		$ctx['type'] = 'search';
	} elseif ( is_shop() ) {
		$ctx['type'] = 'shop';
	}

	if ( in_array( $ctx['type'], array( 'silo', 'category', 'brand' ), true ) ) {
		$range      = bk_price_range( $ctx['cat']->term_id ?? null, $ctx['brand']->term_id ?? null );
		$ctx['min'] = $range['min'];
		$ctx['max'] = $range['max'];
	}
	$ctx['profile'] = bk_category_profile( $ctx['cat'] );
	return $ctx;
}

/* -------------------------------------------------------------------------
 * Titles, descriptions, H1
 * ---------------------------------------------------------------------- */

function bk_seo_title( array $ctx ): string {
	$p     = $ctx['profile'];
	$store = (string) bk_option( 'store_name' );
	switch ( $ctx['type'] ) {
		case 'product':
			$brand = $ctx['brand'] ? $ctx['brand']->name : '';
			return sprintf( '%s - ماركة %s الأصلية | السعر والمواصفات في دمشق مع كفالة وتوصيل مجاني', $ctx['product']->get_name(), $brand ?: $store );
		case 'silo':
			return sprintf( 'أسعار ومواصفات %s %s الأصلية في دمشق وسوريا مع الكفالة', $ctx['cat']->name, $ctx['brand']->name );
		case 'category':
			return sprintf( 'أسعار %s في دمشق وسوريا %s | %s بسعر المحل مع الكفالة', $ctx['cat']->name, bk_year(), $p['synonyms'][0] ?? $p['plural'] );
		case 'brand':
			return sprintf( 'أسعار %s في سوريا اليوم | الوكيل المعتمد - كفالة حقيقية وتوصيل مجاني', $ctx['brand']->name );
		case 'home':
			return sprintf( '%s | الأدوات الكهربائية والمنزلية بسعر المحل في دمشق وسوريا', $store );
		case 'shop':
			return sprintf( 'كل الأجهزة الكهربائية والمنزلية - أسعار اليوم في دمشق | %s', $store );
		case 'search':
			return sprintf( 'نتائج البحث عن: %s | %s', get_search_query( false ), $store );
	}
	return '';
}

function bk_seo_h1( array $ctx ): string {
	switch ( $ctx['type'] ) {
		case 'silo':
			return sprintf( 'أسعار ومواصفات %s %s الأصلية في دمشق وسوريا مع الكفالة', $ctx['cat']->name, $ctx['brand']->name );
		case 'category':
			return sprintf( '%s — أسعار اليوم في دمشق وسوريا', $ctx['cat']->name );
		case 'brand':
			return sprintf( 'منتجات %s — الوكيل المعتمد في سوريا', $ctx['brand']->name );
	}
	return '';
}

function bk_seo_description( array $ctx ): string {
	$p     = $ctx['profile'];
	$from  = $ctx['min'] ? ' بأسعار تبدأ من ' . bk_format_syp( $ctx['min'], false ) : '';
	switch ( $ctx['type'] ) {
		case 'product':
			$product = $ctx['product'];
			$power   = bk_get_product_power( $product->get_id() );
			$price   = $product->get_price() ? ' بسعر ' . bk_format_syp( (float) $product->get_price(), false ) : '';
			$amp     = $power['ampere'] ? '، يسحب ' . bk_format_ampere( $power['ampere'] ) : '';
			return wp_trim_words( sprintf( '%s%s%s. %s أصلي مع %s، توصيل مجاني في دمشق وريفها وشحن رمزي للمحافظات.', $product->get_name(), $price, $amp, $ctx['brand'] ? 'منتج ' . $ctx['brand']->name : 'منتج', bk_product_warranty( $product ) ), 32, '' );
		case 'silo':
			return sprintf( 'تسوّق %d موديل من %s %s الأصلية%s. كفالة الوكيل الحقيقية، نفس سعر المحل، وتوصيل مجاني في دمشق وريفها.', $ctx['count'], $ctx['cat']->name, $ctx['brand']->name, $from );
		case 'category':
			return sprintf( '%s %s (%s)%s من الحافظ، العربي، هنديكو، رامكو وغيرها. مع الكفالة وتوصيل مجاني في دمشق.', sprintf( bk_local_modifiers()['price_city'], $ctx['cat']->name ), bk_year(), implode( '، ', array_slice( $p['synonyms'], 0, 2 ) ), $from );
		case 'brand':
			$meta = bk_brand_meta( $ctx['brand'] );
			return sprintf( 'أسعار %1$s في سوريا اليوم%2$s. %3$s، %4$s. توصيل مجاني في دمشق وريفها.', $ctx['brand']->name, $from, $meta['agent'], $meta['warranty_text'] );
		case 'home':
			return 'برادات، غسالات، شاشات، مكيفات إنفرتر وأدوات مطبخ أصلية بسعر المحل في دمشق، مع كفالة الوكيل الحقيقية، توصيل مجاني لدمشق وريفها وشحن رمزي لكل المحافظات السورية.';
	}
	return (string) get_bloginfo( 'description' );
}

/* -------------------------------------------------------------------------
 * Above-the-fold intro
 * ---------------------------------------------------------------------- */

function bk_seo_intro_html( array $ctx ): string {
	$custom = '';
	if ( 'category' === $ctx['type'] ) {
		$custom = (string) get_term_meta( $ctx['cat']->term_id, 'bk_seo_intro', true ) ?: wp_strip_all_tags( term_description( $ctx['cat'] ) );
	} elseif ( 'brand' === $ctx['type'] ) {
		$custom = wp_strip_all_tags( term_description( $ctx['brand'] ) );
	}
	if ( $custom ) {
		return '<p>' . esc_html( $custom ) . '</p>';
	}

	$p      = $ctx['profile'];
	$brands = array();
	if ( $ctx['cat'] && ! $ctx['brand'] ) {
		$brands = wp_list_pluck( wp_list_pluck( bk_get_category_brands( $ctx['cat']->term_id, 4 ), 'term' ), 'name' );
	}
	$from = $ctx['min'] ? sprintf( ' بأسعار تبدأ من <strong class="bk-num">%s</strong>', esc_html( bk_format_syp( $ctx['min'], false ) ) ) : '';

	switch ( $ctx['type'] ) {
		case 'silo':
			$lead = sprintf( 'هنا تجد <strong>%d موديل</strong> من %s %s الأصلية%s، مع جدول مواصفات واضح يوضح الاستطاعة بالواط والسحب بالأمبير لكل جهاز.', $ctx['count'], esc_html( $ctx['cat']->name ), esc_html( $ctx['brand']->name ), $from );
			break;
		case 'brand':
			$lead = sprintf( 'كل منتجات <strong>%s</strong> الأصلية في مكان واحد (%d منتج)%s، مباشرة من %s.', esc_html( $ctx['brand']->name ), $ctx['count'], $from, esc_html( bk_brand_meta( $ctx['brand'] )['agent'] ) );
			break;
		default:
			$lead = sprintf(
				'تصفّح <strong>%d موديل</strong> من %s (%s)%s%s.',
				$ctx['count'],
				esc_html( $ctx['cat'] ? $ctx['cat']->name : $p['plural'] ),
				esc_html( implode( '، ', array_slice( $p['synonyms'], 0, 3 ) ) ),
				$brands ? ' من ماركات ' . esc_html( implode( '، ', $brands ) ) : '',
				$from
			);
	}

	return '<p>' . $lead . ' ' . esc_html__( 'كل الأجهزة أصلية مع كفالة حقيقية من الوكيل، وبنفس سعر المحل بدون أي زيادات — توصيل مجاني داخل دمشق وريفها وشحن رمزي لكافة المحافظات السورية.', 'baytkahraba' ) . '</p>';
}

/* -------------------------------------------------------------------------
 * Below-the-grid buying guide
 * ---------------------------------------------------------------------- */

function bk_seo_guide_html( array $ctx ): string {
	if ( 'category' === $ctx['type'] && ( $custom = (string) get_term_meta( $ctx['cat']->term_id, 'bk_seo_guide', true ) ) ) {
		return wp_kses_post( $custom );
	}
	if ( 'brand' === $ctx['type'] && ( $custom = bk_brand_meta( $ctx['brand'] )['guide'] ) ) {
		return wp_kses_post( $custom );
	}

	$p        = $ctx['profile'];
	$subject  = trim( ( $ctx['cat'] ? $ctx['cat']->name : $p['plural'] ) . ( $ctx['brand'] ? ' ' . $ctx['brand']->name : '' ) );
	$singular = $p['singular'];
	$html     = '';

	// H2 — buying guide.
	$html .= '<h2>' . esc_html( sprintf( 'دليل شراء واختيار أفضل %s في دمشق وسوريا', $subject ) ) . '</h2>';
	$html .= '<p>' . esc_html(
		sprintf(
			'قبل شراء %1$s اسأل نفسك ثلاثة أسئلة: ما السعة التي تحتاجها عائلتك؟ كم أمبير يتوفر لديك في ساعات التقنين؟ وما مدة الكفالة وأين أقرب مركز صيانة؟ يجمع هذا الدليل خبرة المحل اليومية مع الزبائن في دمشق ليساعدك على اختيار %2$s المناسب من بين %3$s (%4$s).',
			$singular,
			$singular,
			$p['plural'],
			implode( '، ', $p['synonyms'] )
		)
	) . '</p>';

	foreach ( $p['tips'] as $heading => $text ) {
		$html .= '<h3>' . esc_html( $heading ) . '</h3><p>' . esc_html( $text ) . '</p>';
	}

	// H3 — ampere / inverter compatibility.
	$solar_count = $ctx['cat'] ? bk_count_power_products( $ctx['cat']->term_id, $ctx['brand']->term_id ?? 0 ) : 0;
	$html       .= '<h3>' . esc_html( sprintf( 'كيف تختار %s المتوافق مع نظام الأمبيرات والإنفرتر المنزلي؟', $singular ) ) . '</h3>';
	$html       .= '<p>' . esc_html( $p['power'] ) . '</p>';
	$html       .= '<ul><li>' . esc_html__( 'راجع خانة "السحب بالأمبير" في جدول المواصفات: اشتراك 1 أمبير يكفي الشاشات والمراوح والإنارة، و2 أمبير يكفي معظم البرادات الإنفرتر.', 'baytkahraba' ) . '</li>';
	$html       .= '<li>' . esc_html__( 'الأجهزة ذات شارة "يعمل على الانفرتر والطاقة الشمسية" مجرّبة لدينا على المنظومات المنزلية الشائعة.', 'baytkahraba' ) . '</li>';
	$html       .= '<li>' . esc_html__( 'تجنّب تشغيل جهازين بسحب عالٍ (مكواة + غسالة مثلاً) معاً على نفس الاشتراك.', 'baytkahraba' ) . '</li></ul>';
	if ( $solar_count ) {
		$html .= '<p>' . sprintf( esc_html__( 'يوجد حالياً %s في هذا القسم تعمل على الأمبير المنخفض أو الطاقة الشمسية — فعّل فلتر "يعمل على الانفرتر / الطاقة الشمسية" أعلى الصفحة لعرضها فقط.', 'baytkahraba' ), '<strong>' . (int) $solar_count . ' ' . esc_html__( 'أجهزة', 'baytkahraba' ) . '</strong>' ) . '</p>';
	}

	// H3 — price & spec comparison (real data table per brand).
	$html .= '<h3>' . esc_html( sprintf( 'مقارنة أسعار ومواصفات %s مع الكفالة الرسمية', $subject ) ) . '</h3>';
	$rows  = array();
	if ( $ctx['cat'] && ! $ctx['brand'] ) {
		foreach ( bk_get_category_brands( $ctx['cat']->term_id, 8 ) as $row ) {
			$range  = bk_price_range( $ctx['cat']->term_id, $row['term']->term_id );
			$meta   = bk_brand_meta( $row['term'] );
			$rows[] = '<tr><th><a href="' . esc_url( bk_silo_url( $row['term'], $ctx['cat'] ) ) . '">' . esc_html( $ctx['cat']->name . ' ' . $row['term']->name ) . '</a></th><td class="bk-num">' . (int) $row['count'] . '</td><td class="bk-num">' . esc_html( $range['min'] ? bk_format_syp( $range['min'], false ) : '—' ) . '</td><td class="bk-num">' . esc_html( $range['max'] ? bk_format_syp( $range['max'], false ) : '—' ) . '</td><td>' . esc_html( $meta['warranty_text'] ) . '</td></tr>';
		}
	} elseif ( $ctx['brand'] ) {
		foreach ( array_slice( bk_get_brand_hub_tabs( $ctx['brand']->term_id ), 0, 8 ) as $row ) {
			$range  = bk_price_range( $row['term']->term_id, $ctx['brand']->term_id );
			$rows[] = '<tr><th><a href="' . esc_url( bk_silo_url( $ctx['brand'], $row['term'] ) ) . '">' . esc_html( $row['term']->name . ' ' . $ctx['brand']->name ) . '</a></th><td class="bk-num">' . (int) $row['count'] . '</td><td class="bk-num">' . esc_html( $range['min'] ? bk_format_syp( $range['min'], false ) : '—' ) . '</td><td class="bk-num">' . esc_html( $range['max'] ? bk_format_syp( $range['max'], false ) : '—' ) . '</td><td>' . esc_html( bk_brand_meta( $ctx['brand'] )['warranty_text'] ) . '</td></tr>';
		}
	}
	if ( $rows ) {
		$html .= '<div class="overflow-x-auto"><table><thead><tr><th>' . esc_html__( 'الفئة', 'baytkahraba' ) . '</th><th>' . esc_html__( 'عدد الموديلات', 'baytkahraba' ) . '</th><th>' . esc_html__( 'يبدأ من', 'baytkahraba' ) . '</th><th>' . esc_html__( 'حتى', 'baytkahraba' ) . '</th><th>' . esc_html__( 'الكفالة', 'baytkahraba' ) . '</th></tr></thead><tbody>' . implode( '', $rows ) . '</tbody></table></div>';
	}
	$html .= '<p>' . esc_html( sprintf( 'الأسعار المعروضة هي %1$s ومحدّثة يومياً حسب سعر الصرف، وكل جهاز يصلك مع فاتورة وبطاقة كفالة الوكيل. لمقارنة موديلين أو أكثر جنباً إلى جنب استخدم زر "قارن" على بطاقة المنتج.', bk_local_modifiers()['shop'] ) ) . '</p>';

	// H3 — delivery.
	$c     = bk_shipping_config();
	$html .= '<h3>' . esc_html__( 'خدمة التوصيل المجاني في دمشق والشحن لكافة المحافظات السورية', 'baytkahraba' ) . '</h3>';
	$html .= '<p>' . esc_html( sprintf( 'التوصيل مجاني لباب البيت داخل دمشق وريفها القريب (المزة، الميدان، مشروع دمر، كفرسوسة، جرمانا، صحنايا، قدسيا وغيرها) %1$s، مع إمكانية فحص الجهاز عند الاستلام. لمناطق ريف دمشق الأبعد مثل التل وقطنا والكسوة ودوما رسم توصيل رمزي، وللمحافظات (حلب، حمص، حماة، اللاذقية، طرطوس، درعا، السويداء…) نشحن عبر %2$s بتغليف آمن خلال %3$s.', $c['eta'][1], $c['couriers'], $c['eta'][3] ) ) . '</p>';

	// Internal silo mesh.
	if ( $ctx['cat'] && ! $ctx['brand'] ) {
		$links = array();
		foreach ( bk_get_category_brands( $ctx['cat']->term_id ) as $row ) {
			$links[] = '<a href="' . esc_url( bk_silo_url( $row['term'], $ctx['cat'] ) ) . '">' . esc_html( $ctx['cat']->name . ' ' . $row['term']->name ) . '</a>';
		}
		if ( $links ) {
			$html .= '<p><strong>' . esc_html__( 'تصفح حسب الماركة: ', 'baytkahraba' ) . '</strong>' . implode( ' · ', $links ) . '</p>';
		}
	}

	return $html;
}

/**
 * Count of solar_ready / low_amp products in a category (+ brand).
 */
function bk_count_power_products( int $cat_id, int $brand_id = 0 ): int {
	return (int) bk_remember(
		"power-count:{$cat_id}:{$brand_id}",
		static function () use ( $cat_id, $brand_id ) {
			$tax = array( array( 'taxonomy' => 'product_cat', 'terms' => $cat_id, 'include_children' => true ) );
			if ( $brand_id ) {
				$tax[] = array( 'taxonomy' => BK_BRAND_TAX, 'terms' => $brand_id );
			}
			$q = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => 1,
					'fields'                 => 'ids',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'tax_query'              => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_query'             => array( array( 'key' => '_solar_inverter_friendly', 'value' => array( 'solar_ready', 'low_amp' ), 'compare' => 'IN' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			return (int) $q->found_posts;
		}
	);
}

/* -------------------------------------------------------------------------
 * FAQ generation
 * ---------------------------------------------------------------------- */

/**
 * @return array<int, array{q: string, a: string}>
 */
function bk_seo_faqs( array $ctx ): array {
	$faqs     = array();
	$p        = $ctx['profile'];
	$c        = bk_shipping_config();
	$delivery = sprintf( 'نعم، التوصيل مجاني لباب البيت داخل دمشق وريفها القريب %s، ولمناطق الريف الأبعد رسم رمزي، وللمحافظات شحن رمزي عبر %s.', $c['eta'][1], $c['couriers'] );

	switch ( $ctx['type'] ) {
		case 'product':
			$product = $ctx['product'];
			$power   = bk_get_product_power( $product->get_id() );
			$name    = $product->get_name();
			if ( $product->get_price() ) {
				$faqs[] = array( 'q' => "كم سعر {$name} في دمشق اليوم؟", 'a' => sprintf( 'سعر %s اليوم %s، وهو نفس سعر المحل بدون زيادات ويُحدّث يومياً حسب سعر الصرف.', $name, bk_format_syp( (float) $product->get_price(), false ) ) );
			}
			if ( $power['ampere'] || $power['wattage'] ) {
				$faqs[] = array( 'q' => "كم أمبير يسحب {$name}؟", 'a' => trim( sprintf( 'استطاعة الجهاز %s ويسحب حوالي %s. %s', bk_format_watt( $power['wattage'] ), bk_format_ampere( $power['ampere'] ), $power['label'] ) ) );
			}
			if ( $power['class'] ) {
				$faqs[] = array( 'q' => "هل يعمل {$name} على الانفرتر المنزلي أو الطاقة الشمسية؟", 'a' => 'solar_ready' === $power['class'] ? 'نعم، هذا الجهاز يعمل على الانفرتر المنزلي ومنظومات الطاقة الشمسية بسبب سحبه المنخفض.' : ( 'low_amp' === $power['class'] ? 'هو جهاز اقتصادي يعمل على اشتراك أمبير منخفض، ويمكن تشغيله على الانفرتر حسب قدرة منظومتك.' : 'هذا الجهاز يحتاج كهرباء نظامية أو اشتراك أمبير عالٍ، ولا ننصح بتشغيله على الانفرتر المنزلي الصغير.' ) );
			}
			$faqs[] = array( 'q' => "ما مدة كفالة {$name}؟", 'a' => bk_product_warranty( $product ) . ( $ctx['brand'] ? ' من ' . bk_brand_meta( $ctx['brand'] )['agent'] . '.' : '.' ) );
			$faqs[] = array( 'q' => 'هل التوصيل مجاني؟', 'a' => $delivery );
			$faqs   = array_merge( $faqs, bk_parse_faq( (string) $product->get_meta( '_bk_faq' ) ) );
			break;

		case 'category':
		case 'silo':
			$subject = trim( $ctx['cat']->name . ( $ctx['brand'] ? ' ' . $ctx['brand']->name : '' ) );
			if ( $ctx['min'] ) {
				$faqs[] = array( 'q' => "كم سعر {$subject} في سوريا اليوم؟", 'a' => sprintf( 'تبدأ أسعار %s من %s وتصل إلى %s حسب السعة والمواصفات، والأسعار محدّثة يومياً بنفس سعر المحل.', $subject, bk_format_syp( $ctx['min'], false ), bk_format_syp( $ctx['max'], false ) ) );
			}
			if ( ! $ctx['brand'] ) {
				$brands = wp_list_pluck( wp_list_pluck( bk_get_category_brands( $ctx['cat']->term_id, 5 ), 'term' ), 'name' );
				if ( $brands ) {
					$faqs[] = array( 'q' => "ما هي أفضل ماركات {$ctx['cat']->name} المتوفرة؟", 'a' => sprintf( 'نوفر %s من ماركات %s عبر الوكلاء المعتمدين، والاختيار الأفضل يعتمد على السعة وميزانيتك وعدد الأمبيرات المتوفرة لديك.', $ctx['cat']->name, implode( '، ', $brands ) ) );
				}
			} else {
				$faqs[] = array( 'q' => "هل {$subject} أصلية مع كفالة؟", 'a' => sprintf( 'نعم، كل %s أصلية عبر %s مع %s.', $subject, bk_brand_meta( $ctx['brand'] )['agent'], bk_brand_meta( $ctx['brand'] )['warranty_text'] ) );
			}
			$faqs[] = array( 'q' => sprintf( 'هل يوجد %s تعمل على الأمبير أو الطاقة الشمسية؟', $p['plural'] ), 'a' => 'نعم، استخدم فلتر "يعمل على الانفرتر / الطاقة الشمسية" أعلى القائمة، وراجع خانة السحب بالأمبير في جدول مواصفات كل جهاز. ' . $p['power'] );
			$faqs[] = array( 'q' => 'هل التوصيل مجاني؟', 'a' => $delivery );
			break;

		case 'brand':
			$meta   = bk_brand_meta( $ctx['brand'] );
			$name   = $ctx['brand']->name;
			$faqs[] = array( 'q' => "هل {$name} وكيل رسمي في سوريا؟", 'a' => $meta['agent'] . '.' );
			$faqs[] = array( 'q' => "ما مدة كفالة {$name}؟", 'a' => $meta['warranty_text'] . '.' );
			if ( $meta['service_centers'] ) {
				$faqs[] = array( 'q' => "أين مراكز صيانة {$name}؟", 'a' => implode( ' — ', array_map( static fn( $c ) => trim( $c['city'] . ': ' . $c['address'] . ' ' . $c['phone'] ), $meta['service_centers'] ) ) );
			}
			if ( $ctx['min'] ) {
				$faqs[] = array( 'q' => "كم أسعار {$name} اليوم؟", 'a' => sprintf( 'تبدأ أسعار منتجات %s من %s، بنفس سعر المحل ومحدّثة يومياً.', $name, bk_format_syp( $ctx['min'], false ) ) );
			}
			$faqs = array_merge( $faqs, $meta['faqs'] );
			$faqs[] = array( 'q' => 'هل التوصيل مجاني؟', 'a' => $delivery );
			break;

		case 'shop':
		case 'home':
			$faqs[] = array( 'q' => 'هل الأسعار نفس سعر المحل؟', 'a' => 'نعم، المتجر الإلكتروني مرتبط مباشرة بأسعار المحل ويُحدّث يومياً حسب سعر الصرف، بدون أي زيادة على الطلب أونلاين.' );
			$faqs[] = array( 'q' => 'هل الكفالة حقيقية؟', 'a' => 'كل الأجهزة أصلية عبر الوكلاء المعتمدين (الحافظ، العربي، هنديكو، رامكو وغيرهم) مع بطاقة كفالة الوكيل، ويمكنك تسجيل كفالتك إلكترونياً من صفحة التحقق من الكفالة.' );
			$faqs[] = array( 'q' => 'هل التوصيل مجاني؟', 'a' => $delivery );
			$faqs[] = array( 'q' => 'ما طرق الدفع المتاحة؟', 'a' => 'الدفع عند الاستلام داخل دمشق وريفها، أو عبر الحوالات المالية المعتمدة (الهرم، الفؤاد، سيريتل كاش، شام كاش) لطلبات المحافظات.' );
			break;
	}
	return apply_filters( 'bk_seo_faqs', $faqs, $ctx );
}
