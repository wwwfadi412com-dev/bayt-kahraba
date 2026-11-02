<?php
/**
 * Shared catalog layout: shop, search, categories, brand hubs, brand × category silos.
 * Server-rendered from the main query; filters are plain GET links/forms
 * (crawlable, shareable, zero JS required).
 *
 * @var array $args {
 *   h1:           string
 *   intro_html:   string   Short SEO intro (trusted HTML).
 *   guide_html:   string   Buying guide below the grid (trusted HTML).
 *   faqs:         array
 *   child_cats:   array    Child category nodes.
 *   brand_filter: array    WP_Term[] or [{term, count}] brand options.
 *   header_html:  string   Optional trusted HTML above the title (brand hub).
 * }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$bk_filters = bk_catalog_filters( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification
$bk_base    = preg_replace( '#/page/\d+/?#', '/', strtok( (string) ( bk_canonical_url() ?: home_url( add_query_arg( array() ) ) ), '?' ) );
$bk_total   = (int) $wp_query->found_posts;
$bk_keep    = static function ( array $override = array() ) use ( $bk_filters, $bk_base ): string {
	$q = array_filter(
		array_merge(
			array(
				'power'        => $bk_filters['power'],
				'amp_max'      => $bk_filters['amp_max'] ?: '',
				'brand_filter' => $bk_filters['brand_filter'],
				'orderby'      => $bk_filters['orderby'],
				's'            => get_search_query( false ),
				'post_type'    => is_search() ? 'product' : '',
			),
			$override
		),
		static fn( $v ) => '' !== $v && null !== $v
	);
	return add_query_arg( $q, $bk_base );
};
$bk_power_opts = array(
	''        => array( __( 'الكل', 'baytkahraba' ), '' ),
	'solar'   => array( __( 'بتشتغل على الطاقة الشمسية والبطارية', 'baytkahraba' ), 'sun' ),
	'low_amp' => array( __( 'أمبير منخفض', 'baytkahraba' ), 'bolt' ),
);
$bk_orderby = array(
	''           => __( 'الترتيب: المقترح', 'baytkahraba' ),
	'popularity' => __( 'الأكثر طلباً', 'baytkahraba' ),
	'price'      => __( 'السعر: الأقل أولاً', 'baytkahraba' ),
	'price-desc' => __( 'السعر: الأعلى أولاً', 'baytkahraba' ),
	'date'       => __( 'الأحدث', 'baytkahraba' ),
);
$bk_brands = array();
foreach ( (array) ( $args['brand_filter'] ?? array() ) as $bk_b ) {
	$bk_term = is_array( $bk_b ) ? ( $bk_b['term'] ?? null ) : $bk_b;
	if ( $bk_term instanceof WP_Term ) {
		$bk_brands[] = $bk_term;
	}
}
$bk_paged = max( 1, (int) get_query_var( 'paged' ) );
?>
<div class="wrap pb-6">
	<?php echo $args['header_html'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted template HTML ?>

	<header class="max-w-3xl pt-6 lg:pt-10">
		<h1 class="font-display text-[30px] font-bold leading-[1.2] tracking-[-.02em] lg:text-[44px]"><?php echo esc_html( $args['h1'] ?? '' ); ?></h1>
		<?php if ( ! empty( $args['intro_html'] ) && 1 === $bk_paged ) : ?>
			<div class="prose-bk mt-3 text-[15.5px] leading-7 text-muted [&_p]:my-2"><?php echo wp_kses_post( $args['intro_html'] ); ?></div>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $args['child_cats'] ) ) : ?>
		<ul class="rail mt-8 lg:flex lg:flex-wrap lg:gap-3 lg:overflow-visible" aria-label="<?php esc_attr_e( 'الأقسام الفرعية', 'baytkahraba' ); ?>">
			<?php foreach ( $args['child_cats'] as $bk_node ) : ?>
				<li>
					<a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="flex h-[72px] items-center gap-3 rounded-2xl bg-stage pe-5 ps-2 transition hover:bg-line/70">
						<span class="block size-14 overflow-hidden rounded-xl"><?php echo bk_category_image( $bk_node, 'bk-thumb', array( 'class' => 'size-full object-contain', 'sizes' => '56px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="whitespace-nowrap">
							<span class="block text-[14.5px] font-semibold leading-tight text-ink"><?php echo esc_html( $bk_node['name'] ); ?></span>
							<span class="text-[12.5px] text-muted"><?php echo esc_html( sprintf( /* translators: %d models */ __( '%d موديل', 'baytkahraba' ), (int) $bk_node['count'] ) ); ?></span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<!-- Toolbar: power chips + brand + sort (plain GET) -->
	<div class="mt-8 flex flex-col gap-4 border-b border-line pb-5 lg:flex-row lg:items-center lg:justify-between">
		<div class="rail !pb-0 lg:flex-wrap" aria-label="<?php esc_attr_e( 'فلتر الكهرباء', 'baytkahraba' ); ?>">
			<?php foreach ( $bk_power_opts as $bk_value => $bk_opt ) : ?>
				<?php $bk_on = $bk_filters['power'] === $bk_value && ! $bk_filters['amp_max']; ?>
				<a href="<?php echo esc_url( $bk_keep( array( 'power' => $bk_value, 'amp_max' => '' ) ) ); ?>" class="chip<?php echo $bk_on ? ' is-active' : ''; ?>" <?php echo $bk_on ? 'aria-current="true"' : ''; ?> rel="nofollow">
					<?php if ( $bk_opt[1] ) : ?>
						<?php bk_the_icon( $bk_opt[1] ); ?>
					<?php endif; ?>
					<?php echo esc_html( $bk_opt[0] ); ?>
				</a>
			<?php endforeach; ?>
			<?php if ( $bk_filters['amp_max'] ) : ?>
				<a href="<?php echo esc_url( $bk_keep( array( 'amp_max' => '' ) ) ); ?>" class="chip is-active" rel="nofollow"><?php echo esc_html( sprintf( /* translators: %s amps */ __( 'حتى %s أمبير', 'baytkahraba' ), rtrim( rtrim( number_format( $bk_filters['amp_max'], 1 ), '0' ), '.' ) ) ); ?><?php bk_the_icon( 'close' ); ?></a>
			<?php endif; ?>
		</div>
		<form method="get" action="<?php echo esc_url( $bk_base ); ?>" class="flex flex-wrap items-center gap-2" data-autosubmit>
			<?php foreach ( array( 'power', 'amp_max' ) as $bk_k ) : ?>
				<?php if ( $bk_filters[ $bk_k ] ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $bk_k ); ?>" value="<?php echo esc_attr( (string) $bk_filters[ $bk_k ] ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( is_search() ) : ?>
				<input type="hidden" name="s" value="<?php echo esc_attr( get_search_query( false ) ); ?>"><input type="hidden" name="post_type" value="product">
			<?php endif; ?>
			<span class="me-2 text-[14px] text-muted"><span class="bk-num font-semibold text-ink"><?php echo (int) $bk_total; ?></span> <?php esc_html_e( 'جهاز', 'baytkahraba' ); ?></span>
			<?php if ( count( $bk_brands ) > 1 ) : ?>
				<label class="screen-reader-text" for="bk-brand"><?php esc_html_e( 'الماركة', 'baytkahraba' ); ?></label>
				<select id="bk-brand" name="brand_filter" class="select">
					<option value=""><?php esc_html_e( 'كل الماركات', 'baytkahraba' ); ?></option>
					<?php foreach ( $bk_brands as $bk_term ) : ?>
						<option value="<?php echo esc_attr( $bk_term->slug ); ?>" <?php selected( $bk_filters['brand_filter'], $bk_term->slug ); ?>><?php echo esc_html( $bk_term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
			<label class="screen-reader-text" for="bk-sort"><?php esc_html_e( 'الترتيب', 'baytkahraba' ); ?></label>
			<select id="bk-sort" name="orderby" class="select">
				<?php foreach ( $bk_orderby as $bk_value => $bk_label ) : ?>
					<option value="<?php echo esc_attr( $bk_value ); ?>" <?php selected( $bk_filters['orderby'], $bk_value ); ?>><?php echo esc_html( $bk_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<noscript><button class="btn-ink h-10" type="submit"><?php esc_html_e( 'تطبيق', 'baytkahraba' ); ?></button></noscript>
		</form>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="mt-8 grid grid-cols-2 gap-x-3 gap-y-9 sm:grid-cols-3 lg:grid-cols-4 lg:gap-x-6 lg:gap-y-12">
			<?php
			$bk_pos = 0;
			while ( have_posts() ) :
				the_post();
				bk_part( 'template-parts/product/card', array( 'product' => wc_get_product( get_the_ID() ), 'position' => $bk_pos++, 'heading' => 'h2' ) );
			endwhile;
			?>
		</div>
		<?php
		$bk_links = paginate_links(
			array(
				'base'      => trailingslashit( $bk_base ) . 'page/%#%/',
				'format'    => '',
				'current'   => $bk_paged,
				'total'     => (int) $wp_query->max_num_pages,
				'add_args'  => array_filter( array( 'power' => $bk_filters['power'], 'amp_max' => $bk_filters['amp_max'] ?: '', 'brand_filter' => $bk_filters['brand_filter'], 'orderby' => $bk_filters['orderby'] ) ),
				'prev_text' => __( 'السابق', 'baytkahraba' ),
				'next_text' => __( 'التالي', 'baytkahraba' ),
				'mid_size'  => 1,
			)
		);
		if ( $bk_links ) {
			echo '<nav class="pager" aria-label="' . esc_attr__( 'صفحات المنتجات', 'baytkahraba' ) . '">' . wp_kses_post( $bk_links ) . '</nav>';
		}
		?>
	<?php else : ?>
		<div class="mx-auto max-w-md py-20 text-center">
			<p class="font-display text-[22px] font-semibold text-ink"><?php esc_html_e( 'ما في أجهزة بهالفلتر حالياً', 'baytkahraba' ); ?></p>
			<p class="mt-2 text-muted"><?php esc_html_e( 'جرّب تشيل الفلتر، أو اسألنا على واتساب ومنلاقيلك البديل.', 'baytkahraba' ); ?></p>
			<div class="mt-6 flex justify-center gap-3">
				<a class="btn-ghost" href="<?php echo esc_url( $bk_base ); ?>"><?php esc_html_e( 'إزالة الفلاتر', 'baytkahraba' ); ?></a>
				<a class="btn-wa" href="<?php echo bk_esc_wa( bk_general_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php bk_the_icon( 'whatsapp' ); ?><?php esc_html_e( 'واتساب', 'baytkahraba' ); ?></a>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( 1 === $bk_paged && ( ! empty( $args['guide_html'] ) || ! empty( $args['faqs'] ) ) ) : ?>
		<div class="mt-20 grid gap-12 border-t border-line pt-14 lg:grid-cols-12 lg:gap-16">
			<?php if ( ! empty( $args['guide_html'] ) ) : ?>
				<article class="prose-bk min-w-0 lg:col-span-7"><?php echo wp_kses_post( $args['guide_html'] ); ?></article>
			<?php endif; ?>
			<?php if ( ! empty( $args['faqs'] ) ) : ?>
				<div class="min-w-0 <?php echo ! empty( $args['guide_html'] ) ? 'lg:col-span-5' : 'lg:col-span-8'; ?>"><?php bk_part( 'template-parts/global/faq', array( 'faqs' => $args['faqs'] ) ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
