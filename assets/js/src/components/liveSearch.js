// Live search with keyboard navigation (results include dialect synonyms).
import { rest } from '../lib/api';

export default () => ({
  q: '',
  results: null,
  open: false,
  loading: false,
  active: -1,
  controller: null,

  init() {
    const input = this.$el.querySelector('input[type="search"]');
    this.q = input ? input.value : '';
  },

  async search() {
    const q = this.q.trim();
    if (q.length < 2) {
      this.open = false;
      this.results = null;
      return;
    }
    if (this.controller) this.controller.abort();
    this.controller = new AbortController();
    this.open = true;
    this.loading = true;
    try {
      this.results = await rest('search', { params: { q }, signal: this.controller.signal });
      this.active = -1;
    } catch (error) {
      if (error.name !== 'AbortError') this.results = { products: [], categories: [], brands: [], total: 0 };
    } finally {
      this.loading = false;
    }
  },

  move(step) {
    const count = this.results ? this.results.products.length : 0;
    if (!count) return;
    this.active = (this.active + step + count) % count;
  },

  enter(event) {
    const product = this.results && this.results.products[this.active];
    if (this.open && product) {
      event.preventDefault();
      window.location.href = product.url;
    }
  },

  submit(event) {
    if (!this.q.trim()) event.preventDefault();
  },
});
