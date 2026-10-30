<?php
/**
 * Hierarchical breadcrumbs (mirrors the BreadcrumbList schema).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_crumbs = bk_breadcrumbs();
if ( count( $bk_crumbs ) < 2 ) {
	return;
}
$bk_last = count( $bk_crumbs ) - 1;
?>
<nav class="border-b border-brand-line bg-white" aria-label="<?php esc_attr_e( 'مسار التنقل', 'baytkahraba' ); ?>">
	<ol class="bk-container bk-scrollbar-none flex items-center gap-1.5 overflow-x-auto whitespace-nowrap py-2.5 text-[13px] text-brand-muted">
		<?php foreach ( $bk_crumbs as $bk_i => $bk_crumb ) : ?>
			<li class="flex items-center gap-1.5">
				<?php if ( $bk_i > 0 ) : ?>
					<?php bk_the_icon( 'chevron', 'size-3.5 text-slate-300' ); ?>
				<?php endif; ?>
				<?php if ( $bk_i === $bk_last || ! $bk_crumb['url'] ) : ?>
					<span class="font-medium text-brand-ink" aria-current="page"><?php echo esc_html( $bk_crumb['name'] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $bk_crumb['url'] ); ?>" class="hover:text-brand-royal"><?php echo esc_html( $bk_crumb['name'] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
