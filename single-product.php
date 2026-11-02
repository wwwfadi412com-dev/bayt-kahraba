<?php
/**
 * Single product — gallery, the essentials, one big WhatsApp order button,
 * quick questions, then description, specs, FAQ and similar devices.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	if ( ! $product ) {
		continue;
	}

	$bk_ctx      = bk_seo_context();
	$bk_id       = $product->get_id();
	$bk_brand    = $bk_ctx['brand'];
	$bk_cat      = $bk_ctx['cat'];
	$bk_power    = bk_get_product_power( $bk_id );
	$bk_model    = (string) $product->get_meta( '_bk_model_code' ) ?: $product->get_sku();
	$bk_gallery  = array_values( array_unique( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) ) );
	$bk_warranty = bk_product_warranty( $product );
	$bk_faqs     = bk_seo_faqs( $bk_ctx );
	$bk_specs    = bk_product_specs( $product );
	$bk_sale     = bk_sale_percent( $product );
	$bk_wa       = bk_product_whatsapp_url( $product );
	$bk_capacity = (string) $product->get_meta( '_bk_capacity' );
	$bk_desc     = trim( (string) $product->get_description() );
	?>
	<article id="product-<?php echo (int) $bk_id; ?>" <?php wc_product_class( 'wrap pb-24 pt-6 lg:pb-10 lg:pt-8', $product ); ?>>
		<div class="grid gap-8 lg:grid-cols-12 lg:gap-14">

			<!-- Gallery -->
			<div class="min-w-0 lg:col-span-7" data-gallery>
				<div class="lg:sticky lg:top-[calc(var(--header-h)+1.5rem)]">
					<div class="gallery-stage">
						<?php if ( $bk_gallery ) : ?>
							<?php
							echo wp_get_attachment_image(
								$bk_gallery[0],
								'woocommerce_single',
								false,
								array(
									'data-main'     => '',
									'fetchpriority' => 'high',
									'loading'       => 'eager',
									'alt'           => $product->get_name(),
									'sizes'         => '(min-width: 1024px) 680px, 100vw',
								)
							);
							?>
						<?php else : ?>
							<img src="<?php echo esc_attr( bk_placeholder_src() ); ?>" alt="" width="800" height="800">
						<?php endif; ?>
						<span class="absolute start-4 top-4 flex flex-col items-start gap-1.5">
							<?php if ( $bk_sale ) : ?>
								<span class="badge-sale bk-num">-<?php echo (int) $bk_sale; ?>%</span>
							<?php endif; ?>
						</span>
					</div>
					<?php if ( count( $bk_gallery ) > 1 ) : ?>
						<div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto">
							<?php foreach ( $bk_gallery as $bk_i => $bk_img ) : ?>
								<button type="button" class="thumb" data-thumb aria-current="<?php echo 0 === $bk_i ? 'true' : 'false'; ?>"
									data-src="<?php echo esc_url( (string) wp_get_attachment_image_url( $bk_img, 'woocommerce_single' ) ); ?>"
									data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $bk_img, 'woocommerce_single' ) ); ?>"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %d image number */ __( 'صورة %d', 'baytkahraba' ), $bk_i + 1 ) ); ?>">
									<?php echo wp_get_attachment_image( $bk_img, 'bk-thumb', false, array( 'class' => 'size-full object-contain', 'alt' => '' ) ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Essentials -->
			<div class="min-w-0 lg:col-span-5">
				<div class="flex flex-wrap items-center gap-2 text-[13.5px]">
					<?php if ( $bk_brand ) : ?>
						<a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="font-semibold text-ink underline decoration-glow decoration-2 underline-offset-4"><?php echo esc_html( $bk_brand->name ); ?></a>
						<span class="text-line">•</span>
					<?php endif; ?>
					<span class="text-muted"><?php esc_html_e( 'وكيل رسمي', 'baytkahraba' ); ?></span>
					<?php if ( $bk_model ) : ?>
						<span class="text-line">•</span><span class="bk-ltr text-muted"><?php echo esc_html( $bk_model ); ?></span>
					<?php endif; ?>
				</div>

				<h1 class="mt-3 font-display text-[26px] font-semibold leading-[1.3] lg:text-[34px]"><?php the_title(); ?></h1>

				<div class="mt-5 flex items-end gap-4">
					<div><?php echo bk_price_html( $product, 'text-[32px] lg:text-[36px]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php if ( $product->is_in_stock() ) : ?>
						<span class="mb-1 inline-flex items-center gap-1.5 text-[13.5px] font-medium text-wa-ink"><span class="size-2 rounded-full bg-wa"></span><?php esc_html_e( 'متوفر بالمحل', 'baytkahraba' ); ?></span>
					<?php else : ?>
						<span class="mb-1 text-[13.5px] font-medium text-muted"><?php esc_html_e( 'غير متوفر حالياً — اسألنا عن الموعد', 'baytkahraba' ); ?></span>
					<?php endif; ?>
				</div>
				<p class="mt-1 text-[13px] text-muted"><?php esc_html_e( 'نفس سعر المحل، محدّث اليوم.', 'baytkahraba' ); ?></p>

				<?php if ( $product->get_short_description() ) : ?>
					<div class="mt-5 text-[16px] leading-8 text-ink-2"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php endif; ?>

				<dl class="mt-6 grid grid-cols-3 gap-2">
					<?php if ( $bk_power['ampere'] ) : ?>
						<div class="spec-tile"><dt><?php esc_html_e( 'السحب', 'baytkahraba' ); ?></dt><dd><?php echo esc_html( bk_format_ampere( $bk_power['ampere'] ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $bk_power['wattage'] ) : ?>
						<div class="spec-tile"><dt><?php esc_html_e( 'الاستطاعة', 'baytkahraba' ); ?></dt><dd><?php echo esc_html( bk_format_watt( $bk_power['wattage'] ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $bk_capacity ) : ?>
						<div class="spec-tile"><dt><?php esc_html_e( 'السعة', 'baytkahraba' ); ?></dt><dd class="!text-[15px] leading-6"><?php echo esc_html( $bk_capacity ); ?></dd></div>
					<?php endif; ?>
				</dl>

				<?php if ( in_array( $bk_power['class'], array( 'solar_ready', 'low_amp' ), true ) ) : ?>
					<p class="mt-3 flex items-center gap-2 rounded-2xl bg-glow-soft px-4 py-3 text-[14px] font-medium text-glow-ink">
						<?php bk_the_icon( 'solar_ready' === $bk_power['class'] ? 'sun' : 'bolt', 'size-5 shrink-0' ); ?><?php echo esc_html( $bk_power['label'] ); ?>
					</p>
				<?php endif; ?>

				<a href="<?php echo bk_esc_wa( $bk_wa ); ?>" target="_blank" rel="noopener nofollow" class="btn-wa btn-lg mt-7 w-full text-[17px]">
					<?php bk_the_icon( 'whatsapp', 'size-6' ); ?><?php esc_html_e( 'اطلب على واتساب', 'baytkahraba' ); ?>
				</a>
				<p class="mt-2 text-center text-[13px] text-muted"><?php esc_html_e( 'بتنفتح رسالة جاهزة فيها اسم الجهاز والموديل والسعر.', 'baytkahraba' ); ?></p>

				<div class="mt-6">
					<p class="mb-2.5 text-[13.5px] font-medium text-ink-2"><?php esc_html_e( 'عندك سؤال؟ اضغط وبيوصلنا مع اسم الجهاز:', 'baytkahraba' ); ?></p>
					<div class="flex flex-wrap gap-2">
						<?php foreach ( bk_product_quick_questions() as $bk_intent => $bk_label ) : ?>
							<a class="ask" href="<?php echo bk_esc_wa( bk_product_whatsapp_url( $product, $bk_intent ) ); ?>" target="_blank" rel="noopener nofollow"><?php bk_the_icon( 'chat', 'size-4' ); ?><?php echo esc_html( $bk_label ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>

				<ul class="mt-8 space-y-3.5 border-t border-line pt-6">
					<li class="promise"><?php bk_the_icon( 'truck' ); ?><span><?php esc_html_e( 'توصيل مجاني لباب البيت بدمشق وريفها القريب، وشحن آمن لكل المحافظات.', 'baytkahraba' ); ?></span></li>
					<li class="promise"><?php bk_the_icon( 'shield' ); ?><span><?php echo esc_html( $bk_warranty ); ?><?php echo $bk_brand ? ' — ' . esc_html( $bk_brand->name ) : ''; ?></span></li>
					<li class="promise"><?php bk_the_icon( 'eye' ); ?><span><?php esc_html_e( 'بتفحص الجهاز قدامك، وبتدفع عند الاستلام.', 'baytkahraba' ); ?></span></li>
				</ul>
				<p class="mt-5 text-[14px] text-muted"><?php esc_html_e( 'بتحب تحكي؟', 'baytkahraba' ); ?> <a class="bk-ltr font-semibold text-ink underline decoration-line underline-offset-4" href="tel:<?php echo esc_attr( (string) bk_option( 'phone' ) ); ?>"><?php echo esc_html( (string) bk_option( 'phone' ) ); ?></a></p>
			</div>
		</div>

		<!-- Details -->
		<div class="mt-16 grid gap-14 border-t border-line pt-14 lg:mt-24 lg:grid-cols-12 lg:gap-16">
			<div class="min-w-0 lg:col-span-7">
				<?php if ( $bk_desc ) : ?>
					<section aria-labelledby="bk-about">
						<h2 id="bk-about" class="h-section mb-5 !text-[26px] lg:!text-[30px]"><?php esc_html_e( 'عن الجهاز', 'baytkahraba' ); ?></h2>
						<div class="prose-bk"><?php echo wp_kses_post( apply_filters( 'the_content', $bk_desc ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?></div>
					</section>
				<?php endif; ?>
			</div>
			<aside class="min-w-0 lg:col-span-5">
				<section aria-labelledby="bk-specs">
					<h2 id="bk-specs" class="h-section mb-3 !text-[22px] lg:!text-[24px]"><?php esc_html_e( 'المواصفات', 'baytkahraba' ); ?></h2>
					<dl class="spec-table">
						<?php foreach ( $bk_specs as $bk_row ) : ?>
							<div><dt><?php echo esc_html( $bk_row['label'] ); ?></dt><dd><?php echo esc_html( $bk_row['value'] ); ?></dd></div>
						<?php endforeach; ?>
					</dl>
				</section>
			</aside>
		</div>

		<?php if ( $bk_faqs ) : ?>
			<div class="mt-16 max-w-3xl lg:mt-24"><?php bk_part( 'template-parts/global/faq', array( 'faqs' => $bk_faqs, 'title' => __( 'أسئلة عن هالجهاز', 'baytkahraba' ) ) ); ?></div>
		<?php endif; ?>

		<?php
		$bk_similar = bk_similar_price_products( $product, 8 );
		if ( count( $bk_similar ) < 4 && $bk_cat ) {
			$bk_up      = bk_category_ancestor_ids( $bk_cat->term_id );
			$bk_root_id = $bk_up ? (int) end( $bk_up ) : (int) $bk_cat->term_id;
			$bk_similar = array_merge(
				$bk_similar,
				bk_query_products(
					array(
						'post__not_in' => array_merge( array( $bk_id ), array_map( static fn( $p ) => $p->get_id(), $bk_similar ) ),
						'tax_query'    => array( array( 'taxonomy' => 'product_cat', 'terms' => $bk_root_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
					),
					8 - count( $bk_similar )
				)
			);
		}
		bk_part(
			'template-parts/product/rail',
			array(
				'title'      => __( 'أجهزة ممكن تعجبك', 'baytkahraba' ),
				'products'   => $bk_similar,
				'link'       => $bk_cat ? get_term_link( $bk_cat ) : '',
				'link_label' => $bk_cat ? sprintf( /* translators: %s category */ __( 'كل %s', 'baytkahraba' ), $bk_cat->name ) : '',
			)
		);
		?>

		<?php if ( $bk_brand && $bk_cat ) : ?>
			<nav class="flex flex-wrap gap-2 border-t border-line pt-8" aria-label="<?php esc_attr_e( 'تصفح أيضاً', 'baytkahraba' ); ?>">
				<?php if ( bk_intersection_count( $bk_brand->term_id, $bk_cat->term_id ) > 1 ) : ?>
					<a class="chip" href="<?php echo esc_url( bk_silo_url( $bk_brand, $bk_cat ) ); ?>"><?php echo esc_html( sprintf( /* translators: 1 category 2 brand */ __( '%1$s %2$s', 'baytkahraba' ), $bk_cat->name, $bk_brand->name ) ); ?></a>
				<?php endif; ?>
				<a class="chip" href="<?php echo esc_url( get_term_link( $bk_cat ) ); ?>"><?php echo esc_html( $bk_cat->name ); ?></a>
				<a class="chip" href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s brand */ __( 'كل أجهزة %s', 'baytkahraba' ), $bk_brand->name ) ); ?></a>
				<?php if ( in_array( $bk_power['class'], array( 'solar_ready', 'low_amp' ), true ) ) : ?>
					<a class="chip" href="<?php echo esc_url( add_query_arg( 'power', 'solar', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php bk_the_icon( 'sun' ); ?><?php esc_html_e( 'أجهزة الطاقة الشمسية', 'baytkahraba' ); ?></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</article>

	<!-- Mobile order bar -->
	<div class="buy-bar">
		<div class="min-w-0 flex-1"><?php echo bk_price_html( $product, 'text-[20px]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<a href="<?php echo bk_esc_wa( $bk_wa ); ?>" target="_blank" rel="noopener nofollow" class="btn-wa h-12 px-5"><?php bk_the_icon( 'whatsapp' ); ?><?php esc_html_e( 'اطلب على واتساب', 'baytkahraba' ); ?></a>
	</div>
	<?php
endwhile;

get_footer();
