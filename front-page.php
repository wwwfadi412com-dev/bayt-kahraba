<?php
/**
 * Home page: hero, trust, dynamic category grid, solar band, brands,
 * best-sellers per parent category, bundle & warranty CTAs, FAQ + SEO copy.
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
 * Cached best-sellers of a category (IDs only).
 *
 * @return WC_Product[]
 */
$bk_best = static function ( array $tax, int $limit = 10, array $meta = array() ): array {
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

$bk_tree   = bk_get_category_tree();
$bk_brands = bk_get_brands();
$bk_hero   = (int) bk_option( 'hero_image_id', 0 );
$bk_shop   = wc_get_page_permalink( 'shop' );
$bk_bundle = bk_page_by_template( 'page-bundle-builder.php' );
$bk_warr   = (int) get_option( 'bk_warranty_page_id', 0 );
?>

<!-- Hero -->
<section class="relative overflow-hidden bg-brand-navy text-white">
	<div class="pointer-events-none absolute -end-32 -top-32 size-[520px] rounded-full bg-brand-royal/40 blur-3xl"></div>
	<div class="pointer-events-none absolute -bottom-40 start-1/3 size-[420px] rounded-full bg-brand-amber/20 blur-3xl"></div>
	<div class="bk-container relative grid items-center gap-10 py-12 lg:grid-cols-12 lg:py-20">
		<div class="min-w-0 lg:col-span-6">
			<span class="bk-badge-amber mb-5"><?php bk_the_icon( 'bolt', 'size-3.5' ); ?><?php esc_html_e( 'أسعار محدّثة اليوم بسعر المحل', 'baytkahraba' ); ?></span>
			<h2 class="bk-text-balance text-3xl font-bold leading-[1.35] text-white lg:text-5xl lg:leading-[1.3]"><?php echo esc_html( (string) bk_option( 'hero_title' ) ); ?></h2>
			<p class="mt-5 max-w-xl text-base leading-8 text-slate-300 lg:text-lg"><?php echo esc_html( (string) bk_option( 'hero_subtitle' ) ); ?></p>
			<div class="mt-8 flex flex-wrap gap-3">
				<a href="<?php echo esc_url( $bk_shop ); ?>" class="bk-btn-amber bk-btn-lg"><?php bk_the_icon( 'cart' ); ?><?php esc_html_e( 'تسوّق كل الأجهزة', 'baytkahraba' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'power', 'solar', $bk_shop ) ); ?>" class="bk-btn bk-btn-lg bg-white/10 text-white ring-1 ring-white/20 hover:bg-brand-emerald"><?php bk_the_icon( 'sun' ); ?><?php esc_html_e( 'أجهزة الطاقة الشمسية والأمبير', 'baytkahraba' ); ?></a>
			</div>
			<dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 text-center">
				<div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10"><dt class="text-xs text-slate-400"><?php esc_html_e( 'ماركة ووكيل', 'baytkahraba' ); ?></dt><dd class="text-2xl font-bold bk-num"><?php echo (int) count( $bk_brands ); ?>+</dd></div>
				<div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10"><dt class="text-xs text-slate-400"><?php esc_html_e( 'جهاز متوفر', 'baytkahraba' ); ?></dt><dd class="text-2xl font-bold bk-num"><?php echo (int) array_sum( wp_list_pluck( $bk_tree, 'count' ) ); ?>+</dd></div>
				<div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10"><dt class="text-xs text-slate-400"><?php esc_html_e( 'توصيل دمشق', 'baytkahraba' ); ?></dt><dd class="text-2xl font-bold text-brand-emerald"><?php esc_html_e( 'مجاني', 'baytkahraba' ); ?></dd></div>
			</dl>
		</div>
		<div class="min-w-0 lg:col-span-6">
			<?php if ( $bk_hero ) : ?>
				<?php echo wp_get_attachment_image( $bk_hero, 'bk-banner', false, array( 'class' => 'w-full rounded-3xl object-cover shadow-2xl', 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '(min-width:1024px) 50vw, 100vw', 'alt' => (string) bk_option( 'hero_title' ) ) ); ?>
			<?php else : ?>
				<div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
					<?php foreach ( array_slice( $bk_tree, 0, 6 ) as $bk_i => $bk_node ) : ?>
						<a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="group rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 transition hover:bg-white hover:text-brand-ink <?php echo 1 === $bk_i % 3 ? 'sm:translate-y-6' : ''; ?>">
							<span class="mb-3 grid aspect-square place-items-center rounded-xl bg-white">
								<?php echo $bk_node['image_id'] ? wp_get_attachment_image( $bk_node['image_id'], 'bk-thumb', false, array( 'class' => 'size-3/4 object-contain', 'loading' => $bk_i < 3 ? 'eager' : 'lazy' ) ) : bk_icon( 'bolt', 'size-10 text-brand-amber' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</span>
							<span class="block text-sm font-bold"><?php echo esc_html( $bk_node['name'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php bk_part( 'template-parts/global/trust-bar' ); ?>

<div class="bk-container">
	<!-- Categories -->
	<?php if ( $bk_tree ) : ?>
		<section class="bk-section">
			<div class="mb-6 flex items-end justify-between">
				<div>
					<p class="bk-eyebrow"><?php esc_html_e( 'تسوّق حسب القسم', 'baytkahraba' ); ?></p>
					<h2 class="bk-section-title mt-1"><?php esc_html_e( 'كل أجهزة البيت في مكان واحد', 'baytkahraba' ); ?></h2>
				</div>
			</div>
			<ul class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5 lg:gap-5">
				<?php foreach ( $bk_tree as $bk_node ) : ?>
					<li class="bk-card bk-card-hover group overflow-hidden">
						<a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="block p-4">
							<span class="mb-4 grid aspect-[4/3] place-items-center rounded-xl bg-gradient-to-b from-brand-stripe to-white">
								<?php echo $bk_node['image_id'] ? wp_get_attachment_image( $bk_node['image_id'], 'bk-card', false, array( 'class' => 'h-4/5 w-auto object-contain transition group-hover:scale-105' ) ) : bk_icon( 'bolt', 'size-12 text-brand-amber' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</span>
							<span class="block font-bold text-brand-ink group-hover:text-brand-royal"><?php echo esc_html( $bk_node['name'] ); ?></span>
							<span class="text-xs text-brand-muted"><span class="bk-num"><?php echo (int) $bk_node['count']; ?></span> <?php esc_html_e( 'منتج', 'baytkahraba' ); ?></span>
						</a>
						<?php if ( $bk_node['children'] ) : ?>
							<ul class="flex flex-wrap gap-1.5 border-t border-brand-line p-3 text-xs">
								<?php foreach ( array_slice( $bk_node['children'], 0, 4 ) as $bk_child ) : ?>
									<li><a href="<?php echo esc_url( $bk_child['url'] ); ?>" class="inline-block rounded-md bg-brand-stripe px-2 py-1 hover:bg-brand-royal-50 hover:text-brand-royal"><?php echo esc_html( $bk_child['name'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>

<!-- Solar / Ampere band -->
<?php $bk_solar = $bk_best( array(), 10, array( array( 'key' => '_solar_inverter_friendly', 'value' => array( 'solar_ready', 'low_amp' ), 'compare' => 'IN' ) ) ); ?>
<?php if ( $bk_solar ) : ?>
	<section class="bg-gradient-to-b from-brand-emerald-50 to-brand-canvas">
		<div class="bk-container py-10">
			<div class="mb-2 flex flex-wrap items-center gap-3">
				<span class="grid size-12 place-items-center rounded-2xl bg-brand-emerald text-white"><?php bk_the_icon( 'sun', 'size-7' ); ?></span>
				<div>
					<h2 class="bk-section-title"><?php esc_html_e( 'أجهزة تعمل على الأمبير والانفرتر والطاقة الشمسية', 'baytkahraba' ); ?></h2>
					<p class="text-sm text-brand-body"><?php esc_html_e( 'سحب كهرباء منخفض مجرّب على منظومات البيت — مع ذكر الواط والأمبير لكل جهاز.', 'baytkahraba' ); ?></p>
				</div>
			</div>
			<?php
			bk_part(
				'template-parts/product/rail',
				array(
					'title'     => '',
					'products'  => $bk_solar,
					'link'      => add_query_arg( 'power', 'solar', $bk_shop ),
					'link_text' => __( 'كل أجهزة الطاقة الشمسية', 'baytkahraba' ),
				)
			);
			?>
		</div>
	</section>
<?php endif; ?>

<div class="bk-container">
	<!-- Brands -->
	<?php if ( $bk_brands ) : ?>
		<section class="bk-section">
			<p class="bk-eyebrow"><?php esc_html_e( 'الوكلاء الرسميون', 'baytkahraba' ); ?></p>
			<h2 class="bk-section-title mb-6 mt-1"><?php esc_html_e( 'ماركات أصلية بكفالة الوكيل الحقيقية', 'baytkahraba' ); ?></h2>
			<ul class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
				<?php foreach ( $bk_brands as $bk_brand ) : ?>
					<li>
						<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="bk-card bk-card-hover grid h-24 place-items-center p-4" title="<?php echo esc_attr( $bk_brand->name ); ?>">
							<?php echo bk_brand_logo( $bk_brand, 'max-h-12 w-auto object-contain text-sm', 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<!-- Best sellers per parent -->
	<?php foreach ( array_slice( $bk_tree, 0, 4 ) as $bk_node ) : ?>
		<?php
		bk_part(
			'template-parts/product/rail',
			array(
				/* translators: %s parent category */
				'title'     => sprintf( __( 'الأكثر طلباً في %s', 'baytkahraba' ), $bk_node['name'] ),
				'products'  => $bk_best( array( array( 'taxonomy' => 'product_cat', 'terms' => $bk_node['id'] ) ), 10 ),
				'link'      => $bk_node['url'],
				'link_text' => sprintf( __( 'كل %s', 'baytkahraba' ), $bk_node['name'] ),
			)
		);
		?>
	<?php endforeach; ?>

	<!-- Bundle & warranty CTAs -->
	<section class="grid gap-4 py-10 lg:grid-cols-2">
		<?php if ( $bk_bundle ) : ?>
			<a href="<?php echo esc_url( (string) get_permalink( $bk_bundle ) ); ?>" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-amber to-orange-500 p-8 text-brand-navy shadow-card transition hover:shadow-lift">
				<?php bk_the_icon( 'gift', 'mb-4 size-12' ); ?>
				<h2 class="text-2xl text-brand-navy"><?php esc_html_e( 'حاسبة تجهيز العرسان والبيت الكامل', 'baytkahraba' ); ?></h2>
				<p class="mt-2 max-w-md"><?php esc_html_e( 'براد، غسالة، شاشة، طباخ وأدوات صغيرة — اختر باقة اقتصادية أو ذكية أو فاخرة وشاهد المجموع والخصم فوراً.', 'baytkahraba' ); ?></p>
				<span class="bk-btn-navy mt-6"><?php esc_html_e( 'ابدأ تجهيز بيتك', 'baytkahraba' ); ?><?php bk_the_icon( 'chevron', 'size-4' ); ?></span>
			</a>
		<?php endif; ?>
		<?php if ( $bk_warr ) : ?>
			<a href="<?php echo esc_url( (string) get_permalink( $bk_warr ) ); ?>" class="group relative overflow-hidden rounded-3xl bg-brand-royal p-8 text-white shadow-card transition hover:shadow-lift">
				<?php bk_the_icon( 'shield', 'mb-4 size-12 text-brand-amber' ); ?>
				<h2 class="text-2xl text-white"><?php esc_html_e( 'التحقق من الكفالة والفاتورة الإلكترونية', 'baytkahraba' ); ?></h2>
				<p class="mt-2 max-w-md text-slate-200"><?php esc_html_e( 'اقرأ شروط كفالة كل وكيل وسجّل رقم فاتورتك وموبايلك لتحفظ تاريخ بدء كفالتك إلكترونياً.', 'baytkahraba' ); ?></p>
				<span class="bk-btn-amber mt-6"><?php esc_html_e( 'سجّل كفالتك', 'baytkahraba' ); ?></span>
			</a>
		<?php endif; ?>
	</section>

	<!-- FAQ + SEO copy -->
	<section class="grid gap-6 pb-14 lg:grid-cols-2">
		<?php bk_part( 'template-parts/global/faq', array( 'faqs' => bk_seo_faqs( bk_seo_context() ) ) ); ?>
		<article class="bk-card bk-prose p-5 lg:p-8">
			<h2><?php esc_html_e( 'متجر الأدوات الكهربائية والمنزلية في دمشق بسعر المحل', 'baytkahraba' ); ?></h2>
			<p><?php esc_html_e( 'نحن شريك تجزئة وجملة مرتبط مباشرة بالوكلاء الرسميين في سوريا. تجد لدينا البرادات والثلاجات (براد بابين، براد إنفرتر موفر للطاقة، فريزر أفقي وعمودي)، الغسالات الأوتوماتيك وغسالات الحوضين، الشاشات السمارت وشاشات أندرويد 4K مع رسيفر داخلي، المكيفات الإنفرتر التي تعمل على الأمبير والطاقة الشمسية، ومراوح الشحن ببطارية الليثيوم.', 'baytkahraba' ); ?></p>
			<h3><?php esc_html_e( 'أسعار الأدوات الكهربائية في سوريا اليوم', 'baytkahraba' ); ?></h3>
			<p><?php esc_html_e( 'أسعارنا هي نفس سعر المحل والجملة وتُحدّث يومياً حسب سعر الصرف، وكل جهاز يصلك مع كفالة الوكيل الحقيقية وفاتورة رسمية.', 'baytkahraba' ); ?></p>
			<h3><?php esc_html_e( 'توصيل مجاني في دمشق وريفها وشحن لكل المحافظات', 'baytkahraba' ); ?></h3>
			<p><?php esc_html_e( 'التوصيل مجاني لباب البيت في المزة، الميدان، مشروع دمر، كفرسوسة، جرمانا، صحنايا وقدسيا، وبرسم رمزي لريف دمشق الأبعد، ونشحن إلى حلب وحمص وحماة واللاذقية وطرطوس ودرعا والسويداء بتغليف آمن.', 'baytkahraba' ); ?></p>
		</article>
	</section>
</div>

<?php
get_footer();
