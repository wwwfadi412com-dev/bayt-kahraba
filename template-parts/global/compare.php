<?php
/**
 * Floating compare bar + side-by-side comparison modal.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_rows = array(
	'price_html' => __( 'السعر (بسعر المحل)', 'baytkahraba' ),
	'brand'      => __( 'الماركة', 'baytkahraba' ),
	'category'   => __( 'القسم', 'baytkahraba' ),
	'model'      => __( 'رقم الموديل', 'baytkahraba' ),
	'warranty'   => __( 'الكفالة', 'baytkahraba' ),
	'wattage'    => __( 'الاستطاعة (واط)', 'baytkahraba' ),
	'ampere'     => __( 'السحب (أمبير)', 'baytkahraba' ),
	'power'      => __( 'الانفرتر / الطاقة الشمسية', 'baytkahraba' ),
	'capacity'   => __( 'السعة / الحجم', 'baytkahraba' ),
	'dimensions' => __( 'الأبعاد', 'baytkahraba' ),
	'stock'      => __( 'التوفر', 'baytkahraba' ),
);
?>
<div x-data x-cloak>
	<!-- Floating bar -->
	<div x-show="$store.compare.ids.length && !$store.compare.open" x-transition
		class="fixed inset-x-3 bottom-3 z-50 mx-auto flex max-w-2xl items-center gap-3 rounded-2xl bg-brand-navy p-3 text-white shadow-lift bk-safe-bottom lg:bottom-5">
		<span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white/10"><?php bk_the_icon( 'compare', 'size-5 text-brand-amber' ); ?></span>
		<p class="min-w-0 flex-1 text-sm">
			<span class="font-bold bk-num" x-text="$store.compare.ids.length"></span> <?php esc_html_e( 'منتجات للمقارنة', 'baytkahraba' ); ?>
			<span class="block text-xs text-slate-400"><?php esc_html_e( 'حتى 4 منتجات جنباً إلى جنب', 'baytkahraba' ); ?></span>
		</p>
		<button type="button" class="text-xs text-slate-300 hover:text-white" @click="$store.compare.clear()"><?php esc_html_e( 'مسح', 'baytkahraba' ); ?></button>
		<button type="button" class="bk-btn-amber" @click="$store.compare.show()" :disabled="$store.compare.ids.length < 2"><?php esc_html_e( 'قارن الآن', 'baytkahraba' ); ?></button>
	</div>

	<!-- Modal -->
	<div x-show="$store.compare.open" class="bk-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'مقارنة الموديلات والماركات', 'baytkahraba' ); ?>" @keydown.escape.window="$store.compare.open = false">
		<div class="bk-overlay" x-transition.opacity @click="$store.compare.open = false"></div>
		<div class="relative z-[71] flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-lift" x-trap.noscroll="$store.compare.open" x-transition.scale.95>
			<header class="flex items-center justify-between border-b border-brand-line p-4">
				<h2 class="text-lg"><?php esc_html_e( 'مقارنة الموديلات والماركات الفورية', 'baytkahraba' ); ?></h2>
				<button type="button" class="bk-icon-btn" @click="$store.compare.open = false" aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
			</header>
			<div class="overflow-auto">
				<template x-if="$store.compare.loading"><div class="grid gap-3 p-6 sm:grid-cols-3"><div class="bk-skeleton h-64"></div><div class="bk-skeleton h-64"></div><div class="bk-skeleton h-64"></div></div></template>
				<template x-if="!$store.compare.loading && $store.compare.items.length < 2">
					<p class="p-10 text-center text-brand-muted"><?php esc_html_e( 'اضغط "قارن" على منتجين على الأقل لعرض المقارنة.', 'baytkahraba' ); ?></p>
				</template>
				<table x-show="!$store.compare.loading && $store.compare.items.length > 1" class="w-full min-w-[640px] text-sm">
					<thead class="sticky top-0 z-10 bg-white">
						<tr>
							<th class="w-40 p-3"></th>
							<template x-for="p in $store.compare.items" :key="p.id">
								<th class="border-s border-brand-line p-3 align-top">
									<div class="relative">
										<button type="button" class="absolute -top-1 end-0 bk-icon-btn size-7 text-brand-muted" @click="$store.compare.toggle(p.id)" aria-label="<?php esc_attr_e( 'إزالة', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close', 'size-4' ); ?></button>
										<img :src="p.image" :alt="p.name" class="mx-auto h-32 w-auto object-contain" loading="lazy">
										<a :href="p.url" class="mt-2 line-clamp-2 block text-sm font-bold text-brand-ink hover:text-brand-royal" x-text="p.name"></a>
										<button type="button" x-show="p.purchasable && p.in_stock" class="bk-btn-amber bk-btn-sm mt-2 w-full" @click="$store.cart.add(p.id, 1)"><?php esc_html_e( 'أضف للسلة', 'baytkahraba' ); ?></button>
									</div>
								</th>
							</template>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $bk_rows as $bk_key => $bk_label ) : ?>
							<tr class="odd:bg-brand-stripe/60">
								<th scope="row" class="p-3 text-start font-semibold text-brand-ink"><?php echo esc_html( $bk_label ); ?></th>
								<template x-for="p in $store.compare.items" :key="p.id + '<?php echo esc_attr( $bk_key ); ?>'">
									<td class="border-s border-brand-line p-3 text-center" :class="$store.compare.isBest(p, '<?php echo esc_attr( $bk_key ); ?>') && 'font-bold text-brand-emerald-hover'">
										<?php if ( 'price_html' === $bk_key ) : ?>
											<span class="bk-price" x-html="p.price_html"></span>
										<?php elseif ( in_array( $bk_key, array( 'wattage', 'ampere' ), true ) ) : ?>
											<span class="bk-num" x-text="p.<?php echo esc_attr( $bk_key ); ?> || '—'"></span>
										<?php else : ?>
											<span x-text="p.<?php echo esc_attr( $bk_key ); ?>"></span>
										<?php endif; ?>
									</td>
								</template>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
