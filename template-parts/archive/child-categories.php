<?php
/**
 * Visual drill-down tiles for child categories (top of parent archives & silos).
 *
 * @var array $args { nodes: array, title: string }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_nodes = $args['nodes'] ?? array();
if ( ! $bk_nodes ) {
	return;
}
$bk_total = count( $bk_nodes );
?>
<nav class="mb-8" aria-label="<?php esc_attr_e( 'الأقسام الفرعية', 'baytkahraba' ); ?>">
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<h2 class="bk-section-title mb-4"><?php echo esc_html( $args['title'] ); ?></h2>
	<?php endif; ?>
	<ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
		<?php foreach ( $bk_nodes as $bk_i => $bk_node ) : ?>
			<li>
				<a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="group relative flex h-full min-h-[132px] flex-col overflow-hidden rounded-xl2 border border-brand-line bg-brand-card p-4 transition duration-300 hover:-translate-y-0.5 hover:border-brand-ink">
					<span class="bk-mono bk-ltr self-start text-[11px] text-brand-muted"><?php echo esc_html( sprintf( '%02d / %02d', $bk_i + 1, $bk_total ) ); ?></span>
					<?php if ( ! empty( $bk_node['image_id'] ) ) : ?>
						<span class="pointer-events-none absolute -bottom-2 -start-2 size-24 opacity-90 mix-blend-multiply transition group-hover:scale-105"><?php echo wp_get_attachment_image( $bk_node['image_id'], 'bk-thumb', false, array( 'class' => 'size-full object-contain', 'loading' => 'lazy' ) ); ?></span>
					<?php else : ?>
						<span class="pointer-events-none absolute -bottom-4 -start-3 text-brand-paper-2 transition duration-300 group-hover:-rotate-6 group-hover:text-brand-volt-soft"><?php bk_the_icon( bk_category_icon( (int) $bk_node['id'] ), 'size-24 stroke-[1.2]' ); ?></span>
					<?php endif; ?>
					<span class="relative mt-auto font-display text-[16px] font-extrabold leading-snug text-brand-ink lg:text-[17px]"><?php echo esc_html( $bk_node['name'] ); ?></span>
					<span class="bk-mono relative text-[11.5px] text-brand-muted"><?php echo (int) $bk_node['count']; ?> <?php esc_html_e( 'موديل', 'baytkahraba' ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
