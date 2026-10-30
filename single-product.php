<?php
/**
 * Single product: conversion-first layout with energy badge, real-shop video,
 * delivery estimator, WhatsApp order, spec table, trust box, FAQ (+schema)
 * and the internal-linking silo mesh.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	global $product;
	$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	if ( ! $product ) {
		continue;
	}

	$bk_ctx      = bk_seo_context();
	$bk_id       = $product->get_id();
	$bk_brand    = $bk_ctx['brand'];
	$bk_cat      = $bk_ctx['cat'];
	$bk_power    = bk_get_product_power( $bk_id );
	$bk_model    = (string) $product->get_meta( '_bk_model_code' );
	$bk_video    = bk_product_video( $product );
	$bk_gallery  = array_values( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
	$bk_simple   = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
	$bk_warranty = bk_product_warranty( $product );
	$bk_faqs     = bk_seo_faqs( $bk_ctx );
	$bk_config   = array(
		'id'          => $bk_id,
		'name'        => $product->get_name(),
		'model'       => $bk_model ?: $product->get_sku(),
		'brand'       => $bk_brand ? $bk_brand->name : '',
		'price'       => (float) $product->get_price(),
		'url'         => get_permalink( $bk_id ),
		'weightClass' => $bk_cat ? bk_category_weight_class( $bk_cat->term_id ) : 'heavy',
		'maxQty'      => $product->get_max_purchase_quantity() > 0 ? $product->get_max_purchase_quantity() : 20,
	);
	?>
	<div class="bk-container py-6 lg:py-10" x-data="productPage(<?php echo esc_attr( wp_json_encode( $bk_config ) ); ?>)">
		<?php do_action( 'woocommerce_before_single_product' ); ?>

		<div id="product-<?php echo (int) $bk_id; ?>" <?php wc_product_class( 'grid gap-8 lg:grid-cols-12 lg:gap-12', $product ); ?>>

			<!-- Gallery -->
			<div class="min-w-0 lg:col-span-6">
				<div class="lg:sticky lg:top-[calc(var(--bk-header-h)+4rem)]">
					<div class="bk-grid-paper relative grid aspect-square place-items-center overflow-hidden rounded-xl2 border border-brand-line bg-brand-card">
						<span class="bk-mono bk-ltr absolute bottom-4 end-4 z-10 text-[11px] text-brand-muted"><?php echo esc_html( $bk_model ); ?></span>
						<div class="absolute start-4 top-4 z-10 flex flex-col items-start gap-2">
							<?php bk_part( 'template-parts/product/energy-badge', array( 'power' => $bk_power, 'size' => 'sm' ) ); ?>
							<?php if ( $product->is_on_sale() ) : ?>
								<span class="bk-badge-sale"><?php esc_html_e( 'عرض خاص', 'baytkahraba' ); ?></span>
							<?php endif; ?>
						</div>
						<?php if ( $bk_video ) : ?>
							<div class="absolute end-4 top-4 z-10"><?php bk_part( 'template-parts/product/video-story', array( 'video' => $bk_video, 'name' => $product->get_name() ) ); ?></div>
						<?php endif; ?>
						<?php if ( $bk_gallery ) : ?>
							<?php foreach ( $bk_gallery as $bk_i => $bk_img ) : ?>
								<div class="absolute inset-0 grid place-items-center p-6 lg:p-10" x-show="slide === <?php echo (int) $bk_i; ?>" <?php echo $bk_i ? 'x-cloak' : ''; ?>>
									<?php
									echo wp_get_attachment_image(
										$bk_img,
										'woocommerce_single',
										false,
										array(
											'class'         => 'max-h-full w-auto object-contain mix-blend-multiply',
											'alt'           => $product->get_name(),
											'sizes'         => '(min-width: 1024px) 600px, 100vw',
											'fetchpriority' => 0 === $bk_i ? 'high' : 'auto',
											'loading'       => 0 === $bk_i ? 'eager' : 'lazy',
										)
									);
									?>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<span class="text-brand-ink"><?php bk_the_icon( $bk_cat ? bk_category_icon( $bk_cat->term_id ) : 'bolt', 'size-48 stroke-[1.1] lg:size-64' ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( count( $bk_gallery ) > 1 ) : ?>
						<div class="bk-rail mt-3">
							<?php foreach ( $bk_gallery as $bk_i => $bk_img ) : ?>
								<button type="button" class="size-20 overflow-hidden rounded-xl bg-brand-card p-1.5 ring-2 transition" :class="slide === <?php echo (int) $bk_i; ?> ? 'ring-brand-ink' : 'ring-brand-line hover:ring-black/30'" @click="slide = <?php echo (int) $bk_i; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'صورة %d', 'baytkahraba' ), $bk_i + 1 ) ); ?>">
									<?php echo wp_get_attachment_image( $bk_img, 'bk-thumb', false, array( 'class' => 'size-full object-contain' ) ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Summary -->
			<div class="min-w-0 space-y-5 lg:col-span-6">
				<div class="flex flex-wrap items-center justify-between gap-3">
					<?php if ( $bk_brand ) : ?>
						<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="inline-flex items-center gap-2.5 rounded-full border border-black/20 bg-brand-card py-1 pe-3 ps-1 transition hover:border-brand-ink">
							<span class="rounded-full bg-brand-ink px-3 py-1 font-display text-sm font-extrabold text-brand-paper"><?php echo esc_html( $bk_brand->name ); ?></span>
							<span class="text-xs font-medium text-brand-ink-2"><?php echo esc_html( bk_brand_meta( $bk_brand )['agent'] ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( $product->get_review_count() ) : ?>
						<a href="#reviews" class="inline-flex items-center gap-1 text-sm text-brand-muted"><?php bk_the_icon( 'star', 'size-4 fill-brand-amber text-brand-amber' ); ?><span class="bk-num font-semibold text-brand-ink"><?php echo esc_html( number_format( (float) $product->get_average_rating(), 1 ) ); ?></span> (<span class="bk-num"><?php echo (int) $product->get_review_count(); ?></span>)</a>
					<?php endif; ?>
				</div>

				<div>
					<h1 class="text-[28px] leading-[1.2] lg:text-[40px]"><?php the_title(); ?></h1>
					<p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-brand-muted">
						<?php if ( $bk_model ) : ?>
							<span><?php esc_html_e( 'الموديل', 'baytkahraba' ); ?> <span class="bk-mono bk-ltr font-semibold text-brand-ink"><?php echo esc_html( $bk_model ); ?></span></span>
						<?php endif; ?>
						<span class="inline-flex items-center gap-1.5 font-semibold <?php echo $product->is_in_stock() ? 'text-brand-sun-ink' : 'text-brand-volt-ink'; ?>">
							<span class="size-2 rounded-full <?php echo $product->is_in_stock() ? 'bg-brand-sun' : 'bg-brand-volt'; ?>"></span>
							<?php echo $product->is_in_stock() ? esc_html__( 'متوفر في المحل', 'baytkahraba' ) : esc_html__( 'غير متوفر حالياً', 'baytkahraba' ); ?>
						</span>
					</p>
				</div>

				<div class="flex flex-wrap items-end justify-between gap-3 border-y border-black/20 py-5">
					<div>
						<p class="bk-price text-[34px] leading-none lg:text-[42px]"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
						<p class="mt-2 flex items-center gap-1.5 text-sm text-brand-sun-ink"><?php bk_the_icon( 'store', 'size-4' ); ?><?php esc_html_e( 'نفس سعر المحل بدون زيادات — محدّث اليوم', 'baytkahraba' ); ?></p>
					</div>
					<?php bk_part( 'template-parts/product/energy-badge', array( 'power' => $bk_power, 'size' => 'sm' ) ); ?>
				</div>

				<?php if ( $bk_power['wattage'] || $bk_power['ampere'] ) : ?>
					<section class="bk-grid-dark rounded-xl2 bg-brand-ink p-5 text-brand-paper" aria-label="<?php esc_attr_e( 'البطاقة الكهربائية', 'baytkahraba' ); ?>">
						<p class="font-mono text-[11px] uppercase tracking-label text-[#9C978C]">Electrical datasheet · <?php esc_html_e( 'البطاقة الكهربائية', 'baytkahraba' ); ?></p>
						<dl class="mt-3 grid grid-cols-3 divide-x divide-x-reverse divide-white/10 text-center">
							<div><dt class="text-xs text-[#9C978C]"><?php esc_html_e( 'الاستطاعة', 'baytkahraba' ); ?></dt><dd class="bk-mono bk-ltr mt-1 text-[22px] font-semibold"><?php echo $bk_power['wattage'] ? esc_html( number_format( $bk_power['wattage'] ) ) . '<small class="text-sm text-[#9C978C]">W</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
							<div><dt class="text-xs text-[#9C978C]"><?php esc_html_e( 'السحب', 'baytkahraba' ); ?></dt><dd class="bk-mono bk-ltr mt-1 text-[22px] font-semibold text-brand-volt"><?php echo $bk_power['ampere'] ? esc_html( rtrim( rtrim( number_format( $bk_power['ampere'], 2, '.', '' ), '0' ), '.' ) ) . '<small class="text-sm text-[#9C978C]">A</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
							<div><dt class="text-xs text-[#9C978C]"><?php esc_html_e( 'السعة', 'baytkahraba' ); ?></dt><dd class="mt-1 text-[15px] font-semibold leading-8"><?php echo esc_html( (string) $product->get_meta( '_bk_capacity' ) ?: '—' ); ?></dd></div>
						</dl>
						<?php if ( $bk_power['ampere'] ) : ?>
							<div class="mt-4 [&_.text-brand-muted]:!text-[#9C978C]"><?php bk_part( 'template-parts/product/energy-scale', array( 'ampere' => $bk_power['ampere'] ) ); ?></div>
						<?php endif; ?>
						<?php if ( $bk_power['label'] ) : ?>
							<p class="mt-3 flex items-center gap-2 rounded-lg bg-white/5 px-3 py-2 text-sm"><?php bk_the_icon( $bk_power['icon'], 'size-4 text-brand-sun' ); ?><?php echo esc_html( $bk_power['label'] ); ?></p>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<section aria-labelledby="bk-highlights">
					<h2 id="bk-highlights" class="mb-2 text-base"><?php esc_html_e( 'أبرز المميزات باختصار', 'baytkahraba' ); ?></h2>
					<ul class="space-y-2 text-[15px]">
						<?php foreach ( bk_product_highlights( $product ) as $bk_line ) : ?>
							<li class="flex gap-2"><?php bk_the_icon( 'check', 'mt-1 size-5 shrink-0 text-brand-emerald' ); ?><span><?php echo esc_html( $bk_line ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				</section>

				<!-- Delivery estimator -->
				<section class="rounded-2xl bg-brand-stripe/70 p-4" aria-labelledby="bk-delivery">
					<h2 id="bk-delivery" class="mb-3 flex items-center gap-2 text-base"><?php bk_the_icon( 'truck', 'size-5 text-brand-royal' ); ?><?php esc_html_e( 'احسب التوصيل لمنطقتك', 'baytkahraba' ); ?></h2>
					<div class="grid grid-cols-2 gap-2">
						<select class="bk-select" x-model="state" @change="area = ''; save()" aria-label="<?php esc_attr_e( 'المحافظة', 'baytkahraba' ); ?>">
							<template x-for="(name, code) in BK.shipping.states" :key="code"><option :value="code" x-text="name" :selected="code === state"></option></template>
						</select>
						<select class="bk-select" x-model="area" @change="save()" x-show="['DI','RD'].includes(state)" aria-label="<?php esc_attr_e( 'المنطقة', 'baytkahraba' ); ?>">
							<option value=""><?php esc_html_e( 'اختر المنطقة', 'baytkahraba' ); ?></option>
							<template x-for="a in $store.cart.areasFor(state)" :key="a[0]"><option :value="a[0]" x-text="a[0]" :selected="a[0] === area"></option></template>
						</select>
					</div>
					<p class="mt-3 flex items-center justify-between gap-2 text-sm">
						<span x-text="quote.label"></span>
						<strong :class="quote.cost ? 'text-brand-ink bk-num' : 'text-brand-emerald-hover'" x-text="quote.cost ? $store.cart.format(quote.cost) : BK.i18n.free"></strong>
					</p>
					<p class="text-xs text-brand-muted" x-text="quote.eta"></p>
				</section>

				<!-- Purchase -->
				<?php if ( $bk_simple ) : ?>
					<div class="flex flex-wrap items-stretch gap-2">
						<div class="inline-flex items-center rounded-xl bg-white ring-1 ring-brand-line">
							<button type="button" class="grid size-12 place-items-center hover:bg-brand-stripe" @click="qty = Math.max(1, qty - 1)" aria-label="<?php esc_attr_e( 'إنقاص', 'baytkahraba' ); ?>"><?php bk_the_icon( 'minus', 'size-4' ); ?></button>
							<input type="number" min="1" :max="cfg.maxQty" x-model.number="qty" class="w-12 border-0 bg-transparent text-center font-bold bk-num focus:ring-0" aria-label="<?php esc_attr_e( 'الكمية', 'baytkahraba' ); ?>">
							<button type="button" class="grid size-12 place-items-center hover:bg-brand-stripe" @click="qty = Math.min(cfg.maxQty, qty + 1)" aria-label="<?php esc_attr_e( 'زيادة', 'baytkahraba' ); ?>"><?php bk_the_icon( 'plus', 'size-4' ); ?></button>
						</div>
						<button type="button" class="bk-btn-amber bk-btn-lg flex-1" @click="$store.cart.add(cfg.id, qty, $el)"><?php bk_the_icon( 'cart' ); ?><?php esc_html_e( 'أضف للسلة', 'baytkahraba' ); ?></button>
					</div>
				<?php elseif ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
					<div class="bk-wc-form"><?php woocommerce_template_single_add_to_cart(); ?></div>
				<?php endif; ?>
				<div class="grid gap-2 sm:grid-cols-2">
					<a :href="whatsappUrl" target="_blank" rel="noopener nofollow" class="bk-btn-emerald bk-btn-lg"><?php bk_the_icon( 'whatsapp' ); ?><?php esc_html_e( 'اطلب الآن عبر واتساب', 'baytkahraba' ); ?></a>
					<button type="button" class="bk-btn-ghost bk-btn-lg" @click="$store.compare.toggle(cfg.id)" :class="$store.compare.has(cfg.id) && '!border-brand-royal !text-brand-royal'">
						<?php bk_the_icon( 'compare' ); ?><span x-text="$store.compare.has(cfg.id) ? '<?php echo esc_js( __( 'مضاف للمقارنة', 'baytkahraba' ) ); ?>' : '<?php echo esc_js( __( 'قارن مع موديل آخر', 'baytkahraba' ) ); ?>'"></span>
					</button>
				</div>

				<ul class="grid grid-cols-3 gap-2 text-center text-xs">
					<li class="rounded-xl bg-white p-3 ring-1 ring-brand-line"><?php bk_the_icon( 'shield', 'mx-auto mb-1 size-6 text-brand-royal' ); ?><?php echo esc_html( $bk_warranty ); ?></li>
					<li class="rounded-xl bg-white p-3 ring-1 ring-brand-line"><?php bk_the_icon( 'eye', 'mx-auto mb-1 size-6 text-brand-royal' ); ?><?php esc_html_e( 'افحص الجهاز عند الاستلام في دمشق', 'baytkahraba' ); ?></li>
					<li class="rounded-xl bg-white p-3 ring-1 ring-brand-line"><?php bk_the_icon( 'cash', 'mx-auto mb-1 size-6 text-brand-royal' ); ?><?php esc_html_e( 'ادفع عند الاستلام', 'baytkahraba' ); ?></li>
				</ul>
			</div>
		</div>

		<!-- Section nav -->
		<nav class="bk-scrollbar-none sticky top-[var(--bk-header-h)] z-20 -mx-4 mt-12 flex gap-1 overflow-x-auto border-b border-brand-line bg-brand-canvas/95 px-4 py-2 text-sm font-semibold backdrop-blur lg:top-[calc(var(--bk-header-h)+3rem)]" aria-label="<?php esc_attr_e( 'أقسام الصفحة', 'baytkahraba' ); ?>">
			<?php
			$bk_sections = array(
				'bk-description' => __( 'الوصف', 'baytkahraba' ),
				'bk-specs'       => __( 'المواصفات', 'baytkahraba' ),
				'bk-why'         => __( 'لماذا من متجرنا؟', 'baytkahraba' ),
				'bk-faq-title'   => __( 'الأسئلة الشائعة', 'baytkahraba' ),
			);
			if ( comments_open() ) {
				$bk_sections['reviews'] = __( 'التقييمات', 'baytkahraba' );
			}
			foreach ( $bk_sections as $bk_anchor => $bk_label ) :
				?>
				<a href="#<?php echo esc_attr( $bk_anchor ); ?>" class="whitespace-nowrap rounded-full px-4 py-2 text-brand-body hover:bg-white hover:text-brand-royal"><?php echo esc_html( $bk_label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="mt-8 grid gap-8 lg:grid-cols-12">
			<div class="min-w-0 space-y-8 lg:col-span-8">
				<section id="bk-description" class="bk-card bk-prose scroll-mt-40 p-5 lg:p-8">
					<h2><?php echo esc_html( sprintf( __( 'وصف %s', 'baytkahraba' ), $product->get_name() ) ); ?></h2>
					<?php
					if ( trim( (string) get_the_content() ) ) {
						the_content();
					} else {
						echo '<p>' . esc_html( wp_strip_all_tags( bk_seo_description( $bk_ctx ) ) ) . '</p>';
					}
					?>
				</section>

				<section class="bk-card scroll-mt-40 p-5 lg:p-8">
					<?php bk_part( 'template-parts/product/spec-table', array( 'product' => $product ) ); ?>
				</section>

				<section id="bk-why" class="scroll-mt-40 overflow-hidden rounded-xl2 bg-brand-navy p-6 text-white lg:p-8">
					<h2 class="mb-5 text-xl text-white"><?php esc_html_e( 'لماذا تشتري هذا المنتج من متجرنا؟', 'baytkahraba' ); ?></h2>
					<ul class="grid gap-4 sm:grid-cols-2">
						<?php
						$bk_why = array(
							array( 'store', __( 'نفس سعر المحل مباشرة', 'baytkahraba' ), __( 'السعر أونلاين مطابق لسعر المحل الفعلي ويُحدّث يومياً، بدون أي عمولة إضافية.', 'baytkahraba' ) ),
							array( 'shield', __( 'بطاقة كفالة الوكيل الرسمية', 'baytkahraba' ), $bk_warranty . ( $bk_brand ? ' — ' . bk_brand_meta( $bk_brand )['agent'] : '' ) ),
							array( 'eye', __( 'الفحص عند الاستلام في دمشق', 'baytkahraba' ), __( 'افتح الكرتونة وجرّب الجهاز أمام مندوب التوصيل قبل الدفع.', 'baytkahraba' ) ),
							array( 'truck', __( 'تغليف آمن للمحافظات', 'baytkahraba' ), __( 'تغليف مضاعف وتأمين الشحن عبر شركات موثوقة حتى باب بيتك.', 'baytkahraba' ) ),
						);
						foreach ( $bk_why as $bk_row ) :
							?>
							<li class="flex gap-3">
								<span class="grid size-11 shrink-0 place-items-center rounded-xl bg-white/10 text-brand-amber"><?php bk_the_icon( $bk_row[0], 'size-6' ); ?></span>
								<span><span class="block font-bold"><?php echo esc_html( $bk_row[1] ); ?></span><span class="text-sm text-slate-300"><?php echo esc_html( $bk_row[2] ); ?></span></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<div class="scroll-mt-40"><?php bk_part( 'template-parts/global/faq', array( 'faqs' => $bk_faqs, 'title' => sprintf( __( 'الأسئلة الشائعة حول %s', 'baytkahraba' ), $product->get_name() ) ) ); ?></div>

				<?php if ( comments_open() ) : ?>
					<section id="reviews" class="bk-card scroll-mt-40 p-5 lg:p-8"><?php comments_template(); ?></section>
				<?php endif; ?>
			</div>

			<aside class="min-w-0 space-y-4 lg:col-span-4">
				<?php if ( $bk_brand ) : ?>
					<?php $bk_meta = bk_brand_meta( $bk_brand ); ?>
					<div class="bk-card p-5">
						<p class="mb-2 font-bold text-brand-ink"><?php printf( esc_html__( 'عن وكالة %s', 'baytkahraba' ), esc_html( $bk_brand->name ) ); ?></p>
						<p class="text-sm text-brand-body"><?php echo esc_html( $bk_meta['agent'] ); ?> — <?php echo esc_html( $bk_meta['warranty_text'] ); ?></p>
						<?php if ( $bk_meta['service_centers'] ) : ?>
							<ul class="mt-3 space-y-1.5 text-sm">
								<?php foreach ( $bk_meta['service_centers'] as $bk_center ) : ?>
									<li class="flex gap-2"><?php bk_the_icon( 'wrench', 'size-4 shrink-0 text-brand-amber' ); ?><span><strong><?php echo esc_html( $bk_center['city'] ); ?>:</strong> <?php echo esc_html( $bk_center['address'] ); ?> <span class="bk-ltr bk-num"><?php echo esc_html( $bk_center['phone'] ); ?></span></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php $bk_bundle_page = bk_page_by_template( 'page-bundle-builder.php' ); ?>
				<?php if ( $bk_bundle_page ) : ?>
					<a href="<?php echo esc_url( (string) get_permalink( $bk_bundle_page ) ); ?>" class="block rounded-xl2 bg-gradient-to-br from-brand-amber to-orange-500 p-5 text-brand-navy shadow-card transition hover:shadow-lift">
						<?php bk_the_icon( 'gift', 'mb-2 size-8' ); ?>
						<p class="text-lg font-bold"><?php esc_html_e( 'تجهّز بيتك أو عرسك؟', 'baytkahraba' ); ?></p>
						<p class="text-sm"><?php esc_html_e( 'كوّن باقتك الكاملة بخصم إضافي من حاسبة تجهيز العرسان.', 'baytkahraba' ); ?></p>
					</a>
				<?php endif; ?>
			</aside>
		</div>

		<div class="mt-12"><?php bk_part( 'template-parts/product/internal-links', array( 'product' => $product ) ); ?></div>

		<!-- Sticky mobile buy bar -->
		<div class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-2 border-t border-brand-line bg-white p-3 shadow-lift bk-safe-bottom lg:hidden" x-show="!$store.compare.ids.length">
			<div class="min-w-0 flex-1"><p class="bk-price truncate text-base"><?php echo wp_kses_post( $product->get_price_html() ); ?></p></div>
			<a :href="whatsappUrl" target="_blank" rel="noopener nofollow" class="grid size-12 shrink-0 place-items-center rounded-xl bg-brand-emerald text-white" aria-label="<?php esc_attr_e( 'واتساب', 'baytkahraba' ); ?>"><?php bk_the_icon( 'whatsapp', 'size-6' ); ?></a>
			<?php if ( $bk_simple ) : ?>
				<button type="button" class="bk-btn-amber h-12 flex-1" @click="$store.cart.add(cfg.id, qty, $el)"><?php esc_html_e( 'أضف للسلة', 'baytkahraba' ); ?></button>
			<?php endif; ?>
		</div>
	</div>
	<?php
	do_action( 'woocommerce_after_single_product' );
endwhile;

get_footer();
