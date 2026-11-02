<?php
/**
 * Brand hub header: agent facts + category tabs (brand × category silos).
 *
 * @var array $args { brand: WP_Term, active_cat: ?WP_Term, compact: bool }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_brand = $args['brand'] ?? null;
if ( ! $bk_brand instanceof WP_Term ) {
	return;
}
$bk_meta   = bk_brand_meta( $bk_brand );
$bk_active = $args['active_cat'] ?? null;
$bk_tabs   = bk_get_brand_hub_tabs( $bk_brand->term_id );
?>
<section class="wrap pt-6">
	<div class="flex flex-col gap-6 rounded-[28px] bg-stage p-6 lg:flex-row lg:items-center lg:justify-between lg:p-8">
		<div class="flex items-center gap-4">
			<?php if ( $bk_meta['logo_id'] ) : ?>
				<span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white p-2">
					<?php echo wp_get_attachment_image( $bk_meta['logo_id'], 'bk-thumb', false, array( 'class' => 'max-h-full w-auto object-contain', 'alt' => $bk_brand->name ) ); ?>
				</span>
			<?php endif; ?>
			<div>
				<p class="font-display text-[28px] font-bold leading-tight text-ink"><?php echo esc_html( $bk_brand->name ); ?></p>
				<p class="text-[14px] text-muted"><?php echo esc_html( $bk_meta['agent'] ); ?></p>
			</div>
		</div>
		<dl class="grid grid-cols-3 gap-6 text-[14px]">
			<div><dt class="text-muted"><?php esc_html_e( 'الكفالة', 'baytkahraba' ); ?></dt><dd class="font-display text-[18px] font-semibold text-ink"><?php echo esc_html( sprintf( /* translators: %d months */ __( '%d شهر', 'baytkahraba' ), (int) $bk_meta['warranty_months'] ) ); ?></dd></div>
			<div><dt class="text-muted"><?php esc_html_e( 'مراكز صيانة', 'baytkahraba' ); ?></dt><dd class="font-display text-[18px] font-semibold text-ink bk-num"><?php echo (int) count( $bk_meta['service_centers'] ); ?></dd></div>
			<div><dt class="text-muted"><?php esc_html_e( 'الموديلات', 'baytkahraba' ); ?></dt><dd class="font-display text-[18px] font-semibold text-ink bk-num"><?php echo (int) $bk_meta['count']; ?></dd></div>
		</dl>
	</div>
	<?php if ( count( $bk_tabs ) > 1 ) : ?>
		<nav class="rail mt-5" aria-label="<?php esc_attr_e( 'أقسام الماركة', 'baytkahraba' ); ?>">
			<a href="<?php echo esc_url( $bk_meta['url'] ); ?>" class="chip<?php echo $bk_active ? '' : ' is-active'; ?>"><?php esc_html_e( 'الكل', 'baytkahraba' ); ?></a>
			<?php foreach ( $bk_tabs as $bk_tab ) : ?>
				<a href="<?php echo esc_url( bk_silo_url( $bk_brand, $bk_tab['term'] ) ); ?>" class="chip<?php echo ( $bk_active && (int) $bk_active->term_id === (int) $bk_tab['term']->term_id ) ? ' is-active' : ''; ?>"><?php echo esc_html( $bk_tab['term']->name ); ?> <span class="text-muted bk-num"><?php echo (int) $bk_tab['count']; ?></span></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
</section>
