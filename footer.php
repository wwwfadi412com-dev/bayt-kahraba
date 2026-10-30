<?php
/**
 * Footer: trust strip, dynamic categories & brands, contact, global UI
 * (cart drawer, compare bar/modal, quick view, toast, WhatsApp float).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
$bk_wc = class_exists( 'WooCommerce' );
?>
</main>

<?php bk_part( 'template-parts/global/trust-bar' ); ?>

<footer class="bg-brand-navy text-slate-300">
	<div class="bk-container grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-12">
		<div class="lg:col-span-4">
			<?php bk_part( 'template-parts/header/logo', array( 'variant' => 'light' ) ); ?>
			<p class="mt-4 max-w-sm text-sm leading-7"><?php esc_html_e( 'شريك تجزئة وجملة مرتبط مباشرة بالوكلاء الرسميين في سوريا. نفس سعر المحل، كفالة حقيقية، وتوصيل مجاني لباب البيت في دمشق وريفها.', 'baytkahraba' ); ?></p>
			<ul class="mt-5 space-y-2 text-sm">
				<li class="flex items-start gap-2"><?php bk_the_icon( 'map', 'size-5 shrink-0 text-brand-amber' ); ?><?php echo esc_html( (string) bk_option( 'address' ) ); ?></li>
				<li class="flex items-center gap-2"><?php bk_the_icon( 'phone', 'size-5 text-brand-amber' ); ?><a class="bk-ltr bk-num hover:text-white" href="tel:<?php echo esc_attr( (string) bk_option( 'phone' ) ); ?>"><?php echo esc_html( (string) bk_option( 'phone' ) ); ?></a></li>
				<li class="flex items-center gap-2"><?php bk_the_icon( 'store', 'size-5 text-brand-amber' ); ?><?php echo esc_html( (string) bk_option( 'opening_hours_label' ) ); ?></li>
			</ul>
		</div>

		<?php if ( $bk_wc ) : ?>
			<div class="lg:col-span-3">
				<p class="mb-4 font-bold text-white"><?php esc_html_e( 'الأقسام', 'baytkahraba' ); ?></p>
				<ul class="space-y-2 text-sm">
					<?php foreach ( array_slice( bk_get_category_tree(), 0, 8 ) as $bk_node ) : ?>
						<li><a class="hover:text-white" href="<?php echo esc_url( $bk_node['url'] ); ?>"><?php echo esc_html( $bk_node['name'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="lg:col-span-2">
				<p class="mb-4 font-bold text-white"><?php esc_html_e( 'الوكلاء والماركات', 'baytkahraba' ); ?></p>
				<ul class="space-y-2 text-sm">
					<?php foreach ( array_slice( bk_get_brands(), 0, 10 ) as $bk_brand ) : ?>
						<li><a class="hover:text-white" href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>"><?php echo esc_html( $bk_brand->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="lg:col-span-3">
			<p class="mb-4 font-bold text-white"><?php esc_html_e( 'خدمة العملاء', 'baytkahraba' ); ?></p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'space-y-2 text-sm [&_a:hover]:text-white',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
			<a href="<?php echo esc_url( bk_whatsapp_url( __( 'مرحباً، أحتاج مساعدة في اختيار جهاز', 'baytkahraba' ) ) ); ?>" target="_blank" rel="noopener" class="bk-btn-emerald mt-5"><?php bk_the_icon( 'whatsapp', 'size-5' ); ?><?php esc_html_e( 'استشارة مجانية على واتساب', 'baytkahraba' ); ?></a>
		</div>
	</div>
	<div class="border-t border-white/10">
		<div class="bk-container flex flex-col items-center justify-between gap-3 py-5 text-xs sm:flex-row">
			<p>© <?php echo esc_html( bk_year() . ' ' . bk_option( 'store_name' ) ); ?> — <?php esc_html_e( 'جميع الحقوق محفوظة', 'baytkahraba' ); ?></p>
			<ul class="flex flex-wrap items-center gap-2">
				<?php foreach ( array( 'الدفع عند الاستلام', 'الهرم', 'الفؤاد', 'سيريتل كاش', 'شام كاش' ) as $bk_pay ) : ?>
					<li class="rounded-md bg-white/10 px-2 py-1"><?php echo esc_html( $bk_pay ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</footer>

<?php
if ( $bk_wc ) {
	bk_part( 'template-parts/global/cart-drawer' );
	bk_part( 'template-parts/global/compare' );
	bk_part( 'template-parts/global/quick-view-modal' );
}
bk_part( 'template-parts/global/toast' );
?>

<a href="<?php echo esc_url( bk_whatsapp_url( __( 'مرحباً، أريد الاستفسار عن جهاز', 'baytkahraba' ) ) ); ?>" target="_blank" rel="noopener"
	class="fixed bottom-5 start-5 z-40 hidden size-14 place-items-center rounded-full bg-brand-emerald text-white shadow-lift transition hover:scale-105 hover:bg-brand-emerald-hover lg:grid"
	aria-label="<?php esc_attr_e( 'تواصل عبر واتساب', 'baytkahraba' ); ?>">
	<?php bk_the_icon( 'whatsapp', 'size-7' ); ?>
</a>

<?php wp_footer(); ?>
</body>
</html>
