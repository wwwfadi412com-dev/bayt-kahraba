<?php
/**
 * Native SEO + GEO engine (zero plugins).
 *
 *  - <title>, meta description, canonical, Open Graph, robots for every context.
 *  - Hierarchical breadcrumbs (Home › Parent › Child › Brand silo › Product).
 *  - One connected JSON-LD @graph: Store/LocalBusiness, WebSite+SearchAction,
 *    WebPage/CollectionPage, BreadcrumbList, Product+Offer+Brand+Shipping,
 *    ItemList and FAQPage.
 *  - /llms.txt and /llms-full.txt for AI answer engines.
 *  - Syrian synonym search (براد ↔ ثلاجة) + model/SKU matching.
 *
 * If Yoast SEO or Rank Math is active, meta tags are left to them while the
 * commerce schema, breadcrumbs and llms.txt keep working.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

function bk_seo_meta_enabled(): bool {
	$third_party = defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'AIOSEO_VERSION' );
	return (bool) apply_filters( 'bk_seo_meta_enabled', ! $third_party );
}

/* -------------------------------------------------------------------------
 * Title / meta / canonical / OG
 * ---------------------------------------------------------------------- */

add_filter(
	'pre_get_document_title',
	static function ( string $title ): string {
		if ( ! bk_seo_meta_enabled() || is_admin() ) {
			return $title;
		}
		$custom = bk_seo_title( bk_seo_context() );
		if ( '' === $custom ) {
			return $title;
		}
		$paged = max( 1, (int) get_query_var( 'paged' ) );
		return $paged > 1 ? $custom . ' - ' . sprintf( __( 'صفحة %d', 'baytkahraba' ), $paged ) : $custom;
	},
	20
);

/**
 * Canonical URL of the current request (filters & tracking params stripped).
 */
function bk_canonical_url(): string {
	$ctx   = bk_seo_context();
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = '';
	switch ( $ctx['type'] ) {
		case 'silo':
			$url = bk_silo_url( $ctx['brand'], $ctx['cat'] );
			break;
		case 'category':
			$url = (string) get_term_link( $ctx['cat'] );
			break;
		case 'brand':
			$url = (string) get_term_link( $ctx['brand'] );
			break;
		case 'product':
			$url = (string) get_permalink( $ctx['product']->get_id() );
			break;
		case 'home':
			$url = home_url( '/' );
			break;
		case 'shop':
			$url = (string) wc_get_page_permalink( 'shop' );
			break;
		default:
			$url = is_singular() ? (string) get_permalink() : '';
	}
	if ( $url && $paged > 1 && in_array( $ctx['type'], array( 'silo', 'category', 'brand', 'shop' ), true ) ) {
		$url = user_trailingslashit( trailingslashit( $url ) . 'page/' . $paged );
	}
	return $url;
}

add_action(
	'wp',
	static function (): void {
		if ( bk_seo_meta_enabled() ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! bk_seo_meta_enabled() || is_404() ) {
			return;
		}
		$ctx         = bk_seo_context();
		$description = bk_seo_description( $ctx );
		if ( 'other' === $ctx['type'] && is_singular() ) {
			$description = wp_trim_words( wp_strip_all_tags( (string) get_the_excerpt() ), 30, '' );
		}
		$canonical = bk_canonical_url();
		$title     = wp_get_document_title();
		$image     = '';
		if ( $ctx['product'] ) {
			$image = (string) wp_get_attachment_image_url( $ctx['product']->get_image_id(), 'large' );
		} elseif ( $ctx['brand'] ) {
			$meta  = bk_brand_meta( $ctx['brand'] );
			$image = (string) wp_get_attachment_image_url( $meta['banner_id'] ?: $meta['logo_id'], 'large' );
		} elseif ( $ctx['cat'] ) {
			$image = (string) wp_get_attachment_image_url( (int) get_term_meta( $ctx['cat']->term_id, 'thumbnail_id', true ), 'large' );
		}
		if ( ! $image && ( $hero = (int) bk_option( 'hero_image_id', 0 ) ) ) {
			$image = (string) wp_get_attachment_image_url( $hero, 'large' );
		}

		echo "\n<!-- Bayt Al-Kahraba SEO/GEO -->\n";
		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $description ) ) );
		}
		if ( $canonical ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
		}
		printf( '<meta property="og:locale" content="ar_SY">' . "\n" );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( (string) bk_option( 'store_name' ) ) );
		printf( '<meta property="og:type" content="%s">' . "\n", 'product' === $ctx['type'] ? 'product' : 'website' );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		if ( $description ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $description ) ) );
		}
		if ( $canonical ) {
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $canonical ) );
		}
		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}
		if ( $ctx['product'] && $ctx['product']->get_price() ) {
			printf( '<meta property="product:price:amount" content="%s">' . "\n", esc_attr( (string) $ctx['product']->get_price() ) );
			echo '<meta property="product:price:currency" content="SYP">' . "\n";
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="geo.region" content="SY-DI"><meta name="geo.placename" content="' . esc_attr( (string) bk_option( 'city' ) ) . '">' . "\n";
	},
	2
);

