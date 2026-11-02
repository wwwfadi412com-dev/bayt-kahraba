<?php
/**
 * Footer — light, four short columns, contact first.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
$bk_wc = class_exists( 'WooCommerce' );
?>
</main>

<footer class="mt-10 border-t border-line bg-stage/60">
	<div class="wrap grid gap-10 py-14 text-[15px] sm:grid-cols-2 lg:grid-cols-12 lg:py-16">
		<div class="lg:col-span-4">
			<?php bk_part( 'template-parts/header/logo' ); ?>
			<p class="mt-4 max-w-sm leading-7 text-muted"><?php echo esc_html( (string) bk_option( 'store_tagline' ) ); ?>. <?php esc_html_e( 'نفس سعر المحل، كفالة الوكيل، وتوصيل لباب البيت.', 'baytkahraba' ); ?></p>
			<ul class="mt-5 space-y-2.5 text-ink-2">
				<li class="flex items-start gap-2.5"><?php bk_the_icon( 'map', 'mt-1 size-[18px] shrink-0' ); ?><?php echo esc_html( (string) bk_option( 'address' ) ); ?></li>
				<li class="flex items-center gap-2.5"><?php bk_the_icon( 'phone', 'size-[18px] shrink-0' ); ?><a class="bk-ltr hover:text-ink" href="tel:<?php echo esc_attr( (string) bk_option( 'phone' ) ); ?>"><?php echo esc_html( (string) bk_option( 'phone' ) ); ?></a></li>
				<li class="flex items-center gap-2.5"><?php bk_the_icon( 'store', 'size-[18px] shrink-0' ); ?><?php echo esc_html( (string) bk_option( 'opening_hours_label' ) ); ?></li>
			</ul>
		</div>

		<?php if ( $bk_wc ) : ?>
			<nav class="lg:col-span-3" aria-label="<?php esc_attr_e( 'الأقسام', 'baytkahraba' ); ?>">
				<p class="mb-4 font-display font-semibold text-ink"><?php esc_html_e( 'الأقسام', 'baytkahraba' ); ?></p>
				<ul class="space-y-2.5 text-muted">
					<?php foreach ( bk_shop_categories( 8 ) as $bk_node ) : ?>
						<li><a class="hover:text-ink" href="<?php echo esc_url( $bk_node['url'] ); ?>"><?php echo esc_html( $bk_node['name'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
			<nav class="lg:col-span-2" aria-label="<?php esc_attr_e( 'الماركات', 'baytkahraba' ); ?>">
				<p class="mb-4 font-display font-semibold text-ink"><?php esc_html_e( 'الماركات', 'baytkahraba' ); ?></p>
				<ul class="space-y-2.5 text-muted">
					<?php foreach ( array_slice( bk_get_brands(), 0, 8 ) as $bk_brand ) : ?>
						<li><a class="hover:text-ink" href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>"><?php echo esc_html( $bk_brand->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<div class="lg:col-span-3">
			<p class="mb-4 font-display font-semibold text-ink"><?php esc_html_e( 'الطلب والتوصيل', 'baytkahraba' ); ?></p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'space-y-2.5 text-muted [&_a:hover]:text-ink',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
			<a href="<?php echo bk_esc_wa( bk_general_whatsapp_url() ); ?>" target="_blank" rel="noopener" class="btn-wa mt-5"><?php bk_the_icon( 'whatsapp' ); ?><?php esc_html_e( 'اسألنا على واتساب', 'baytkahraba' ); ?></a>
		</div>
	</div>
	<div class="wrap flex flex-col items-center justify-between gap-2 border-t border-line py-6 text-[13px] text-muted sm:flex-row">
		<p>© <span class="bk-num"><?php echo esc_html( bk_year() ); ?></span> <?php echo esc_html( (string) bk_option( 'store_name' ) ); ?></p>
		<p><?php esc_html_e( 'الأسعار بالليرة السورية وبتتحدّث يومياً حسب سعر الصرف.', 'baytkahraba' ); ?></p>
		<?php
		$bk_social = array_filter(
			array(
				'Facebook'  => (string) bk_option( 'facebook' ),
				'Instagram' => (string) bk_option( 'instagram' ),
				'Telegram'  => (string) bk_option( 'telegram' ),
			)
		);
		?>
		<?php if ( $bk_social ) : ?>
			<ul class="flex gap-4">
				<?php foreach ( $bk_social as $bk_label => $bk_url ) : ?>
					<li><a class="hover:text-ink" href="<?php echo esc_url( $bk_url ); ?>" target="_blank" rel="noopener me"><?php echo esc_html( $bk_label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</footer>

<?php if ( ! ( function_exists( 'is_product' ) && is_product() ) ) : ?>
	<a href="<?php echo bk_esc_wa( bk_general_whatsapp_url() ); ?>" target="_blank" rel="noopener" class="wa-float" aria-label="<?php esc_attr_e( 'تواصل معنا على واتساب', 'baytkahraba' ); ?>">
		<?php bk_the_icon( 'whatsapp' ); ?>
	</a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
