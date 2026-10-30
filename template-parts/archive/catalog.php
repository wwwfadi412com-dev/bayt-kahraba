<?php
/**
 * Shared catalog layout for: shop, search, parent/child categories, brand hubs
 * and brand × category silos.
 *
 * Server renders the first page from the main query (crawlable, no-JS), then
 * the Alpine `catalog` component takes over for instant filtering & paging.
 *
 * @var array $args {
 *   context:      array{brand?: string, cat?: string, s?: string}  REST context.
 *   h1:           string
 *   intro_html:   string   Above-the-fold SEO hook (trusted HTML).
 *   guide_html:   string   Below-the-grid buying guide (trusted HTML).
 *   faqs:         array
 *   child_cats:   array    Child category nodes for the drill-down cards.
 *   brand_filter: array    [{term, count}] brand filter options.
 *   cat_filter:   array    [{term, count}] category filter options.
 *   before_grid:  string   Extra trusted HTML (e.g. hub tabs).
 * }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$bk_filters  = bk_catalog_filters( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification
$bk_context  = $args['context'] ?? array();
$bk_base_url = strtok( (string) ( bk_canonical_url() ?: home_url( add_query_arg( array() ) ) ), '?' );
$bk_paged    = max( 1, (int) get_query_var( 'paged' ) );
$bk_config   = array(
	'context' => $bk_context,
	'filters' => $bk_filters,
	'base'    => preg_replace( '#/page/\d+/?$#', '/', $bk_base_url ),
	'total'   => (int) $wp_query->found_posts,
);
$bk_orderby = array(
	''           => __( 'الترتيب الافتراضي', 'baytkahraba' ),
	'popularity' => __( 'الأكثر مبيعاً', 'baytkahraba' ),
	'price'      => __( 'السعر: من الأقل للأعلى', 'baytkahraba' ),
	'price-desc' => __( 'السعر: من الأعلى للأقل', 'baytkahraba' ),
	'date'       => __( 'الأحدث', 'baytkahraba' ),
	'rating'     => __( 'الأعلى تقييماً', 'baytkahraba' ),
);
$bk_power_opts = array(
	''              => __( 'كل الأجهزة', 'baytkahraba' ),
	'solar'         => __( 'تعمل على البطارية / الانفرتر / الطاقة الشمسية', 'baytkahraba' ),
	'low_amp'       => __( 'اقتصادية - أمبير منخفض', 'baytkahraba' ),
	'standard_grid' => __( 'تحتاج كهرباء نظامية', 'baytkahraba' ),
);
?>
<div class="bk-container py-6 lg:py-10" x-data="catalog(<?php echo esc_attr( wp_json_encode( $bk_config ) ); ?>)" @popstate.window="fromUrl()">

	<header class="mb-6 max-w-4xl">
		<span class="bk-kicker bk-ltr">Catalog · <?php echo (int) $wp_query->found_posts; ?> items</span>
		<h1 class="mt-2 text-[30px] leading-[1.15] lg:text-[46px]"><?php echo esc_html( $args['h1'] ?? '' ); ?></h1>
		<?php if ( ! empty( $args['intro_html'] ) ) : ?>
			<div class="bk-prose mt-3 text-[15px] text-brand-body"><?php echo wp_kses_post( $args['intro_html'] ); ?></div>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $args['child_cats'] ) ) : ?>
		<?php bk_part( 'template-parts/archive/child-categories', array( 'nodes' => $args['child_cats'] ) ); ?>
	<?php endif; ?>

	<?php echo $args['before_grid'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted template HTML ?>

	<?php // Quick power filter: the #1 concern of Syrian households. ?>
	<div class="bk-scrollbar-none -mx-4 mb-5 flex gap-2 overflow-x-auto px-4" role="radiogroup" aria-label="<?php esc_attr_e( 'فلتر الكهرباء', 'baytkahraba' ); ?>">
		<?php foreach ( $bk_power_opts as $bk_value => $bk_label ) : ?>
			<button type="button" role="radio" class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-semibold transition"
				:class="filters.power === '<?php echo esc_attr( $bk_value ); ?>' ? '<?php echo 'solar' === $bk_value || 'low_amp' === $bk_value ? 'border-brand-emerald bg-brand-emerald text-white' : 'border-brand-navy bg-brand-navy text-white'; ?>' : 'border-brand-line bg-white text-brand-ink hover:border-brand-emerald'"
				:aria-checked="filters.power === '<?php echo esc_attr( $bk_value ); ?>'" @click="set('power', '<?php echo esc_attr( $bk_value ); ?>')">
				<?php if ( 'solar' === $bk_value ) : ?>
					<?php bk_the_icon( 'sun', 'size-4' ); ?>
				<?php elseif ( 'low_amp' === $bk_value ) : ?>
					<?php bk_the_icon( 'bolt', 'size-4' ); ?>
				<?php endif; ?>
				<?php echo esc_html( $bk_label ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="grid gap-6 lg:grid-cols-12">
		<!-- Filters -->
		<aside class="min-w-0 lg:col-span-3">
			<div class="fixed inset-0 z-[70] hidden lg:static lg:z-auto lg:block" :class="{ '!block': filtersOpen }" x-trap.noscroll="filtersOpen && window.innerWidth < 1024">
				<div class="bk-overlay lg:hidden" @click="filtersOpen = false"></div>
				<form class="absolute inset-y-0 end-0 flex w-[88%] max-w-sm flex-col overflow-y-auto bg-brand-card p-5 lg:static lg:w-auto lg:max-w-none lg:overflow-visible lg:rounded-xl2 lg:border lg:border-brand-line lg:shadow-card lg:sticky lg:top-[calc(var(--bk-header-h)+4rem)]"
					method="get" @submit.prevent="apply()" aria-label="<?php esc_attr_e( 'تصفية المنتجات', 'baytkahraba' ); ?>">
					<div class="mb-4 flex items-center justify-between">
						<p class="flex items-center gap-2 font-bold text-brand-ink"><?php bk_the_icon( 'filter', 'size-5 text-brand-royal' ); ?><?php esc_html_e( 'تصفية النتائج', 'baytkahraba' ); ?></p>
						<button type="button" class="text-xs font-semibold text-brand-royal hover:underline" x-show="hasFilters()" @click="reset()"><?php esc_html_e( 'مسح الكل', 'baytkahraba' ); ?></button>
						<button type="button" class="bk-icon-btn lg:hidden" @click="filtersOpen = false" aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
					</div>

					<fieldset class="border-t border-brand-line py-4">
						<legend class="mb-2 text-sm font-bold text-brand-ink"><?php esc_html_e( 'السحب بالأمبير', 'baytkahraba' ); ?></legend>
						<div class="flex flex-wrap gap-2">
							<?php foreach ( array( 0 => __( 'الكل', 'baytkahraba' ), 1 => __( 'حتى 1 أمبير', 'baytkahraba' ), 2 => __( 'حتى 2 أمبير', 'baytkahraba' ), 5 => __( 'حتى 5 أمبير', 'baytkahraba' ) ) as $bk_amp => $bk_label ) : ?>
								<button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-semibold" :class="Number(filters.amp_max) === <?php echo (int) $bk_amp; ?> ? 'border-brand-emerald bg-brand-emerald-50 text-brand-emerald-hover' : 'border-brand-line hover:border-brand-emerald'" @click="set('amp_max', <?php echo (int) $bk_amp; ?>)"><?php echo esc_html( $bk_label ); ?></button>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<?php if ( ! empty( $args['brand_filter'] ) ) : ?>
						<fieldset class="border-t border-brand-line py-4">
							<legend class="mb-2 text-sm font-bold text-brand-ink"><?php esc_html_e( 'الماركة', 'baytkahraba' ); ?></legend>
							<div class="max-h-64 space-y-1 overflow-y-auto">
								<label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-brand-stripe"><input type="radio" class="accent-brand-royal" value="" x-model="filters.brand_filter" @change="apply()"><?php esc_html_e( 'كل الماركات', 'baytkahraba' ); ?></label>
								<?php foreach ( $args['brand_filter'] as $bk_row ) : ?>
									<label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-brand-stripe">
										<input type="radio" class="accent-brand-royal" value="<?php echo esc_attr( $bk_row['term']->slug ); ?>" x-model="filters.brand_filter" @change="apply()">
										<span class="flex-1"><?php echo esc_html( $bk_row['term']->name ); ?></span>
										<span class="text-xs text-brand-muted bk-num"><?php echo (int) $bk_row['count']; ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php endif; ?>

					<?php if ( ! empty( $args['cat_filter'] ) ) : ?>
						<fieldset class="border-t border-brand-line py-4">
							<legend class="mb-2 text-sm font-bold text-brand-ink"><?php esc_html_e( 'القسم', 'baytkahraba' ); ?></legend>
							<div class="max-h-64 space-y-1 overflow-y-auto">
								<label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-brand-stripe"><input type="radio" class="accent-brand-royal" value="" x-model="filters.cat_filter" @change="apply()"><?php esc_html_e( 'كل الأقسام', 'baytkahraba' ); ?></label>
								<?php foreach ( $args['cat_filter'] as $bk_row ) : ?>
									<label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-brand-stripe">
										<input type="radio" class="accent-brand-royal" value="<?php echo esc_attr( $bk_row['term']->slug ); ?>" x-model="filters.cat_filter" @change="apply()">
										<span class="flex-1"><?php echo esc_html( $bk_row['term']->name ); ?></span>
										<span class="text-xs text-brand-muted bk-num"><?php echo (int) $bk_row['count']; ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php endif; ?>

					<fieldset class="border-t border-brand-line py-4">
						<legend class="mb-2 text-sm font-bold text-brand-ink"><?php esc_html_e( 'السعر (ل.س)', 'baytkahraba' ); ?></legend>
						<div class="grid grid-cols-2 gap-2">
							<input type="number" min="0" step="1000" inputmode="numeric" class="bk-input !px-3 bk-num" placeholder="<?php esc_attr_e( 'من', 'baytkahraba' ); ?>" x-model.number="filters.min_price" @change.debounce.400ms="apply()" aria-label="<?php esc_attr_e( 'أقل سعر', 'baytkahraba' ); ?>">
							<input type="number" min="0" step="1000" inputmode="numeric" class="bk-input !px-3 bk-num" placeholder="<?php esc_attr_e( 'إلى', 'baytkahraba' ); ?>" x-model.number="filters.max_price" @change.debounce.400ms="apply()" aria-label="<?php esc_attr_e( 'أعلى سعر', 'baytkahraba' ); ?>">
						</div>
					</fieldset>

					<label class="flex cursor-pointer items-center gap-2 border-t border-brand-line py-4 text-sm font-semibold text-brand-ink">
						<input type="checkbox" class="size-4 accent-brand-emerald" x-model="filters.in_stock" @change="apply()"><?php esc_html_e( 'المتوفر في المحل فقط', 'baytkahraba' ); ?>
					</label>

					<button type="button" class="bk-btn-navy mt-auto w-full lg:hidden" @click="filtersOpen = false"><?php esc_html_e( 'عرض', 'baytkahraba' ); ?> <span class="bk-num" x-text="total"></span> <?php esc_html_e( 'منتج', 'baytkahraba' ); ?></button>
				</form>
			</div>
		</aside>

		<!-- Results -->
		<section class="min-w-0 lg:col-span-9" aria-live="polite" :aria-busy="loading">
			<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
				<p class="text-sm text-brand-muted"><span class="font-bold text-brand-ink bk-num" x-text="total"><?php echo (int) $wp_query->found_posts; ?></span> <?php esc_html_e( 'منتج', 'baytkahraba' ); ?></p>
				<div class="flex items-center gap-2">
					<button type="button" class="bk-btn-ghost bk-btn-sm lg:hidden" @click="filtersOpen = true"><?php bk_the_icon( 'filter', 'size-4' ); ?><?php esc_html_e( 'تصفية', 'baytkahraba' ); ?></button>
					<label class="sr-only" for="bk-orderby"><?php esc_html_e( 'ترتيب', 'baytkahraba' ); ?></label>
					<select id="bk-orderby" class="bk-select !w-auto !py-2 text-sm" x-model="filters.orderby" @change="apply()">
						<?php foreach ( $bk_orderby as $bk_value => $bk_label ) : ?>
							<option value="<?php echo esc_attr( $bk_value ); ?>" <?php selected( $bk_filters['orderby'], $bk_value ); ?>><?php echo esc_html( $bk_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div x-ref="grid" class="grid grid-cols-2 gap-3 transition-opacity md:grid-cols-3 lg:gap-5 xl:grid-cols-4" :class="loading && 'opacity-50 pointer-events-none'">
				<?php if ( have_posts() ) : ?>
					<?php
					$bk_position = 0;
					while ( have_posts() ) :
						the_post();
						bk_part(
							'template-parts/product/card',
							array(
								'product'  => wc_get_product( get_the_ID() ),
								'position' => $bk_position,
								'lcp'      => 0 === $bk_position && 1 === $bk_paged,
							)
						);
						++$bk_position;
					endwhile;
					?>
				<?php endif; ?>
			</div>

			<div x-show="!loading && total === 0" <?php echo have_posts() ? 'x-cloak' : ''; ?> class="bk-card grid place-items-center gap-3 p-10 text-center">
				<span class="grid size-14 place-items-center rounded-full bg-brand-stripe text-brand-muted"><?php bk_the_icon( 'search', 'size-7' ); ?></span>
				<p class="font-semibold text-brand-ink"><?php esc_html_e( 'لا توجد منتجات مطابقة لهذه الفلاتر', 'baytkahraba' ); ?></p>
				<button type="button" class="bk-btn-ghost" @click="reset()"><?php esc_html_e( 'مسح الفلاتر', 'baytkahraba' ); ?></button>
			</div>

			<div x-ref="pagination" @click="paginate($event)">
				<?php echo bk_catalog_pagination( (int) $wp_query->max_num_pages, $bk_paged, $bk_config['base'], $bk_filters, ! empty( $bk_context['s'] ) ? array( 's' => $bk_context['s'], 'post_type' => 'product' ) : array() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>
	</div>

	<?php if ( ! empty( $args['guide_html'] ) && 1 === $bk_paged ) : ?>
		<article class="bk-card bk-prose mt-12 p-5 lg:p-10">
			<?php echo wp_kses_post( $args['guide_html'] ); ?>
		</article>
	<?php endif; ?>

	<?php if ( ! empty( $args['faqs'] ) && 1 === $bk_paged ) : ?>
		<div class="mt-8"><?php bk_part( 'template-parts/global/faq', array( 'faqs' => $args['faqs'] ) ); ?></div>
	<?php endif; ?>
</div>
