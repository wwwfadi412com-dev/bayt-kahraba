<?php
/**
 * Home — "ضوء · Showroom".
 * Hero, shop-by-category, best sellers, the ampere switch, how ordering works
 * (WhatsApp), official agents, FAQ + SEO copy.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<div class="wrap py-20 text-center">' . esc_html__( 'فعّل WooCommerce لعرض المتجر.', 'baytkahraba' ) . '</div>';
	get_footer();
	return;
}

$bk_shop   = wc_get_page_permalink( 'shop' );
$bk_cats   = bk_shop_categories();
$bk_brands = bk_get_brands();

$bk_ids = static function ( string $key, array $args, int $limit ): array {
	$ids = bk_remember(
		'home2:' . $key,
		static fn() => array_map( static fn( WC_Product $p ) => $p->get_id(), bk_query_products( $args, $limit ) )
	);
	return array_values( array_filter( array_map( 'wc_get_product', (array) $ids ) ) );
};

$bk_best = $bk_ids(
	'best',
	array(
		'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'  => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
	),
	8
);
$bk_amp = $bk_ids(
	'amp',
	array(
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array( 'key' => '_ampere_draw', 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL(10,2)' ),
			array( 'key' => '_ampere_draw', 'value' => 5, 'compare' => '<=', 'type' => 'DECIMAL(10,2)' ),
		),
		'meta_key'   => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'    => 'meta_value_num',
		'order'      => 'DESC',
	),
	20
);
// Lightest first, so switching from 5A down to 1A visibly trims the end of the rail.
usort( $bk_amp, static fn( $a, $b ) => bk_get_product_power( $a->get_id() )['ampere'] <=> bk_get_product_power( $b->get_id() )['ampere'] );

$bk_hero_id  = (int) bk_option( 'hero_image_id' );
$bk_title    = (string) bk_option( 'hero_title' );
$bk_subtitle = (string) bk_option( 'hero_subtitle' );
$bk_demo     = $bk_best ? $bk_best[0] : null;
?>

<!-- Hero -->
<section class="relative overflow-hidden">
	<div class="wrap grid items-center gap-6 pb-6 pt-8 lg:min-h-[640px] lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-4 lg:pb-14 lg:pt-10">
		<div class="relative z-10 max-w-xl">
			<p class="eyebrow"><?php esc_html_e( 'أسعار اليوم · محدّثة بسعر المحل', 'baytkahraba' ); ?></p>
			<h1 class="mt-5 font-display text-[40px] font-extrabold leading-[1.12] tracking-[-.03em] text-ink sm:text-[52px] lg:text-[64px]">
				<?php
				// The last two words get the yellow highlighter.
				$bk_words = preg_split( '/\s+/u', trim( $bk_title ) );
				$bk_tail  = count( $bk_words ) > 3 ? array_splice( $bk_words, -2 ) : array();
				echo esc_html( implode( ' ', $bk_words ) );
				if ( $bk_tail ) {
					echo ' <span class="mark whitespace-nowrap">' . esc_html( implode( ' ', $bk_tail ) ) . '</span>';
				}
				?>
			</h1>
			<p class="mt-5 text-[17px] leading-8 text-muted lg:text-[18px]"><?php echo esc_html( $bk_subtitle ); ?></p>
			<div class="mt-8 flex flex-wrap gap-3">
				<a href="<?php echo esc_url( $bk_shop ); ?>" class="btn-ink btn-lg"><?php esc_html_e( 'تصفّح الأجهزة', 'baytkahraba' ); ?><?php bk_the_icon( 'arrow' ); ?></a>
				<a href="<?php echo bk_esc_wa( bk_general_whatsapp_url() ); ?>" class="btn-ghost btn-lg" target="_blank" rel="noopener"><?php bk_the_icon( 'whatsapp', 'size-5 text-wa' ); ?><?php esc_html_e( 'اسألنا على واتساب', 'baytkahraba' ); ?></a>
			</div>
			<ul class="mt-9 flex flex-wrap gap-x-6 gap-y-3 text-[14px] text-ink-2">
				<li class="flex items-center gap-2"><?php bk_the_icon( 'truck', 'size-[18px]' ); ?><?php esc_html_e( 'توصيل مجاني بدمشق', 'baytkahraba' ); ?></li>
				<li class="flex items-center gap-2"><?php bk_the_icon( 'shield', 'size-[18px]' ); ?><?php esc_html_e( 'كفالة الوكيل', 'baytkahraba' ); ?></li>
				<li class="flex items-center gap-2"><?php bk_the_icon( 'eye', 'size-[18px]' ); ?><?php esc_html_e( 'افحص قبل ما تدفع', 'baytkahraba' ); ?></li>
			</ul>
		</div>
		<div class="relative -mx-4 sm:mx-0 lg:-me-16">
			<div class="hero-glow pointer-events-none absolute start-[30%] top-[2%] size-[46%]" aria-hidden="true"></div>
			<?php if ( $bk_hero_id ) : ?>
				<?php echo wp_get_attachment_image( $bk_hero_id, 'full', false, array( 'class' => 'relative w-full', 'fetchpriority' => 'high', 'loading' => 'eager', 'alt' => '', 'sizes' => '(min-width: 1024px) 60vw, 100vw' ) ); ?>
			<?php else : ?>
				<img class="relative w-full" src="<?php echo esc_url( BK_URI . '/assets/img/hero.webp' ); ?>" srcset="<?php echo esc_url( BK_URI . '/assets/img/hero-800.webp' ); ?> 800w, <?php echo esc_url( BK_URI . '/assets/img/hero.webp' ); ?> 1600w" sizes="(min-width: 1024px) 60vw, 100vw" width="1600" height="1000" alt="" fetchpriority="high" decoding="async">
			<?php endif; ?>
		</div>
	</div>
</section>

<!-- Shop by category -->
<?php if ( $bk_cats ) : ?>
	<section class="wrap pb-6 pt-8 lg:pt-12" aria-labelledby="bk-cats-title">
		<div class="mb-6 flex items-end justify-between gap-4">
			<h2 id="bk-cats-title" class="font-display text-[22px] font-semibold lg:text-[28px]"><?php esc_html_e( 'شو بدك تجهّز اليوم؟', 'baytkahraba' ); ?></h2>
			<a href="<?php echo esc_url( $bk_shop ); ?>" class="link-arrow shrink-0"><?php esc_html_e( 'كل الأقسام', 'baytkahraba' ); ?><?php bk_the_icon( 'arrow', 'size-4' ); ?></a>
		</div>
		<ul class="rail lg:grid lg:grid-cols-6 lg:gap-x-5 lg:gap-y-8">
			<?php foreach ( $bk_cats as $bk_node ) : ?>
				<li class="ctile lg:[&:nth-child(n+13)]:hidden">
					<a href="<?php echo esc_url( $bk_node['url'] ); ?>" class="contents">
						<span class="ctile-media"><?php echo bk_category_image( $bk_node ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="ctile-name"><?php echo esc_html( $bk_node['name'] ); ?></span>
					</a>
					<span class="ctile-count"><?php echo esc_html( sprintf( /* translators: %d: models */ __( '%d موديل', 'baytkahraba' ), (int) $bk_node['count'] ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<!-- Best sellers -->
