<?php
/**
 * 404 — guide the visitor back to search and categories.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap py-20 text-center lg:py-28">
	<p class="font-display text-[88px] font-extrabold leading-none text-ink"><span class="mark bk-num">404</span></p>
	<h1 class="mt-6 font-display text-[26px] font-semibold lg:text-[34px]"><?php esc_html_e( 'الصفحة مو موجودة — بس الأجهزة موجودة!', 'baytkahraba' ); ?></h1>
	<p class="mx-auto mt-3 max-w-xl text-muted"><?php esc_html_e( 'يمكن تغيّر الرابط أو خلص الموديل. ابحث عن الجهاز اللي بدك ياه أو تصفح الأقسام:', 'baytkahraba' ); ?></p>
	<button type="button" class="search-trigger mx-auto mt-8 !flex" data-open="bk-search"><?php bk_the_icon( 'search', 'size-[18px]' ); ?><span><?php esc_html_e( 'ابحث عن جهاز…', 'baytkahraba' ); ?></span></button>
	<?php if ( function_exists( 'bk_shop_categories' ) ) : ?>
		<ul class="mx-auto mt-8 flex max-w-3xl flex-wrap justify-center gap-2">
			<?php foreach ( bk_shop_categories() as $bk_node ) : ?>
				<li><a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="chip"><?php echo esc_html( $bk_node['name'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
<?php
get_footer();
