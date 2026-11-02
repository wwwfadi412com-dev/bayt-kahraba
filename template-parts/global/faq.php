<?php
/**
 * FAQ — native <details> (no JS). The same data feeds the FAQPage schema.
 *
 * @var array $args { faqs: array<{q,a}>, title: string }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_faqs = $args['faqs'] ?? array();
if ( ! $bk_faqs ) {
	return;
}
?>
<section class="faq" aria-labelledby="bk-faq-title">
	<h2 id="bk-faq-title" class="h-section mb-4"><?php echo esc_html( $args['title'] ?? __( 'أسئلة بتتكرر', 'baytkahraba' ) ); ?></h2>
	<?php foreach ( $bk_faqs as $bk_i => $bk_faq ) : ?>
		<details <?php echo 0 === $bk_i ? 'open' : ''; ?>>
			<summary><h3 class="font-sans text-[16px] font-semibold leading-7 tracking-normal"><?php echo esc_html( $bk_faq['q'] ); ?></h3><span class="plus" aria-hidden="true"><?php bk_the_icon( 'plus', 'size-4' ); ?></span></summary>
			<p><?php echo esc_html( $bk_faq['a'] ); ?></p>
		</details>
	<?php endforeach; ?>
</section>
