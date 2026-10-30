<?php
/**
 * Home — "Volt · the datasheet".
 * Hero headline + live ampere planner, bento categories, datasheet rail,
 * agents registry, bundle tiers, promise band, FAQ + SEO copy.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<div class="bk-container py-20 text-center">' . esc_html__( 'فعّل WooCommerce لعرض المتجر.', 'baytkahraba' ) . '</div>';
	get_footer();
	return;
}

/**
 * Cached best-sellers (IDs only).
 *
 * @return WC_Product[]
 */
$bk_best = static function ( array $tax, int $limit = 8, array $meta = array() ): array {
	$ids = bk_remember(
		'home-rail:' . md5( wp_json_encode( array( $tax, $meta, $limit ) ) ),
		static function () use ( $tax, $limit, $meta ) {
			return array_map(
				static fn( WC_Product $p ) => $p->get_id(),
				bk_query_products(
					array(
						'tax_query'  => array_merge( WC()->query->get_tax_query( array(), true ), $tax ), // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_query' => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_key'   => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
						'orderby'    => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
					),
					$limit
				)
			);
		}
	);
	return array_values( array_filter( array_map( 'wc_get_product', (array) $ids ) ) );
};

$bk_tree    = bk_get_category_tree();
$bk_brands  = bk_get_brands();
$bk_matrix  = bk_get_catalog_matrix();
$bk_shop    = wc_get_page_permalink( 'shop' );
$bk_bundle  = bk_page_by_template( 'page-bundle-builder.php' );
$bk_warr    = (int) get_option( 'bk_warranty_page_id', 0 );
$bk_models  = (int) array_sum( wp_list_pluck( $bk_tree, 'count' ) );
$bk_solar   = bk_power_friendly_total();

// Headline: "كهرباء أقل. بيت أكمل." → two lines, underline the last word of line 1, color the last word of line 2.
$bk_title = (string) bk_option( 'hero_title' );
$bk_lines = preg_split( '/(?<=[.؟!])\s+/u', trim( $bk_title ), 2 );
$bk_mark  = static function ( string $line, string $mode ): string {
	$words = preg_split( '/\s+/u', trim( $line ) );
	$last  = array_pop( $words );
	$head  = $words ? esc_html( implode( ' ', $words ) ) . ' ' : '';
	return 'underline' === $mode
		? $head . '<span class="relative whitespace-nowrap after:absolute after:inset-x-[-.04em] after:bottom-[.16em] after:-z-10 after:h-[.09em] after:rounded-full after:bg-brand-volt">' . esc_html( $last ) . '</span>'
		: $head . '<span class="text-brand-volt">' . esc_html( $last ) . '</span>';
};

$bk_planner = array(
	'items' => bk_ampere_planner_items(),
	'caps'  => array( 1, 2, 3, 5 ),
	'cap'   => 2,
	'shop'  => $bk_shop,
);
?>

