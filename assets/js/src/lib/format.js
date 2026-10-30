// Money & Arabic text helpers.
const cfg = () => window.BK || {};

/** Format an amount in SYP with grouping (e.g. "1,250,000 ل.س"). */
export function formatMoney(amount) {
  const currency = cfg().currency || { decimals: 0, symbol: 'ل.س' };
  const value = Number(amount || 0);
  const number = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: currency.decimals,
    maximumFractionDigits: currency.decimals,
  }).format(value);
  return `${number} ${currency.symbol}`;
}

/** Store API amounts are integers in minor units. */
export function fromMinor(value, unit = 0) {
  return Number(value || 0) / 10 ** Number(unit || 0);
}

/** Mirrors bk_normalize_arabic() in PHP. */
export function normalizeArabic(text) {
  return String(text || '')
    .replace(/[ً-ٰٟـ]/g, '')
    .replace(/[أإآٱ]/g, 'ا')
    .replace(/ة/g, 'ه')
    .replace(/ى/g, 'ي')
    .replace(/ؤ/g, 'و')
    .replace(/ئ/g, 'ي')
    .trim()
    .toLowerCase();
}

/** Safe localStorage access (private mode / blocked storage never break the UI). */
export const storage = {
  get(key, fallback) {
    try {
      const raw = window.localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  },
  set(key, value) {
    try {
      window.localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
      /* storage unavailable */
    }
  },
};