/* -------------------------------------------------------------------------
 * Breadcrumbs
 * ---------------------------------------------------------------------- */

/**
 * @return array<int, array{name: string, url: string}>
 */
function bk_breadcrumbs(): array {
	static $crumbs = null;
	if ( null !== $crumbs ) {
		return $crumbs;
	}
	$ctx    = bk_seo_context();
	$crumbs = array( array( 'name' => __( 'الرئيسية', 'baytkahraba' ), 'url' => home_url( '/' ) ) );
	$chain  = static function ( ?WP_Term $cat ) use ( &$crumbs ): void {
		if ( $cat ) {
			foreach ( bk_category_chain( $cat->term_id ) as $term ) {
				$crumbs[] = array( 'name' => $term->name, 'url' => (string) get_term_link( $term ) );
			}
		}
	};

	switch ( $ctx['type'] ) {
		case 'product':
			$chain( $ctx['cat'] );
			if ( $ctx['brand'] && $ctx['cat'] ) {
				$crumbs[] = array( 'name' => $ctx['cat']->name . ' ' . $ctx['brand']->name, 'url' => bk_silo_url( $ctx['brand'], $ctx['cat'] ) );
			}
			$crumbs[] = array( 'name' => $ctx['product']->get_name(), 'url' => (string) get_permalink( $ctx['product']->get_id() ) );
			break;
		case 'silo':
			$crumbs[] = array( 'name' => $ctx['brand']->name, 'url' => (string) get_term_link( $ctx['brand'] ) );
			$crumbs[] = array( 'name' => $ctx['cat']->name . ' ' . $ctx['brand']->name, 'url' => bk_silo_url( $ctx['brand'], $ctx['cat'] ) );
			break;
		case 'category':
			$chain( $ctx['cat'] );
			break;
		case 'brand':
			$crumbs[] = array( 'name' => $ctx['brand']->name, 'url' => (string) get_term_link( $ctx['brand'] ) );
			break;
		case 'shop':
			$crumbs[] = array( 'name' => __( 'كل المنتجات', 'baytkahraba' ), 'url' => (string) wc_get_page_permalink( 'shop' ) );
			break;
		default:
			if ( is_search() ) {
				$crumbs[] = array( 'name' => sprintf( __( 'نتائج البحث: %s', 'baytkahraba' ), get_search_query() ), 'url' => '' );
			} elseif ( is_singular() ) {
				$crumbs[] = array( 'name' => get_the_title(), 'url' => (string) get_permalink() );
			}
	}
	return $crumbs;
}

