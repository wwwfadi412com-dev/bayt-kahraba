// "كم أمبير عندك؟" — shows which real devices fit a household ampere subscription.
const COLORS = ['#F2EFE8', '#FF5B1F', '#FF9A6B', '#12A150', '#5BC08A', '#BDB7AA', '#3D7BFF', '#F7A81B'];

export default (config) => ({
  items: (config.items || []).map((item, i) => ({ ...item, color: COLORS[i % COLORS.length] })),
  caps: config.caps || [1, 2, 3, 5],
  cap: config.cap || 2,
  shop: config.shop || '/',

  get plan() {
    let used = 0;
    return this.items.map((item) => {
      const fits = used + item.ampere <= this.cap + 1e-9;
      if (fits) used += item.ampere;
      return { ...item, fits };
    });
  },
  get used() {
    return this.plan.filter((i) => i.fits).reduce((sum, i) => sum + i.ampere, 0);
  },
  width(item) {
    return `${Math.min(100, (item.ampere / this.cap) * 100)}%`;
  },
  amp(value) {
    return Number(value).toFixed(2);
  },
  get link() {
    const url = new URL(this.shop, window.location.origin);
    url.searchParams.set('amp_max', this.cap);
    return url.toString();
  },
});
