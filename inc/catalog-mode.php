<?php
/**
 * Catalog mode: the store shows prices but takes no online payment.
 * Every order is a pre-filled WhatsApp message built from the product
 * (name, model, price and link), so the shop confirms availability,
 * delivery and payment by chat.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

// No "add to cart" anywhere: products stay visible with their price.
add_filter( 'woocommerce_is_purchasable', '__return_false' );
add_filter( 'woocommerce_variation_is_purchasable', '__return_false' );

// Cart / checkout pages have nothing to show → send visitors to the catalog.
add_action(
	'template_redirect',
	static function (): void {
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) && ! is_wc_endpoint_url( 'order-received' ) ) {
			wp_safe_redirect( wc_get_page_permalink( 'shop' ), 302 );
			exit;
		}
	}
);

// Reviews are replaced by direct WhatsApp questions.
add_filter(
	'comments_open',
	static function ( bool $open, $post_id ): bool {
		return 'product' === get_post_type( $post_id ) ? false : $open;
	},
	10,
	2
);

/**
 * Plain-text price in Syrian pounds for WhatsApp messages.
 */
function bk_product_price_text( WC_Product $product ): string {
	$price = (float) $product->get_price();
	return $price > 0 ? bk_format_syp( $price, false ) : __( 'السعر عند الطلب', 'baytkahraba' );
}

/**
 * The WhatsApp message for a product.
 *
 * @param string $intent order | stock | delivery | installments | photos
 */
function bk_product_whatsapp_message( WC_Product $product, string $intent = 'order' ): string {
	$name  = $product->get_name();
	$model = (string) $product->get_meta( '_bk_model_code' ) ?: $product->get_sku();
	$ref   = $model ? "{$name} ({$model})" : $name;
	$url   = (string) get_permalink( $product->get_id() );
	$store = (string) bk_option( 'store_name' );

	switch ( $intent ) {
		case 'stock':
			/* translators: 1: product name + model, 2: product URL */
			$msg = sprintf( __( "مرحباً، هل %1\$s متوفر حالياً؟\n%2\$s", 'baytkahraba' ), $ref, $url );
			break;
		case 'delivery':
			/* translators: 1: product name + model, 2: product URL */
			$msg = sprintf( __( "مرحباً، كم أجرة توصيل %1\$s لمنطقتي؟\n%2\$s\n\nمنطقتي: ", 'baytkahraba' ), $ref, $url );
			break;
		case 'installments':
			/* translators: 1: product name + model, 2: product URL */
			$msg = sprintf( __( "مرحباً، في إمكانية تقسيط لـ %1\$s؟\n%2\$s", 'baytkahraba' ), $ref, $url );
			break;
		case 'photos':
			/* translators: 1: product name + model, 2: product URL */
			$msg = sprintf( __( "مرحباً، ممكن صور حقيقية أو فيديو لـ %1\$s من المحل؟\n%2\$s", 'baytkahraba' ), $ref, $url );
			break;
		default:
			$lines = array(
				/* translators: %s: store name */
				sprintf( __( 'مرحباً %s، بدي اطلب هالجهاز:', 'baytkahraba' ), $store ),
				'',
				'• ' . $name,
			);
			if ( $model ) {
				$lines[] = '• ' . __( 'الموديل:', 'baytkahraba' ) . ' ' . $model;
			}
			$lines[] = '• ' . __( 'السعر:', 'baytkahraba' ) . ' ' . bk_product_price_text( $product );
			$lines[] = $url;
			$lines[] = '';
			$lines[] = __( 'الاسم: ', 'baytkahraba' );
			$lines[] = __( 'المنطقة / العنوان: ', 'baytkahraba' );
			$msg     = implode( "\n", $lines );
	}
	return (string) apply_filters( 'bk_product_whatsapp_message', $msg, $product, $intent );
}

function bk_product_whatsapp_url( WC_Product $product, string $intent = 'order' ): string {
	return bk_whatsapp_url( bk_product_whatsapp_message( $product, $intent ) );
}

