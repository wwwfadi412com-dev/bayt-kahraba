<?php
/**
 * Visual drill-down cards for child categories (top of parent archives).
 *
 * @var array $args { nodes: array, title: string }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_nodes = $args['nodes'] ?? array();
if ( ! $bk_nodes ) {
	return;
}
?>
<nav class="mb-8" aria-label="<?php esc_attr_e( 'الأقسام الفرعية', 'baytkahraba' ); ?>">
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<h2 class="bk-section-title mb-4"><?php echo esc_html( $args['title'] ); ?></h2>
	<?php endif; ?>
	<ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
		<?php foreach ( $bk_nodes as $bk_node ) : ?>
			<li>
				<a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="bk-card bk-card-hover group flex h-full items-center gap-3 p-3 lg:flex-col lg:p-4 lg:text-center">
					<span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-stripe lg:size-20">
						<?php if ( $bk_node['image_id'] ) : ?>
							<?php echo wp_get_attachment_image( $bk_node['image_id'], 'bk-thumb', false, array( 'class' => 'size-full object-contain p-1.5 transition group-hover:scale-105', 'loading' => 'lazy' ) ); ?>
						<?php else : ?>
							<?php bk_the_icon( 'bolt', 'size-7 text-brand-amber' ); ?>
						<?php endif; ?>
					</span>
					<span class="min-w-0">
						<span class="block text-sm font-bold leading-6 text-brand-ink group-hover:text-brand-royal"><?php echo esc_html( $bk_node['name'] ); ?></span>
						<span class="block text-xs text-brand-muted"><span class="bk-num"><?php echo (int) $bk_node['count']; ?></span> <?php esc_html_e( 'منتج', 'baytkahraba' ); ?></span>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