<!-- Hero -->
<section class="bk-container grid items-stretch gap-10 pb-10 pt-10 lg:grid-cols-[1.15fr_.85fr] lg:pt-14">
	<div class="flex flex-col">
		<span class="inline-flex items-center gap-2.5 self-start rounded-full border border-black/20 bg-brand-card px-3 py-1.5 font-mono text-xs tracking-wide text-brand-ink-2">
			<span class="size-2 rounded-full bg-brand-sun shadow-[0_0_0_4px_#DDF3E6] animate-pulse2"></span>
			<?php esc_html_e( 'أسعار اليوم · محدّثة بسعر المحل', 'baytkahraba' ); ?>
		</span>
		<h2 class="bk-display relative z-0 my-6 text-[clamp(46px,7.4vw,104px)] text-brand-ink">
			<?php if ( count( $bk_lines ) === 2 ) : ?>
				<?php echo $bk_mark( $bk_lines[0], 'underline' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in closure. ?><br><?php echo $bk_mark( $bk_lines[1], 'color' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<?php echo esc_html( $bk_title ); ?>
			<?php endif; ?>
		</h2>
		<p class="max-w-xl text-[17px] leading-8 text-brand-ink-2 lg:text-lg"><?php echo esc_html( (string) bk_option( 'hero_subtitle' ) ); ?></p>
		<div class="mt-8 flex flex-wrap gap-3">
			<a href="<?php echo esc_url( $bk_shop ); ?>" class="bk-btn-amber bk-btn-lg"><?php esc_html_e( 'تسوّق الأجهزة', 'baytkahraba' ); ?><?php bk_the_icon( 'chevron', 'size-5' ); ?></a>
			<?php if ( $bk_bundle ) : ?>
				<a href="<?php echo esc_url( (string) get_permalink( $bk_bundle ) ); ?>" class="bk-btn-ghost bk-btn-lg"><?php bk_the_icon( 'gift', 'size-5' ); ?><?php esc_html_e( 'جهّز بيت العرسان', 'baytkahraba' ); ?></a>
			<?php endif; ?>
		</div>
		<dl class="mt-auto grid grid-cols-3 border-t border-black/20 pt-4 lg:mt-12">
			<div><dd class="font-display text-[26px] font-extrabold tracking-[-.02em] text-brand-ink lg:text-[32px]"><span class="bk-mono bk-ltr"><?php echo (int) $bk_models; ?>+</span></dd><dt class="text-[13px] text-brand-muted"><?php esc_html_e( 'موديل متوفر الآن', 'baytkahraba' ); ?></dt></div>
			<div class="border-s border-brand-line ps-5"><dd class="font-display text-[26px] font-extrabold tracking-[-.02em] text-brand-ink lg:text-[32px]"><span class="bk-mono"><?php echo esc_html( str_pad( (string) count( $bk_brands ), 2, '0', STR_PAD_LEFT ) ); ?></span></dd><dt class="text-[13px] text-brand-muted"><?php esc_html_e( 'وكلاء رسميون', 'baytkahraba' ); ?></dt></div>
			<div class="border-s border-brand-line ps-5"><dd class="font-display text-[26px] font-extrabold tracking-[-.02em] text-brand-ink lg:text-[32px]">0 <small class="text-sm font-medium"><?php esc_html_e( 'ل.س', 'baytkahraba' ); ?></small></dd><dt class="text-[13px] text-brand-muted"><?php esc_html_e( 'توصيل داخل دمشق', 'baytkahraba' ); ?></dt></div>
		</dl>
	</div>

	<?php if ( count( $bk_planner['items'] ) > 1 ) : ?>
		<aside class="bk-grid-dark relative flex flex-col overflow-hidden rounded-4xl bg-brand-ink p-6 text-brand-paper lg:p-7" x-data="ampPlanner(<?php echo esc_attr( wp_json_encode( $bk_planner ) ); ?>)" aria-label="<?php esc_attr_e( 'حاسبة الأمبير', 'baytkahraba' ); ?>">
			<span class="font-mono text-[11px] uppercase tracking-label text-[#9C978C]">Ampere planner · <?php esc_html_e( 'حاسبة الأمبير', 'baytkahraba' ); ?></span>
			<h3 class="mb-5 mt-1.5 font-display text-[26px] font-extrabold leading-tight text-brand-paper lg:text-[28px]"><?php esc_html_e( 'كم أمبير عندك؟', 'baytkahraba' ); ?><br><?php esc_html_e( 'نقلّك شو بيشتغل.', 'baytkahraba' ); ?></h3>
			<div class="grid grid-cols-4 gap-1.5 rounded-[14px] bg-[#232323] p-1.5" role="radiogroup" aria-label="<?php esc_attr_e( 'اشتراك الأمبير', 'baytkahraba' ); ?>">
				<template x-for="c in caps" :key="c">
					<button type="button" role="radio" class="h-11 rounded-[10px] font-mono text-[15px] font-semibold transition" :class="cap === c ? 'bg-brand-volt text-white' : 'text-[#BDB7AA] hover:text-white'" :aria-checked="cap === c" @click="cap = c" x-text="c + 'A'"></button>
				</template>
			</div>
			<div class="mt-5 flex h-4 gap-0.5 overflow-hidden rounded-lg bg-[#262626]" aria-hidden="true">
				<template x-for="item in plan.filter(i => i.fits)" :key="item.name">
					<i class="block h-full transition-[width] duration-500" :style="{ width: width(item), background: item.color }"></i>
				</template>
			</div>
			<div class="mt-2 flex justify-between font-mono text-[12.5px] text-[#BDB7AA]">
				<span><?php esc_html_e( 'الحمل:', 'baytkahraba' ); ?> <b class="bk-ltr text-white" x-text="amp(used) + 'A'"></b></span>
				<span><?php esc_html_e( 'المتاح:', 'baytkahraba' ); ?> <b class="bk-ltr text-white" x-text="cap + 'A'"></b></span>
			</div>
			<ul class="mt-4 grid flex-1 gap-2">
				<template x-for="item in plan" :key="item.name">
					<li class="flex items-center gap-3 rounded-xl border border-[#2A2A2A] bg-[#1C1C1C] px-3 py-2.5 text-[14px] transition" :class="!item.fits && 'opacity-35 line-through decoration-[#555]'">
						<i class="size-2.5 shrink-0 rounded-[3px]" :style="{ background: item.color }"></i>
						<span class="min-w-0 flex-1">
							<span class="block truncate" x-text="item.name"></span>
							<a x-show="item.url" :href="item.url" class="block truncate text-[11.5px] text-[#9C978C] no-underline hover:text-brand-volt" x-text="item.product"></a>
						</span>
						<span class="bk-ltr shrink-0 font-mono text-[12.5px] text-[#BDB7AA]" x-text="amp(item.ampere) + 'A'"></span>
					</li>
				</template>
			</ul>
			<a :href="link" href="<?php echo esc_url( add_query_arg( 'amp_max', 2, $bk_shop ) ); ?>" class="mt-4 flex items-center justify-between rounded-[14px] bg-brand-paper px-4 py-3.5 font-semibold text-brand-ink transition hover:bg-white">
				<?php esc_html_e( 'اعرض الأجهزة المناسبة لاشتراكي', 'baytkahraba' ); ?>
				<span class="grid size-8 place-items-center rounded-full bg-brand-volt text-white"><?php bk_the_icon( 'chevron', 'size-4' ); ?></span>
			</a>
		</aside>
	<?php endif; ?>
</section>

<!-- Categories (bento) -->
<?php if ( $bk_tree ) : ?>
	<section class="bk-container bk-section">
		<div class="bk-rv mb-7 flex items-end justify-between gap-6">
			<div>
				<span class="bk-kicker bk-ltr">01 — Catalog</span>
				<h2 class="bk-section-title mt-1"><?php esc_html_e( 'كل غرفة في البيت،', 'baytkahraba' ); ?><br><?php esc_html_e( 'بقسم واحد.', 'baytkahraba' ); ?></h2>
			</div>
			<a href="<?php echo esc_url( $bk_shop ); ?>" class="inline-flex shrink-0 items-center gap-1.5 border-b-[1.5px] border-brand-ink pb-0.5 text-sm font-semibold hover:border-brand-volt hover:text-brand-volt-ink"><?php esc_html_e( 'كل المنتجات', 'baytkahraba' ); ?><?php bk_the_icon( 'chevron', 'size-4' ); ?></a>
		</div>
		<?php
		$bk_total = count( $bk_tree );
		$bk_first = array_shift( $bk_tree );
		$bk_tile  = static function ( array $node, int $index, int $total, string $span ): void {
			$range = bk_price_range( $node['id'] );
			?>
			<a href="<?php echo esc_url( $node['url'] ); ?>" class="bk-rv group relative flex flex-col overflow-hidden rounded-xl2 border border-brand-line bg-brand-card p-5 transition duration-300 hover:-translate-y-1 hover:border-brand-ink <?php echo esc_attr( $span ); ?>">
				<span class="absolute end-4 top-4 grid size-9 place-items-center rounded-full border border-black/20 transition group-hover:border-brand-volt group-hover:bg-brand-volt group-hover:text-white"><?php bk_the_icon( 'arrow-ul', 'size-4' ); ?></span>
				<span class="pointer-events-none absolute -bottom-6 -start-4 text-brand-paper-2 transition duration-300 group-hover:-rotate-6 group-hover:text-brand-volt-soft"><?php bk_the_icon( bk_category_icon( $node['id'] ), 'size-36 stroke-[1.2]' ); ?></span>
				<span class="bk-mono bk-ltr relative self-start text-xs text-brand-muted"><?php echo esc_html( sprintf( '%02d / %02d', $index, $total ) ); ?></span>
				<h3 class="relative mt-auto text-[21px] lg:text-[24px]"><?php echo esc_html( $node['name'] ); ?></h3>
				<span class="relative font-mono text-[12px] text-brand-muted"><?php echo (int) $node['count']; ?> <?php esc_html_e( 'موديل', 'baytkahraba' ); ?><?php echo $range['min'] ? ' · ' . esc_html__( 'من', 'baytkahraba' ) . ' ' . esc_html( number_format( $range['min'] ) ) : ''; ?></span>
			</a>
			<?php
		};
		?>
		<div class="grid auto-rows-[150px] grid-cols-2 gap-3.5 lg:auto-rows-[170px] lg:grid-cols-6">
			<a href="<?php echo esc_url( $bk_first['url'] ); ?>" class="bk-rv group relative col-span-2 row-span-2 flex flex-col overflow-hidden rounded-xl2 bg-brand-ink p-6 text-brand-paper transition duration-300 hover:-translate-y-1 lg:col-span-3">
				<span class="absolute end-5 top-5 grid size-10 place-items-center rounded-full border border-white/20 transition group-hover:border-brand-volt group-hover:bg-brand-volt"><?php bk_the_icon( 'arrow-ul', 'size-4' ); ?></span>
				<span class="pointer-events-none absolute -bottom-10 -start-6 text-[#242424] transition duration-500 group-hover:-rotate-6 group-hover:text-[#2c2c2c]"><?php bk_the_icon( bk_category_icon( $bk_first['id'] ), 'size-72 stroke-[1]' ); ?></span>
				<span class="bk-mono bk-ltr relative self-start text-xs text-[#9C978C]"><?php echo esc_html( sprintf( '01 / %02d', $bk_total ) ); ?></span>
				<h3 class="relative mt-auto text-[30px] text-brand-paper lg:text-[40px]"><?php echo esc_html( $bk_first['name'] ); ?></h3>
				<span class="relative font-mono text-[12.5px] text-[#9C978C]"><?php echo (int) $bk_first['count']; ?> <?php esc_html_e( 'موديل', 'baytkahraba' ); ?></span>
				<?php if ( $bk_first['children'] ) : ?>
					<span class="relative mt-3 flex flex-wrap gap-1.5">
						<?php foreach ( array_slice( $bk_first['children'], 0, 5 ) as $bk_child ) : ?>
							<span class="rounded-full border border-[#3a3a3a] px-2.5 py-0.5 text-[12.5px] text-[#D8D3C8]"><?php echo esc_html( $bk_child['name'] ); ?></span>
						<?php endforeach; ?>
					</span>
				<?php endif; ?>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'power', 'solar', $bk_shop ) ); ?>" class="bk-rv group relative col-span-2 flex flex-col overflow-hidden rounded-xl2 bg-brand-sun p-5 text-white transition duration-300 hover:-translate-y-1 lg:col-span-3">
				<span class="absolute end-4 top-4 grid size-9 place-items-center rounded-full border border-white/40 transition group-hover:bg-white group-hover:text-brand-sun"><?php bk_the_icon( 'arrow-ul', 'size-4' ); ?></span>
				<span class="pointer-events-none absolute -bottom-6 -start-4 text-white/15"><?php bk_the_icon( 'solar', 'size-40 stroke-[1.2]' ); ?></span>
				<span class="bk-mono relative self-start text-xs text-white/80">SOLAR · AMP</span>
				<h3 class="relative mt-auto text-[21px] text-white lg:text-[24px]"><?php esc_html_e( 'تعمل على الأمبير والطاقة الشمسية', 'baytkahraba' ); ?></h3>
				<span class="relative font-mono text-[12px] text-white/80"><?php echo (int) $bk_solar; ?> <?php esc_html_e( 'جهاز · سحب منخفض', 'baytkahraba' ); ?></span>
			</a>
			<?php
			foreach ( $bk_tree as $bk_i => $bk_node ) {
				$bk_tile( $bk_node, $bk_i + 2, $bk_total, 0 === $bk_i ? 'col-span-2 lg:col-span-3' : 'col-span-1 lg:col-span-2' );
			}
			?>
		</div>
	</section>
