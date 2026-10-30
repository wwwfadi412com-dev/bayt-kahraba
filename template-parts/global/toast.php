<?php
/**
 * Accessible toast notifications.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;
?>
<div x-data x-cloak class="pointer-events-none fixed inset-x-0 top-24 z-[80] flex flex-col items-center gap-2 px-4" aria-live="polite">
	<template x-for="t in $store.toast.items" :key="t.id">
		<div x-transition class="pointer-events-auto flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold shadow-lift"
			:class="t.type === 'error' ? 'bg-red-600 text-white' : 'bg-brand-navy text-white'">
			<span x-show="t.type !== 'error'" class="grid size-6 place-items-center rounded-full bg-brand-emerald"><?php bk_the_icon( 'check', 'size-4' ); ?></span>
			<span x-text="t.text"></span>
			<button type="button" x-show="t.action" class="ms-2 rounded-lg bg-white/15 px-2 py-1 text-xs hover:bg-white/25" @click="t.action(); $store.toast.dismiss(t.id)" x-text="t.actionLabel"></button>
		</div>
	</template>
</div>
