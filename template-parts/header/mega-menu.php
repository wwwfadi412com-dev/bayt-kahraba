<?php
/**
 * RTL mega menu: Parent → Child → Sub-child + brands inside each parent.
 * Fully generated from the category tree & brand matrix (cached as HTML,
 * invalidated automatically when categories, brands or products change).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_tree = bk_get_category_tree();
if ( ! $bk_tree ) {
	return;
}
?>
<ul class="bk-container bk-scrollbar-none flex items-stretch gap-0.5 overflow-x-auto whitespace-nowrap text-[13px] font-medium xl:gap-1 xl:overflow-visible xl:text-[14px]">
	<?php foreach ( $bk_tree as $bk_parent ) : ?>
		<?php $bk_brands = bk_get_category_brands( $bk_parent['id'], 8 ); ?>
		<li x-data="{ open: false, t: null }"
			@mouseenter="clearTimeout(t); t = setTimeout(() => open = true, 90)"
			@mouseleave="clearTimeout(t); t = setTimeout(() => open = false, 120)"
			@focusin="open = true" @focusout="$el.contains($event.relatedTarget) || (open = false)">
			<a href="<?php echo esc_url( $bk_parent['url'] ); ?>" class="flex h-12 items-center gap-1 rounded-t-lg px-2.5 text-slate-200 transition hover:text-white xl:px-3" :class="open && 'bg-white !text-brand-ink'" :aria-expanded="open">
				<?php echo esc_html( $bk_parent['name'] ); ?>
				<?php if ( $bk_parent['children'] ) : ?>
					<?php bk_the_icon( 'chevron-d', 'size-4 opacity-60' ); ?>
				<?php endif; ?>
			</a>
			<?php if ( $bk_parent['children'] || $bk_brands ) : ?>
				<div x-cloak x-show="open" x-transition.opacity.duration.120ms class="absolute inset-x-0 top-full z-50 border-t border-brand-line bg-white text-brand-ink shadow-lift">
					<div class="bk-container grid grid-cols-12 gap-8 py-8">
						<div class="col-span-8 grid grid-cols-3 gap-x-8 gap-y-6">
							<?php foreach ( $bk_parent['children'] as $bk_child ) : ?>
								<div>
									<a href="<?php echo esc_url( $bk_child['url'] ); ?>" class="group flex items-center gap-2 font-bold text-brand-ink hover:text-brand-royal">
										<?php if ( $bk_child['image_id'] ) : ?>
											<?php echo wp_get_attachment_image( $bk_child['image_id'], 'bk-thumb', false, array( 'class' => 'size-9 rounded-lg bg-brand-stripe object-contain', 'loading' => 'lazy' ) ); ?>
										<?php endif; ?>
										<span><?php echo esc_html( $bk_child['name'] ); ?></span>
										<span class="text-xs font-normal text-brand-muted bk-num">(<?php echo (int) $bk_child['count']; ?>)</span>
									</a>
									<?php if ( $bk_child['children'] ) : ?>
										<ul class="mt-2 space-y-1.5 border-s-2 border-brand-line ps-3 text-sm">
											<?php foreach ( $bk_child['children'] as $bk_grand ) : ?>
												<li><a href="<?php echo esc_url( $bk_grand['url'] ); ?>" class="text-brand-body hover:text-brand-royal"><?php echo esc_html( $bk_grand['name'] ); ?></a></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
							<a href="<?php echo esc_url( $bk_parent['url'] ); ?>" class="col-span-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-royal hover:text-brand-amber-hover">
								<?php printf( esc_html__( 'كل %1$s (%2$d منتج)', 'baytkahraba' ), esc_html( $bk_parent['name'] ), (int) $bk_parent['count'] ); ?>
								<?php bk_the_icon( 'chevron', 'size-4' ); ?>
							</a>
						</div>
						<div class="col-span-4 space-y-5 border-s border-brand-line ps-8">
							<?php if ( $bk_brands ) : ?>
								<div>
									<p class="bk-eyebrow mb-3"><?php printf( esc_html__( 'ماركات %s', 'baytkahraba' ), esc_html( $bk_parent['name'] ) ); ?></p>
									<div class="grid grid-cols-4 gap-2">
										<?php foreach ( $bk_brands as $bk_row ) : ?>
											<a href="<?php echo esc_url( bk_silo_url( $bk_row['term'], $bk_parent['id'] ) ); ?>" class="grid h-14 place-items-center rounded-xl border border-brand-line bg-white p-2 transition hover:border-brand-royal hover:shadow-card" title="<?php echo esc_attr( $bk_parent['name'] . ' ' . $bk_row['term']->name ); ?>">
												<?php echo bk_brand_logo( $bk_row['term'], 'max-h-9 w-auto object-contain text-xs', 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
							<a href="<?php echo esc_url( add_query_arg( 'power', 'solar', $bk_parent['url'] ) ); ?>" class="flex items-center gap-3 rounded-2xl bg-brand-emerald-50 p-4 ring-1 ring-brand-emerald-100 transition hover:ring-brand-emerald">
								<span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-emerald text-white"><?php bk_the_icon( 'sun', 'size-6' ); ?></span>
								<span>
									<span class="block font-bold text-brand-ink"><?php esc_html_e( 'يعمل على الانفرتر والطاقة الشمسية', 'baytkahraba' ); ?></span>
									<span class="text-xs text-brand-muted"><?php printf( esc_html__( 'أجهزة %s بسحب أمبير منخفض', 'baytkahraba' ), esc_html( $bk_parent['name'] ) ); ?></span>
								</span>
							</a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
	<li class="ms-auto flex items-center">
		<a href="<?php echo esc_url( add_query_arg( 'power', 'solar', wc_get_page_permalink( 'shop' ) ) ); ?>" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-brand-emerald/15 px-3 text-sm font-semibold text-emerald-300 hover:bg-brand-emerald hover:text-white">
			<?php bk_the_icon( 'sun', 'size-4' ); ?><?php esc_html_e( 'أجهزة الطاقة الشمسية', 'baytkahraba' ); ?>
		</a>
	</li>
</ul>