<?php endif; ?>

<!-- Datasheet rail -->
<?php $bk_rail = $bk_best( array(), 8 ); ?>
<?php if ( $bk_rail ) : ?>
	<section class="bk-container bk-section !pt-2">
		<div class="bk-rv mb-7 flex flex-wrap items-end justify-between gap-6">
			<div>
				<span class="bk-kicker bk-ltr">02 — Datasheet</span>
				<h2 class="bk-section-title mt-1"><?php esc_html_e( 'اقرأ الجهاز', 'baytkahraba' ); ?><br><?php esc_html_e( 'قبل ما تشتريه.', 'baytkahraba' ); ?></h2>
			</div>
			<p class="max-w-md text-[15px] text-brand-muted"><?php esc_html_e( 'كل بطاقة تعرض الاستطاعة والسحب ومقياس استهلاك ملوّن، فتعرف من النظرة الأولى إن كان الجهاز يناسب اشتراكك.', 'baytkahraba' ); ?></p>
		</div>
		<div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 lg:gap-3.5">
			<?php foreach ( $bk_rail as $bk_i => $bk_p ) : ?>
				<div class="bk-rv"><?php bk_part( 'template-parts/product/card', array( 'product' => $bk_p, 'position' => $bk_i ) ); ?></div>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<!-- Agents registry -->
