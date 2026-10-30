// Bride & Home Setup Bundle Builder.
import Alpine from 'alpinejs';
import { rest, openWhatsApp } from '../lib/api';
import { formatMoney } from '../lib/format';

export default () => ({
  loading: true,
  busy: false,
  steps: [],
  tiers: {},
  tier: 'smart',
  selected: {},
  current: 0,
  discountPct: 0,
  minItems: 3,

  async init() {
    try {
      const data = await rest('bundle');
      this.steps = data.steps;
      this.tiers = data.tiers;
      this.discountPct = Number(data.discount || 0);
      this.minItems = Number(data.minItems || 3);
      this.setTier('smart');
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    } finally {
      this.loading = false;
    }
  },

  format: formatMoney,

  setTier(tier) {
    this.tier = tier;
    const selected = {};
    this.steps.forEach((step) => {
      selected[step.key] = [...((step.picks && step.picks[tier]) || [])];
    });
    this.selected = selected;
  },

  isSelected(step, id) {
    return (this.selected[step.key] || []).includes(id);
  },

  toggle(step, id) {
    let list = this.selected[step.key] || [];
    if (step.multi) {
      list = list.includes(id) ? list.filter((x) => x !== id) : [...list, id];
    } else {
      list = list.includes(id) ? [] : [id];
      if (list.length && this.current < this.steps.length - 1) {
        setTimeout(() => this.current++, 250);
      }
    }
    this.selected = { ...this.selected, [step.key]: list };
  },

  itemsFor(selection) {
    return this.steps.flatMap((step) =>
      (selection[step.key] || [])
        .map((id) => step.candidates.find((c) => c.id === id))
        .filter(Boolean)
        .map((c) => ({ ...c, step: step.label }))
    );
  },

  totalFor(items) {
    const subtotal = items.reduce((sum, item) => sum + Number(item.price), 0);
    const discount = items.length >= this.minItems ? Math.round((subtotal * this.discountPct) / 100) : 0;
    return { subtotal, discount, total: subtotal - discount };
  },

  tierTotal(tier) {
    const selection = {};
    this.steps.forEach((step) => {
      selection[step.key] = (step.picks && step.picks[tier]) || [];
    });
    return this.totalFor(this.itemsFor(selection)).total;
  },

  get items() {
    return this.itemsFor(this.selected);
  },
  get subtotal() {
    return this.totalFor(this.items).subtotal;
  },
  get discount() {
    return this.totalFor(this.items).discount;
  },
  get total() {
    return this.totalFor(this.items).total;
  },

  ids() {
    return this.items.map((item) => item.id);
  },

  async addAll() {
    this.busy = true;
    try {
      await rest('bundle/add', { method: 'POST', body: { ids: this.ids() } });
      await Alpine.store('cart').refresh();
      Alpine.store('cart').open = true;
      Alpine.store('toast').push(window.BK.i18n.added);
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    } finally {
      this.busy = false;
    }
  },

  async whatsapp() {
    const cart = Alpine.store('cart');
    try {
      await openWhatsApp(async () =>
        (await rest('bundle/whatsapp', { method: 'POST', body: { ids: this.ids(), tier: this.tier, state: cart.state, area: cart.area } })).url
      );
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    }
  },
});
