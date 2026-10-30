<?php
/**
 * Site header: trust top bar, navy header with live search, mega menu.
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

<div class="bg-brand-navy-800 text-[12px] text-slate-200">
	<div class="bk-container flex h-9 items-center justify-between gap-4">
		<p class="flex min-w-0 items-center gap-2 truncate">
			<?php bk_the_icon( 'truck', 'size-4 shrink-0 text-brand-emerald' ); ?>
			<span class="truncate"><?php echo esc_html( (string) bk_option( 'top_bar_message' ) ); ?></span>
		</p>
		<div class="hidden items-center gap-5 md:flex">
			<span class="inline-flex items-center gap-1.5"><?php bk_the_icon( 'shield', 'size-4 text-brand-amber' ); ?><?php esc_html_e( 'كفالة الوكيل الحقيقية', 'baytkahraba' ); ?></span>
			<a class="inline-flex items-center gap-1.5 hover:text-white" href="tel:<?php echo esc_attr( (string) bk_option( 'phone' ) ); ?>"><?php bk_the_icon( 'phone', 'size-4' ); ?><span class="bk-ltr bk-num"><?php echo esc_html( (string) bk_option( 'phone' ) ); ?></span></a>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'utility',
					'container'      => false,
					'menu_class'     => 'flex items-center gap-4 [&_a:hover]:text-white',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
		</div>
	</div>
</div>

<header class="sticky top-0 z-50 bg-brand-navy text-white shadow-lg shadow-brand-navy/20" x-data="{ menu: false }" @keydown.escape.window="menu = false">
	<div class="bk-container flex h-[var(--bk-header-h)] items-center gap-3 lg:gap-6">
		<button type="button" class="bk-icon-btn text-white hover:bg-white/10 lg:hidden" @click="menu = true" aria-label="<?php esc_attr_e( 'القائمة', 'baytkahraba' ); ?>" :aria-expanded="menu">
			<?php bk_the_icon( 'menu', 'size-6' ); ?>
		</button>

		<?php bk_part( 'template-parts/header/logo', array( 'variant' => 'light', 'tag' => is_front_page() ? 'h1' : 'div' ) ); ?>

		<div class="hidden flex-1 lg:block">
			<?php bk_part( 'template-parts/header/search' ); ?>
		</div>

		<div class="ms-auto flex items-center gap-1 lg:ms-0">
			<?php if ( $bk_wc ) : ?>
				<button type="button" class="bk-icon-btn relative text-white hover:bg-white/10" x-data @click="$store.compare.show()" aria-label="<?php esc_attr_e( 'مقارنة المنتجات', 'baytkahraba' ); ?>">
					<?php bk_the_icon( 'compare', 'size-6' ); ?>
					<span x-cloak x-show="$store.compare.ids.length" x-text="$store.compare.ids.length" class="absolute -top-0.5 -end-0.5 grid size-5 place-items-center rounded-full bg-brand-royal text-[10px] font-bold bk-num"></span>
				</button>
			<?php endif; ?>
			<a class="bk-icon-btn hidden text-white hover:bg-white/10 sm:inline-grid" href="<?php echo esc_url( bk_whatsapp_url( __( 'مرحباً، أريد الاستفسار عن جهاز', 'baytkahraba' ) ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'واتساب', 'baytkahraba' ); ?>">
				<?php bk_the_icon( 'whatsapp', 'size-6 text-brand-emerald' ); ?>
			</a>
			<?php if ( $bk_wc ) : ?>
				<button type="button" class="relative inline-flex items-center gap-2 rounded-xl bg-brand-amber px-3 py-2 font-semibold text-brand-navy transition hover:bg-brand-amber-hover hover:text-white" x-data @click="$store.cart.show()" aria-label="<?php esc_attr_e( 'سلة المشتريات', 'baytkahraba' ); ?>">
					<?php bk_the_icon( 'cart', 'size-5' ); ?>
					<span class="hidden text-sm sm:inline"><?php esc_html_e( 'السلة', 'baytkahraba' ); ?></span>
					<span class="grid min-w-5 place-items-center rounded-full bg-brand-navy px-1 text-[11px] font-bold text-white bk-num" x-text="$store.cart.count">0</span>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<div class="bk-container pb-3 lg:hidden">
		<?php bk_part( 'template-parts/header/search' ); ?>
	</div>

	<?php if ( $bk_wc ) : ?>
		<nav class="relative hidden border-t border-white/10 bg-brand-navy-800 lg:block" aria-label="<?php esc_attr_e( 'الأقسام', 'baytkahraba' ); ?>">
			<?php
			echo bk_remember_html( 'mega-menu', static fn() => bk_part( 'template-parts/header/mega-menu' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</nav>
		<?php
		echo bk_remember_html( 'mobile-menu', static fn() => bk_part( 'template-parts/header/mobile-menu' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	<?php endif; ?>
</header>

<?php if ( $bk_wc && ! is_front_page() ) : ?>
	<?php bk_part( 'template-parts/global/breadcrumbs' ); ?>
<?php endif; ?>

<main id="main" class="min-h-[60vh]">
