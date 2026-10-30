// Side cart powered by the WooCommerce Store API (no jQuery, no cart fragments).
import Alpine from 'alpinejs';
import { storeApi, rest, openWhatsApp } from '../lib/api';
import { formatMoney, fromMinor, storage } from '../lib/format';
import { quote, areasFor } from '../lib/shipping';

const AREA_KEY = 'bk_area';

export default {
  open: false,
  loading: false,
  busy: null,
  items: [],
  fees: [],
  totals: null,
  count: 0,
  state: storage.get(AREA_KEY, {}).state || 'DI',
  area: storage.get(AREA_KEY, {}).area || '',

  init() {
    // Only hit the API when WooCommerce says a cart exists (keeps cached pages request-free).
    if (document.cookie.includes('woocommerce_items_in_cart')) {
      this.refresh();
    }
  },

  apply(cart) {
    this.items = cart.items || [];
    this.fees = cart.fees || [];
    this.totals = cart.totals || null;
    this.count = Number(cart.items_count || 0);
  },

  async refresh() {
    this.loading = true;
    try {
      this.apply(await storeApi('cart'));
    } catch (e) {
      /* silent: drawer shows the empty state */
    } finally {
      this.loading = false;
    }
  },

  show() {
    this.open = true;
    this.refresh();
  },

  async add(id, quantity = 1, button = null) {
    if (button) {
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
    }
    try {
      this.apply(await storeApi('cart/add-item', { method: 'POST', body: { id, quantity } }));
      this.open = true;
      Alpine.store('toast').push(window.BK.i18n.added);
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    } finally {
      if (button) {
        button.disabled = false;
        button.removeAttribute('aria-busy');
      }
    }
  },

  async update(key, quantity) {
    if (quantity < 1) {
      return this.remove(key);
    }
    this.busy = key;
    try {
      this.apply(await storeApi('cart/update-item', { method: 'POST', body: { key, quantity } }));
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    } finally {
      this.busy = null;
    }
  },

  async remove(key) {
    this.busy = key;
    try {
      this.apply(await storeApi('cart/remove-item', { method: 'POST', body: { key } }));
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    } finally {
      this.busy = null;
    }
  },

  /* Delivery ------------------------------------------------------------ */

  areasFor,

  saveArea() {
    storage.set(AREA_KEY, { state: this.state, area: this.area });
  },

  weights() {
    return this.items.reduce(
      (acc, item) => {
        const cls = (item.extensions && item.extensions.bk && item.extensions.bk.weight) || 'heavy';
        acc[cls] += Number(item.quantity || 1);
        return acc;
      },
      { small: 0, heavy: 0 }
    );
  },

  quote() {
    return quote(this.state, this.area, this.weights());
  },

  /* Formatting ---------------------------------------------------------- */

  money(value, unit) {
    return formatMoney(fromMinor(value, unit));
  },

  format: formatMoney,

  /* WhatsApp ------------------------------------------------------------ */

  async whatsapp() {
    try {
      await openWhatsApp(async () => (await rest('whatsapp/cart', { method: 'POST', body: { state: this.state, area: this.area } })).url);
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    }
  },

  async whatsappFromCheckout() {
    const value = (id) => (document.getElementById(id) || {}).value || '';
    try {
      await openWhatsApp(async () =>
        (
          await rest('whatsapp/cart', {
            method: 'POST',
            body: {
              name: value('billing_first_name'),
              state: value('billing_state'),
              area: value('billing_bk_area').replace('__other', ''),
              address: value('billing_address_1'),
            },
          })
        ).url
      );
    } catch (error) {
      Alpine.store('toast').push(error.message, 'error');
    }
  },
};
