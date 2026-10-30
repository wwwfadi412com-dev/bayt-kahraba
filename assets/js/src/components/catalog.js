// Instant catalog filtering & paging with crawlable, shareable URLs.
import Alpine from 'alpinejs';
import { rest } from '../lib/api';

const DEFAULTS = {
  power: '',
  amp_max: 0,
  brand_filter: '',
  cat_filter: '',
  min_price: '',
  max_price: '',
  orderby: '',
  in_stock: false,
};

const pageFromPath = (path) => {
  const match = String(path).match(/\/page\/(\d+)\/?/);
  return match ? Number(match[1]) : 1;
};

export default (config) => ({
  filters: { ...DEFAULTS, ...normalize(config.filters) },
  total: Number(config.total || 0),
  page: pageFromPath(window.location.pathname),
  loading: false,
  filtersOpen: false,
  controller: null,

  set(key, value) {
    this.filters[key] = value;
    this.apply();
  },

  hasFilters() {
    return Object.keys(DEFAULTS).some((k) => k !== 'orderby' && this.filters[k] && this.filters[k] !== DEFAULTS[k]);
  },

  reset() {
    this.filters = { ...DEFAULTS };
    this.apply();
  },

  params() {
    const out = {};
    Object.entries(this.filters).forEach(([key, value]) => {
      if (value !== '' && value !== 0 && value !== false && value !== null) out[key] = value === true ? 1 : value;
    });
    return out;
  },

  url(page) {
    const query = new URLSearchParams(this.params());
    if (config.context.s) {
      query.set('s', config.context.s);
      query.set('post_type', 'product');
    }
    const path = config.base + (page > 1 ? `page/${page}/` : '');
    const qs = query.toString();
    return qs ? `${path}?${qs}` : path;
  },

  async apply(page = 1, push = true) {
    if (this.controller) this.controller.abort();
    this.controller = new AbortController();
    this.loading = true;
    try {
      const data = await rest('products', {
        signal: this.controller.signal,
        params: {
          ...this.params(),
          page,
          brand: config.context.brand || '',
          cat: config.context.cat || '',
          s: config.context.s || '',
          base_url: new URL(config.base, window.location.origin).href,
        },
      });
      this.$refs.grid.innerHTML = data.html;
      this.$refs.pagination.innerHTML = data.pagination;
      this.total = data.total;
      this.page = data.page;
      if (push) {
        window.history.pushState({ bk: true }, '', this.url(page));
      }
      if (page > 1 || !push) {
        this.$refs.grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    } catch (error) {
      if (error.name !== 'AbortError') {
        Alpine.store('toast').push(error.message, 'error');
      }
    } finally {
      this.loading = false;
    }
  },

  paginate(event) {
    const link = event.target.closest('a');
    if (!link || event.metaKey || event.ctrlKey) return;
    event.preventDefault();
    this.apply(pageFromPath(new URL(link.href).pathname));
  },

  fromUrl() {
    const query = Object.fromEntries(new URLSearchParams(window.location.search));
    this.filters = { ...DEFAULTS, ...normalize(query) };
    this.apply(pageFromPath(window.location.pathname), false);
  },
});

function normalize(source = {}) {
  const out = {};
  Object.keys(DEFAULTS).forEach((key) => {
    if (source[key] === undefined || source[key] === null) return;
    if (key === 'in_stock') out[key] = Boolean(source[key]) && source[key] !== '0';
    else if (key === 'amp_max') out[key] = Number(source[key]) || 0;
    else if (key === 'min_price' || key === 'max_price') out[key] = Number(source[key]) || '';
    else out[key] = String(source[key]);
  });
  return out;
}
