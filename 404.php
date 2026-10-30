<?php
/**
 * 404 — guide the visitor back to categories and search.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="bk-container py-16 text-center lg:py-24">
	<p class="text-7xl font-bold text-brand-amber bk-num">404</p>
	<h1 class="mt-4 text-2xl lg:text-3xl"><?php esc_html_e( 'الصفحة غير موجودة — لكن الأجهزة موجودة!', 'baytkahraba' ); ?></h1>
	<p class="mx-auto mt-3 max-w-xl text-brand-body"><?php esc_html_e( 'ربما تغيّر الرابط أو نفد الموديل. ابحث عن الجهاز الذي تريده أو تصفح الأقسام:', 'baytkahraba' ); ?></p>
	<div class="mx-auto mt-6 max-w-xl"><?php bk_part( 'template-parts/header/search' ); ?></div>
	<?php if ( function_exists( 'bk_get_category_tree' ) ) : ?>
		<ul class="mx-auto mt-8 flex max-w-3xl flex-wrap justify-center gap-2">
			<?php foreach ( bk_get_category_tree() as $bk_node ) : ?>
				<li><a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="bk-btn-ghost"><?php echo esc_html( $bk_node['name'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
<?php
get_footer();
