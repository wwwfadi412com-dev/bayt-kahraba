<?php
/**
 * EU-label style consumption scale positioned by the product's ampere draw.
 *
 * @var array $args { ampere: float, compact: bool }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_amp = (float) ( $args['ampere'] ?? 0 );
if ( $bk_amp <= 0 ) {
	return;
}
$bk_class = bk_energy_class( $bk_amp );
?>
<div class="bk-escale-wrap" title="<?php echo esc_attr( sprintf( __( 'مقياس الاستهلاك: %s', 'baytkahraba' ), $bk_class['label'] ) ); ?>">
	<div class="bk-escale" aria-hidden="true">
		<?php foreach ( $bk_class['colors'] as $bk_i => $bk_color ) : ?>
			<i style="background:<?php echo esc_attr( $bk_color ); ?>" class="<?php echo $bk_i === $bk_class['index'] ? 'is-me' : ''; ?>"></i>
		<?php endforeach; ?>
	</div>
	<div class="mt-1.5 flex justify-between text-[11px] text-brand-muted">
		<span><?php esc_html_e( 'الاستهلاك:', 'baytkahraba' ); ?> <b style="color:<?php echo esc_attr( $bk_class['index'] === 3 ? '#B8A400' : $bk_class['color'] ); ?>"><?php echo esc_html( $bk_class['label'] ); ?></b></span>
		<span class="bk-mono bk-ltr"><?php echo esc_html( rtrim( rtrim( number_format( $bk_amp, 2, '.', '' ), '0' ), '.' ) ); ?>A</span>
	</div>
</div>
