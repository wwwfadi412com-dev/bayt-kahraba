<?php
/**
 * Energy & Solar badge (Feature 1).
 *
 * @var array $args { power: array (bk_get_product_power), size: 'sm'|'lg' }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_power = $args['power'] ?? array();
if ( empty( $bk_power['class'] ) ) {
	return;
}
$bk_large = 'lg' === ( $args['size'] ?? 'sm' );
?>
<?php if ( $bk_large ) : ?>
	<div class="flex items-center gap-3 rounded-2xl p-3 ring-1 <?php echo 'standard_grid' === $bk_power['class'] ? 'bg-slate-50 ring-slate-200' : 'bg-brand-emerald-50 ring-brand-emerald-100'; ?>">
		<span class="grid size-11 shrink-0 place-items-center rounded-xl <?php echo 'standard_grid' === $bk_power['class'] ? 'bg-slate-200 text-slate-600' : 'bg-brand-emerald text-white'; ?>">
			<?php bk_the_icon( $bk_power['icon'], 'size-6' ); ?>
		</span>
		<div class="min-w-0">
			<p class="text-sm font-bold text-brand-ink"><?php echo esc_html( $bk_power['label'] ); ?></p>
			<p class="text-xs text-brand-body">
				<?php
				$bk_parts = array_filter( array( bk_format_watt( $bk_power['wattage'] ), $bk_power['ampere'] ? sprintf( __( 'يسحب حوالي %s', 'baytkahraba' ), bk_format_ampere( $bk_power['ampere'] ) ) : '' ) );
				echo esc_html( implode( ' · ', $bk_parts ) );
				?>
			</p>
		</div>
	</div>
<?php else : ?>
	<span class="<?php echo esc_attr( $bk_power['badge'] ); ?>" title="<?php echo esc_attr( $bk_power['label'] ); ?>">
		<?php bk_the_icon( $bk_power['icon'], 'size-3.5' ); ?><?php echo esc_html( $bk_power['short'] ); ?>
	</span>
<?php endif; ?>