<?php if ( $bk_best ) : ?>
	<section class="wrap section !pt-12" aria-labelledby="bk-best-title">
		<div class="mb-8 flex items-end justify-between gap-4">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'الأكثر طلباً', 'baytkahraba' ); ?></p>
				<h2 id="bk-best-title" class="h-section mt-2"><?php esc_html_e( 'اللي عم ياخدوه الناس هالأسبوع', 'baytkahraba' ); ?></h2>
			</div>
			<a href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', $bk_shop ) ); ?>" class="link-arrow hidden shrink-0 sm:inline-flex"><?php esc_html_e( 'كل الأجهزة', 'baytkahraba' ); ?><?php bk_the_icon( 'arrow', 'size-4' ); ?></a>
		</div>
		<div class="grid grid-cols-2 gap-x-3 gap-y-9 sm:grid-cols-3 lg:grid-cols-4 lg:gap-x-6 lg:gap-y-12">
			<?php foreach ( $bk_best as $bk_i => $bk_p ) : ?>
				<?php bk_part( 'template-parts/product/card', array( 'product' => $bk_p, 'position' => 10 + $bk_i ) ); ?>
			<?php endforeach; ?>
		</div>
		<a href="<?php echo esc_url( $bk_shop ); ?>" class="btn-ghost mt-10 w-full sm:hidden"><?php esc_html_e( 'كل الأجهزة', 'baytkahraba' ); ?></a>
	</section>
<?php endif; ?>

