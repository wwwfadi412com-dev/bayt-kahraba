<?php
/**
 * Mobile off-canvas menu (accordion tree + brands). Rendered inside the
 * header's Alpine scope (`menu`).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
?>
<div x-cloak x-show="menu" class="lg:hidden">
	<div class="bk-overlay" x-show="menu" x-transition.opacity @click="menu = false"></div>
	<aside class="fixed inset-y-0 start-0 z-[70] flex w-[88%] max-w-sm flex-col bg-white text-brand-ink shadow-lift"
		x-show="menu" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
		x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
		x-trap.noscroll="menu" aria-label="<?php esc_attr_e( 'القائمة', 'baytkahraba' ); ?>">
		<div class="flex items-center justify-between border-b border-brand-line p-4">
			<?php bk_part( 'template-parts/header/logo', array( 'show_tagline' => false ) ); ?>
			<button type="button" class="bk-icon-btn" @click="menu = false" aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
		</div>
		<nav class="flex-1 overflow-y-auto p-2" aria-label="<?php esc_attr_e( 'الأقسام', 'baytkahraba' ); ?>">
			<ul>
				<?php foreach ( bk_get_category_tree() as $bk_parent ) : ?>
					<li x-data="{ o: false }" class="border-b border-brand-line last:border-0">
						<div class="flex items-center">
							<a href="<?php echo esc_url( $bk_parent['url'] ); ?>" class="flex-1 px-3 py-3.5 font-semibold"><?php echo esc_html( $bk_parent['name'] ); ?></a>
							<?php if ( $bk_parent['children'] ) : ?>
								<button type="button" class="bk-icon-btn" @click="o = !o" :aria-expanded="o" aria-label="<?php esc_attr_e( 'فتح الأقسام الفرعية', 'baytkahraba' ); ?>">
									<span class="transition" :class="o && 'rotate-180'"><?php bk_the_icon( 'chevron-d', 'size-5' ); ?></span>
								</button>
							<?php endif; ?>
						</div>
						<?php if ( $bk_parent['children'] ) : ?>
							<ul x-show="o" x-collapse class="mb-2 space-y-0.5 rounded-xl bg-brand-stripe p-2 text-sm">
								<?php foreach ( $bk_parent['children'] as $bk_child ) : ?>
									<li>
										<a href="<?php echo esc_url( $bk_child['url'] ); ?>" class="block rounded-lg px-3 py-2 font-medium hover:bg-white"><?php echo esc_html( $bk_child['name'] ); ?> <span class="text-brand-muted bk-num">(<?php echo (int) $bk_child['count']; ?>)</span></a>
										<?php foreach ( $bk_child['children'] as $bk_grand ) : ?>
											<a href="<?php echo esc_url( $bk_grand['url'] ); ?>" class="block rounded-lg py-1.5 pe-3 ps-7 text-brand-body hover:bg-white"><?php echo esc_html( $bk_grand['name'] ); ?></a>
										<?php endforeach; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php $bk_brands = bk_get_brands(); ?>
			<?php if ( $bk_brands ) : ?>
				<p class="bk-eyebrow mt-6 px-3"><?php esc_html_e( 'الماركات والوكلاء', 'baytkahraba' ); ?></p>
				<div class="mt-2 grid grid-cols-3 gap-2 px-2">
					<?php foreach ( $bk_brands as $bk_brand ) : ?>
						<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="grid h-12 place-items-center rounded-xl border border-brand-line p-1.5"><?php echo bk_brand_logo( $bk_brand, 'max-h-8 w-auto object-contain text-xs', 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</nav>
		<div class="grid grid-cols-2 gap-2 border-t border-brand-line p-3 bk-safe-bottom">
			<a href="tel:<?php echo esc_attr( (string) bk_option( 'phone' ) ); ?>" class="bk-btn-ghost"><?php bk_the_icon( 'phone', 'size-4' ); ?><?php esc_html_e( 'اتصل بنا', 'baytkahraba' ); ?></a>
			<a href="<?php echo esc_url( bk_whatsapp_url() ); ?>" class="bk-btn-emerald" target="_blank" rel="noopener"><?php bk_the_icon( 'whatsapp', 'size-4' ); ?><?php esc_html_e( 'واتساب', 'baytkahraba' ); ?></a>
		</div>
	</aside>
</div>
