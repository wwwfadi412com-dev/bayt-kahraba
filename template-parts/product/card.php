<?php
/**
 * Product card: render on the stage, brand, name, power line, price and a
 * one-tap WhatsApp order button. The whole card links to the product.
 *
 * @var array $args { product: WC_Product, position: int, heading: 'h2'|'h3', attrs: string }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_product = $args['product'] ?? null;
if ( ! $bk_product instanceof WC_Product ) {
	return;
}
$bk_id    = $bk_product->get_id();
$bk_url   = $bk_product->get_permalink();
$bk_name  = $bk_product->get_name();
$bk_brand = bk_get_product_brand( $bk_id );
$bk_power = bk_get_product_power( $bk_id );
$bk_sale  = bk_sale_percent( $bk_product );
$bk_pos   = (int) ( $args['position'] ?? 99 );
$bk_tag   = ( $args['heading'] ?? 'h3' ) === 'h2' ? 'h2' : 'h3';

$bk_meta = array();
if ( $bk_power['ampere'] ) {
	$bk_meta[] = bk_format_ampere( $bk_power['ampere'] );
}
if ( $bk_power['wattage'] ) {
	$bk_meta[] = bk_format_watt( $bk_power['wattage'] );
}
?>
<article class="pcard" <?php echo $args['attrs'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted data-* attributes from templates ?>>
	<a href="<?php echo esc_url( $bk_url ); ?>" class="pcard-media" tabindex="-1" aria-hidden="true">
		<?php
		echo $bk_product->get_image_id() // phpcs:ignore WordPress.Security.EscapeOutput
			? wp_get_attachment_image(
				$bk_product->get_image_id(),
				'bk-card',
				false,
				array(
					'alt'           => '',
					'sizes'         => '(min-width: 1280px) 300px, (min-width: 1024px) 23vw, (min-width: 640px) 31vw, 47vw',
					'fetchpriority' => $bk_pos < 2 ? 'high' : 'auto',
					'loading'       => $bk_pos < 4 ? 'eager' : 'lazy',
				)
			)
			: '<img src="' . esc_attr( bk_placeholder_src() ) . '" alt="" width="480" height="480">';
		?>
		<span class="pcard-badges">
			<?php if ( $bk_sale ) : ?>
				<span class="badge-sale bk-num">-<?php echo (int) $bk_sale; ?>%</span>
			<?php endif; ?>
			<?php if ( 'solar_ready' === $bk_power['class'] ) : ?>
				<span class="badge-sun"><?php bk_the_icon( 'sun' ); ?><?php echo esc_html( $bk_power['short'] ); ?></span>
			<?php elseif ( 'low_amp' === $bk_power['class'] ) : ?>
				<span class="badge-amp"><?php bk_the_icon( 'bolt' ); ?><?php echo esc_html( $bk_power['short'] ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<div class="pcard-body">
		<?php if ( $bk_brand ) : ?>
			<p class="pcard-brand"><?php echo esc_html( $bk_brand->name ); ?></p>
		<?php endif; ?>
		<<?php echo $bk_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="pcard-title"><a href="<?php echo esc_url( $bk_url ); ?>"><?php echo esc_html( $bk_name ); ?></a></<?php echo $bk_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php if ( $bk_meta ) : ?>
			<p class="pcard-meta"><?php echo esc_html( implode( ' · ', $bk_meta ) ); ?></p>
		<?php endif; ?>
		<div class="pcard-foot"><?php echo bk_price_html( $bk_product, 'text-[18px] sm:text-[19px]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	</div>
	<a class="pcard-wa" href="<?php echo bk_esc_wa( bk_product_whatsapp_url( $bk_product ) ); ?>" target="_blank" rel="noopener nofollow"
		aria-label="<?php echo esc_attr( sprintf( /* translators: %s product name */ __( 'اطلب %s على واتساب', 'baytkahraba' ), $bk_name ) ); ?>">
		<?php bk_the_icon( 'whatsapp' ); ?>
	</a>
</article>
