<?php
/**
 * Brand mark: a yellow "light" disc with a bolt + the store name.
 * Uses the Customizer logo when one is uploaded.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_name = (string) bk_option( 'store_name', get_bloginfo( 'name' ) );
?>
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" rel="home" aria-label="<?php echo esc_attr( $bk_name ); ?>">
	<?php if ( has_custom_logo() ) : ?>
		<?php echo wp_get_attachment_image( (int) get_theme_mod( 'custom_logo' ), 'medium', false, array( 'class' => 'h-9 w-auto lg:h-10', 'alt' => $bk_name, 'loading' => 'eager' ) ); ?>
	<?php else : ?>
		<span class="logo-mark" aria-hidden="true">
			<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13.4 2.5 5.8 13.2h5.1l-1.3 8.3 7.6-10.7h-5.1l1.3-8.3Z"/></svg>
		</span>
		<span class="logo-name"><?php echo esc_html( $bk_name ); ?></span>
	<?php endif; ?>
</a>