/* -------------------------------------------------------------------------
 * JSON-LD @graph
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	static function (): void {
		if ( function_exists( 'WC' ) && WC()->structured_data ) {
			remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
		}
	},
	20
);

function bk_store_id(): string {
	return home_url( '/#store' );
}

function bk_schema_store( bool $full ): array {
	$logo  = has_custom_logo() ? (string) wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' ) : '';
	$types = array_values( array_filter( array_map( 'trim', explode( ',', (string) bk_option( 'schema_types' ) ) ) ) );
	$node  = array(
		'@type'              => $types ?: array( 'Store' ),
		'@id'                => bk_store_id(),
		'name'               => (string) bk_option( 'store_name' ),
		'url'                => home_url( '/' ),
		'telephone'          => bk_phone_international( (string) bk_option( 'phone' ) ) ? '+' . bk_phone_international( (string) bk_option( 'phone' ) ) : '',
		'email'              => (string) bk_option( 'email' ),
		'slogan'             => (string) bk_option( 'store_tagline' ),
		'priceRange'         => (string) bk_option( 'price_range' ),
		'currenciesAccepted' => 'SYP',
		'paymentAccepted'    => 'الدفع عند الاستلام، الهرم، الفؤاد، سيريتل كاش، شام كاش',
		'address'            => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => (string) bk_option( 'address' ),
			'addressLocality' => (string) bk_option( 'city' ),
			'addressRegion'   => 'Damascus',
			'addressCountry'  => 'SY',
		),
		'areaServed'         => array(
			array( '@type' => 'City', 'name' => 'Damascus', 'alternateName' => 'دمشق' ),
			array( '@type' => 'AdministrativeArea', 'name' => 'Rif Dimashq', 'alternateName' => 'ريف دمشق' ),
			array( '@type' => 'Country', 'name' => 'Syria', 'alternateName' => 'سوريا' ),
		),
	);
	if ( $logo ) {
		$node['logo']  = $logo;
		$node['image'] = $logo;
	}
	if ( bk_option( 'geo_lat' ) && bk_option( 'geo_lng' ) ) {
		$node['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) bk_option( 'geo_lat' ), 'longitude' => (float) bk_option( 'geo_lng' ) );
	}
	if ( bk_option( 'opening_hours' ) ) {
		$node['openingHours'] = (string) bk_option( 'opening_hours' );
	}
	$same_as = array_values( array_filter( array( bk_option( 'facebook' ), bk_option( 'instagram' ), bk_option( 'telegram' ) ) ) );
	if ( $same_as ) {
		$node['sameAs'] = $same_as;
	}
	if ( $full ) {
		$node['description'] = 'متجر أدوات كهربائية ومنزلية في دمشق: توصيل مجاني لباب البيت داخل دمشق وريفها القريب، رسم رمزي لريف دمشق الأبعد، وشحن رمزي لكافة المحافظات السورية. كفالة الوكيل الحقيقية وبنفس سعر المحل.';
		$node['brand']       = array_map(
			static fn( WP_Term $b ) => array( '@type' => 'Brand', 'name' => $b->name, 'url' => (string) get_term_link( $b ) ),
			bk_get_brands()
		);
		$node['hasOfferCatalog'] = array(
			'@type'           => 'OfferCatalog',
			'name'            => 'الأجهزة الكهربائية والمنزلية',
			'itemListElement' => array_map(
				static fn( $n ) => array( '@type' => 'OfferCatalog', 'name' => $n['name'], 'url' => $n['url'] ),
				bk_get_category_tree()
			),
		);
	}
	return array_filter( $node );
}

function bk_schema_shipping_details( WC_Product $product ): array {
	$c      = bk_shipping_config();
	$cat    = bk_product_primary_category( $product->get_parent_id() ?: $product->get_id() );
	$class  = $cat ? bk_category_weight_class( $cat->term_id ) : 'heavy';
	$zone2  = array_filter( array_map( static fn( $a ) => 2 === $a['zone'] ? $a['fee'] : null, $c['areas'] ), static fn( $v ) => null !== $v );
	$zone3  = array_filter( array_map( static fn( $g ) => $g[ $class ], $c['gov'] ) );
	$detail = static function ( float $fee, string $region, int $max_days ): array {
		return array(
			'@type'               => 'OfferShippingDetails',
			'shippingRate'        => array( '@type' => 'MonetaryAmount', 'value' => $fee, 'currency' => 'SYP' ),
			'shippingDestination' => array_filter( array( '@type' => 'DefinedRegion', 'addressCountry' => 'SY', 'addressRegion' => $region ) ),
			'deliveryTime'        => array(
				'@type'        => 'ShippingDeliveryTime',
				'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY' ),
				'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => $max_days, 'unitCode' => 'DAY' ),
			),
		);
	};
	$out = array( $detail( 0.0, 'Damascus', 1 ) );
	if ( $zone2 ) {
		$out[] = $detail( (float) min( $zone2 ), 'Rif Dimashq', 2 );
	}
	if ( $zone3 ) {
		$out[] = $detail( (float) min( $zone3 ), '', 4 );
	}
	return $out;
}

function bk_schema_product( WC_Product $product, array $ctx ): array {
	$id     = $product->get_id();
	$url    = (string) get_permalink( $id );
	$brand  = $ctx['brand'];
	$power  = bk_get_product_power( $id );
	$images = array_filter( array_map( static fn( $img ) => wp_get_attachment_image_url( $img, 'large' ), array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
	$avail  = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
	if ( $product->is_on_backorder() ) {
		$avail = 'https://schema.org/BackOrder';
	}

	$offer = array(
		'@type'           => 'Offer',
		'url'             => $url,
		'priceCurrency'   => 'SYP',
		'price'           => (float) wc_get_price_to_display( $product ),
		'priceValidUntil' => wp_date( 'Y-m-d', strtotime( '+30 days' ) ),
		'availability'    => $avail,
		'itemCondition'   => 'https://schema.org/NewCondition',
		'seller'          => array( '@id' => bk_store_id() ),
		'shippingDetails' => bk_schema_shipping_details( $product ),
	);
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		$offer  = array_merge(
			$offer,
			array(
				'@type'      => 'AggregateOffer',
				'lowPrice'   => (float) min( $prices['price'] ?: array( 0 ) ),
				'highPrice'  => (float) max( $prices['price'] ?: array( 0 ) ),
				'offerCount' => count( $prices['price'] ),
			)
		);
		unset( $offer['price'] );
	}
	if ( ( $days = (int) bk_option( 'return_days', 0 ) ) > 0 ) {
		$offer['hasMerchantReturnPolicy'] = array(
			'@type'                => 'MerchantReturnPolicy',
			'applicableCountry'    => 'SY',
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'merchantReturnDays'   => $days,
			'returnMethod'         => 'https://schema.org/ReturnInStore',
			'returnFees'           => 'https://schema.org/FreeReturn',
		);
	}

	$props = array();
	if ( $power['wattage'] ) {
		$props[] = array( '@type' => 'PropertyValue', 'name' => 'الاستطاعة', 'value' => $power['wattage'], 'unitCode' => 'WTT', 'unitText' => 'واط' );
	}
	if ( $power['ampere'] ) {
		$props[] = array( '@type' => 'PropertyValue', 'name' => 'السحب بالأمبير', 'value' => $power['ampere'], 'unitCode' => 'AMP', 'unitText' => 'أمبير' );
	}
	if ( $power['label'] ) {
		$props[] = array( '@type' => 'PropertyValue', 'name' => 'التوافق مع الانفرتر والطاقة الشمسية', 'value' => $power['label'] );
	}
	foreach ( array( 'capacity', 'dims', 'warranty' ) as $key ) {
		$spec = bk_product_specs( $product )[ $key ] ?? null;
		if ( $spec ) {
			$props[] = array( '@type' => 'PropertyValue', 'name' => $spec['label'], 'value' => $spec['value'] );
		}
	}

	$node = array(
		'@type'              => 'Product',
		'@id'                => $url . '#product',
		'name'               => $product->get_name(),
		'url'                => $url,
		'description'        => wp_trim_words( wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ), 60, '' ) ?: bk_seo_description( $ctx ),
		'sku'                => $product->get_sku(),
		'mpn'                => (string) $product->get_meta( '_bk_model_code' ),
		'image'              => array_values( $images ),
		'category'           => $ctx['cat'] ? implode( ' > ', wp_list_pluck( bk_category_chain( $ctx['cat']->term_id ), 'name' ) ) : '',
		'additionalProperty' => $props,
		'offers'             => $offer,
		'mainEntityOfPage'   => array( '@id' => $url . '#webpage' ),
	);
	if ( $brand ) {
		$meta          = bk_brand_meta( $brand );
		$node['brand'] = array_filter(
			array(
				'@type' => 'Brand',
				'name'  => $brand->name,
				'url'   => (string) get_term_link( $brand ),
				'logo'  => $meta['logo_id'] ? (string) wp_get_attachment_image_url( $meta['logo_id'], 'medium' ) : '',
			)
		);
	}
	if ( $product->get_review_count() > 0 ) {
		$node['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (float) $product->get_average_rating(),
			'reviewCount' => (int) $product->get_review_count(),
			'bestRating'  => 5,
			'worstRating' => 1,
		);
		$reviews = get_comments( array( 'post_id' => $id, 'status' => 'approve', 'type' => 'review', 'number' => 3 ) );
		foreach ( $reviews as $review ) {
			$rating           = (int) get_comment_meta( $review->comment_ID, 'rating', true );
			$node['review'][] = array_filter(
				array(
					'@type'         => 'Review',
					'author'        => array( '@type' => 'Person', 'name' => $review->comment_author ),
					'datePublished' => mysql2date( 'c', $review->comment_date_gmt ),
					'reviewBody'    => wp_strip_all_tags( $review->comment_content ),
					'reviewRating'  => $rating ? array( '@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5 ) : null,
				)
			);
		}
	}
	return array_filter( $node, static fn( $v ) => '' !== $v && array() !== $v && null !== $v );
}

function bk_schema_graph(): array {
	$ctx   = bk_seo_context();
	$url   = bk_canonical_url() ?: home_url( add_query_arg( array() ) );
	$graph = array();

	$graph[] = bk_schema_store( 'home' === $ctx['type'] );
	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'url'             => home_url( '/' ),
		'name'            => (string) bk_option( 'store_name' ),
		'inLanguage'      => 'ar-SY',
		'publisher'       => array( '@id' => bk_store_id() ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array( '@type' => 'EntryPoint', 'urlTemplate' => home_url( '/?post_type=product&s={search_term_string}' ) ),
			'query-input' => 'required name=search_term_string',
		),
	);

	$crumbs = bk_breadcrumbs();
	if ( count( $crumbs ) > 1 ) {
		$items = array();
		foreach ( $crumbs as $i => $crumb ) {
			$items[] = array_filter( array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'], 'item' => $crumb['url'] ?: null ) );
		}
		$graph[] = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items );
	}

	$is_collection = in_array( $ctx['type'], array( 'category', 'brand', 'silo', 'shop' ), true );
	$page          = array(
		'@type'      => $is_collection ? 'CollectionPage' : ( 'product' === $ctx['type'] ? 'ItemPage' : 'WebPage' ),
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => wp_get_document_title(),
		'inLanguage' => 'ar-SY',
		'isPartOf'   => array( '@id' => home_url( '/#website' ) ),
	);
	if ( count( $crumbs ) > 1 ) {
		$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}
	if ( $desc = bk_seo_description( $ctx ) ) {
		$page['description'] = wp_strip_all_tags( $desc );
	}

	if ( $is_collection ) {
		global $wp_query;
		$items = array();
		foreach ( (array) $wp_query->posts as $i => $post ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink( $post ), 'name' => get_the_title( $post ) );
		}
		if ( $items ) {
			$page['mainEntity'] = array( '@type' => 'ItemList', 'numberOfItems' => (int) $wp_query->found_posts, 'itemListElement' => $items );
		}
		if ( $ctx['brand'] ) {
			$page['about'] = array( '@type' => 'Brand', 'name' => $ctx['brand']->name );
		}
	}
	$graph[] = $page;

	if ( 'product' === $ctx['type'] && $ctx['product'] ) {
		$graph[] = bk_schema_product( $ctx['product'], $ctx );
	}

	$faqs = bk_seo_faqs( $ctx );
	if ( $faqs ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => $url . '#faq',
			'mainEntity' => array_map(
				static fn( $f ) => array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ) ),
				$faqs
			),
		);
	}
	return apply_filters( 'bk_schema_graph', $graph, $ctx );
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_404() || is_admin() ) {
			return;
		}
		$json = wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => bk_schema_graph() ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( $json ) {
			echo '<script type="application/ld+json">' . str_replace( '</', '<\/', $json ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	30
);

/* -------------------------------------------------------------------------
 * /llms.txt & /llms-full.txt (GEO)
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	static function (): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?bk_llms=short', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?bk_llms=full', 'top' );
	}
);
add_filter( 'query_vars', static fn( $vars ) => array_merge( $vars, array( 'bk_llms' ) ) );

add_action(
	'template_redirect',
	static function (): void {
		$mode = get_query_var( 'bk_llms' );
		if ( ! in_array( $mode, array( 'short', 'full' ), true ) ) {
			return;
		}
		$body = bk_remember( 'llms:' . $mode, static fn() => bk_build_llms_txt( 'full' === $mode ), 6 * HOUR_IN_SECONDS );
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		header( 'X-Robots-Tag: noindex' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	},
	0
);

/**
 * Markdown summary of the store following the llms.txt convention.
 */
