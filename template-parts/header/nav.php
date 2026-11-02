<?php
/**
 * Desktop navigation: categories panel, brands panel, two direct links.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_tree   = bk_get_category_tree();
$bk_brands = bk_get_brands();
$bk_shop   = wc_get_page_permalink( 'shop' );
?>
<div class="nav-item">
	<button type="button" class="nav-link" aria-expanded="false" aria-controls="bk-panel-cats"><?php esc_html_e( 'الأقسام', 'baytkahraba' ); ?><?php bk_the_icon( 'chevron-d', 'size-4' ); ?></button>
	<div class="nav-panel" id="bk-panel-cats">
		<div class="grid grid-cols-3 gap-x-10 gap-y-7">
			<?php foreach ( $bk_tree as $bk_node ) : ?>
				<div class="min-w-[180px]">
					<a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="font-display text-[16px] font-semibold text-ink hover:underline hover:decoration-glow hover:decoration-2 hover:underline-offset-4"><?php echo esc_html( $bk_node['name'] ); ?></a>
					<?php if ( $bk_node['children'] ) : ?>
						<ul class="mt-2.5 space-y-1.5 text-[14.5px]">
							<?php foreach ( $bk_node['children'] as $bk_child ) : ?>
								<li><a href="<?php echo esc_url( $bk_child['url'] ); ?>" class="text-muted transition hover:text-ink"><?php echo esc_html( $bk_child['name'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="mt-6 flex items-center justify-between gap-4 border-t border-line pt-5">
			<a href="<?php echo esc_url( add_query_arg( 'power', 'solar', $bk_shop ) ); ?>" class="badge-sun h-9 px-4 text-[13.5px]"><?php bk_the_icon( 'sun' ); ?><?php esc_html_e( 'أجهزة بتشتغل على الطاقة الشمسية والأمبير', 'baytkahraba' ); ?></a>
			<a href="<?php echo esc_url( $bk_shop ); ?>" class="link-arrow"><?php esc_html_e( 'كل الأجهزة', 'baytkahraba' ); ?><?php bk_the_icon( 'arrow', 'size-4' ); ?></a>
		</div>
	</div>
</div>

<?php if ( $bk_brands ) : ?>
	<div class="nav-item">
		<button type="button" class="nav-link" aria-expanded="false" aria-controls="bk-panel-brands"><?php esc_html_e( 'الماركات', 'baytkahraba' ); ?><?php bk_the_icon( 'chevron-d', 'size-4' ); ?></button>
		<div class="nav-panel" id="bk-panel-brands">
			<ul class="grid grid-cols-2 gap-2">
				<?php foreach ( $bk_brands as $bk_brand ) : ?>
					<li>
						<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="flex min-w-[200px] items-center justify-between gap-6 rounded-2xl px-4 py-3 transition hover:bg-stage">
							<span class="font-display text-[17px] font-semibold text-ink"><?php echo esc_html( $bk_brand->name ); ?></span>
							<span class="text-[13px] text-muted"><?php echo esc_html( sprintf( /* translators: %d models */ _n( '%d موديل', '%d موديل', (int) $bk_brand->count, 'baytkahraba' ), (int) $bk_brand->count ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php endif; ?>

<a class="nav-link" href="<?php echo esc_url( add_query_arg( 'power', 'solar', $bk_shop ) ); ?>"><?php esc_html_e( 'طاقة شمسية', 'baytkahraba' ); ?></a>
<a class="nav-link" href="<?php echo esc_url( home_url( '/#how' ) ); ?>"><?php esc_html_e( 'كيف بتطلب؟', 'baytkahraba' ); ?></a>
