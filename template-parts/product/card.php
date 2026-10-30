<?php
/**
 * Product "datasheet" card (archives, REST filtering, rails, related blocks).
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
$bk_model    = (string) ( $bk_product->get_meta( '_bk_model_code' ) ?: $bk_product->get_sku() );
$bk_capacity = (string) $bk_product->get_meta( '_bk_capacity' );
$bk_is_lcp   = ! empty( $args['lcp'] );
$bk_simple   = $bk_product->is_type( 'simple' ) && $bk_product->is_purchasable() && $bk_product->is_in_stock();
$bk_index    = isset( $args['position'] ) ? (int) $args['position'] + 1 : 0;
$bk_discount = 0;
if ( $bk_product->is_on_sale() && $bk_product->get_regular_price() ) {
	$bk_discount = (int) round( 100 - ( (float) $bk_product->get_sale_price() / (float) $bk_product->get_regular_price() * 100 ) );
}
// Shortest meaningful capacity token for the readout (e.g. "16 قدم - 450 لتر" → "16 قدم").
$bk_cap_short = $bk_capacity ? trim( (string) preg_split( '/\s[-–—]\s/u', $bk_capacity )[0] ) : '—';
$bk_wa_msg    = sprintf( "مرحباً، أريد طلب:\n%s%s\nالسعر: %s\n%s", $bk_product->get_name(), $bk_model ? " ({$bk_model})" : '', bk_format_syp( (float) $bk_product->get_price(), false ), $bk_url );
?>
<article class="group relative flex flex-col overflow-hidden rounded-xl2 border border-brand-line bg-brand-card transition duration-300 hover:border-brand-ink hover:shadow-lift" x-data>
	<div class="bk-grid-paper relative border-b border-brand-line">
		<?php if ( $bk_index ) : ?>
			<span class="bk-mono absolute start-3.5 top-3 z-10 text-[11px] text-brand-muted"><?php echo esc_html( str_pad( (string) $bk_index, 2, '0', STR_PAD_LEFT ) ); ?></span>
		<?php endif; ?>
		<div class="absolute end-2.5 top-2.5 z-10 flex flex-col items-end gap-1.5">
			<?php if ( $bk_discount > 0 ) : ?>
				<span class="bk-badge-sale bk-mono">-<?php echo (int) $bk_discount; ?>%</span>
			<?php endif; ?>
			<?php bk_part( 'template-parts/product/energy-badge', array( 'power' => $bk_power, 'size' => 'sm' ) ); ?>
		</div>

		<a href="<?php echo esc_url( $bk_url ); ?>" class="block aspect-[4/3.4] p-6 sm:p-7" tabindex="-1" aria-hidden="true">
			<?php
			if ( $bk_product->get_image_id() ) {
				echo wp_get_attachment_image(
					$bk_product->get_image_id(),
					'bk-card',
					false,
					array(
						'class'         => 'h-full w-full object-contain mix-blend-multiply transition duration-500 group-hover:scale-[1.05]',
						'alt'           => $bk_product->get_name(),
						'sizes'         => '(min-width: 1280px) 300px, (min-width: 768px) 30vw, 46vw',
						'fetchpriority' => $bk_is_lcp ? 'high' : 'auto',
						'loading'       => $bk_is_lcp ? 'eager' : 'lazy',
					)
				);
			} else {
				// No photo yet: the category's line drawing (datasheet style) instead of a grey box.
				$bk_cat_term = bk_product_primary_category( $bk_id );
				echo '<span class="grid h-full place-items-center text-brand-ink transition duration-500 group-hover:scale-[1.06]">' . bk_icon( $bk_cat_term ? bk_category_icon( $bk_cat_term->term_id ) : 'bolt', 'size-20 stroke-[1.3] sm:size-24' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</a>

		<div class="absolute bottom-2.5 start-2.5 z-10 flex gap-1.5 transition lg:translate-y-1 lg:opacity-0 lg:group-hover:translate-y-0 lg:group-hover:opacity-100 lg:focus-within:opacity-100">
			<button type="button" class="grid size-9 place-items-center rounded-[10px] border border-black/20 bg-brand-card text-brand-ink hover:border-brand-ink" @click="$store.qv.show(<?php echo (int) $bk_id; ?>)" aria-label="<?php esc_attr_e( 'عرض سريع', 'baytkahraba' ); ?>">
				<?php bk_the_icon( 'eye', 'size-[17px]' ); ?>
			</button>
			<button type="button" class="grid size-9 place-items-center rounded-[10px] border transition" :class="$store.compare.has(<?php echo (int) $bk_id; ?>) ? 'border-brand-ink bg-brand-ink text-brand-paper' : 'border-black/20 bg-brand-card text-brand-ink hover:border-brand-ink'"
				@click="$store.compare.toggle(<?php echo (int) $bk_id; ?>)" :aria-pressed="$store.compare.has(<?php echo (int) $bk_id; ?>)" aria-label="<?php esc_attr_e( 'قارن', 'baytkahraba' ); ?>">
				<?php bk_the_icon( 'compare', 'size-[17px]' ); ?>
			</button>
		</div>
	</div>

	<div class="flex flex-1 flex-col gap-2.5 p-4">
		<div class="flex items-center justify-between gap-2 text-[12.5px]">
			<?php if ( $bk_brand ) : ?>
				<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="font-semibold text-brand-ink hover:text-brand-volt-ink"><?php echo esc_html( $bk_brand->name ); ?></a>
			<?php endif; ?>
			<?php if ( $bk_model ) : ?>
				<span class="bk-mono bk-ltr truncate text-[11px] text-brand-muted"><?php echo esc_html( $bk_model ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="m-0 font-sans text-[15px] font-semibold leading-6 tracking-normal">
			<a href="<?php echo esc_url( $bk_url ); ?>" class="line-clamp-2 text-brand-ink hover:text-brand-volt-ink"><?php echo esc_html( $bk_product->get_name() ); ?></a>
		</h3>

		<?php if ( $bk_power['wattage'] || $bk_power['ampere'] ) : ?>
			<div class="bk-readout">
				<div><small><?php esc_html_e( 'الاستطاعة', 'baytkahraba' ); ?></small><strong><?php echo $bk_power['wattage'] ? esc_html( number_format( $bk_power['wattage'] ) . 'W' ) : '—'; ?></strong></div>
				<div><small><?php esc_html_e( 'السحب', 'baytkahraba' ); ?></small><strong><?php echo $bk_power['ampere'] ? esc_html( rtrim( rtrim( number_format( $bk_power['ampere'], 2, '.', '' ), '0' ), '.' ) . 'A' ) : '—'; ?></strong></div>
				<div><small><?php esc_html_e( 'السعة', 'baytkahraba' ); ?></small><strong class="!font-sans !text-[12px]"><?php echo esc_html( $bk_cap_short ); ?></strong></div>
			</div>
			<?php bk_part( 'template-parts/product/energy-scale', array( 'ampere' => $bk_power['ampere'] ) ); ?>
		<?php endif; ?>

		<div class="mt-auto flex items-end justify-between gap-2 pt-1">
			<div class="min-w-0">
				<p class="bk-price text-[20px] leading-tight lg:text-[22px]"><?php echo wp_kses_post( $bk_product->get_price_html() ); ?></p>
				<p class="text-[11px] text-brand-sun-ink"><?php esc_html_e( 'سعر المحل · كفالة الوكيل', 'baytkahraba' ); ?></p>
			</div>
			<div class="flex shrink-0 gap-1.5">
				<a href="<?php echo esc_url( bk_whatsapp_url( $bk_wa_msg ) ); ?>" target="_blank" rel="noopener nofollow" class="grid size-11 place-items-center rounded-[14px] border border-black/15 text-brand-sun transition hover:border-brand-sun hover:bg-brand-sun hover:text-white" aria-label="<?php esc_attr_e( 'اطلب عبر واتساب', 'baytkahraba' ); ?>">
					<?php bk_the_icon( 'whatsapp', 'size-5' ); ?>
				</a>
				<?php if ( $bk_simple ) : ?>
					<button type="button" class="grid size-11 place-items-center rounded-[14px] bg-brand-ink text-brand-paper transition duration-300 hover:rotate-90 hover:bg-brand-volt" @click="$store.cart.add(<?php echo (int) $bk_id; ?>, 1, $el)" aria-label="<?php echo esc_attr( sprintf( __( 'أضف %s للسلة', 'baytkahraba' ), $bk_product->get_name() ) ); ?>">
						<?php bk_the_icon( 'plus', 'size-5' ); ?>
					</button>
				<?php elseif ( $bk_product->is_in_stock() ) : ?>
					<a href="<?php echo esc_url( $bk_url ); ?>" class="grid size-11 place-items-center rounded-[14px] bg-brand-ink text-brand-paper hover:bg-brand-volt" aria-label="<?php esc_attr_e( 'اختر المواصفات', 'baytkahraba' ); ?>"><?php bk_the_icon( 'chevron', 'size-5' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! $bk_product->is_in_stock() ) : ?>
			<p class="rounded-lg bg-brand-paper-2 px-2 py-1 text-center text-xs text-brand-ink-2"><?php esc_html_e( 'نفدت الكمية — اسأل عن التوفر عبر واتساب', 'baytkahraba' ); ?></p>
		<?php endif; ?>
	</div>
</article>