function bk_build_llms_txt( bool $full ): string {
	$store = (string) bk_option( 'store_name' );
	$c     = bk_shipping_config();
	$lines = array();

	$lines[] = '# ' . $store;
	$lines[] = '';
	$lines[] = '> متجر وموزّع معتمد للأدوات الكهربائية والمنزلية في دمشق، سوريا. أسعار المتجر الإلكتروني مطابقة لسعر المحل الفعلي وتُحدّث يومياً حسب سعر الصرف (العملة: الليرة السورية SYP). كل الأجهزة أصلية مع كفالة الوكيل الرسمي.';
	$lines[] = '';
	$lines[] = '- الموقع: ' . bk_option( 'address' ) . ' — ' . bk_option( 'city' ) . '، سوريا';
	$lines[] = '- الهاتف / واتساب: ' . bk_option( 'phone' ) . ' / +' . bk_phone_international( (string) bk_option( 'whatsapp' ) );
	$lines[] = '- ساعات العمل: ' . bk_option( 'opening_hours_label' );
	$lines[] = '- آخر تحديث للبيانات: ' . wp_date( 'Y-m-d' );
	$lines[] = '';

	$lines[] = '## سياسة التوصيل والدفع';
	$lines[] = '- توصيل مجاني لباب البيت داخل دمشق وريفها القريب (' . $c['eta'][1] . ').';
	$lines[] = '- ريف دمشق الأبعد: رسم توصيل رمزي حسب المنطقة (' . $c['eta'][2] . ').';
	$lines[] = '- باقي المحافظات السورية: شحن رمزي عبر ' . $c['couriers'] . ' حسب وزن الجهاز (' . $c['eta'][3] . ').';
	$lines[] = '- الدفع: عند الاستلام داخل دمشق وريفها، أو عبر الحوالات (الهرم، الفؤاد، سيريتل كاش، شام كاش) للمحافظات.';
	$lines[] = '- الطلب متاح أيضاً عبر واتساب برسالة مفصلة تلقائياً.';
	$lines[] = '';

	$lines[] = '## الماركات والوكلاء الرسميون';
	foreach ( bk_get_brands() as $brand ) {
		$meta    = bk_brand_meta( $brand );
		$range   = bk_price_range( null, $brand->term_id );
		$from    = $range['min'] ? ' — الأسعار تبدأ من ' . bk_format_syp( $range['min'], false ) : '';
		$lines[] = sprintf( '- [%s](%s): %s. %s%s', $brand->name, get_term_link( $brand ), $meta['agent'], $meta['warranty_text'], $from );
	}
	$lines[] = '';

	$lines[] = '## الأقسام';
	$walk = static function ( array $nodes, int $indent ) use ( &$walk, &$lines ) {
		foreach ( $nodes as $node ) {
			$range   = bk_price_range( $node['id'] );
			$lines[] = str_repeat( '  ', $indent ) . sprintf( '- [%s](%s): %d منتج%s', $node['name'], $node['url'], $node['count'], $range['min'] ? '، من ' . bk_format_syp( $range['min'], false ) . ' إلى ' . bk_format_syp( $range['max'], false ) : '' );
			$walk( $node['children'], $indent + 1 );
		}
	};
	$walk( bk_get_category_tree(), 0 );
	$lines[] = '';

	$lines[] = '## صفحات مهمة';
	$lines[] = '- [كل المنتجات](' . wc_get_page_permalink( 'shop' ) . ')';
	foreach ( get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-bundle-builder.php' ) ) as $page ) { // phpcs:ignore WordPress.DB.SlowDBQuery
		$lines[] = '- [' . $page->post_title . '](' . get_permalink( $page ) . '): حاسبة تجهيز العرسان والبيت الكامل بثلاث باقات.';
	}
	$lines[] = '- [ملخص كامل للمنتجات والأسعار](' . home_url( '/llms-full.txt' ) . ')';

	if ( $full ) {
		$lines[] = '';
		$lines[] = '## صفحات الماركة × القسم';
		foreach ( array_slice( bk_all_silos( 1 ), 0, 500 ) as $silo ) {
			$brand = get_term( $silo['brand_id'], BK_BRAND_TAX );
			$cat   = get_term( $silo['cat_id'], 'product_cat' );
			if ( $brand instanceof WP_Term && $cat instanceof WP_Term ) {
				$lines[] = sprintf( '- [%s %s](%s): %d منتج', $cat->name, $brand->name, bk_silo_url( $brand, $cat ), $silo['count'] );
			}
		}
		$lines[] = '';
		$lines[] = '## المنتجات (الاسم — الماركة — السعر — الكهرباء — الكفالة)';
		$page    = 1;
		do {
			$ids = wc_get_products( array( 'status' => 'publish', 'limit' => 200, 'page' => $page++, 'return' => 'ids', 'visibility' => 'catalog' ) );
			foreach ( $ids as $id ) {
				$product = wc_get_product( $id );
				if ( ! $product ) {
					continue;
				}
				$brand   = bk_get_product_brand( $id );
				$power   = bk_get_product_power( $id );
				$parts   = array_filter(
					array(
						$brand ? $brand->name : '',
						$product->get_price() ? bk_format_syp( (float) $product->get_price(), false ) : '',
						$product->is_in_stock() ? 'متوفر' : 'غير متوفر',
						trim( bk_format_watt( $power['wattage'] ) . ' ' . bk_format_ampere( $power['ampere'] ) ),
						$power['short'],
						bk_product_warranty( $product ),
					)
				);
				$lines[] = sprintf( '- [%s](%s): %s', $product->get_name(), $product->get_permalink(), implode( ' | ', $parts ) );
			}
		} while ( count( $ids ) === 200 && $page < 60 );
	}

	return implode( "\n", $lines ) . "\n";
}

