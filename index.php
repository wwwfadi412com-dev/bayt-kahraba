<?php
/**
 * Fallback template: blog index, archives, single posts and non-product search.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="bk-container py-8 lg:py-12">
	<?php if ( is_singular() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'mx-auto max-w-3xl' ); ?>>
				<h1 class="mb-3 text-2xl lg:text-4xl"><?php the_title(); ?></h1>
				<p class="mb-6 text-sm text-brand-muted"><?php echo esc_html( get_the_date() ); ?></p>
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large', array( 'class' => 'mb-8 w-full rounded-2xl', 'fetchpriority' => 'high' ) ); ?>
				<?php endif; ?>
				<div class="bk-prose"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<h1 class="mb-8 text-2xl lg:text-3xl"><?php echo is_search() ? esc_html( sprintf( __( 'نتائج البحث عن: %s', 'baytkahraba' ), get_search_query() ) ) : wp_kses_post( get_the_archive_title() ?: __( 'المقالات والأدلة', 'baytkahraba' ) ); ?></h1>
		<?php if ( have_posts() ) : ?>
			<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="bk-card bk-card-hover overflow-hidden">
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" tabindex="-1"><?php the_post_thumbnail( 'bk-card', array( 'class' => 'aspect-video w-full object-cover' ) ); ?></a>
						<?php endif; ?>
						<div class="p-5">
							<h2 class="text-lg"><a href="<?php the_permalink(); ?>" class="hover:text-brand-royal"><?php the_title(); ?></a></h2>
							<p class="mt-2 text-sm text-brand-body"><?php echo esc_html( get_the_excerpt() ); ?></p>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<div class="mt-10"><?php the_posts_pagination( array( 'class' => 'flex justify-center gap-2' ) ); ?></div>
		<?php else : ?>
			<p class="text-brand-muted"><?php esc_html_e( 'لا يوجد محتوى بعد.', 'baytkahraba' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
