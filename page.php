<?php
/**
 * Generic page.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap py-10 lg:py-16">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'mx-auto max-w-3xl' ); ?>>
			<h1 class="mb-8 font-display text-[32px] font-bold leading-tight lg:text-[44px]"><?php the_title(); ?></h1>
			<div class="prose-bk"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
