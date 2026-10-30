# Bayt Al-Kahraba — engineering contract

WordPress + WooCommerce theme for an electrical & home-appliance store in Syria.
RTL Arabic (`ar-SY`), Tailwind 3 + Alpine.js 3 (esbuild), zero jQuery on the storefront.
Read this before changing anything.

## Golden rules
- **Nothing hard-coded.** Brands, categories (3 levels), brand×category silos, menus, filters,
  sitemaps and llms.txt are all derived from `bk_get_catalog_matrix()` / `bk_get_category_tree()`.
- **One filter definition.** `inc/catalog-query.php` (`bk_catalog_filters()` → `bk_catalog_clauses()`)
  drives BOTH the main query (crawlable URLs) and REST `/bk/v1/products`. Never fork it.
- **Caching = versioned keys.** Use `bk_remember()` / `bk_remember_html()`. Invalidate with
  `bk_cache_bump()` (already wired to term/product/price/stock changes). Never delete transients by pattern.
- **Store IDs, not URLs** in options/meta (migration-safe).
- **Meta keys are a public contract** (CSV import/export, REST, schema): `_wattage_consumption`,
  `_ampere_draw`, `_solar_inverter_friendly` (solar_ready|low_amp|standard_grid), `_base_usd_price`,
  `_base_usd_sale_price`, `_bk_model_code`, `_bk_capacity`, `_bk_dimensions`, `_bk_origin`,
  `_bk_warranty_override`, `_bk_highlights`, `_bk_faq`, `_bk_shop_video`, `_bk_shop_video_poster`, `_bk_bundle_tier`.
- Prefix everything `bk_` / `BK_`; text domain `baytkahraba`; escape late; nonces on every write.

## Module map (`inc/`)
| Module | Responsibility |
|---|---|
| helpers | settings (`bk_option`), cache, money/phone/WhatsApp, power classes, icons |
| environment | staging noindex, legacy-URL rewrite, mail guard |
| setup / performance / assets | supports, image sizes, WebP, bloat removal, enqueue + `BK` JS config |
| admin-settings | "بيت الكهرباء" menu + reusable `bk_admin_field()` |
| taxonomies-brands | `product_brand` (extends WC core Brands if present) + term meta |
| category-hierarchy-engine | tree, ancestors, catalog matrix, weight class, price ranges |
| seo-silo-rewrites | `/brand/{brand}/{cat}/`, 404 on empty, silo sitemap |
| product-meta | product data tab, CSV mapping, specs/highlights/video API |
| catalog-query | filters, REST query args, pagination, related-product engines |
| rest-api | `/products`, `/search`, `/quick-view/{id}`, `/compare` |
| syria-pricing-sync | USD→SYP set-based SQL sync, history, CSV sheet, `wp bk prices` |
| syria-shipping-checkout | governorates, 3-zone method, 4-field checkout, COD rule, transfer gateway, WhatsApp |
| seo-content-templates | keyword matrix, titles/H1/meta, intro, buying guide, FAQs, synonyms |
| seo-geo-engine | head tags, breadcrumbs, JSON-LD @graph, llms.txt, synonym search |
| bundle-builder | tiers, REST, bundle discount fee |
| warranty-registry | `bk_warranty` CPT, `[bk_warranty]`, REST |
| seed-demo-data | WooCommerce bootstrap + demo catalog, `wp bk seed [--remove]` |

## Commands
```
npm install
npm run build        # fonts + CSS + JS (commit assets/*/dist)
npm run lint:php     # PHP 8.1 syntax check without a PHP binary
wp bk seed           # bootstrap store + demo data
wp bk prices --rate=13000 --step=5000 [--brand=slug|--cat=slug] [--dry-run]
```
After adding Tailwind classes in PHP/JS run `npm run build:css` (dynamic classes → `safelist`).
