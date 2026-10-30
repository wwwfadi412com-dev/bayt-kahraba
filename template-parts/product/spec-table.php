<?php
/**
 * Semantic technical & electrical specification table.
 *
 * @var array $args { product: WC_Product, heading: bool }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_product = $args['product'] ?? null;
if ( ! $bk_product instanceof WC_Product ) {
	return;
}
$bk_specs = bk_product_specs( $bk_product );
if ( ! $bk_specs ) {
	return;
}
?>
<?php if ( $args['heading'] ?? true ) : ?>
	<h2 class="bk-section-title mb-4" id="bk-specs"><?php esc_html_e( 'جدول المواصفات التقنية والكهربائية', 'baytkahraba' ); ?></h2>
<?php endif; ?>
<table class="bk-spec-table">
	<caption class="sr-only"><?php echo esc_html( sprintf( __( 'مواصفات %s', 'baytkahraba' ), $bk_product->get_name() ) ); ?></caption>
	<tbody>
		<?php foreach ( $bk_specs as $bk_key => $bk_row ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $bk_row['label'] ); ?></th>
				<td class="bk-spec-value <?php echo in_array( $bk_key, array( 'model' ), true ) ? 'bk-ltr' : ''; ?> <?php echo 'power' === $bk_key ? 'font-semibold text-brand-emerald-hover' : ''; ?>"><?php echo esc_html( $bk_row['value'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
