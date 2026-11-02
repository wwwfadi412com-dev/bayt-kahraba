<?php
/**
 * Site header — calm bar: logo, three links, search, WhatsApp.
 * Menu and search open as native <dialog>s (no framework).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
$bk_wc = class_exists( 'WooCommerce' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main"><?php esc_html_e( 'تخطي إلى المحتوى', 'baytkahraba' ); ?></a>

<header class="site-header">
	<div class="bar">
		<button type="button" class="icon-btn -ms-2 lg:hidden" data-open="bk-menu" aria-label="<?php esc_attr_e( 'القائمة', 'baytkahraba' ); ?>">
			<?php bk_the_icon( 'menu' ); ?>
		</button>

		<?php bk_part( 'template-parts/header/logo' ); ?>

		<?php if ( $bk_wc ) : ?>
			<nav class="nav" aria-label="<?php esc_attr_e( 'التنقل الرئيسي', 'baytkahraba' ); ?>">
				<?php echo bk_remember_html( 'nav-desktop', static fn() => bk_part( 'template-parts/header/nav' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</nav>
		<?php endif; ?>

		<button type="button" class="search-trigger" data-open="bk-search">
			<?php bk_the_icon( 'search', 'size-[18px]' ); ?>
			<span><?php esc_html_e( 'ابحث عن براد، غسالة، شاشة…', 'baytkahraba' ); ?></span>
			<kbd>/</kbd>
		</button>

		<div class="ms-auto flex items-center gap-1 md:ms-0">
			<button type="button" class="icon-btn md:hidden" data-open="bk-search" aria-label="<?php esc_attr_e( 'بحث', 'baytkahraba' ); ?>">
				<?php bk_the_icon( 'search' ); ?>
			</button>
			<a class="btn-wa hidden h-11 px-5 sm:inline-flex" href="<?php echo bk_esc_wa( bk_general_whatsapp_url() ); ?>" target="_blank" rel="noopener">
				<?php bk_the_icon( 'whatsapp' ); ?><?php esc_html_e( 'واتساب', 'baytkahraba' ); ?>
			</a>
			<a class="icon-btn text-wa sm:hidden" href="<?php echo bk_esc_wa( bk_general_whatsapp_url() ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'واتساب', 'baytkahraba' ); ?>">
				<?php bk_the_icon( 'whatsapp' ); ?>
			</a>
		</div>
	</div>
</header>

<?php
bk_part( 'template-parts/header/search' );
if ( $bk_wc ) {
	echo bk_remember_html( 'nav-mobile', static fn() => bk_part( 'template-parts/header/mobile-menu' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
}
?>

<main id="main">
<?php if ( $bk_wc && ! is_front_page() ) : ?>
	<?php bk_part( 'template-parts/global/breadcrumbs' ); ?>
<?php endif; ?>
