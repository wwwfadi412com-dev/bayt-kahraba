<?php
/**
 * Side cart drawer (WooCommerce Store API, no jQuery, no fragments).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
?>
<div x-data x-cloak x-show="$store.cart.open" @keydown.escape.window="$store.cart.open = false" class="relative z-[70]" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'سلة المشتريات', 'baytkahraba' ); ?>">
	<div class="bk-overlay" x-show="$store.cart.open" x-transition.opacity @click="$store.cart.open = false"></div>
	<aside class="bk-drawer" x-show="$store.cart.open" x-trap.noscroll="$store.cart.open"
		x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
		x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
		<header class="flex items-center justify-between border-b border-brand-line p-4">
			<p class="flex items-center gap-2 text-lg font-bold text-brand-ink"><?php bk_the_icon( 'cart', 'size-6 text-brand-amber' ); ?><?php esc_html_e( 'سلة المشتريات', 'baytkahraba' ); ?> <span class="text-sm font-normal text-brand-muted bk-num" x-text="'(' + $store.cart.count + ')'"></span></p>
			<button type="button" class="bk-icon-btn" @click="$store.cart.open = false" aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
		</header>

		<div class="flex-1 overflow-y-auto p-4">
			<template x-if="$store.cart.loading && !$store.cart.items.length">
				<div class="space-y-3"><div class="bk-skeleton h-20"></div><div class="bk-skeleton h-20"></div></div>
			</template>
			<template x-if="!$store.cart.loading && !$store.cart.items.length">
				<div class="grid place-items-center gap-3 py-16 text-center">
					<span class="grid size-16 place-items-center rounded-full bg-brand-stripe text-brand-muted"><?php bk_the_icon( 'cart', 'size-8' ); ?></span>
					<p class="font-semibold text-brand-ink"><?php esc_html_e( 'سلتك فارغة', 'baytkahraba' ); ?></p>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="bk-btn-navy"><?php esc_html_e( 'تسوّق الآن', 'baytkahraba' ); ?></a>
				</div>
			</template>
			<ul class="space-y-3">
				<template x-for="item in $store.cart.items" :key="item.key">
					<li class="flex gap-3 rounded-xl border border-brand-line p-3" :class="$store.cart.busy === item.key && 'opacity-50'">
						<a :href="item.permalink" class="shrink-0"><img :src="item.images?.[0]?.thumbnail || BK.placeholder" :alt="item.name" class="size-20 rounded-lg bg-white object-contain" width="80" height="80" loading="lazy"></a>
						<div class="min-w-0 flex-1">
							<a :href="item.permalink" class="line-clamp-2 text-sm font-semibold text-brand-ink hover:text-brand-royal" x-html="item.name"></a>
							<p class="mt-1 text-sm font-bold bk-price" x-text="$store.cart.money(item.totals.line_total, item.totals.currency_minor_unit)"></p>
							<div class="mt-2 flex items-center justify-between">
								<div class="inline-flex items-center rounded-lg border border-brand-line">
									<button type="button" class="grid size-8 place-items-center hover:bg-brand-stripe" @click="$store.cart.update(item.key, item.quantity - 1)" aria-label="<?php esc_attr_e( 'إنقاص', 'baytkahraba' ); ?>"><?php bk_the_icon( 'minus', 'size-4' ); ?></button>
									<span class="w-8 text-center text-sm font-semibold bk-num" x-text="item.quantity"></span>
									<button type="button" class="grid size-8 place-items-center hover:bg-brand-stripe" @click="$store.cart.update(item.key, item.quantity + 1)" aria-label="<?php esc_attr_e( 'زيادة', 'baytkahraba' ); ?>"><?php bk_the_icon( 'plus', 'size-4' ); ?></button>
								</div>
								<button type="button" class="text-xs text-red-600 hover:underline" @click="$store.cart.remove(item.key)"><?php esc_html_e( 'حذف', 'baytkahraba' ); ?></button>
							</div>
						</div>
					</li>
				</template>
			</ul>
		</div>

		<footer x-show="$store.cart.items.length" class="space-y-3 border-t border-brand-line bg-brand-canvas p-4 bk-safe-bottom">
			<div class="grid grid-cols-2 gap-2">
				<label class="text-xs font-semibold text-brand-ink"><?php esc_html_e( 'المحافظة', 'baytkahraba' ); ?>
					<select class="bk-select mt-1 !py-2" x-model="$store.cart.state" @change="$store.cart.area = ''; $store.cart.saveArea()">
						<template x-for="(name, code) in BK.shipping.states" :key="code"><option :value="code" x-text="name" :selected="code === $store.cart.state"></option></template>
					</select>
				</label>
				<label class="text-xs font-semibold text-brand-ink" x-show="['DI','RD'].includes($store.cart.state)"><?php esc_html_e( 'المنطقة', 'baytkahraba' ); ?>
					<select class="bk-select mt-1 !py-2" x-model="$store.cart.area" @change="$store.cart.saveArea()">
						<option value=""><?php esc_html_e( 'اختر…', 'baytkahraba' ); ?></option>
						<template x-for="a in $store.cart.areasFor($store.cart.state)" :key="a[0]"><option :value="a[0]" x-text="a[0]" :selected="a[0] === $store.cart.area"></option></template>
					</select>
				</label>
			</div>
			<dl class="space-y-1 text-sm">
				<div class="flex justify-between"><dt><?php esc_html_e( 'المجموع', 'baytkahraba' ); ?></dt><dd class="font-semibold bk-num" x-text="$store.cart.money($store.cart.totals?.total_items, $store.cart.totals?.currency_minor_unit)"></dd></div>
				<template x-for="fee in ($store.cart.fees || [])" :key="fee.key"><div class="flex justify-between text-brand-emerald-hover"><dt x-text="fee.name"></dt><dd class="bk-num" x-text="$store.cart.money(fee.totals.total, fee.totals.currency_minor_unit)"></dd></div></template>
				<div class="flex justify-between"><dt><?php esc_html_e( 'التوصيل', 'baytkahraba' ); ?></dt><dd class="font-semibold" :class="$store.cart.quote().cost ? 'bk-num' : 'text-brand-emerald-hover'" x-text="$store.cart.quote().cost ? $store.cart.format($store.cart.quote().cost) : BK.i18n.free"></dd></div>
			</dl>
			<a :href="BK.checkoutUrl" class="bk-btn-amber bk-btn-lg w-full"><?php esc_html_e( 'إتمام الطلب', 'baytkahraba' ); ?></a>
			<button type="button" class="bk-btn-emerald w-full" @click="$store.cart.whatsapp()"><?php bk_the_icon( 'whatsapp', 'size-5' ); ?><?php esc_html_e( 'اطلب السلة كاملة عبر واتساب', 'baytkahraba' ); ?></button>
		</footer>
	</aside>
</div>
