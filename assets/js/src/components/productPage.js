// Single product: gallery, quantity, delivery estimator and WhatsApp order.
import Alpine from 'alpinejs';
import { formatMoney } from '../lib/format';
import { quote } from '../lib/shipping';

export default (cfg) => ({
  cfg,
  qty: 1,
  slide: 0,

  // Governorate / area are shared with the side cart (and remembered).
  get state() {
    return Alpine.store('cart').state;
  },
  set state(value) {
    Alpine.store('cart').state = value;
  },
  get area() {
    return Alpine.store('cart').area;
  },
  set area(value) {
    Alpine.store('cart').area = value;
  },

  save() {
    Alpine.store('cart').saveArea();
  },

  get quote() {
    return quote(this.state, this.area, { [cfg.weightClass]: this.qty });
  },

  get whatsappUrl() {
    const states = (window.BK.shipping && window.BK.shipping.states) || {};
    const subtotal = cfg.price * this.qty;
    const q = this.quote;
    const lines = [
      `🛒 طلب جديد من ${window.BK.storeName}`,
      '',
      `1) ${cfg.name}`,
      `   ${[cfg.brand && `الماركة: ${cfg.brand}`, cfg.model && `الموديل: ${cfg.model}`, `الكمية: ${this.qty}`, `السعر: ${formatMoney(subtotal)}`]
        .filter(Boolean)
        .join(' | ')}`,
      '',
      `المحافظة / المنطقة: ${states[this.state] || this.state}${this.area ? ` - ${this.area}` : ''}`,
      `التوصيل: ${q.cost ? formatMoney(q.cost) : window.BK.i18n.free} (${q.eta})`,
      `الإجمالي النهائي: ${formatMoney(subtotal + q.cost)}`,
      cfg.url,
      '',
      'أرجو تأكيد الطلب وموعد التوصيل، شكراً.',
    ];
    return `https://wa.me/${window.BK.whatsapp}?text=${encodeURIComponent(lines.join('\n'))}`;
  },
});
