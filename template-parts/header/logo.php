<?php
/**
 * Brand mark: ink tile with a roof line and an electric bolt + Alexandria wordmark.
 * Falls back to the inline mark when no Customizer logo is uploaded.
 *
 * @var array $args { variant: 'dark'|'light', show_tagline: bool, tag: 'div'|'h1' }
 */

defined( 'ABSPATH' ) || exit;

$variant      = $args['variant'] ?? 'dark';
$show_tagline = $args['show_tagline'] ?? true;
$wrapper_tag  = ( $args['tag'] ?? 'div' ) === 'h1' ? 'h1' : 'div';
$is_light     = 'light' === $variant;
$store_name   = bk_option( 'store_name', get_bloginfo( 'name' ) );
?>
<<?php echo $wrapper_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="bk-logo m-0 leading-none">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="group inline-flex items-center gap-3" rel="home" aria-label="<?php echo esc_attr( $store_name ); ?>">
		<?php if ( has_custom_logo() ) : ?>
			<?php echo wp_get_attachment_image( (int) get_theme_mod( 'custom_logo' ), 'medium', false, array( 'class' => 'h-10 w-auto lg:h-11', 'alt' => $store_name, 'loading' => 'eager' ) ); ?>
		<?php else : ?>
			<span class="grid size-10 shrink-0 place-items-center rounded-xl lg:size-[42px] <?php echo $is_light ? 'bg-brand-paper' : 'bg-brand-ink'; ?>" aria-hidden="true">
				<svg class="size-6 transition-transform duration-300 group-hover:-rotate-6" viewBox="0 0 24 24" fill="none">
					<path d="M3.5 11.5 12 4l8.5 7.5" stroke="<?php echo $is_light ? '#121212' : '#F2EFE8'; ?>" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M13.2 9.5 9.6 14.6h3l-1 4.4 4.3-6h-3.1l.4-3.5Z" fill="#FF5B1F"/>
				</svg>
			</span>
		<?php endif; ?>
		<span class="flex flex-col">
			<span class="font-display text-[19px] font-extrabold leading-none tracking-[-.02em] lg:text-[21px] <?php echo $is_light ? 'text-brand-paper' : 'text-brand-ink'; ?>"><?php echo esc_html( $store_name ); ?></span>
			<?php if ( $show_tagline ) : ?>
				<span class="mt-1.5 hidden font-mono text-[9.5px] font-medium uppercase tracking-[.2em] sm:block <?php echo $is_light ? 'text-[#9C978C]' : 'text-brand-muted'; ?>">Bayt Al-Kahraba · Damascus</span>
			<?php endif; ?>
		</span>
	</a>
</<?php echo $wrapper_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
