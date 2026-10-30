<?php
/**
 * "Real Shop Video Preview" story bubble (Feature 5).
 * The video is never downloaded until the bubble is tapped (preload="none").
 *
 * @var array $args { video: array{src,type,poster}, name: string }
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

$bk_video = $args['video'] ?? null;
if ( ! $bk_video ) {
	return;
}
?>
<div x-data="{ open: false }" class="contents">
	<button type="button" @click="open = true; $nextTick(() => $refs.v.play())" class="group flex flex-col items-center gap-1" aria-label="<?php esc_attr_e( 'شاهد الجهاز من أرض المحل', 'baytkahraba' ); ?>">
		<span class="bk-story-ring grid size-[74px] place-items-center rounded-full p-[3px] transition group-hover:scale-105">
			<span class="relative grid size-full place-items-center overflow-hidden rounded-full border-[3px] border-white bg-brand-navy">
				<?php if ( $bk_video['poster'] ) : ?>
					<img src="<?php echo esc_url( $bk_video['poster'] ); ?>" alt="" class="absolute inset-0 size-full object-cover opacity-80" loading="lazy" width="74" height="74">
				<?php endif; ?>
				<span class="relative grid size-7 place-items-center rounded-full bg-white/90 text-brand-navy"><?php bk_the_icon( 'play', 'size-4' ); ?></span>
			</span>
		</span>
		<span class="text-[11px] font-semibold text-brand-ink"><?php esc_html_e( 'من أرض المحل', 'baytkahraba' ); ?></span>
	</button>

	<template x-teleport="body">
		<div x-show="open" x-cloak class="bk-modal" role="dialog" aria-modal="true" @keydown.escape.window="open = false; $refs.v.pause()">
			<div class="bk-overlay" @click="open = false; $refs.v.pause()"></div>
			<div class="relative z-[71] w-full max-w-[380px]" x-trap.noscroll="open">
				<video x-ref="v" class="aspect-[9/16] w-full rounded-2xl bg-black object-cover shadow-lift" playsinline controls loop preload="none" poster="<?php echo esc_url( $bk_video['poster'] ); ?>">
					<source src="<?php echo esc_url( $bk_video['src'] ); ?>" type="<?php echo esc_attr( $bk_video['type'] ); ?>">
				</video>
				<p class="mt-3 text-center text-sm text-white"><?php echo esc_html( sprintf( __( 'معاينة حقيقية لـ %s من داخل المحل — الحجم والتفاصيل كما ستصلك.', 'baytkahraba' ), $args['name'] ?? '' ) ); ?></p>
				<button type="button" class="bk-icon-btn absolute -top-3 -end-3 bg-white" @click="open = false; $refs.v.pause()" aria-label="<?php esc_attr_e( 'إغلاق', 'baytkahraba' ); ?>"><?php bk_the_icon( 'close' ); ?></button>
			</div>
		</div>
	</template>
</div>
