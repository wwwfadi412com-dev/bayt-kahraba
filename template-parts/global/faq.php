<?php
/**
 * FAQ accordion (the same data feeds the FAQPage schema).
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
<section class="bk-card p-5 lg:p-8" aria-labelledby="bk-faq-title">
	<h2 id="bk-faq-title" class="bk-section-title mb-2"><?php echo esc_html( $args['title'] ?? __( 'الأسئلة الشائعة', 'baytkahraba' ) ); ?></h2>
	<div x-data="{ open: 0 }">
		<?php foreach ( $bk_faqs as $bk_i => $bk_faq ) : ?>
			<div class="bk-acc-item">
				<h3 class="m-0 text-base">
					<button type="button" class="bk-acc-btn" @click="open = open === <?php echo (int) $bk_i; ?> ? -1 : <?php echo (int) $bk_i; ?>" :aria-expanded="open === <?php echo (int) $bk_i; ?>" aria-controls="bk-faq-<?php echo (int) $bk_i; ?>">
						<span><?php echo esc_html( $bk_faq['q'] ); ?></span>
						<span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-stripe transition" :class="open === <?php echo (int) $bk_i; ?> && 'rotate-45 bg-brand-amber text-brand-navy'"><?php bk_the_icon( 'plus', 'size-4' ); ?></span>
					</button>
				</h3>
				<div id="bk-faq-<?php echo (int) $bk_i; ?>" x-show="open === <?php echo (int) $bk_i; ?>" x-collapse <?php echo 0 === $bk_i ? '' : 'x-cloak'; ?>>
					<p class="pb-4 text-[15px] leading-8 text-brand-body"><?php echo esc_html( $bk_faq['a'] ); ?></p>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
