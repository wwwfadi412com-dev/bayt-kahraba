// Client-side mirror of bk_shipping_quote() (PHP) for instant estimates.
import { normalizeArabic } from './format';

const config = () => (window.BK && window.BK.shipping) || { areas: [], gov: {}, eta: {}, labels: {} };

/** Areas [name, state, zone, fee] belonging to a governorate. */
export function areasFor(state) {
  return config().areas.filter((area) => area[1] === state);
}

/**
 * @param {string} state   Governorate code (DI, RD, HL …).
 * @param {string} area    Area name (Damascus / Rif only).
 * @param {{small?: number, heavy?: number}} weights Quantities per weight class.
 * @returns {{zone: number, cost: number, label: string, eta: string}}
 */
export function quote(state, area, weights = { heavy: 1 }) {
  const c = config();
  const code = (state || 'DI').toUpperCase();

  if (code === 'DI' || code === 'RD') {
    const match = c.areas.find((a) => normalizeArabic(a[0]) === normalizeArabic(area));
    if ((match && match[2] === 1) || (!match && code === 'DI')) {
      return { zone: 1, cost: 0, label: c.labels.z1, eta: c.eta[1] };
    }
    const fee = match ? Number(match[3]) : Number(c.zone2Def);
    return { zone: 2, cost: fee, label: c.labels.z2.replace('%s', area || c.labels.z2def), eta: c.eta[2] };
  }

  const fees = c.gov[code] || { small: 0, heavy: 0 };
  const heavy = Number(weights.heavy || 0);
  const small = Number(weights.small || 0);
  const cost = heavy > 0 ? fees.heavy * heavy : small > 0 ? fees.small : 0;
  return { zone: 3, cost, label: c.labels.z3, eta: c.eta[3] };
}
