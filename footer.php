<?php
/**
 * Footer — ink slab with dynamic categories & agents and a giant wordmark,
 * plus global UI (cart drawer, compare, quick view, toast, WhatsApp).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
$bk_wc = class_exists( 'WooCommerce' );
?>
</main>

<?php if ( ! is_front_page() ) : ?>
	<?php bk_part( 'template-parts/global/trust-bar' ); ?>
<?php endif; ?>

<footer class="mt-6 overflow-hidden bg-brand-ink pt-14 text-[#BDB7AA]">
	<div class="bk-container">
		<div class="grid gap-10 text-sm sm:grid-cols-2 lg:grid-cols-12">
			<div class="lg:col-span-4">
				<?php bk_part( 'template-parts/header/logo', array( 'variant' => 'light' ) ); ?>
				<p class="mt-5 max-w-sm leading-7"><?php esc_html_e( 'شريك تجزئة وجملة مرتبط مباشرة بالوكلاء الرسميين في سوريا. نفس سعر المحل، كفالة حقيقية، وتوصيل مجاني لباب البيت في دمشق وريفها.', 'baytkahraba' ); ?></p>
				<ul class="mt-5 space-y-2">
					<li class="flex items-start gap-2"><?php bk_the_icon( 'map', 'size-5 shrink-0 text-brand-volt' ); ?><?php echo esc_html( (string) bk_option( 'address' ) ); ?></li>
					<li class="flex items-center gap-2"><?php bk_the_icon( 'phone', 'size-5 text-brand-volt' ); ?><a class="bk-mono bk-ltr text-white hover:text-brand-volt" href="tel:<?php echo esc_attr( (string) bk_option( 'phone' ) ); ?>"><?php echo esc_html( (string) bk_option( 'phone' ) ); ?></a></li>
					<li class="flex items-center gap-2"><?php bk_the_icon( 'store', 'size-5 text-brand-volt' ); ?><?php echo esc_html( (string) bk_option( 'opening_hours_label' ) ); ?></li>
				</ul>
			</div>

			<?php if ( $bk_wc ) : ?>
				<div class="lg:col-span-3">
					<p class="bk-kicker mb-4 !text-[#9C978C]">Catalog · الأقسام</p>
					<ul class="space-y-2">
						<?php foreach ( array_slice( bk_get_category_tree(), 0, 8 ) as $bk_node ) : ?>
							<li><a class="hover:text-white" href="<?php echo esc_url( $bk_node['url'] ); ?>"><?php echo esc_html( $bk_node['name'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="lg:col-span-2">
					<p class="bk-kicker mb-4 !text-[#9C978C]">Agents · الوكلاء</p>
					<ul class="space-y-2">
						<?php foreach ( array_slice( bk_get_brands(), 0, 10 ) as $bk_brand ) : ?>
							<li><a class="hover:text-white" href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>"><?php echo esc_html( $bk_brand->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="lg:col-span-3">
				<p class="bk-kicker mb-4 !text-[#9C978C]">Service · خدمة العملاء</p>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'space-y-2 [&_a:hover]:text-white',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
				<a href="<?php echo esc_url( bk_whatsapp_url( __( 'مرحباً، أحتاج مساعدة في اختيار جهاز', 'baytkahraba' ) ) ); ?>" target="_blank" rel="noopener" class="bk-btn-emerald mt-5"><?php bk_the_icon( 'whatsapp', 'size-5' ); ?><?php esc_html_e( 'استشارة مجانية على واتساب', 'baytkahraba' ); ?></a>
			</div>
		</div>

		<div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-white/10 py-5 text-xs sm:flex-row">
			<p>© <span class="bk-mono"><?php echo esc_html( bk_year() ); ?></span> <?php echo esc_html( (string) bk_option( 'store_name' ) ); ?> — <?php esc_html_e( 'جميع الحقوق محفوظة', 'baytkahraba' ); ?></p>
			<ul class="flex flex-wrap items-center gap-2">
				<?php foreach ( array( 'الدفع عند الاستلام', 'الهرم', 'الفؤاد', 'سيريتل كاش', 'شام كاش' ) as $bk_pay ) : ?>
					<li class="rounded-md border border-white/15 px-2 py-1"><?php echo esc_html( $bk_pay ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<p class="select-none whitespace-nowrap px-4 font-display text-[clamp(64px,15vw,230px)] font-black leading-[.85] tracking-[-.05em] text-[#1E1E1E]" aria-hidden="true"><?php echo esc_html( (string) bk_option( 'store_name' ) ); ?><span class="text-brand-volt">.</span></p>
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
	class="fixed bottom-5 start-5 z-40 hidden h-14 items-center gap-2.5 rounded-full bg-brand-sun pe-5 ps-2 font-semibold text-white shadow-[0_14px_30px_-12px_rgba(18,161,80,.7)] transition hover:-translate-y-0.5 hover:bg-brand-sun-ink lg:inline-flex"
	aria-label="<?php esc_attr_e( 'تواصل عبر واتساب', 'baytkahraba' ); ?>">
	<span class="grid size-10 place-items-center rounded-full bg-white/20"><?php bk_the_icon( 'whatsapp', 'size-6' ); ?></span>
	<?php esc_html_e( 'اطلب عبر واتساب', 'baytkahraba' ); ?>
</a>

<?php wp_footer(); ?>
</body>
</html>
