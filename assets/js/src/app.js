// Bayt Al-Kahraba front end — Alpine.js (no jQuery).
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

import cart from './stores/cart';
import compare from './stores/compare';
import { toast, quickView } from './stores/ui';
import catalog from './components/catalog';
import liveSearch from './components/liveSearch';
import productPage from './components/productPage';
import bundleBuilder from './components/bundleBuilder';
import warranty from './components/warranty';

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.store('toast', toast);
Alpine.store('cart', cart);
Alpine.store('compare', compare);
Alpine.store('qv', quickView);

Alpine.data('catalog', catalog);
Alpine.data('liveSearch', liveSearch);
Alpine.data('productPage', productPage);
Alpine.data('bundleBuilder', bundleBuilder);
Alpine.data('warranty', warranty);

window.Alpine = Alpine;
Alpine.start();
