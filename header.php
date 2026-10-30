<?php
/**
 * Site header — "Volt": live ticker, paper bar with command-style search,
 * category nav with mega panels.
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
	<script>document.documentElement.classList.add('bk-js');setTimeout(function(){if(!window.Alpine){document.querySelectorAll('.bk-rv').forEach(function(e){e.classList.add('is-in')})}},3000);</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main"><?php esc_html_e( 'تخطي إلى المحتوى', 'baytkahraba' ); ?></a>

<?php
echo bk_remember_html( 'ticker', static fn() => bk_part( 'template-parts/header/ticker' ), HOUR_IN_SECONDS ); // phpcs:ignore WordPress.Security.EscapeOutput
?>

<header class="sticky top-0 z-50 border-b border-brand-line bg-brand-paper/90 backdrop-blur-md" x-data="{ menu: false }" @keydown.escape.window="menu = false">
	<div class="bk-container flex h-[var(--bk-header-h)] items-center gap-3 lg:gap-6">
		<button type="button" class="bk-icon-btn lg:hidden" @click="menu = true" aria-label="<?php esc_attr_e( 'القائمة', 'baytkahraba' ); ?>" :aria-expanded="menu">
			<?php bk_the_icon( 'menu', 'size-5' ); ?>
		</button>

		<?php bk_part( 'template-parts/header/logo', array( 'tag' => is_front_page() ? 'h1' : 'div' ) ); ?>

		<div class="hidden flex-1 lg:block lg:max-w-xl lg:ms-auto">
			<?php bk_part( 'template-parts/header/search' ); ?>
		</div>

		<div class="ms-auto flex items-center gap-2 lg:ms-0">
			<a class="bk-icon-btn hidden sm:inline-grid" href="<?php echo esc_url( bk_whatsapp_url( __( 'مرحباً، أريد الاستفسار عن جهاز', 'baytkahraba' ) ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'واتساب', 'baytkahraba' ); ?>">
				<?php bk_the_icon( 'whatsapp', 'size-5 text-brand-sun' ); ?>
			</a>
			<?php if ( $bk_wc ) : ?>
				<button type="button" class="bk-icon-btn relative" x-data @click="$store.compare.show()" aria-label="<?php esc_attr_e( 'مقارنة المنتجات', 'baytkahraba' ); ?>">
					<?php bk_the_icon( 'compare', 'size-5' ); ?>
					<span x-cloak x-show="$store.compare.ids.length" x-text="$store.compare.ids.length" class="absolute -top-1.5 -start-1.5 grid min-w-5 h-5 place-items-center rounded-full bg-brand-ink px-1 font-mono text-[11px] font-semibold text-brand-paper"></span>
				</button>
				<button type="button" class="bk-icon-btn relative" x-data @click="$store.cart.show()" aria-label="<?php esc_attr_e( 'سلة المشتريات', 'baytkahraba' ); ?>">
					<?php bk_the_icon( 'cart', 'size-5' ); ?>
					<span class="absolute -top-1.5 -start-1.5 grid min-w-5 h-5 place-items-center rounded-full bg-brand-volt px-1 font-mono text-[11px] font-semibold text-white" x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count"></span>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<div class="bk-container pb-3 lg:hidden">
		<?php bk_part( 'template-parts/header/search' ); ?>
	</div>

	<?php if ( $bk_wc ) : ?>
		<nav class="relative hidden border-t border-brand-line lg:block" aria-label="<?php esc_attr_e( 'الأقسام', 'baytkahraba' ); ?>">
			<?php echo bk_remember_html( 'mega-menu', static fn() => bk_part( 'template-parts/header/mega-menu' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</nav>
		<?php echo bk_remember_html( 'mobile-menu', static fn() => bk_part( 'template-parts/header/mobile-menu' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php endif; ?>
</header>

<?php if ( $bk_wc && ! is_front_page() ) : ?>
	<?php bk_part( 'template-parts/global/breadcrumbs' ); ?>
<?php endif; ?>

<main id="main" class="min-h-[60vh]">
