<?php
/**
 * Template Name: حاسبة تجهيز العرسان والبيت الكامل
 *
 * Interactive Bride & Home Setup Bundle Builder (data from REST /bk/v1/bundle).
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

get_header();
$bk_settings = function_exists( 'bk_bundle_settings' ) ? bk_bundle_settings() : array( 'discount' => 0, 'min_items' => 3 );
?>
<div class="bk-container py-8 lg:py-12" x-data="bundleBuilder()">
	<header class="mx-auto mb-8 max-w-3xl text-center">
		<span class="bk-badge-amber mb-3"><?php bk_the_icon( 'gift', 'size-3.5' ); ?><?php printf( esc_html__( 'خصم إضافي %s%% على الباقة الكاملة', 'baytkahraba' ), esc_html( (string) $bk_settings['discount'] ) ); ?></span>
		<h1 class="text-2xl lg:text-4xl"><?php the_title(); ?></h1>
		<p class="mt-3 text-brand-body"><?php esc_html_e( 'اختر الباقة المناسبة لميزانيتك، بدّل أي جهاز بضغطة، وشاهد المجموع بالليرة السورية فوراً — ثم أضف الباقة كاملة للسلة أو اطلبها عبر واتساب.', 'baytkahraba' ); ?></p>
	</header>

	<!-- Tiers -->
	<div class="mx-auto mb-8 grid max-w-4xl gap-3 sm:grid-cols-3" role="radiogroup" aria-label="<?php esc_attr_e( 'الباقة', 'baytkahraba' ); ?>">
		<template x-for="(label, key) in tiers" :key="key">
			<button type="button" role="radio" :aria-checked="tier === key" @click="setTier(key)"
				class="relative rounded-2xl border-2 p-4 text-start transition"
				:class="tier === key ? 'border-brand-amber bg-brand-amber-50 shadow-glow' : 'border-brand-line bg-white hover:border-slate-300'">
				<span x-show="key === 'smart'" class="bk-badge-royal absolute -top-3 end-4"><?php esc_html_e( 'الأكثر طلباً', 'baytkahraba' ); ?></span>
				<span class="block font-bold text-brand-ink" x-text="label"></span>
				<span class="mt-1 block text-sm text-brand-muted bk-num" x-text="format(tierTotal(key))"></span>
			</button>
		</template>
	</div>

	<template x-if="loading">
		<div class="grid gap-4 lg:grid-cols-3"><div class="bk-skeleton h-96 lg:col-span-2"></div><div class="bk-skeleton h-96"></div></div>
	</template>

	<div x-show="!loading" x-cloak class="grid gap-6 lg:grid-cols-12">
		<!-- Steps -->
		<div class="min-w-0 space-y-4 lg:col-span-8">
			<ol class="bk-scrollbar-none flex gap-2 overflow-x-auto pb-1">
				<template x-for="(step, i) in steps" :key="step.key">
					<li>
						<button type="button" @click="current = i" class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition"
							:class="current === i ? 'bg-brand-navy text-white' : (selected[step.key]?.length ? 'bg-brand-emerald-50 text-brand-emerald-hover ring-1 ring-brand-emerald-100' : 'bg-white text-brand-body ring-1 ring-brand-line')">
							<span class="grid size-6 place-items-center rounded-full bg-white/20 text-xs bk-num" x-text="i + 1"></span>
							<span x-text="step.label"></span>
						</button>
					</li>
				</template>
			</ol>

			<template x-for="(step, i) in steps" :key="step.key">
				<section x-show="current === i" class="bk-card p-4 lg:p-6">
					<div class="mb-4 flex items-center justify-between gap-3">
						<h2 class="text-lg"><span class="bk-num" x-text="(i + 1) + '. '"></span><span x-text="step.label"></span></h2>
						<span class="text-xs text-brand-muted" x-text="step.multi ? '<?php echo esc_js( __( 'يمكنك اختيار أكثر من جهاز', 'baytkahraba' ) ); ?>' : '<?php echo esc_js( __( 'اختر جهازاً واحداً', 'baytkahraba' ) ); ?>'"></span>
					</div>
					<ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
						<template x-for="c in step.candidates" :key="c.id">
							<li>
								<button type="button" @click="toggle(step, c.id)" class="flex h-full w-full flex-col rounded-2xl border-2 bg-white p-3 text-start transition"
									:class="isSelected(step, c.id) ? 'border-brand-amber shadow-glow' : 'border-brand-line hover:border-slate-300'" :aria-pressed="isSelected(step, c.id)">
									<span class="relative mb-2 grid aspect-square place-items-center rounded-xl bg-white">
										<img :src="c.image" :alt="c.name" class="max-h-full w-auto object-contain" loading="lazy" width="160" height="160">
										<span x-show="isSelected(step, c.id)" class="absolute end-1 top-1 grid size-7 place-items-center rounded-full bg-brand-amber text-brand-navy"><?php bk_the_icon( 'check', 'size-4' ); ?></span>
										<span x-show="c.tier" class="bk-badge-royal absolute start-1 top-1 !text-[10px]" x-text="tiers[c.tier]"></span>
									</span>
									<span class="text-xs font-semibold text-brand-royal" x-text="c.brand"></span>
									<span class="line-clamp-2 text-sm font-semibold text-brand-ink" x-text="c.name"></span>
									<span x-show="c.power" class="bk-badge-emerald-soft mt-1 self-start !py-0.5" x-text="c.power"></span>
									<span class="mt-auto pt-2 font-bold bk-num" x-text="format(c.price)"></span>
								</button>
							</li>
						</template>
					</ul>
					<div class="mt-5 flex justify-between">
						<button type="button" class="bk-btn-ghost" x-show="i > 0" @click="current--"><?php esc_html_e( 'السابق', 'baytkahraba' ); ?></button>
						<button type="button" class="bk-btn-navy ms-auto" x-show="i < steps.length - 1" @click="current++"><?php esc_html_e( 'الخطوة التالية', 'baytkahraba' ); ?></button>
					</div>
				</section>
			</template>
		</div>

		<!-- Summary -->
		<aside class="min-w-0 lg:col-span-4">
			<div class="bk-card space-y-4 p-5 lg:sticky lg:top-[calc(var(--bk-header-h)+4rem)]">
				<h2 class="text-lg"><?php esc_html_e( 'ملخص باقتك', 'baytkahraba' ); ?></h2>
				<ul class="divide-y divide-brand-line text-sm">
					<template x-for="item in items" :key="item.id">
						<li class="flex items-center gap-3 py-2">
							<img :src="item.image" alt="" class="size-12 rounded-lg object-contain" loading="lazy" width="48" height="48">
							<span class="min-w-0 flex-1"><span class="block truncate font-semibold" x-text="item.name"></span><span class="text-xs text-brand-muted" x-text="item.step"></span></span>
							<span class="font-semibold bk-num" x-text="format(item.price)"></span>
						</li>
					</template>
					<li x-show="!items.length" class="py-6 text-center text-brand-muted"><?php esc_html_e( 'لم تختر أي جهاز بعد', 'baytkahraba' ); ?></li>
				</ul>
				<dl class="space-y-1.5 border-t border-brand-line pt-3 text-sm">
					<div class="flex justify-between"><dt><?php esc_html_e( 'المجموع', 'baytkahraba' ); ?></dt><dd class="font-semibold bk-num" x-text="format(subtotal)"></dd></div>
					<div class="flex justify-between text-brand-emerald-hover" x-show="discount > 0"><dt><?php esc_html_e( 'خصم الباقة', 'baytkahraba' ); ?> (<span class="bk-num" x-text="discountPct + '%'"></span>)</dt><dd class="font-semibold bk-num" x-text="'-' + format(discount)"></dd></div>
					<p class="text-xs text-brand-muted" x-show="discount === 0 && items.length < minItems"><?php printf( esc_html__( 'أضف %s أجهزة على الأقل لتفعيل خصم الباقة.', 'baytkahraba' ), '<span class="bk-num" x-text="minItems"></span>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<div class="flex justify-between border-t border-brand-line pt-2 text-lg font-bold text-brand-ink"><dt><?php esc_html_e( 'الإجمالي', 'baytkahraba' ); ?></dt><dd class="bk-num" x-text="format(total)"></dd></div>
				</dl>
				<button type="button" class="bk-btn-amber bk-btn-lg w-full" :disabled="!items.length || busy" @click="addAll()"><?php bk_the_icon( 'cart' ); ?><?php esc_html_e( 'إضافة الباقة كاملة للسلة', 'baytkahraba' ); ?></button>
				<button type="button" class="bk-btn-emerald w-full" :disabled="!items.length || busy" @click="whatsapp()"><?php bk_the_icon( 'whatsapp', 'size-5' ); ?><?php esc_html_e( 'اطلب الباقة عبر واتساب', 'baytkahraba' ); ?></button>
				<p class="text-center text-xs text-brand-muted"><?php esc_html_e( 'توصيل مجاني للباقة داخل دمشق وريفها القريب', 'baytkahraba' ); ?></p>
			</div>
		</aside>
	</div>

	<?php while ( have_posts() ) : the_post(); ?>
		<?php if ( trim( (string) get_the_content() ) ) : ?>
			<article class="bk-card bk-prose mt-12 p-5 lg:p-10"><?php the_content(); ?></article>
		<?php endif; ?>
	<?php endwhile; ?>
</div>
<?php
get_footer();
