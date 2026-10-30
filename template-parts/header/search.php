<?php
/**
 * Live search (REST + Syrian synonyms). Works as a normal GET form without JS.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
$bk_uid = wp_unique_id( 'bk-search-' );
?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="relative" x-data="liveSearch()" @submit="submit($event)" @click.outside="open = false" @keydown.escape="open = false">
	<label for="<?php echo esc_attr( $bk_uid ); ?>" class="sr-only"><?php esc_html_e( 'ابحث عن جهاز', 'baytkahraba' ); ?></label>
	<input type="hidden" name="post_type" value="product">
	<div class="relative">
		<input id="<?php echo esc_attr( $bk_uid ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off"
			class="h-11 w-full rounded-xl border border-black/20 bg-brand-card ps-11 pe-20 text-sm text-brand-ink placeholder:text-brand-muted focus:border-brand-ink focus:outline-none focus:ring-4 focus:ring-brand-volt/15"
			placeholder="<?php esc_attr_e( 'ابحث: براد بابين، غسالة أوتوماتيك، شاشة 55، مكيف إنفرتر…', 'baytkahraba' ); ?>"
			x-model="q" @input.debounce.250ms="search()" @focus="q.length > 1 && (open = true)"
			@keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter="enter($event)"
			role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="<?php echo esc_attr( $bk_uid ); ?>-list">
		<span class="pointer-events-none absolute inset-y-0 start-3.5 grid place-items-center text-brand-muted"><?php bk_the_icon( 'search', 'size-[18px]' ); ?></span>
		<button type="submit" class="absolute inset-y-1.5 end-1.5 rounded-lg bg-brand-ink px-3.5 text-[13px] font-semibold text-brand-paper transition hover:bg-brand-volt"><?php esc_html_e( 'بحث', 'baytkahraba' ); ?></button>
	</div>

	<div x-cloak x-show="open" x-transition.opacity.duration.150ms id="<?php echo esc_attr( $bk_uid ); ?>-list" role="listbox"
		class="absolute inset-x-0 top-full z-[60] mt-2 max-h-[70vh] overflow-y-auto rounded-2xl border border-brand-ink bg-brand-card p-2 text-brand-ink shadow-lift">
		<template x-if="loading">
			<div class="space-y-2 p-2"><div class="bk-skeleton h-12"></div><div class="bk-skeleton h-12"></div><div class="bk-skeleton h-12"></div></div>
		</template>
		<template x-if="!loading && results">
			<div>
				<template x-if="results.categories.length || results.brands.length">
					<div class="flex flex-wrap gap-2 border-b border-brand-line p-2">
						<template x-for="c in results.categories" :key="c.url">
							<a :href="c.url" class="bk-badge-slate hover:border-brand-royal hover:text-brand-royal"><span x-text="c.name"></span><span class="text-brand-muted bk-num" x-text="'(' + c.count + ')'"></span></a>
						</template>
						<template x-for="b in results.brands" :key="b.url">
							<a :href="b.url" class="bk-badge bg-brand-royal-50 text-brand-royal" x-text="b.name"></a>
						</template>
					</div>
				</template>
				<template x-for="(p, i) in results.products" :key="p.id">
					<a :href="p.url" role="option" :aria-selected="active === i" :class="active === i ? 'bg-brand-stripe' : ''" class="flex items-center gap-3 rounded-xl p-2 hover:bg-brand-stripe">
						<img :src="p.image" alt="" class="size-14 shrink-0 rounded-lg bg-white object-contain" loading="lazy" width="56" height="56">
						<span class="min-w-0 flex-1">
							<span class="line-clamp-1 text-sm font-semibold" x-text="p.name"></span>
							<span class="flex flex-wrap items-center gap-2 text-xs text-brand-muted">
								<span x-text="p.brand"></span><span x-show="p.model" class="bk-ltr" x-text="p.model"></span>
								<span x-show="p.power" class="bk-badge-emerald-soft !py-0.5" x-text="p.power"></span>
							</span>
						</span>
						<span class="shrink-0 text-sm font-bold bk-price" x-html="p.price"></span>
					</a>
				</template>
				<p x-show="!results.products.length" class="p-4 text-center text-sm text-brand-muted" x-text="BK.i18n.noResults"></p>
				<a x-show="results.total > results.products.length" :href="results.all_url" class="mt-1 block rounded-xl bg-brand-navy p-3 text-center text-sm font-semibold text-white hover:bg-brand-royal">
					<?php esc_html_e( 'عرض كل النتائج', 'baytkahraba' ); ?> (<span class="bk-num" x-text="results.total"></span>)
				</a>
			</div>
		</template>
	</div>
</form>
