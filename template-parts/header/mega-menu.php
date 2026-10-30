<?php
/**
 * Mega menu: Parent → Child → Sub-child + agents present in each parent.
 * Generated from the category tree & brand matrix (cached HTML, auto-invalidated).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_tree = bk_get_category_tree();
if ( ! $bk_tree ) {
	return;
}
$bk_total = count( $bk_tree );
?>
<ul class="bk-container bk-scrollbar-none flex h-14 items-center gap-1 overflow-x-auto whitespace-nowrap text-[14px] font-medium xl:overflow-visible">
	<?php foreach ( $bk_tree as $bk_i => $bk_parent ) : ?>
		<?php $bk_brands = bk_get_category_brands( $bk_parent['id'], 8 ); ?>
		<li x-data="{ open: false, t: null }"
			@mouseenter="clearTimeout(t); t = setTimeout(() => open = true, 90)"
			@mouseleave="clearTimeout(t); t = setTimeout(() => open = false, 140)"
			@focusin="open = true" @focusout="$el.contains($event.relatedTarget) || (open = false)">
			<a href="<?php echo esc_url( $bk_parent['url'] ); ?>" class="flex h-9 items-center gap-1.5 rounded-full px-3.5 transition" :class="open ? 'bg-brand-ink text-brand-paper' : 'text-brand-ink hover:bg-brand-paper-2'" :aria-expanded="open">
				<?php echo esc_html( $bk_parent['name'] ); ?>
				<?php if ( $bk_parent['children'] ) : ?>
					<?php bk_the_icon( 'chevron-d', 'size-3.5 opacity-60' ); ?>
				<?php endif; ?>
			</a>
			<?php if ( $bk_parent['children'] || $bk_brands ) : ?>
				<div x-cloak x-show="open" x-transition.opacity.duration.150ms class="absolute inset-x-0 top-full z-50 border-y border-brand-ink bg-brand-paper text-brand-ink shadow-lift">
					<div class="bk-container grid grid-cols-12 gap-8 py-9">
						<div class="col-span-8">
							<div class="mb-6 flex items-end justify-between border-b border-brand-line pb-4">
								<div>
									<span class="bk-kicker bk-ltr"><?php echo esc_html( sprintf( '%02d / %02d', $bk_i + 1, $bk_total ) ); ?></span>
									<p class="font-display text-[28px] font-extrabold leading-tight tracking-[-.02em]"><?php echo esc_html( $bk_parent['name'] ); ?></p>
								</div>
								<a href="<?php echo esc_url( $bk_parent['url'] ); ?>" class="inline-flex items-center gap-1.5 border-b-[1.5px] border-brand-ink pb-0.5 text-sm font-semibold hover:border-brand-volt hover:text-brand-volt-ink">
									<?php printf( esc_html__( 'كل القسم (%d)', 'baytkahraba' ), (int) $bk_parent['count'] ); ?><?php bk_the_icon( 'chevron', 'size-4' ); ?>
								</a>
							</div>
							<div class="grid grid-cols-3 gap-x-8 gap-y-6">
								<?php foreach ( $bk_parent['children'] as $bk_child ) : ?>
									<div>
										<a href="<?php echo esc_url( $bk_child['url'] ); ?>" class="group flex items-baseline justify-between gap-2 font-semibold hover:text-brand-volt-ink">
											<span><?php echo esc_html( $bk_child['name'] ); ?></span>
											<span class="bk-mono text-xs font-normal text-brand-muted"><?php echo (int) $bk_child['count']; ?></span>
										</a>
										<?php if ( $bk_child['children'] ) : ?>
											<ul class="mt-2 space-y-1.5 border-s border-brand-line ps-3 text-sm">
												<?php foreach ( $bk_child['children'] as $bk_grand ) : ?>
													<li><a href="<?php echo esc_url( $bk_grand['url'] ); ?>" class="text-brand-ink-2 hover:text-brand-volt-ink"><?php echo esc_html( $bk_grand['name'] ); ?></a></li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="col-span-4 space-y-4">
							<?php if ( $bk_brands ) : ?>
								<div class="rounded-xl2 border border-brand-line bg-brand-card p-4">
									<p class="bk-kicker mb-3"><?php esc_html_e( 'Official agents · الوكلاء', 'baytkahraba' ); ?></p>
									<ul class="divide-y divide-brand-line">
										<?php foreach ( $bk_brands as $bk_row ) : ?>
											<li>
												<a href="<?php echo esc_url( bk_silo_url( $bk_row['term'], $bk_parent['id'] ) ); ?>" class="flex items-center justify-between py-2 font-display text-[17px] font-extrabold hover:text-brand-volt-ink">
													<?php echo esc_html( $bk_row['term']->name ); ?>
													<span class="bk-mono text-xs font-normal text-brand-muted"><?php echo (int) $bk_row['count']; ?></span>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<a href="<?php echo esc_url( add_query_arg( 'power', 'solar', $bk_parent['url'] ) ); ?>" class="group flex items-center gap-3 rounded-xl2 bg-brand-sun p-4 text-white transition hover:bg-brand-sun-ink">
								<span class="grid size-11 shrink-0 place-items-center rounded-xl bg-white/15"><?php bk_the_icon( 'sun', 'size-6' ); ?></span>
								<span>
									<span class="block font-display text-[15px] font-extrabold"><?php esc_html_e( 'يعمل على الأمبير والطاقة الشمسية', 'baytkahraba' ); ?></span>
									<span class="text-xs text-white/80"><?php printf( esc_html__( 'أجهزة %s بسحب منخفض', 'baytkahraba' ), esc_html( $bk_parent['name'] ) ); ?></span>
								</span>
							</a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
	<li class="ms-auto flex items-center gap-1">
		<?php $bk_bundle = bk_page_by_template( 'page-bundle-builder.php' ); ?>
		<?php if ( $bk_bundle ) : ?>
			<a href="<?php echo esc_url( (string) get_permalink( $bk_bundle ) ); ?>" class="inline-flex h-9 items-center gap-1.5 rounded-full px-3.5 text-brand-ink hover:bg-brand-paper-2"><?php bk_the_icon( 'gift', 'size-4 text-brand-volt' ); ?><?php esc_html_e( 'جهّز بيتك', 'baytkahraba' ); ?></a>
		<?php endif; ?>
		<a href="<?php echo esc_url( add_query_arg( 'power', 'solar', wc_get_page_permalink( 'shop' ) ) ); ?>" class="inline-flex h-9 items-center gap-1.5 rounded-full bg-brand-sun px-3.5 font-semibold text-white hover:bg-brand-sun-ink">
			<?php bk_the_icon( 'sun', 'size-4' ); ?><?php esc_html_e( 'أجهزة الطاقة الشمسية', 'baytkahraba' ); ?>
		</a>
	</li>
</ul>
