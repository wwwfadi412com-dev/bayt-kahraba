<?php
/**
 * Product card (used by archives, REST filtering, rails and related blocks).
 *
 * @var array $args { product: WC_Product, position: int, lcp: bool }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product|null $bk_product */
$bk_product = $args['product'] ?? null;
if ( ! $bk_product instanceof WC_Product ) {
	return;
}
$bk_id       = $bk_product->get_id();
$bk_url      = $bk_product->get_permalink();
$bk_brand    = bk_get_product_brand( $bk_id );
$bk_power    = bk_get_product_power( $bk_id );
$bk_model    = (string) $bk_product->get_meta( '_bk_model_code' );
$bk_is_lcp   = ! empty( $args['lcp'] );
$bk_simple   = $bk_product->is_type( 'simple' ) && $bk_product->is_purchasable() && $bk_product->is_in_stock();
$bk_discount = 0;
if ( $bk_product->is_on_sale() && $bk_product->get_regular_price() ) {
	$bk_discount = (int) round( 100 - ( (float) $bk_product->get_sale_price() / (float) $bk_product->get_regular_price() * 100 ) );
}
$bk_wa_msg = sprintf( "مرحباً، أريد طلب:\n%s%s\nالسعر: %s\n%s", $bk_product->get_name(), $bk_model ? " ({$bk_model})" : '', bk_format_syp( (float) $bk_product->get_price(), false ), $bk_url );
?>
<article class="bk-card bk-card-hover group relative flex flex-col overflow-hidden" x-data>
	<div class="absolute start-3 top-3 z-10 flex flex-col items-start gap-1.5">
		<?php if ( $bk_discount > 0 ) : ?>
			<span class="bk-badge-sale bk-num">-<?php echo (int) $bk_discount; ?>%</span>
		<?php endif; ?>
		<?php bk_part( 'template-parts/product/energy-badge', array( 'power' => $bk_power, 'size' => 'sm' ) ); ?>
	</div>

	<div class="absolute end-3 top-3 z-10 flex flex-col gap-1.5 opacity-100 transition lg:translate-x-[-4px] lg:opacity-0 lg:group-hover:translate-x-0 lg:group-hover:opacity-100 lg:focus-within:opacity-100">
		<button type="button" class="grid size-9 place-items-center rounded-full bg-white/95 text-brand-ink shadow-card ring-1 ring-brand-line hover:text-brand-royal" @click="$store.qv.show(<?php echo (int) $bk_id; ?>)" aria-label="<?php esc_attr_e( 'عرض سريع', 'baytkahraba' ); ?>">
			<?php bk_the_icon( 'eye', 'size-[18px]' ); ?>
		</button>
		<button type="button" class="grid size-9 place-items-center rounded-full shadow-card ring-1 transition" :class="$store.compare.has(<?php echo (int) $bk_id; ?>) ? 'bg-brand-royal text-white ring-brand-royal' : 'bg-white/95 text-brand-ink ring-brand-line hover:text-brand-royal'"
			@click="$store.compare.toggle(<?php echo (int) $bk_id; ?>)" :aria-pressed="$store.compare.has(<?php echo (int) $bk_id; ?>)" aria-label="<?php esc_attr_e( 'قارن', 'baytkahraba' ); ?>">
			<?php bk_the_icon( 'compare', 'size-[18px]' ); ?>
		</button>
	</div>

	<a href="<?php echo esc_url( $bk_url ); ?>" class="relative block aspect-square bg-white p-5" tabindex="-1" aria-hidden="true">
		<?php
		if ( $bk_product->get_image_id() ) {
			echo wp_get_attachment_image(
				$bk_product->get_image_id(),
				'bk-card',
				false,
				array(
					'class'         => 'h-full w-full object-contain transition duration-300 group-hover:scale-[1.03]',
					'alt'           => $bk_product->get_name(),
					'sizes'         => '(min-width: 1280px) 260px, (min-width: 768px) 30vw, 46vw',
					'fetchpriority' => $bk_is_lcp ? 'high' : 'auto',
					'loading'       => $bk_is_lcp ? 'eager' : 'lazy',
				)
			);
		} else {
			echo '<img src="' . esc_attr( bk_placeholder_src() ) . '" alt="" class="h-full w-full object-contain" width="400" height="400">';
		}
		?>
	</a>

	<div class="flex flex-1 flex-col gap-2 border-t border-brand-line p-4">
		<div class="flex items-center justify-between gap-2 text-xs text-brand-muted">
			<?php if ( $bk_brand ) : ?>
				<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="font-semibold text-brand-royal hover:underline"><?php echo esc_html( $bk_brand->name ); ?></a>
			<?php endif; ?>
			<?php if ( $bk_model ) : ?>
				<span class="bk-ltr bk-sku truncate"><?php echo esc_html( $bk_model ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="m-0 text-[15px] font-semibold leading-7">
			<a href="<?php echo esc_url( $bk_url ); ?>" class="line-clamp-2 text-brand-ink hover:text-brand-royal"><?php echo esc_html( $bk_product->get_name() ); ?></a>
		</h3>

		<?php if ( $bk_power['wattage'] || $bk_power['ampere'] ) : ?>
			<p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-brand-body">
				<?php if ( $bk_power['wattage'] ) : ?>
					<span class="inline-flex items-center gap-1"><?php bk_the_icon( 'bolt', 'size-3.5 text-brand-amber' ); ?><span class="bk-num"><?php echo esc_html( bk_format_watt( $bk_power['wattage'] ) ); ?></span></span>
				<?php endif; ?>
				<?php if ( $bk_power['ampere'] ) : ?>
					<span class="inline-flex items-center gap-1"><?php bk_the_icon( 'plug', 'size-3.5 text-brand-emerald' ); ?><span class="bk-num"><?php echo esc_html( bk_format_ampere( $bk_power['ampere'] ) ); ?></span></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<div class="mt-auto pt-1">
			<p class="bk-price text-lg"><?php echo wp_kses_post( $bk_product->get_price_html() ); ?></p>
			<p class="text-[11px] text-brand-emerald-hover"><?php esc_html_e( 'نفس سعر المحل · كفالة الوكيل', 'baytkahraba' ); ?></p>
		</div>

		<div class="mt-2 flex gap-2">
			<?php if ( $bk_simple ) : ?>
				<button type="button" class="bk-btn-amber flex-1 !px-3" @click="$store.cart.add(<?php echo (int) $bk_id; ?>, 1, $el)">
					<?php bk_the_icon( 'cart', 'size-[18px]' ); ?><?php esc_html_e( 'أضف للسلة', 'baytkahraba' ); ?>
				</button>
			<?php elseif ( ! $bk_product->is_in_stock() ) : ?>
				<span class="bk-btn flex-1 cursor-default bg-slate-100 text-slate-500"><?php esc_html_e( 'نفدت الكمية — اسأل عن التوفر', 'baytkahraba' ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $bk_url ); ?>" class="bk-btn-navy flex-1 !px-3"><?php esc_html_e( 'اختر المواصفات', 'baytkahraba' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( bk_whatsapp_url( $bk_wa_msg ) ); ?>" target="_blank" rel="noopener nofollow" class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-emerald-50 text-brand-emerald-hover ring-1 ring-brand-emerald-100 hover:bg-brand-emerald hover:text-white" aria-label="<?php esc_attr_e( 'اطلب عبر واتساب', 'baytkahraba' ); ?>">
				<?php bk_the_icon( 'whatsapp', 'size-5' ); ?>
			</a>
		</div>
	</div>
</article>
