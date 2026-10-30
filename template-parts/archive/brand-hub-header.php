<?php
/**
 * Brand Experience Hub header + live category tab bar.
 *
 * @var array $args { brand: WP_Term, active_cat: ?WP_Term, compact: bool }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_brand = $args['brand'] ?? null;
if ( ! $bk_brand instanceof WP_Term ) {
	return;
}
$bk_meta    = bk_brand_meta( $bk_brand );
$bk_active  = $args['active_cat'] ?? null;
$bk_compact = ! empty( $args['compact'] );
$bk_tabs    = bk_get_brand_hub_tabs( $bk_brand->term_id );
$bk_accent  = $bk_meta['color'] ?: '#1E3A8A';
?>
<section class="relative overflow-hidden bg-brand-navy text-white" style="--bk-accent: <?php echo esc_attr( $bk_accent ); ?>">
	<?php if ( $bk_meta['banner_id'] && ! $bk_compact ) : ?>
		<?php echo wp_get_attachment_image( $bk_meta['banner_id'], 'bk-banner', false, array( 'class' => 'absolute inset-0 size-full object-cover opacity-30', 'fetchpriority' => 'high', 'alt' => '' ) ); ?>
	<?php endif; ?>
	<div class="absolute inset-0 bg-gradient-to-l from-brand-navy via-brand-navy/90 to-[color:var(--bk-accent)]/60"></div>
	<div class="bk-container relative grid gap-6 <?php echo $bk_compact ? 'py-6' : 'py-10 lg:grid-cols-12 lg:items-center lg:py-14'; ?>">
		<div class="flex items-center gap-5 <?php echo $bk_compact ? '' : 'lg:col-span-7'; ?>">
			<span class="grid size-20 shrink-0 place-items-center rounded-2xl bg-white p-3 shadow-lift lg:size-24">
				<?php echo bk_brand_logo( $bk_brand, 'max-h-full w-auto object-contain text-sm', 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</span>
			<div>
				<span class="bk-badge-royal mb-2 bg-white/15"><?php bk_the_icon( 'shield', 'size-3.5' ); ?><?php echo esc_html( $bk_meta['agent'] ); ?></span>
				<p class="text-2xl font-bold lg:text-3xl"><?php echo esc_html( $bk_brand->name ); ?></p>
				<p class="mt-1 text-sm text-slate-300"><span class="bk-num"><?php echo (int) ( bk_get_catalog_matrix()['brand_totals'][ $bk_brand->term_id ] ?? 0 ); ?></span> <?php esc_html_e( 'منتج أصلي متوفر', 'baytkahraba' ); ?></p>
			</div>
		</div>
		<?php if ( ! $bk_compact ) : ?>
			<dl class="grid gap-3 text-sm sm:grid-cols-2 lg:col-span-5">
				<div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
					<dt class="mb-1 flex items-center gap-2 text-xs text-slate-300"><?php bk_the_icon( 'shield', 'size-4 text-brand-amber' ); ?><?php esc_html_e( 'الكفالة', 'baytkahraba' ); ?></dt>
					<dd class="font-semibold"><?php echo esc_html( $bk_meta['warranty_text'] ); ?></dd>
				</div>
				<div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
					<dt class="mb-1 flex items-center gap-2 text-xs text-slate-300"><?php bk_the_icon( 'wrench', 'size-4 text-brand-amber' ); ?><?php esc_html_e( 'مراكز الصيانة', 'baytkahraba' ); ?></dt>
					<dd class="font-semibold"><?php echo esc_html( $bk_meta['service_centers'] ? implode( '، ', array_unique( wp_list_pluck( $bk_meta['service_centers'], 'city' ) ) ) : __( 'دمشق وكافة المحافظات', 'baytkahraba' ) ); ?></dd>
				</div>
			</dl>
		<?php endif; ?>
	</div>
</section>

<?php if ( $bk_tabs ) : ?>
	<nav class="sticky top-[var(--bk-header-h)] z-30 border-b border-brand-line bg-white/95 backdrop-blur lg:top-[calc(var(--bk-header-h)+3rem)]" aria-label="<?php printf( esc_attr__( 'أقسام %s', 'baytkahraba' ), esc_attr( $bk_brand->name ) ); ?>">
		<ul class="bk-container bk-scrollbar-none flex gap-1 overflow-x-auto py-2 text-sm font-semibold">
			<li>
				<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="inline-flex whitespace-nowrap rounded-full px-4 py-2 <?php echo $bk_active ? 'text-brand-body hover:bg-brand-stripe' : 'bg-brand-navy text-white'; ?>">
					<?php esc_html_e( 'الكل', 'baytkahraba' ); ?>
				</a>
			</li>
			<?php foreach ( $bk_tabs as $bk_tab ) : ?>
				<?php $bk_is_active = $bk_active && $bk_active->term_id === $bk_tab['term']->term_id; ?>
				<li>
					<a href="<?php echo esc_url( bk_silo_url( $bk_brand, $bk_tab['term'] ) ); ?>" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 <?php echo $bk_is_active ? 'bg-brand-navy text-white' : 'text-brand-body hover:bg-brand-stripe'; ?>">
						<?php echo esc_html( $bk_tab['term']->name . ' ' . $bk_brand->name ); ?>
						<span class="text-xs opacity-70 bk-num">(<?php echo (int) $bk_tab['count']; ?>)</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
<?php endif; ?>
