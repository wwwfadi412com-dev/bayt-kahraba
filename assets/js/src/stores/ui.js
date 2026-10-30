// Small UI stores: toast notifications and the quick-view modal.
import Alpine from 'alpinejs';
import { rest } from '../lib/api';

export const toast = {
  items: [],
  seq: 0,
  push(text, type = 'success', action = null, actionLabel = '') {
    const id = ++this.seq;
    this.items.push({ id, text, type, action, actionLabel });
    setTimeout(() => this.dismiss(id), type === 'error' ? 5000 : 3000);
  },
  dismiss(id) {
    this.items = this.items.filter((t) => t.id !== id);
  },
};

export const quickView = {
  open: false,
  loading: false,
  html: '',
  cache: {},
  async show(id) {
    this.open = true;
    if (this.cache[id]) {
      this.html = this.cache[id];
      return;
    }
    this.loading = true;
    try {
      this.html = this.cache[id] = (await rest(`quick-view/${id}`)).html;
    } catch (error) {
      this.open = false;
      Alpine.store('toast').push(error.message, 'error');
    } finally {
      this.loading = false;
    }
  },
};
