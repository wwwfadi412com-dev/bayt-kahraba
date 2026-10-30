<?php
/**
 * Live store ticker: today's reference exchange rate + promises (stock-ticker style).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_items = array();
if ( function_exists( 'bk_pricing_settings' ) ) {
	$bk_rate    = (float) bk_pricing_settings()['rate'];
	$bk_items[] = array( 'currency-dollar', sprintf( '%s <b class="bk-mono bk-ltr text-brand-volt">%s</b> %s', esc_html__( 'سعر الصرف المعتمد اليوم', 'baytkahraba' ), esc_html( number_format( $bk_rate ) ), esc_html__( 'ل.س', 'baytkahraba' ) ) );
}
$bk_items[] = array( 'truck', esc_html( (string) bk_option( 'top_bar_message' ) ) );
$bk_items[] = array( 'shield', esc_html__( 'كفالة الوكيل الحقيقية على كل جهاز', 'baytkahraba' ) );
if ( function_exists( 'bk_power_friendly_total' ) && ( $bk_power = bk_power_friendly_total() ) ) {
	$bk_items[] = array( 'sun', sprintf( '<b class="bk-mono text-brand-volt">%d</b> %s', (int) $bk_power, esc_html__( 'جهازاً يعمل على الأمبير والطاقة الشمسية', 'baytkahraba' ) ) );
}
$bk_items[] = array( 'cash', esc_html__( 'الدفع عند الاستلام · الهرم · الفؤاد · سيريتل كاش · شام كاش', 'baytkahraba' ) );

$bk_render = static function () use ( $bk_items ): void {
	foreach ( $bk_items as $bk_item ) {
		echo '<span class="inline-flex items-center gap-2">' . bk_icon( $bk_item[0], 'size-4 text-brand-volt' ) . $bk_item[1] . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
	}
};
?>
<div class="bk-ticker overflow-hidden whitespace-nowrap bg-brand-ink text-[12.5px] text-brand-paper" role="marquee" aria-label="<?php esc_attr_e( 'أخبار المتجر', 'baytkahraba' ); ?>">
	<div class="bk-ticker-track">
		<?php $bk_render(); ?>
		<span aria-hidden="true" class="contents"><?php $bk_render(); ?></span>
	</div>
</div>
