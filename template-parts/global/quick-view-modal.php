<?php
/**
 * Quick-view modal shell (content loaded from REST).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
?>
<div x-data x-cloak x-show="$store.qv.open" class="bk-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'عرض سريع', 'baytkahraba' ); ?>" @keydown.escape.window="$store.qv.open = false">
	<div class="bk-overlay" x-transition.opacity @click="$store.qv.open = false"></div>
	<div class="relative z-[71] max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-lift" x-trap.noscroll="$store.qv.open" x-transition.scale.95>
		<button type="button" class="bk-icon-btn absolute end-3 top-3 z-10 bg-white/90" @click="$store.qv.open = false" aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
		<div x-show="$store.qv.loading" class="grid gap-6 p-6 md:grid-cols-2"><div class="bk-skeleton aspect-square"></div><div class="space-y-3"><div class="bk-skeleton h-8"></div><div class="bk-skeleton h-6 w-1/2"></div><div class="bk-skeleton h-32"></div></div></div>
		<div x-show="!$store.qv.loading" x-html="$store.qv.html"></div>
	</div>
</div>
