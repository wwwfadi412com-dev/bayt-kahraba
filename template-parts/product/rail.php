<?php
/**
 * Horizontal product rail / grid section.
 *
 * @var array $args { title: string, products: WC_Product[], link: string, link_text: string, layout: 'rail'|'grid', id: string }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_products = array_filter( (array) ( $args['products'] ?? array() ), static fn( $p ) => $p instanceof WC_Product );
if ( ! $bk_products ) {
	return;
}
$bk_layout = $args['layout'] ?? 'rail';
?>
<section class="bk-section !py-8" <?php echo ! empty( $args['id'] ) ? 'id="' . esc_attr( $args['id'] ) . '"' : ''; ?>>
	<div class="mb-5 flex items-end justify-between gap-4">
		<h2 class="bk-section-title"><?php echo esc_html( $args['title'] ?? '' ); ?></h2>
		<?php if ( ! empty( $args['link'] ) ) : ?>
			<a href="<?php echo esc_url( $args['link'] ); ?>" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-brand-royal hover:text-brand-amber-hover"><?php echo esc_html( $args['link_text'] ?? __( 'عرض الكل', 'baytkahraba' ) ); ?><?php bk_the_icon( 'chevron', 'size-4' ); ?></a>
		<?php endif; ?>
	</div>
	<div class="<?php echo 'grid' === $bk_layout ? 'grid grid-cols-2 gap-3 md:grid-cols-3 lg:gap-5 xl:grid-cols-5' : 'bk-rail -mx-4 px-4 [&>*]:w-[46%] sm:[&>*]:w-[31%] lg:[&>*]:w-[23%] xl:[&>*]:w-[19%]'; ?>">
		<?php foreach ( $bk_products as $bk_p ) : ?>
			<?php bk_part( 'template-parts/product/card', array( 'product' => $bk_p ) ); ?>
		<?php endforeach; ?>
	</div>
</section>