<!-- Ampere switch -->
<?php if ( count( $bk_amp ) > 2 ) : ?>
	<section class="wrap" aria-labelledby="bk-amp-title" data-amp-switch>
		<div class="rounded-[32px] bg-stage px-5 py-10 sm:px-8 lg:px-14 lg:py-16">
			<div class="grid items-end gap-8 lg:grid-cols-2">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'مفتاح الأمبير', 'baytkahraba' ); ?></p>
					<h2 id="bk-amp-title" class="h-section mt-2"><?php esc_html_e( 'عندك اشتراك أمبير؟', 'baytkahraba' ); ?><br><?php esc_html_e( 'شوف شو بيشتغل عليه.', 'baytkahraba' ); ?></h2>
					<p class="lede"><?php esc_html_e( 'كل جهاز عنا مكتوب جنبه كم بيسحب. اختار اشتراكك ومنفرجيك الأجهزة اللي بتمشي معه بدون ما يفصل.', 'baytkahraba' ); ?></p>
				</div>
				<div class="lg:justify-self-end">
					<div class="amp-plate" role="group" aria-label="<?php esc_attr_e( 'اشتراك الأمبير', 'baytkahraba' ); ?>">
						<?php foreach ( array( 1, 2, 3, 5 ) as $bk_cap ) : ?>
							<button type="button" class="amp-key" data-amp="<?php echo (int) $bk_cap; ?>" aria-pressed="<?php echo 5 === $bk_cap ? 'true' : 'false'; ?>">
								<span class="bk-ltr leading-none"><?php echo (int) $bk_cap; ?><small>A</small></span>
							</button>
						<?php endforeach; ?>
					</div>
					<p class="mt-3 text-[14px] text-muted"><span class="font-semibold text-ink bk-num" data-amp-count><?php echo count( $bk_amp ); ?></span> <?php esc_html_e( 'جهاز بيشتغل على هالاشتراك', 'baytkahraba' ); ?></p>
				</div>
			</div>
			<div class="mt-10 hidden justify-end gap-2 lg:flex">
				<button type="button" class="icon-btn bg-white ring-1 ring-line" data-rail-prev aria-label="<?php esc_attr_e( 'السابق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'chevron', 'size-5 rotate-180' ); ?></button>
				<button type="button" class="icon-btn bg-white ring-1 ring-line" data-rail-next aria-label="<?php esc_attr_e( 'التالي', 'baytkahraba' ); ?>"><?php bk_the_icon( 'chevron', 'size-5' ); ?></button>
			</div>
			<div class="rail mt-6 lg:mt-4 lg:gap-6" data-rail>
				<?php foreach ( $bk_amp as $bk_p ) : ?>
					<?php $bk_a = bk_get_product_power( $bk_p->get_id() )['ampere']; ?>
					<div class="w-[46%] shrink-0 sm:w-[30%] lg:w-[calc((100%-4.5rem)/4)]" data-amp-card="<?php echo esc_attr( (string) $bk_a ); ?>">
						<?php bk_part( 'template-parts/product/card', array( 'product' => $bk_p, 'position' => 50 ) ); ?>
					</div>
				<?php endforeach; ?>
			</div>
			<a data-amp-more href="<?php echo esc_url( add_query_arg( 'amp_max', 5, $bk_shop ) ); ?>" class="link-arrow mt-8"><?php esc_html_e( 'كل الأجهزة المناسبة لاشتراكي', 'baytkahraba' ); ?><?php bk_the_icon( 'arrow', 'size-4' ); ?></a>
		</div>
	</section>
<?php endif; ?>

