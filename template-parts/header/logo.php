<?php
/**
 * Brand mark: minimalist home roof + electric spark/plug.
 * Falls back to the inline SVG mark when no Customizer logo is uploaded.
 *
 * @var array $args { variant: 'dark'|'light', show_tagline: bool, tag: 'div'|'h1' }
 */

defined( 'ABSPATH' ) || exit;

$variant      = $args['variant'] ?? 'dark';
$show_tagline = $args['show_tagline'] ?? true;
$wrapper_tag  = ( $args['tag'] ?? 'div' ) === 'h1' ? 'h1' : 'div';
$name_color   = 'light' === $variant ? 'text-white' : 'text-brand-ink';
$tag_color    = 'light' === $variant ? 'text-slate-300' : 'text-brand-muted';
$roof_color   = 'light' === $variant ? '#FFFFFF' : '#1E3A8A';
$store_name   = bk_option( 'store_name', get_bloginfo( 'name' ) );
$tagline      = bk_option( 'store_tagline', __( 'الوكيل المعتمد للأدوات الكهربائية والمنزلية', 'baytkahraba' ) );
?>
<<?php echo $wrapper_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="bk-logo m-0 leading-none">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="group inline-flex items-center gap-2.5" rel="home" aria-label="<?php echo esc_attr( $store_name ); ?>">
		<?php if ( has_custom_logo() ) : ?>
			<?php
			echo wp_get_attachment_image(
				(int) get_theme_mod( 'custom_logo' ),
				'medium',
				false,
				array(
					'class'         => 'h-10 w-auto lg:h-12',
					'alt'           => $store_name,
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			);
			?>
		<?php else : ?>
			<svg class="h-10 w-10 shrink-0 lg:h-12 lg:w-12" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
				<rect x="1" y="1" width="46" height="46" rx="12" fill="<?php echo 'light' === $variant ? '#1E3A8A' : '#EFF6FF'; ?>"/>
				<path d="M9 23.5 24 11l15 12.5" stroke="<?php echo esc_attr( $roof_color ); ?>" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M13.5 21v14.5a2 2 0 0 0 2 2h17a2 2 0 0 0 2-2V21" stroke="<?php echo esc_attr( $roof_color ); ?>" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
				<path class="animate-spark" d="M25.8 19.5 20 28.4h4.6l-1.9 7.1 6.6-9.6h-4.7l1.2-6.4Z" fill="#F59E0B" stroke="#F59E0B" stroke-width="1" stroke-linejoin="round"/>
				<path d="M31.5 11.5v-3M35 13l2-2.2" stroke="#F59E0B" stroke-width="2.2" stroke-linecap="round"/>
			</svg>
		<?php endif; ?>
		<span class="flex flex-col">
			<span class="text-lg font-bold tracking-tight lg:text-xl <?php echo esc_attr( $name_color ); ?>"><?php echo esc_html( $store_name ); ?></span>
			<?php if ( $show_tagline && $tagline ) : ?>
				<span class="mt-1 hidden items-center gap-1 text-[11px] font-medium sm:inline-flex <?php echo esc_attr( $tag_color ); ?>">
					<svg class="h-3 w-3 text-brand-emerald" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1.9 3 4.7v4.6c0 4.4 3 8.4 7 9.4 4-1 7-5 7-9.4V4.7l-7-2.8Zm3.7 6.3a.9.9 0 0 0-1.3-1.3L9.1 10.2 7.6 8.7a.9.9 0 1 0-1.3 1.3l2.2 2.2c.3.3.9.3 1.2 0l4-4Z" clip-rule="evenodd"/></svg>
					<?php echo esc_html( $tagline ); ?>
				</span>
			<?php endif; ?>
		</span>
	</a>
</<?php echo $wrapper_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
