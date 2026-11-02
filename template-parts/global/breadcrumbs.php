<?php
/**
 * Breadcrumbs (mirrors the BreadcrumbList schema).
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
<nav class="wrap" aria-label="<?php esc_attr_e( 'مسار التنقل', 'baytkahraba' ); ?>">
	<ol class="no-scrollbar flex items-center gap-1.5 overflow-x-auto whitespace-nowrap pt-5 text-[13px] text-muted">
		<?php foreach ( $bk_crumbs as $bk_i => $bk_crumb ) : ?>
			<li class="flex items-center gap-1.5">
				<?php if ( $bk_i > 0 ) : ?>
					<span aria-hidden="true" class="text-line">/</span>
				<?php endif; ?>
				<?php if ( $bk_i === $bk_last || ! $bk_crumb['url'] ) : ?>
					<span class="text-ink-2" aria-current="page"><?php echo esc_html( $bk_crumb['name'] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $bk_crumb['url'] ); ?>" class="hover:text-ink"><?php echo esc_html( $bk_crumb['name'] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
