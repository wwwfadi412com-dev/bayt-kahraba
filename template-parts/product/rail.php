<?php
/**
 * Horizontal product rail with a heading.
 *
 * @var array $args { title: string, products: WC_Product[], link: string, link_label: string }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_products = array_filter( (array) ( $args['products'] ?? array() ), static fn( $p ) => $p instanceof WC_Product );
if ( ! $bk_products ) {
	return;
}
?>
<section class="py-10 lg:py-14" aria-label="<?php echo esc_attr( $args['title'] ?? '' ); ?>">
	<div class="mb-6 flex items-end justify-between gap-4">
		<h2 class="font-display text-[22px] font-semibold lg:text-[28px]"><?php echo esc_html( $args['title'] ?? '' ); ?></h2>
		<?php if ( ! empty( $args['link'] ) ) : ?>
			<a href="<?php echo esc_url( $args['link'] ); ?>" class="link-arrow shrink-0"><?php echo esc_html( $args['link_label'] ?? __( 'عرض الكل', 'baytkahraba' ) ); ?><?php bk_the_icon( 'arrow', 'size-4' ); ?></a>
		<?php endif; ?>
	</div>
	<div class="rail lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible">
		<?php foreach ( array_slice( $bk_products, 0, 8 ) as $bk_p ) : ?>
			<div class="w-[46%] shrink-0 sm:w-[30%] lg:w-auto lg:[&:nth-child(n+5)]:hidden"><?php bk_part( 'template-parts/product/card', array( 'product' => $bk_p ) ); ?></div>
		<?php endforeach; ?>
	</div>
</section>
