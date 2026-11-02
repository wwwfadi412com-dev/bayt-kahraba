<?php
/**
 * Fallback template: blog index, archives, single posts and non-product search.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap py-10 lg:py-16">
	<?php if ( is_singular() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'mx-auto max-w-3xl' ); ?>>
				<h1 class="mb-3 font-display text-[32px] font-bold leading-tight lg:text-[44px]"><?php the_title(); ?></h1>
				<p class="mb-8 text-[14px] text-muted"><?php echo esc_html( get_the_date() ); ?></p>
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large', array( 'class' => 'mb-8 w-full rounded-[28px]', 'fetchpriority' => 'high' ) ); ?>
				<?php endif; ?>
				<div class="prose-bk"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<h1 class="mb-10 font-display text-[30px] font-bold lg:text-[40px]"><?php echo is_search() ? esc_html( sprintf( /* translators: %s query */ __( 'نتائج البحث عن: %s', 'baytkahraba' ), get_search_query() ) ) : wp_kses_post( get_the_archive_title() ?: __( 'المقالات والأدلة', 'baytkahraba' ) ); ?></h1>
		<?php if ( have_posts() ) : ?>
			<div class="grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="group">
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" tabindex="-1" class="mb-4 block overflow-hidden rounded-[var(--radius)] bg-stage"><?php the_post_thumbnail( 'bk-card', array( 'class' => 'aspect-video w-full object-cover' ) ); ?></a>
						<?php endif; ?>
						<h2 class="text-[19px]"><a href="<?php the_permalink(); ?>" class="hover:underline hover:decoration-glow hover:decoration-2 hover:underline-offset-4"><?php the_title(); ?></a></h2>
						<p class="mt-2 text-[15px] text-muted"><?php echo esc_html( get_the_excerpt() ); ?></p>
					</article>
				<?php endwhile; ?>
			</div>
			<div class="pager"><?php echo wp_kses_post( (string) paginate_links() ); ?></div>
		<?php else : ?>
			<p class="text-muted"><?php esc_html_e( 'ما في محتوى لهلق.', 'baytkahraba' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
