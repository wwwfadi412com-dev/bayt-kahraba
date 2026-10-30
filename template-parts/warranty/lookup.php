<?php
/**
 * Digital Warranty & Authenticity Lookup ([bk_warranty] shortcode).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_brands = bk_get_brands();
?>
<div class="not-prose space-y-6" x-data="warranty()">
	<div class="inline-flex rounded-xl bg-brand-stripe p-1" role="tablist">
		<button type="button" role="tab" class="rounded-lg px-4 py-2 text-sm font-semibold" :class="tab === 'register' ? 'bg-white shadow-card text-brand-ink' : 'text-brand-muted'" @click="tab = 'register'" :aria-selected="tab === 'register'"><?php esc_html_e( 'تسجيل / التحقق من كفالتي', 'baytkahraba' ); ?></button>
		<button type="button" role="tab" class="rounded-lg px-4 py-2 text-sm font-semibold" :class="tab === 'terms' ? 'bg-white shadow-card text-brand-ink' : 'text-brand-muted'" @click="tab = 'terms'" :aria-selected="tab === 'terms'"><?php esc_html_e( 'شروط كفالة كل وكيل', 'baytkahraba' ); ?></button>
	</div>

	<section x-show="tab === 'register'" class="grid gap-6 lg:grid-cols-2">
		<form class="bk-card space-y-4 p-5" @submit.prevent="submit()">
			<p class="text-sm text-brand-body"><?php esc_html_e( 'أدخل رقم الطلب/الفاتورة ورقم الموبايل المستخدم في الطلب. نحفظ تاريخ بدء كفالتك إلكترونياً لتعود إليه في أي وقت.', 'baytkahraba' ); ?></p>
			<div>
				<label class="bk-label" for="bk-w-order"><?php esc_html_e( 'رقم الطلب / الفاتورة', 'baytkahraba' ); ?></label>
				<input id="bk-w-order" class="bk-input bk-num" inputmode="numeric" x-model="order" required dir="ltr" placeholder="1024">
			</div>
			<div>
				<label class="bk-label" for="bk-w-phone"><?php esc_html_e( 'رقم الموبايل', 'baytkahraba' ); ?></label>
				<input id="bk-w-phone" class="bk-input bk-num" inputmode="tel" x-model="phone" required dir="ltr" placeholder="09xxxxxxxx">
			</div>
			<button type="submit" class="bk-btn-navy w-full" :disabled="loading"><span x-show="!loading"><?php esc_html_e( 'تحقق وسجّل الكفالة', 'baytkahraba' ); ?></span><span x-show="loading" x-text="BK.i18n.loading"></span></button>
			<p x-show="error" x-text="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700"></p>
		</form>

		<div class="space-y-3">
			<template x-for="r in records" :key="r.product + r.start">
				<article class="bk-card p-5">
					<div class="flex items-start justify-between gap-3">
						<div>
							<p class="text-xs font-semibold text-brand-royal" x-text="r.brand"></p>
							<a :href="r.url" class="font-bold text-brand-ink" x-text="r.product"></a>
						</div>
						<span :class="r.active ? 'bk-badge-emerald' : 'bk-badge-slate'" x-text="r.active ? '<?php echo esc_js( __( 'كفالة سارية', 'baytkahraba' ) ); ?>' : '<?php echo esc_js( __( 'منتهية', 'baytkahraba' ) ); ?>'"></span>
					</div>
					<dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
						<div class="rounded-lg bg-brand-stripe p-2"><dt class="text-brand-muted"><?php esc_html_e( 'البداية', 'baytkahraba' ); ?></dt><dd class="font-semibold bk-num" x-text="r.start"></dd></div>
						<div class="rounded-lg bg-brand-stripe p-2"><dt class="text-brand-muted"><?php esc_html_e( 'النهاية', 'baytkahraba' ); ?></dt><dd class="font-semibold bk-num" x-text="r.end"></dd></div>
						<div class="rounded-lg bg-brand-stripe p-2"><dt class="text-brand-muted"><?php esc_html_e( 'متبقي', 'baytkahraba' ); ?></dt><dd class="font-semibold bk-num" x-text="r.daysLeft + ' <?php echo esc_js( __( 'يوم', 'baytkahraba' ) ); ?>'"></dd></div>
					</dl>
					<p class="mt-3 text-sm text-brand-body" x-text="r.terms"></p>
					<ul class="mt-2 space-y-1 text-xs text-brand-muted"><template x-for="c in r.centers"><li x-text="c.city + ': ' + c.address + ' ' + c.phone"></li></template></ul>
				</article>
			</template>
		</div>
	</section>

	<section x-show="tab === 'terms'" x-cloak class="grid gap-4 md:grid-cols-2">
		<?php foreach ( $bk_brands as $bk_brand ) : ?>
			<?php $bk_meta = bk_brand_meta( $bk_brand ); ?>
			<article class="bk-card p-5">
				<div class="mb-3 flex items-center gap-3">
					<span class="grid h-12 w-20 place-items-center rounded-lg bg-white ring-1 ring-brand-line"><?php echo bk_brand_logo( $bk_brand, 'max-h-9 w-auto object-contain text-xs', 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div>
						<h3 class="text-base"><a href="<?php echo esc_url( get_term_link( $bk_brand ) ); ?>" class="hover:text-brand-royal"><?php echo esc_html( $bk_brand->name ); ?></a></h3>
						<p class="text-xs text-brand-muted"><?php echo esc_html( $bk_meta['agent'] ); ?></p>
					</div>
				</div>
				<p class="flex gap-2 text-sm font-semibold text-brand-ink"><?php bk_the_icon( 'shield', 'size-5 shrink-0 text-brand-emerald' ); ?><?php echo esc_html( $bk_meta['warranty_text'] ); ?></p>
				<?php if ( $bk_meta['service_centers'] ) : ?>
					<ul class="mt-3 space-y-1 text-sm text-brand-body">
						<?php foreach ( $bk_meta['service_centers'] as $bk_center ) : ?>
							<li class="flex gap-2"><?php bk_the_icon( 'wrench', 'size-4 shrink-0 text-brand-amber' ); ?><span><strong><?php echo esc_html( $bk_center['city'] ); ?>:</strong> <?php echo esc_html( $bk_center['address'] ); ?> <span class="bk-ltr bk-num"><?php echo esc_html( $bk_center['phone'] ); ?></span></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</section>
</div>
