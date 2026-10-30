// REST helpers for the theme API (bk/v1) and the WooCommerce Store API.
const cfg = () => window.BK || {};

let storeNonce = null;

function buildUrl(base, path, params) {
  const url = new URL(base + String(path).replace(/^\//, ''), window.location.origin);
  Object.entries(params || {}).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined && value !== false && value !== 0) {
      url.searchParams.set(key, value === true ? '1' : value);
    }
  });
  return url;
}

async function parse(res) {
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const error = new Error(data.message || cfg().i18n?.error || 'Request failed');
    error.status = res.status;
    error.data = data;
    throw error;
  }
  return data;
}

/**
 * Theme REST API. Read requests are public (cache friendly); writes send the
 * wp_rest nonce and transparently retry without it when a full-page cache
 * served a stale nonce to a guest.
 */
export async function rest(path, { method = 'GET', params, body, signal } = {}) {
  const url = buildUrl(cfg().rest, path, params);
  const send = (withNonce) =>
    fetch(url, {
      method,
      signal,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
        ...(withNonce && cfg().nonce ? { 'X-WP-Nonce': cfg().nonce } : {}),
      },
      body: body ? JSON.stringify(body) : undefined,
    });

  let res = await send(method !== 'GET');
  if (res.status === 403 && method !== 'GET') {
    res = await send(false);
  }
  return parse(res);
}

/**
 * WooCommerce Store API (cart). Keeps the rolling `Nonce` header fresh so
 * cached pages never break add-to-cart.
 */
export async function storeApi(path, { method = 'GET', body } = {}, retried = false) {
  if (method !== 'GET' && !storeNonce) {
    await storeApi('cart'); // Prime a fresh nonce from the server.
  }
  const res = await fetch(buildUrl(cfg().storeApi, path), {
    method,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Nonce: storeNonce || cfg().storeNonce || '',
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  const nonce = res.headers.get('Nonce');
  if (nonce) {
    storeNonce = nonce;
  }
  if ((res.status === 401 || res.status === 403) && !retried && method !== 'GET') {
    storeNonce = null;
    return storeApi(path, { method, body }, true);
  }
  return parse(res);
}

/**
 * Open WhatsApp reliably from async code (pre-opens the tab inside the click
 * gesture so mobile popup blockers allow it).
 */
export async function openWhatsApp(producer) {
  const tab = window.open('', '_blank');
  try {
    const url = await producer();
    if (tab) {
      tab.location.href = url;
    } else {
      window.location.href = url;
    }
  } catch (error) {
    if (tab) tab.close();
    throw error;
  }
}