/**
 * Quick questions shown under the order button.
 *
 * @return array<string, string> intent => label
 */
function bk_product_quick_questions(): array {
	return (array) apply_filters(
		'bk_product_quick_questions',
		array(
			'stock'        => __( 'متوفر هلق؟', 'baytkahraba' ),
			'delivery'     => __( 'كم التوصيل لعندي؟', 'baytkahraba' ),
			'installments' => __( 'في تقسيط؟', 'baytkahraba' ),
			'photos'       => __( 'بدي صور من المحل', 'baytkahraba' ),
		)
	);
}

/**
 * Generic WhatsApp link (header, footer, floating button).
 */
function bk_general_whatsapp_url(): string {
	/* translators: %s: store name */
	return bk_whatsapp_url( sprintf( __( 'مرحباً %s، بدي استفسر عن جهاز.', 'baytkahraba' ), (string) bk_option( 'store_name' ) ) );
}

/**
 * Price markup: current price (+ old price when on sale).
 */
function bk_price_html( WC_Product $product, string $size = 'text-[19px]' ): string {
	$price = (float) $product->get_price();
	if ( $price <= 0 ) {
		return '<span class="price ' . esc_attr( $size ) . '">' . esc_html__( 'السعر عند الطلب', 'baytkahraba' ) . '</span>';
	}
	$out = '';
	if ( $product->is_on_sale() && (float) $product->get_regular_price() > $price ) {
		$out .= '<del class="price-old"><span class="screen-reader-text">' . esc_html__( 'السعر السابق:', 'baytkahraba' ) . '</span><span class="bk-num">' . esc_html( number_format( (float) $product->get_regular_price() ) ) . '</span></del>';
	}
	$out .= '<span class="price ' . esc_attr( $size ) . '"><span class="bk-num">' . esc_html( number_format( $price ) ) . '</span><span class="price-cur">' . esc_html__( 'ل.س', 'baytkahraba' ) . '</span></span>';
	return $out;
}

/**
 * Sale percentage (0 when not on sale).
 */
function bk_sale_percent( WC_Product $product ): int {
	$regular = (float) $product->get_regular_price();
	$price   = (float) $product->get_price();
	return ( $product->is_on_sale() && $regular > $price && $regular > 0 ) ? (int) round( ( 1 - $price / $regular ) * 100 ) : 0;
}

/**
 * Category thumbnail (falls back to a line icon on the stage colour).
 */
function bk_category_image( array $node, string $size = 'bk-card', array $attr = array() ): string {
	if ( ! empty( $node['image_id'] ) ) {
		return (string) wp_get_attachment_image( (int) $node['image_id'], $size, false, array_merge( array( 'alt' => '', 'sizes' => '(min-width: 1024px) 180px, 150px', 'loading' => 'lazy', 'fetchpriority' => 'auto' ), $attr ) );
	}
	return '<span class="grid size-full place-items-center text-muted">' . bk_icon( bk_category_icon( (int) $node['id'] ), 'size-1/3' ) . '</span>';
}

/**
 * Second-level categories ("برادات", "غسالات", "شاشات"…) — the way people shop.
 * Falls back to top-level nodes for flat catalogs.
 */
function bk_shop_categories( int $limit = 0 ): array {
	$out = array();
	foreach ( bk_get_category_tree() as $node ) {
		if ( $node['children'] ) {
			foreach ( $node['children'] as $child ) {
				$out[] = $child;
			}
		} else {
			$out[] = $node;
		}
	}
	return $limit ? array_slice( $out, 0, $limit ) : $out;
}

/**
 * Escape a wa.me link for an href attribute.
 * esc_url() strips encoded line breaks (%0A), which would flatten the
 * multi-line WhatsApp message — our links are built with rawurlencode(),
 * so attribute escaping is enough for them.
 */
function bk_esc_wa( string $url ): string {
	return str_starts_with( $url, 'https://wa.me/' ) ? esc_attr( $url ) : esc_url( $url );
}
