<?php
/**
 * Quick-view modal body (returned by REST /quick-view/{id}).
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
$bk_power = bk_get_product_power( $bk_id );
?>
<div class="grid gap-6 p-5 md:grid-cols-2 md:p-8" x-data>
	<div class="grid aspect-square place-items-center rounded-2xl bg-white ring-1 ring-brand-line">
		<?php echo $bk_product->get_image_id() ? wp_get_attachment_image( $bk_product->get_image_id(), 'large', false, array( 'class' => 'max-h-full w-auto object-contain p-6' ) ) : '<img src="' . esc_attr( bk_placeholder_src() ) . '" alt="">'; ?>
	</div>
	<div class="space-y-4">
		<?php if ( $bk_brand ) : ?>
			<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="text-sm font-semibold text-brand-royal"><?php echo esc_html( $bk_brand->name ); ?></a>
		<?php endif; ?>
		<h2 class="text-xl lg:text-2xl"><?php echo esc_html( $bk_product->get_name() ); ?></h2>
		<p class="bk-price text-2xl"><?php echo wp_kses_post( $bk_product->get_price_html() ); ?></p>
		<?php bk_part( 'template-parts/product/energy-badge', array( 'power' => $bk_power, 'size' => 'lg' ) ); ?>
		<ul class="space-y-1.5 text-sm">
			<?php foreach ( array_slice( bk_product_highlights( $bk_product ), 0, 4 ) as $bk_line ) : ?>
				<li class="flex gap-2"><?php bk_the_icon( 'check', 'size-5 shrink-0 text-brand-emerald' ); ?><span><?php echo esc_html( $bk_line ); ?></span></li>
			<?php endforeach; ?>
		</ul>
		<div class="flex flex-wrap gap-2 pt-2">
			<?php if ( $bk_product->is_type( 'simple' ) && $bk_product->is_purchasable() && $bk_product->is_in_stock() ) : ?>
				<button type="button" class="bk-btn-amber bk-btn-lg flex-1" @click="$store.cart.add(<?php echo (int) $bk_id; ?>, 1); $store.qv.open = false"><?php bk_the_icon( 'cart' ); ?><?php esc_html_e( 'أضف للسلة', 'baytkahraba' ); ?></button>
			<?php endif; ?>
			<a href="<?php echo esc_url( $bk_product->get_permalink() ); ?>" class="bk-btn-ghost bk-btn-lg flex-1"><?php esc_html_e( 'كل التفاصيل والمواصفات', 'baytkahraba' ); ?></a>
		</div>
	</div>
</div>
