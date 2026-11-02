// Bayt Al-Kahraba front end — dependency-free (~3KB). Every feature degrades
// gracefully: links, native <dialog>, <details> and GET forms work without JS.
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
const cfg = window.BK || {};
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/* Header border once the page scrolls */
const header = $('.site-header');
if (header) {
  const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/* Dialogs: [data-open="id"] opens <dialog id>, backdrop click / [data-close] closes */
$$('[data-open]').forEach((btn) =>
  btn.addEventListener('click', () => {
    const d = document.getElementById(btn.dataset.open);
    if (!d?.showModal) return;
    d.showModal();
    $('input', d)?.focus();
  })
);
$$('dialog').forEach((d) => {
  d.addEventListener('click', (e) => {
    if (e.target === d || e.target.closest('[data-close]')) d.close();
  });
});
document.addEventListener('keydown', (e) => {
  if (e.key === '/' && !/input|textarea|select/i.test(document.activeElement?.tagName || '')) {
    const s = document.getElementById('bk-search');
    if (s?.showModal) {
      e.preventDefault();
      s.showModal();
      $('input', s)?.focus();
    }
  }
});

/* Desktop nav panels */
$$('.nav-item').forEach((item) => {
  const btn = $('button', item);
  if (!btn) return;
  const set = (open) => {
    item.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', String(open));
  };
  btn.addEventListener('click', () => set(!item.classList.contains('is-open')));
  item.addEventListener('mouseenter', () => set(true));
  item.addEventListener('mouseleave', () => set(false));
  item.addEventListener('focusout', (e) => !item.contains(e.relatedTarget) && set(false));
  document.addEventListener('keydown', (e) => e.key === 'Escape' && set(false));
});

/* Live search */
const form = $('#bk-search form');
if (form && cfg.rest) {
  const input = $('input[name="s"]', form);
  const out = $('[data-results]', form.closest('dialog'));
  let timer;
  let ctrl;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) {
      out.innerHTML = '';
      return;
    }
    timer = setTimeout(async () => {
      ctrl?.abort();
      ctrl = new AbortController();
      try {
        const res = await fetch(`${cfg.rest}search?q=${encodeURIComponent(q)}`, { signal: ctrl.signal });
        const data = await res.json();
        const cats = (data.categories || []).concat(data.brands || []);
        out.innerHTML =
          (cats.length ? `<div class="mb-2 flex flex-wrap gap-2">${cats.map((c) => `<a class="chip" href="${esc(c.url)}">${esc(c.name)}</a>`).join('')}</div>` : '') +
          ((data.products || []).map((p) => `<a class="search-hit" href="${esc(p.url)}"><img src="${esc(p.image)}" alt="" width="56" height="56" loading="lazy"><span class="min-w-0 flex-1"><span class="block truncate font-semibold text-ink">${esc(p.name)}</span><span class="block text-[13px] text-muted">${esc(p.brand)}${p.model ? ' · ' + esc(p.model) : ''}</span></span><span class="shrink-0 text-end">${p.price}</span></a>`).join('') ||
            `<p class="p-4 text-center text-muted">${esc(cfg.i18n?.noResults || '')}</p>`) +
          (data.total > (data.products || []).length ? `<a class="btn-ghost mt-3 w-full" href="${esc(data.all_url)}">${esc(cfg.i18n?.all || '')} (${data.total})</a>` : '');
      } catch (e) {
        /* aborted or offline: the form still submits normally */
      }
    }, 220);
  });
}

/* Amp switch: show only devices that fit the chosen subscription */
$$('[data-amp-switch]').forEach((root) => {
  const keys = $$('[data-amp]', root.querySelector('.amp-plate'));
  const cards = $$('[data-amp-card]', root);
  const count = $('[data-amp-count]', root);
  const more = $('[data-amp-more]', root);
  const apply = (cap) => {
    let n = 0;
    keys.forEach((k) => k.setAttribute('aria-pressed', String(Number(k.dataset.amp) === cap)));
    cards.forEach((c) => {
      const fits = Number(c.dataset.ampCard) <= cap;
      c.hidden = !fits;
      n += fits ? 1 : 0;
    });
    if (count) count.textContent = n;
    if (more) more.href = more.href.replace(/amp_max=[\d.]+/, `amp_max=${cap}`);
  };
  keys.forEach((k) => k.addEventListener('click', () => apply(Number(k.dataset.amp))));
});

/* Rail arrows (desktop) */
$$('[data-rail]').forEach((rail) => {
  const box = rail.parentElement;
  const step = (dir) => rail.scrollBy({ left: dir * rail.clientWidth * (document.dir === 'rtl' || document.documentElement.dir === 'rtl' ? -1 : 1), behavior: 'smooth' });
  $('[data-rail-next]', box)?.addEventListener('click', () => step(1));
  $('[data-rail-prev]', box)?.addEventListener('click', () => step(-1));
});

/* Product gallery */
$$('[data-gallery]').forEach((g) => {
  const main = $('[data-main]', g);
  $$('[data-thumb]', g).forEach((t) =>
    t.addEventListener('click', () => {
      main.src = t.dataset.src;
      main.srcset = t.dataset.srcset || '';
      $$('[data-thumb]', g).forEach((x) => x.setAttribute('aria-current', String(x === t)));
    })
  );
});

/* Filter selects submit their GET form instantly */
$$('form[data-autosubmit] select').forEach((s) => s.addEventListener('change', () => s.form.submit()));