<?php if ( $bk_brands ) : ?>
	<section class="bk-container bk-section !pt-2">
		<div class="bk-rv mb-7 flex flex-wrap items-end justify-between gap-6">
			<div>
				<span class="bk-kicker bk-ltr">03 — Official agents</span>
				<h2 class="bk-section-title mt-1"><?php esc_html_e( 'سجلّ الوكلاء.', 'baytkahraba' ); ?></h2>
			</div>
			<p class="max-w-md text-[15px] text-brand-muted"><?php esc_html_e( 'نتعامل مباشرة مع الوكلاء الرسميين فقط. كل كفالة حقيقية ولها مراكز صيانة في المحافظات.', 'baytkahraba' ); ?></p>
		</div>
		<ol class="border-t-[1.5px] border-brand-ink">
			<?php foreach ( $bk_brands as $bk_i => $bk_brand ) : ?>
				<?php $bk_meta = bk_brand_meta( $bk_brand ); ?>
				<li class="bk-rv">
					<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="group grid grid-cols-[40px_1fr_44px] items-center gap-4 border-b border-black/20 py-5 transition-all hover:bg-brand-card hover:px-4 lg:grid-cols-[60px_1.4fr_1fr_1fr_1fr_48px]">
						<span class="bk-mono bk-ltr text-[13px] text-brand-muted"><?php echo esc_html( sprintf( 'A/%02d', $bk_i + 1 ) ); ?></span>
						<span class="font-display text-[24px] font-extrabold tracking-[-.02em] text-brand-ink lg:text-[30px]"><?php echo esc_html( $bk_brand->name ); ?></span>
						<span class="hidden lg:block"><span class="block text-xs text-brand-muted"><?php esc_html_e( 'الكفالة', 'baytkahraba' ); ?></span><span class="bk-mono bk-ltr text-[15px] font-semibold text-brand-ink"><?php echo (int) $bk_meta['warranty_months']; ?> MO</span></span>
						<span class="hidden lg:block"><span class="block text-xs text-brand-muted"><?php esc_html_e( 'مراكز الصيانة', 'baytkahraba' ); ?></span><span class="bk-mono text-[15px] font-semibold text-brand-ink"><?php echo esc_html( str_pad( (string) count( $bk_meta['service_centers'] ), 2, '0', STR_PAD_LEFT ) ); ?></span></span>
						<span class="hidden lg:block"><span class="block text-xs text-brand-muted"><?php esc_html_e( 'موديلات', 'baytkahraba' ); ?></span><span class="bk-mono text-[15px] font-semibold text-brand-ink"><?php echo esc_html( str_pad( (string) (int) ( $bk_matrix['brand_totals'][ $bk_brand->term_id ] ?? 0 ), 2, '0', STR_PAD_LEFT ) ); ?></span></span>
						<span class="grid size-11 place-items-center rounded-full border border-black/20 transition group-hover:-rotate-45 group-hover:border-brand-volt group-hover:bg-brand-volt group-hover:text-white"><?php bk_the_icon( 'chevron', 'size-5' ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
<?php endif; ?>

<!-- Bundle -->
<?php
if ( $bk_bundle && function_exists( 'bk_bundle_payload' ) ) :
	$bk_payload = bk_bundle_payload();
	$bk_tiers   = array();
	foreach ( $bk_payload['tiers'] as $bk_key => $bk_label ) {
		$bk_names = array();
		$bk_sum   = 0.0;
		foreach ( $bk_payload['steps'] as $bk_step ) {
			foreach ( $bk_step['picks'][ $bk_key ] ?? array() as $bk_pid ) {
				foreach ( $bk_step['candidates'] as $bk_c ) {
					if ( $bk_c['id'] === $bk_pid ) {
						$bk_sum    += $bk_c['price'];
						$bk_names[] = $bk_c['name'];
					}
				}
			}
		}
		$bk_disc              = count( $bk_names ) >= $bk_payload['minItems'] ? round( $bk_sum * $bk_payload['discount'] / 100 ) : 0;
		$bk_tiers[ $bk_key ] = array( 'label' => $bk_label, 'total' => $bk_sum - $bk_disc, 'names' => array_slice( $bk_names, 0, 3 ) );
	}
	?>
	<section class="bk-container bk-section !pt-2">
		<div class="bk-rv relative grid gap-10 overflow-hidden rounded-4xl bg-brand-volt p-7 text-white lg:grid-cols-[1fr_1.4fr] lg:p-12">
			<span class="pointer-events-none absolute -bottom-32 -start-20 size-[360px] rounded-full border-[60px] border-white/10"></span>
			<div class="relative">
				<span class="font-mono text-[11px] uppercase tracking-label text-white/80">04 — Home setup</span>
				<h2 class="bk-display mt-3 text-[clamp(34px,4.4vw,58px)] text-white"><?php esc_html_e( 'جهّز بيتك', 'baytkahraba' ); ?><br><?php esc_html_e( 'بثلاث نقرات.', 'baytkahraba' ); ?></h2>
				<p class="mt-4 text-[16.5px] text-white/90"><?php printf( esc_html__( 'براد، غسالة، شاشة، طباخ وأدوات صغيرة. اختر الباقة، بدّل ما تريد، واحصل على خصم %s%% إضافي على الباقة كاملة.', 'baytkahraba' ), esc_html( (string) $bk_payload['discount'] ) ); ?></p>
			</div>
			<div class="relative grid gap-3 sm:grid-cols-3">
				<?php foreach ( $bk_tiers as $bk_key => $bk_tier ) : ?>
					<?php $bk_hot = 'smart' === $bk_key; ?>
					<a href="<?php echo esc_url( (string) get_permalink( $bk_bundle ) ); ?>" class="flex flex-col gap-2 rounded-[20px] border p-5 transition hover:-translate-y-1 <?php echo $bk_hot ? 'border-white bg-white text-brand-ink sm:-translate-y-2.5' : 'border-white/25 bg-black/15'; ?>">
						<span class="font-mono text-[11px] uppercase tracking-label <?php echo $bk_hot ? 'text-brand-muted' : 'text-white/75'; ?>"><?php echo esc_html( strtoupper( $bk_key ) ); ?><?php echo $bk_hot ? ' · ' . esc_html__( 'الأكثر طلباً', 'baytkahraba' ) : ''; ?></span>
						<strong class="font-display text-[22px] font-extrabold leading-tight"><?php echo esc_html( $bk_tier['label'] ); ?></strong>
						<span class="bk-mono bk-ltr self-start text-sm"><?php echo esc_html( bk_format_syp( $bk_tier['total'], false ) ); ?></span>
						<ul class="space-y-1 text-[13px] <?php echo $bk_hot ? 'text-brand-ink-2' : 'text-white/90'; ?>">
							<?php foreach ( $bk_tier['names'] as $bk_name ) : ?>
								<li class="truncate">— <?php echo esc_html( $bk_name ); ?></li>
							<?php endforeach; ?>
						</ul>
						<span class="mt-auto grid h-10 place-items-center rounded-xl border-[1.5px] text-sm font-semibold <?php echo $bk_hot ? 'border-brand-ink bg-brand-ink text-white' : 'border-white'; ?>"><?php esc_html_e( 'ابدأ بهذه الباقة', 'baytkahraba' ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php bk_part( 'template-parts/global/trust-bar' ); ?>

<!-- FAQ + SEO copy -->
<section class="bk-container grid gap-6 pb-14 lg:grid-cols-2">
	<div class="bk-rv"><?php bk_part( 'template-parts/global/faq', array( 'faqs' => bk_seo_faqs( bk_seo_context() ) ) ); ?></div>
	<article class="bk-rv bk-card bk-prose p-6 lg:p-9">
		<h2><?php esc_html_e( 'متجر الأدوات الكهربائية والمنزلية في دمشق بسعر المحل', 'baytkahraba' ); ?></h2>
		<p><?php esc_html_e( 'نحن شريك تجزئة وجملة مرتبط مباشرة بالوكلاء الرسميين في سوريا. تجد لدينا البرادات والثلاجات (براد بابين، براد إنفرتر موفر للطاقة، فريزر أفقي وعمودي)، الغسالات الأوتوماتيك وغسالات الحوضين، الشاشات السمارت وشاشات أندرويد 4K مع رسيفر داخلي، المكيفات الإنفرتر التي تعمل على الأمبير والطاقة الشمسية، ومراوح الشحن ببطارية الليثيوم.', 'baytkahraba' ); ?></p>
		<h3><?php esc_html_e( 'أسعار الأدوات الكهربائية في سوريا اليوم', 'baytkahraba' ); ?></h3>
		<p><?php esc_html_e( 'أسعارنا هي نفس سعر المحل والجملة وتُحدّث يومياً حسب سعر الصرف، وكل جهاز يصلك مع كفالة الوكيل الحقيقية وفاتورة رسمية.', 'baytkahraba' ); ?></p>
		<h3><?php esc_html_e( 'توصيل مجاني في دمشق وريفها وشحن لكل المحافظات', 'baytkahraba' ); ?></h3>
		<p><?php esc_html_e( 'التوصيل مجاني لباب البيت في المزة، الميدان، مشروع دمر، كفرسوسة، جرمانا، صحنايا وقدسيا، وبرسم رمزي لريف دمشق الأبعد، ونشحن إلى حلب وحمص وحماة واللاذقية وطرطوس ودرعا والسويداء بتغليف آمن.', 'baytkahraba' ); ?></p>
	</article>
</section>

<?php
get_footer();
