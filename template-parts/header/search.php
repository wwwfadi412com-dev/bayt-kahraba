<?php
/**
 * Search dialog — a real GET form (works without JS), live results when JS runs.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="bk-search" class="sheet" aria-label="<?php esc_attr_e( 'البحث', 'baytkahraba' ); ?>">
	<div class="search-panel">
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
			<label class="search-field flex-1">
				<?php bk_the_icon( 'search', 'size-5 shrink-0 text-muted' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'ابحث عن جهاز', 'baytkahraba' ); ?></span>
				<input type="search" name="s" autocomplete="off" enterkeyhint="search" placeholder="<?php esc_attr_e( 'براد، غسالة، شاشة 55، مكيف إنفرتر…', 'baytkahraba' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<input type="hidden" name="post_type" value="product">
				<?php endif; ?>
			</label>
			<button type="button" class="icon-btn" data-close aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
		</form>
		<div class="mt-3 max-h-[60vh] overflow-y-auto" data-results aria-live="polite"></div>
	</div>
</dialog>
