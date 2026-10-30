<?php
/**
 * Generic page (also hosts cart, checkout, my-account and the warranty shortcode).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();
$bk_is_wc_page = function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() );
?>
<div class="bk-container py-8 lg:py-12">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( $bk_is_wc_page ? 'woocommerce' : 'mx-auto max-w-4xl' ); ?>>
			<h1 class="mb-6 text-2xl lg:text-3xl"><?php the_title(); ?></h1>
			<div class="<?php echo $bk_is_wc_page ? '' : 'bk-prose'; ?>"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
