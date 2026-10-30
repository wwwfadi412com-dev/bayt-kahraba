<?php
/**
 * "Promise" band: four big, honest numbers (delivery, price parity, warranty, reach).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_months = 24;
if ( function_exists( 'bk_get_brands' ) ) {
	$bk_all = array_map( static fn( $b ) => bk_brand_meta( $b )['warranty_months'], bk_get_brands() );
	$bk_months = $bk_all ? max( $bk_all ) : 24;
}
$bk_items = array(
	array( '0', __( 'ل.س', 'baytkahraba' ), __( 'توصيل داخل دمشق', 'baytkahraba' ), __( 'وريفها القريب، مع فحص الجهاز عند الاستلام', 'baytkahraba' ) ),
	array( '1:1', '', __( 'نفس سعر المحل', 'baytkahraba' ), __( 'محدّث يومياً حسب سعر الصرف', 'baytkahraba' ) ),
	array( (string) $bk_months, 'm', __( 'كفالة الوكيل', 'baytkahraba' ), __( 'بطاقة رسمية وتسجيل إلكتروني', 'baytkahraba' ) ),
	array( '14', '', __( 'محافظة نشحن إليها', 'baytkahraba' ), __( 'بتغليف آمن وسعر رمزي', 'baytkahraba' ) ),
);
?>
<section class="bk-container py-10" aria-label="<?php esc_attr_e( 'وعودنا', 'baytkahraba' ); ?>">
	<ul class="grid grid-cols-2 overflow-hidden rounded-3xl border-[1.5px] border-brand-ink bg-brand-card lg:grid-cols-4">
		<?php foreach ( $bk_items as $bk_i => $bk_item ) : ?>
			<li class="p-5 lg:p-7 <?php echo $bk_i % 2 ? 'border-s border-brand-line' : ''; ?> <?php echo $bk_i > 1 ? 'border-t border-brand-line lg:border-t-0' : ''; ?> <?php echo 2 === $bk_i ? 'lg:border-s' : ''; ?>">
				<strong class="block font-display text-[34px] font-black leading-none tracking-[-.04em] text-brand-ink lg:text-[46px]">
					<?php if ( preg_match( '/^[a-z]+$/i', $bk_item[1] ) ) : // Latin unit stays glued to the number (LTR). ?>
						<span class="bk-ltr"><?php echo esc_html( $bk_item[0] ); ?><em class="not-italic text-brand-volt text-[.7em]"><?php echo esc_html( $bk_item[1] ); ?></em></span>
					<?php else : ?>
						<span class="bk-ltr"><?php echo esc_html( $bk_item[0] ); ?></span><?php if ( $bk_item[1] ) : ?> <em class="not-italic text-brand-volt text-[.7em]"><?php echo esc_html( $bk_item[1] ); ?></em><?php endif; ?>
					<?php endif; ?>
				</strong>
				<span class="mt-3 block font-semibold text-brand-ink"><?php echo esc_html( $bk_item[2] ); ?></span>
				<small class="text-[13px] text-brand-muted"><?php echo esc_html( $bk_item[3] ); ?></small>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