<!-- How ordering works -->
<section id="how" class="wrap section" aria-labelledby="bk-how-title">
	<div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'بدون دفع أونلاين', 'baytkahraba' ); ?></p>
			<h2 id="bk-how-title" class="h-section mt-2"><?php esc_html_e( 'الطلب برسالة واتساب.', 'baytkahraba' ); ?><br><?php esc_html_e( 'والباقي علينا.', 'baytkahraba' ); ?></h2>
			<ol class="mt-10 space-y-8">
				<?php
				$bk_steps = array(
					array( __( 'اختار جهازك', 'baytkahraba' ), __( 'قارن الأسعار والأمبير والكفالة على راحتك.', 'baytkahraba' ) ),
					array( __( 'اضغط «اطلب على واتساب»', 'baytkahraba' ), __( 'بتنفتح رسالة جاهزة فيها اسم الجهاز والموديل والسعر — بس ابعتها.', 'baytkahraba' ) ),
					array( __( 'منأكدلك ومنوصّل', 'baytkahraba' ), __( 'منأكد التوفر والتوصيل، وبتفحص الجهاز وبتدفع عند الاستلام.', 'baytkahraba' ) ),
				);
				foreach ( $bk_steps as $bk_i => $bk_step ) :
					?>
					<li class="flex gap-5">
						<span class="step-num bk-num"><?php echo (int) $bk_i + 1; ?></span>
						<div>
							<h3 class="text-[18px] font-semibold"><?php echo esc_html( $bk_step[0] ); ?></h3>
							<p class="mt-1 text-[15.5px] leading-7 text-muted"><?php echo esc_html( $bk_step[1] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php if ( $bk_demo ) : ?>
			<figure class="wa-chat" aria-label="<?php esc_attr_e( 'مثال على رسالة الطلب', 'baytkahraba' ); ?>">
				<div class="mb-4 flex items-center gap-3 rounded-2xl bg-white/70 p-3">
					<span class="logo-mark size-10"><svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.4 2.5 5.8 13.2h5.1l-1.3 8.3 7.6-10.7h-5.1l1.3-8.3Z"/></svg></span>
					<div class="leading-tight"><p class="font-semibold text-ink"><?php echo esc_html( (string) bk_option( 'store_name' ) ); ?></p><p class="text-[12.5px] text-wa-ink"><?php esc_html_e( 'متصل الآن', 'baytkahraba' ); ?></p></div>
				</div>
				<div class="wa-bubble whitespace-pre-line"><?php echo esc_html( preg_replace( '#\n?https?://\S+#', '', bk_product_whatsapp_message( $bk_demo ) ) ); ?></div>
				<div class="wa-bubble in mt-3"><?php esc_html_e( 'أهلين فيك! الجهاز متوفر، والتوصيل لعندك بكرا الصبح، وبتفحصه قبل ما تدفع.', 'baytkahraba' ); ?></div>
			</figure>
		<?php endif; ?>
	</div>
</section>

<!-- Official agents -->
<?php if ( $bk_brands ) : ?>
	<section class="border-y border-line bg-white" aria-labelledby="bk-brands-title">
		<div class="wrap py-14 lg:py-20">
			<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'وكلاء رسميين', 'baytkahraba' ); ?></p>
					<h2 id="bk-brands-title" class="h-section mt-2"><?php esc_html_e( 'كفالة حقيقية، من الوكيل مباشرة.', 'baytkahraba' ); ?></h2>
				</div>
			</div>
			<ul class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-5">
				<?php foreach ( $bk_brands as $bk_brand ) : ?>
					<?php $bk_bm = bk_brand_meta( $bk_brand ); ?>
					<li>
						<a href="<?php echo esc_url( $bk_bm['url'] ); ?>" class="group flex h-full flex-col rounded-[var(--radius)] border border-line p-5 transition hover:border-ink lg:p-6">
							<span class="font-display text-[24px] font-bold text-ink lg:text-[30px]"><?php echo esc_html( $bk_brand->name ); ?></span>
							<span class="mt-1 text-[13.5px] text-muted"><?php echo esc_html( sprintf( /* translators: %d models */ __( '%d موديل متوفر', 'baytkahraba' ), (int) $bk_brand->count ) ); ?></span>
							<span class="mt-6 flex items-center justify-between text-[14px] font-medium text-ink-2">
								<span class="flex items-center gap-1.5"><?php bk_the_icon( 'shield', 'size-4' ); ?><?php echo esc_html( sprintf( /* translators: %d months */ __( 'كفالة %d شهر', 'baytkahraba' ), (int) $bk_bm['warranty_months'] ) ); ?></span>
								<?php bk_the_icon( 'arrow', 'size-4 transition group-hover:-translate-x-1' ); ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<!-- FAQ + SEO copy -->
<section class="wrap section grid gap-12 lg:grid-cols-12 lg:gap-16">
	<div class="min-w-0 lg:col-span-7">
		<?php bk_part( 'template-parts/global/faq', array( 'faqs' => bk_seo_faqs( bk_seo_context() ) ) ); ?>
	</div>
	<article class="prose-bk min-w-0 text-[15px] text-muted lg:col-span-5">
		<h2 class="!mt-0"><?php esc_html_e( 'متجر الأدوات الكهربائية والمنزلية في دمشق بسعر المحل', 'baytkahraba' ); ?></h2>
		<p><?php esc_html_e( 'شريك تجزئة وجملة مرتبط مباشرة بالوكلاء الرسميين في سوريا: برادات وفريزرات، غسالات أوتوماتيك وحوضين، شاشات سمارت 4K مع رسيفر داخلي، مكيفات إنفرتر بتشتغل على الأمبير والطاقة الشمسية، ومراوح شحن ببطارية ليثيوم.', 'baytkahraba' ); ?></p>
		<h3><?php esc_html_e( 'أسعار الأدوات الكهربائية في سوريا اليوم', 'baytkahraba' ); ?></h3>
		<p><?php esc_html_e( 'أسعارنا نفس سعر المحل وبتتحدّث يومياً حسب سعر الصرف، وكل جهاز بيوصلك مع كفالة الوكيل وفاتورة رسمية.', 'baytkahraba' ); ?></p>
		<h3><?php esc_html_e( 'توصيل مجاني بدمشق وريفها وشحن لكل المحافظات', 'baytkahraba' ); ?></h3>
		<p><?php esc_html_e( 'التوصيل مجاني لباب البيت بالمزة والميدان ومشروع دمر وكفرسوسة وجرمانا وصحنايا وقدسيا، وبرسم رمزي لريف دمشق الأبعد، ومنشحن لحلب وحمص وحماة واللاذقية وطرطوس ودرعا والسويداء بتغليف آمن.', 'baytkahraba' ); ?></p>
	</article>
</section>

<?php
get_footer();