add_filter(
	'robots_txt',
	static function ( string $output, $public ): string {
		if ( $public && bk_is_production() ) {
			$output .= "\n# AI-readable store summary\n# " . home_url( '/llms.txt' ) . "\n";
		}
		return $output;
	},
	100,
	2
);

/* -------------------------------------------------------------------------
 * Synonym-aware product search (title, excerpt, content, SKU, model code)
 * ---------------------------------------------------------------------- */

add_filter(
	'posts_search',
	static function ( string $search, WP_Query $query ): string {
		$s         = (string) $query->get( 's' );
		$post_type = $query->get( 'post_type' );
		$is_product_search = 'product' === $post_type || ( is_array( $post_type ) && array( 'product' ) === $post_type ) || ( $query->is_main_query() && isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === trim( $s ) || ! $is_product_search || is_admin() ) {
			return $search;
		}
		global $wpdb;
		$or = array();
		foreach ( array_slice( bk_search_expand_terms( $s ), 0, 12 ) as $term ) {
			$like = '%' . $wpdb->esc_like( $term ) . '%';
			$or[] = $wpdb->prepare( "({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s)", $like, $like, $like );
		}
		$like_s = '%' . $wpdb->esc_like( trim( $s ) ) . '%';
		$or[]   = $wpdb->prepare( "{$wpdb->posts}.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_sku','_bk_model_code') AND meta_value LIKE %s)", $like_s );
		$or[]   = $wpdb->prepare( "{$wpdb->posts}.ID IN (SELECT post_parent FROM {$wpdb->posts} v INNER JOIN {$wpdb->postmeta} vm ON vm.post_id = v.ID WHERE v.post_type = 'product_variation' AND vm.meta_key = '_sku' AND vm.meta_value LIKE %s)", $like_s );
		return ' AND (' . implode( ' OR ', $or ) . ') ';
	},
	20,
	2
);

// Relevance: exact title matches first.
add_filter(
	'posts_search_orderby',
	static function ( $orderby, WP_Query $query ) {
		if ( 'product' !== $query->get( 'post_type' ) || '' === trim( (string) $query->get( 's' ) ) ) {
			return $orderby;
		}
		global $wpdb;
		return $wpdb->prepare( "({$wpdb->posts}.post_title LIKE %s) DESC, {$wpdb->posts}.post_date DESC", '%' . $wpdb->esc_like( trim( (string) $query->get( 's' ) ) ) . '%' );
	},
	20,
	2
);
