<?php
/**
 * Mobile menu — native <dialog> side sheet with native <details> groups.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_shop = wc_get_page_permalink( 'shop' );
?>
<dialog id="bk-menu" class="sheet" aria-label="<?php esc_attr_e( 'القائمة', 'baytkahraba' ); ?>">
	<div class="sheet-panel">
		<div class="mb-4 flex items-center justify-between">
			<?php bk_part( 'template-parts/header/logo' ); ?>
			<button type="button" class="icon-btn -me-2" data-close aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
		</div>

		<nav class="faq flex-1" aria-label="<?php esc_attr_e( 'الأقسام', 'baytkahraba' ); ?>">
			<?php foreach ( bk_get_category_tree() as $bk_node ) : ?>
				<?php if ( $bk_node['children'] ) : ?>
					<details>
						<summary class="!py-4 !text-[16px]"><?php echo esc_html( $bk_node['name'] ); ?><span class="plus"><?php bk_the_icon( 'plus', 'size-4' ); ?></span></summary>
						<ul class="space-y-1 pb-4">
							<li><a class="block rounded-xl px-3 py-2 font-medium text-ink hover:bg-stage" href="<?php echo esc_url( $bk_node['url'] ); ?>"><?php esc_html_e( 'كل القسم', 'baytkahraba' ); ?></a></li>
							<?php foreach ( $bk_node['children'] as $bk_child ) : ?>
								<li><a class="block rounded-xl px-3 py-2 text-muted hover:bg-stage hover:text-ink" href="<?php echo esc_url( $bk_child['url'] ); ?>"><?php echo esc_html( $bk_child['name'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php else : ?>
					<a class="block border-b border-line py-4 font-semibold text-ink" href="<?php echo esc_url( $bk_node['url'] ); ?>"><?php echo esc_html( $bk_node['name'] ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>

			<?php $bk_brands = bk_get_brands(); ?>
			<?php if ( $bk_brands ) : ?>
				<p class="mb-2 mt-6 text-[13px] font-medium text-muted"><?php esc_html_e( 'الماركات', 'baytkahraba' ); ?></p>
				<div class="flex flex-wrap gap-2">
					<?php foreach ( $bk_brands as $bk_brand ) : ?>
						<a class="chip" href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>"><?php echo esc_html( $bk_brand->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</nav>

		<div class="mt-6 grid gap-2">
			<a class="badge-sun h-11 justify-center text-[14px]" href="<?php echo esc_url( add_query_arg( 'power', 'solar', $bk_shop ) ); ?>"><?php bk_the_icon( 'sun' ); ?><?php esc_html_e( 'أجهزة الطاقة الشمسية والأمبير', 'baytkahraba' ); ?></a>
			<a class="btn-wa w-full" href="<?php echo bk_esc_wa( bk_general_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php bk_the_icon( 'whatsapp' ); ?><?php esc_html_e( 'راسلنا على واتساب', 'baytkahraba' ); ?></a>
		</div>
	</div>
</dialog>
