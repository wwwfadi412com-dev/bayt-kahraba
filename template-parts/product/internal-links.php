<?php
/**
 * Hierarchical SEO silo mesh on the product page.
 *
 * @var array $args { product: WC_Product }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_product = $args['product'] ?? null;
if ( ! $bk_product instanceof WC_Product ) {
	return;
}
$bk_id    = $bk_product->get_id();
$bk_brand = bk_get_product_brand( $bk_id );
$bk_cat   = bk_product_primary_category( $bk_id );
$bk_power = bk_get_product_power( $bk_id );
?>
<?php if ( $bk_brand || $bk_cat ) : ?>
	<nav class="grid gap-3 sm:grid-cols-2" aria-label="<?php esc_attr_e( 'روابط ذات صلة', 'baytkahraba' ); ?>">
		<?php if ( $bk_brand && $bk_cat && bk_intersection_count( $bk_brand->term_id, $bk_cat->term_id ) > 1 ) : ?>
			<a href="<?php echo esc_url( bk_silo_url( $bk_brand, $bk_cat ) ); ?>" class="bk-card bk-card-hover flex items-center justify-between gap-3 p-4">
				<span>
					<span class="block text-xs text-brand-muted"><?php esc_html_e( 'تصفح أيضاً', 'baytkahraba' ); ?></span>
					<span class="font-bold text-brand-ink"><?php printf( esc_html__( 'المزيد من %1$s من ماركة %2$s', 'baytkahraba' ), esc_html( $bk_cat->name ), esc_html( $bk_brand->name ) ); ?></span>
				</span>
				<?php bk_the_icon( 'chevron', 'size-5 text-brand-royal' ); ?>
			</a>
		<?php endif; ?>
		<?php if ( $bk_brand ) : ?>
			<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="bk-card bk-card-hover flex items-center justify-between gap-3 p-4">
				<span class="flex items-center gap-3">
					<?php echo bk_brand_logo( $bk_brand, 'h-8 w-auto max-w-[80px] object-contain text-xs', 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="font-bold text-brand-ink"><?php printf( esc_html__( 'تصفح جميع منتجات وكالة %s', 'baytkahraba' ), esc_html( $bk_brand->name ) ); ?></span>
				</span>
				<?php bk_the_icon( 'chevron', 'size-5 text-brand-royal' ); ?>
			</a>
		<?php endif; ?>
	</nav>
<?php endif; ?>

<?php
if ( $bk_cat ) {
	bk_part(
		'template-parts/product/rail',
		array(
			/* translators: %s child category */
			'title'     => sprintf( __( 'منتجات مشابهة في قسم %s وبسعر قريب', 'baytkahraba' ), $bk_cat->name ),
			'products'  => bk_similar_price_products( $bk_product, 10 ),
			'link'      => get_term_link( $bk_cat ),
			'link_text' => sprintf( __( 'كل %s', 'baytkahraba' ), $bk_cat->name ),
		)
	);
}
if ( in_array( $bk_power['class'], array( 'solar_ready', 'low_amp' ), true ) || $bk_power['wattage'] ) {
	bk_part(
		'template-parts/product/rail',
		array(
			'title'     => __( 'أجهزة أخرى تعمل على الانفرتر بنفس الاستطاعة', 'baytkahraba' ),
			'products'  => bk_similar_power_products( $bk_product, 10 ),
			'link'      => add_query_arg( 'power', 'solar', wc_get_page_permalink( 'shop' ) ),
			'link_text' => __( 'كل أجهزة الطاقة الشمسية', 'baytkahraba' ),
		)
	);
}
