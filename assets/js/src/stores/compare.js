// Side-by-side model & brand comparator (persisted per visitor).
import Alpine from 'alpinejs';
import { rest } from '../lib/api';
import { storage } from '../lib/format';

const KEY = 'bk_compare';

export default {
  ids: storage.get(KEY, []),
  items: [],
  open: false,
  loading: false,

  has(id) {
    return this.ids.includes(id);
  },

  toggle(id) {
    if (this.has(id)) {
      this.ids = this.ids.filter((x) => x !== id);
      this.items = this.items.filter((p) => p.id !== id);
    } else {
      const max = Number(window.BK.compareMax || 4);
      if (this.ids.length >= max) {
        Alpine.store('toast').push(window.BK.i18n.compareFull, 'error');
        return;
      }
      this.ids = [...this.ids, id];
    }
    storage.set(KEY, this.ids);
    if (this.open && this.ids.length < 2) {
      this.open = false;
    }
  },

  clear() {
    this.ids = [];
    this.items = [];
    storage.set(KEY, []);
  },

  async show() {
    if (!this.ids.length) return;
    this.open = true;
    this.loading = true;
    try {
      this.items = (await rest('compare', { params: { ids: this.ids.join(',') } })).products;
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    } finally {
      this.loading = false;
    }
  },

  /** Highlight the best value in a row (lowest price / watt / ampere). */
  isBest(product, key) {
    const field = { price_html: 'price', wattage: 'wattage', ampere: 'ampere' }[key];
    if (!field || this.items.length < 2) return false;
    const values = this.items.map((p) => Number(p[field])).filter((v) => v > 0);
    return values.length > 1 && Number(product[field]) === Math.min(...values);
  },
};
