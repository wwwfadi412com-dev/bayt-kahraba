<?php
/**
 * Four trust promises shown above the footer and on the home page.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_items = array(
	array( 'store', __( 'نفس سعر المحل', 'baytkahraba' ), __( 'بدون أي زيادة على الطلب أونلاين', 'baytkahraba' ) ),
	array( 'shield', __( 'كفالة الوكيل الحقيقية', 'baytkahraba' ), __( 'بطاقة كفالة رسمية مع كل جهاز', 'baytkahraba' ) ),
	array( 'truck', __( 'توصيل مجاني في دمشق', 'baytkahraba' ), __( 'وشحن رمزي لكل المحافظات', 'baytkahraba' ) ),
	array( 'cash', __( 'الدفع عند الاستلام', 'baytkahraba' ), __( 'أو عبر الهرم، الفؤاد، سيريتل وشام كاش', 'baytkahraba' ) ),
);
?>
<section class="border-y border-brand-line bg-white" aria-label="<?php esc_attr_e( 'لماذا نحن', 'baytkahraba' ); ?>">
	<ul class="bk-container grid grid-cols-2 gap-4 py-6 lg:grid-cols-4">
		<?php foreach ( $bk_items as $bk_item ) : ?>
			<li class="flex items-center gap-3">
				<span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-royal-50 text-brand-royal"><?php bk_the_icon( $bk_item[0], 'size-6' ); ?></span>
				<span>
					<span class="block text-sm font-bold text-brand-ink"><?php echo esc_html( $bk_item[1] ); ?></span>
					<span class="block text-xs text-brand-muted"><?php echo esc_html( $bk_item[2] ); ?></span>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
