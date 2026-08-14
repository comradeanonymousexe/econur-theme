# Econur — BUILD NOTES

Living record of decisions, placeholders, deviations, and open questions for the
Econur custom WooCommerce theme (`econur-theme`) and CRM plugin (`econur-crm`).
Required by Master Prompt §0.4 and §11. Keep this current as the build proceeds.

_Last updated: 2026-07-19 — all build phases complete (theme + CRM plugin), lint-clean. Items needing a live WordPress to verify are flagged in §11._

---

## 1. Locked decisions (from discovery)

| Area | Decision | Rationale / consequence |
|---|---|---|
| **Delivery model** | Drop-in source files; client tests on their own WordPress/WooCommerce. | This machine has no PHP/Docker, so no stack is stood up here. All runtime verification (Lighthouse, test orders, checkout, reminder generation) happens on the client's WP install. |
| **Editable fields** | **Native meta boxes** — no ACF. | Zero paid/third-party dependency; self-contained; best fit for the "minimise plugins / no bloat / mobile-speed" mandate (§2, §8). |
| **Meta-box UI** | **Simplest possible** — text/textarea with delimiter conventions (one item per line; `Heading :: description`). | Client choice. **Zero admin JS/CSS** — the most bloat-free way to do native repeaters. Parsed by `inc/content-helpers.php`. Long-form product intro **reuses WooCommerce's own Description editor** (no duplicate field). |
| **Product catalog** | Clearly-labeled **placeholder products + a fillable WooCommerce CSV import path**. | Matches §10.4. No real SKUs/prices invented. A sample `products.csv` + import guide ships so staff bulk-load the real catalog later. |
| **JS interactivity** | **Vanilla JS**, one deferred file, zero dependencies. | Re-decided from Alpine.js in favour of the client's explicit "zero bloat / most elegant" priority. Our interactions (filter grid, quick-view, drawers, mobile nav) are modest enough that hand-written vanilla ships fewer bytes and no framework dependency. |
| **Theme baseline** | From-scratch minimal (not `_s`/Underscores). | Leaner output, no boilerplate to strip, tighter control for Awwwards-style layout + mobile speed. |

---

## 2. Placeholders in use (replace before launch)

All placeholder content is labeled in-code with `PLACEHOLDER:` comments. Nothing
below is a real brand fact — do not treat as confirmed.

- **Products / SKUs / prices / photos** — none are real. Only **Active Defense Bar**
  is treated as the confirmed-live flagship (per brief); everything else is
  placeholder pending the real catalog CSV.
- **Weight/size ladder** — assumed **50gm / 100gm** per spec, but flagged unconfirmed (§10.2).
  The CRM reminder engine keys its offsets on a *config map* (see §4 below), so
  changing real sizes is a one-line change, not a refactor.
- **Delivery rates** — assumed Inside Dhaka ৳60 / Outside Dhaka ৳120 (§10.3), but these
  live in **WooCommerce → Shipping settings**, NOT in code. Do not hard-code.
- **Testimonials** — seeded from the brief's three testimonials, but any product
  names in them will be rewritten to reference only confirmed-live products
  (no "Lavender Serenity" / "Charcoal Detox" / "Licorice Brightening Bar" as
  purchasable until confirmed — §10.1, spec §4.1.3).
- **Lab report** — TFM 75%–86%, Free Alkali "Nil", 100% Natural, and the BCSIR/BUET
  download URLs are seeded from the brief but exposed as editable fields so a
  future re-test needs no developer (spec §4.3).

---

## 3. The three templates (hard constraint — nothing else, spec §1)

1. `front-page.php` — Homepage (6 sections, custom design, IS the shop front).
2. `woocommerce/single-product.php` — one template for every soap.
3. `page-lab-report.php` — static Lab Report page.

WooCommerce-managed Cart/Checkout/My-Account pages are out of "the 3" per §1 (they
are technically required by WooCommerce, not additional designed templates).

---

## 4. CRM data model (spec §7.1) — implemented in Phase 1

Single custom table `{$wpdb->prefix}econur_reminders` (created via `dbDelta` on
activation). Customers are **not** a table — unified by billing phone at query time.

Columns: `id`, `order_id`, `order_item_id`, `customer_name`, `customer_phone`,
`product_id`, `variation_weight`, `purchase_date`, `reminder_type`
(`repurchase_1` | `repurchase_2` | `special_date` | `custom`), `due_date`,
`status` (`pending` | `sent` | `dismissed`), `whatsapp_link`, `created_at`, `sent_at`.

**Reminder generation rule (client's exact spec — to implement in CRM phase):**
- Order reaches `processing`/`completed` → per line item, read weight variation:
  - `50gm`  → two reminders at purchase_date **+15d** and **+30d**.
  - `100gm` → two reminders at purchase_date **+20d** and **+50d**.
- Offsets stored as a filterable config map (`50gm => [15,30]`, `100gm => [20,50]`),
  defaulting exactly to the above, so unconfirmed real sizes (§10.2) are trivial to adjust.
- `econur_special_date` saved/updated → upsert an annual `special_date` reminder.
- Hooked off `woocommerce_order_status_changed` in the plugin (never the theme).

**WhatsApp:** semi-automated (honest scope — WP cannot send WhatsApp natively).
Daily WP-Cron flags due reminders + surfaces a one-tap `wa.me` deep link with a
drafted message in the admin worklist. Staff send, then "Mark Sent".

**Upgrade path (documented per §7.1):** the daily cron fires
`do_action( 'econur_reminder_due', $reminder_row )`. A real WhatsApp Business Cloud
API / Twilio / 360dialog integration can hook that action later WITHOUT touching
core reminder logic.

---

## 5. Deviations from the spec (with rationale, per §0.3)

Each deviation follows best practice over a literal spec instruction (§0.3) and
carries a matching code comment.

1. **Categories reuse WooCommerce's built-in `product_cat`** instead of a new
   `product_category` taxonomy (spec §2). A parallel taxonomy would duplicate
   Woo's product admin, break the CSV importer's `tax:product_cat` column, and
   break Store Analytics — pure bloat. `skin_concern` remains the one new
   custom taxonomy. _See `inc/taxonomies.php`._
2. **Vanilla JS instead of Alpine.js** (spec §2 listed Alpine as an option) —
   see §1 above. Zero framework dependency. _See `assets/js/main.js`._
3. **Product gallery zoom/lightbox/slider NOT enabled** (`add_theme_support`
   omits `wc-product-gallery-*`). Those pull in flexslider + zoom + photoswipe,
   a real JS hit against the mobile-speed budget (§8). A lightweight custom
   gallery is built in the single-product phase instead. _See `inc/setup.php`._

Notable honest-scope clarifications already baked in:
- WhatsApp sending is **semi-automated**, not fully automated (§7.1) — surfaced as an
  admin worklist + deep link, with an action hook for a future paid API.

---

## 6. Open questions (spec §10) — status tracker

| # | Question | Status | Handling until answered |
|---|---|---|---|
| 1 | Full product catalog (SKUs, prices, categories, photos) | **Open** | Labeled placeholders + fillable CSV import path. |
| 2 | Actual weight/price ladder (50gm/100gm?) | **Open** | Assumed 50/100gm; reminder offsets are config-driven. |
| 3 | Delivery pricing (Inside/Outside Dhaka) | **Open** | Lives in WooCommerce Shipping settings, not code. |
| 4 | CSV-driven catalog preferred | **Confirmed** | Sample `products.csv` + import guide will ship. |

Also to confirm with client (surfaced during discovery):
- Confirmed-live product list (beyond Active Defense Bar) for seed content integrity.
- Real WhatsApp support number — brief shows `+880 1410-753555` (`wa.me/8801410753555`).

---

## 7. Client UI revisions — round 1 (post-templates)

Requested after the three templates were approved. All applied and lint-clean.

1. **Showcase → auto-advancing carousel.** Slides = optional promo/offer slides
   (Customizer "Carousel promo / offer slides", one per line
   `Headline :: Subtext :: Button label :: URL :: Image URL`) **+** featured
   products from WooCommerce's native **Featured** star toggle (newest-products
   fallback so it's never empty). Replaces the old single-featured-product picker.
   _`template-parts/homepage/showcase.php`, `assets/js/carousel.js`._
2. **No WhatsApp CTAs anywhere except the footer contact link.** Removed the
   floating WhatsApp FAB (`whatsapp-fab.php` deleted) and the product "Chat on
   WhatsApp" button. All CTAs are Order-oriented. The footer social WhatsApp link
   (contact info) stays.
3. **Richer product cards** (`template-parts/product/card.php`, reused by grid +
   related): image · name · short blurb naming **hero ingredients + target skin
   type** · inline size selector · **Order Now + Add to Cart**. The quick-view
   modal was **retired** (redundant now) — card image/name link to the full page.
4. **Unique main-CTA style** (`.econ-btn--order`): an emerald gradient + glow used
   **only** for the "Order" buy-now action (add to cart → checkout), never on any
   other button. Add-to-Cart is the outline/secondary style. Buy-now handler added
   to `assets/js/buybox.js`.

---

## 8. Setup instructions (expanded as the build lands)

**Required plugins:** WooCommerce (latest). Recommended: a caching plugin at deploy.
No page builder, no ACF.

1. Copy `econur-theme/` → `wp-content/themes/` and activate.
2. Copy `econur-crm/` → `wp-content/plugins/` and activate (creates the reminders
   table + schedules the daily cron).
3. WooCommerce → Payments: only **Cash on Delivery** will appear (enforced in code).
4. WooCommerce → Shipping: create Inside Dhaka / Outside Dhaka zones + flat rates.
5. Import the sample `products.csv` (WooCommerce → Products → Import).
6. Set the homepage: Settings → Reading → "A static page" (front-page.php auto-applies).
7. **Feature carousel products:** WooCommerce → Products, click the **star** to
   feature products. Optionally add promo slides in Appearance → Customize →
   "Econur — Homepage & Brand" → "Carousel promo / offer slides".
8. **Lab Report page:** create a Page (slug `lab-report`), assign the **Lab Report**
   template, and fill its "Econur — Lab Report Fields" box (stats + download URLs).
9. **Menus:** Appearance → Menus → assign a Primary + Footer menu (the header falls
   back to Shop / All Soaps / Lab Report if none is set).
10. **Fonts:** add the six `.woff2` files (see `econur-theme/assets/fonts/README.md`).
11. **Catalog:** import the real catalog via WooCommerce → Products → Import (Woo-native
    fields), then bulk-fill the six product-story meta fields with
    `sample-data/product-story-import.csv` (see `sample-data/README.md`). The story
    fields are `Meta: _econur_*` columns — Woo auto-maps them to post meta, so they
    cannot ride along in a stock Woo product export/import unless you add the columns.
12. **Offer rules (optional):** Econur CRM → Offer Rules to configure the value ladder.
13. **Caching (recommended):** enable a page-cache plugin or host cache — the cart
    count and personalization are cache-safe (see §9).

---

## 9. Performance & mobile (spec §8)

**Implemented in the theme:**
- Mobile-first CSS throughout; per-template stylesheets (each page loads only its own).
- Self-hosted fonts, `font-display: swap`, above-the-fold weights preloaded.
- One deferred vanilla JS file per surface; no framework, no jQuery on custom pages;
  jQuery Migrate removed.
- LCP image (first carousel slide) is `fetchpriority=high`/eager; all other imagery is
  `loading=lazy` with automatic `srcset`/`sizes`.
- Gutenberg block CSS + global-styles dequeued on the custom pages.
- WooCommerce **cart-fragments AJAX dropped** off non-cart pages; the header cart count
  is server-rendered and refreshed cache-safely via a tiny endpoint, gated on the
  WooCommerce cart cookie (no request for empty carts).
- Critical-CSS shim inlined in `<head>` (reserves header height / paints bg → guards CLS).

**Deployment steps (host/plugin level, not code):**
- **WebP/AVIF:** WordPress serves modern formats when images are uploaded as such (WP
  5.8+ WebP, 6.5+ AVIF). For auto-conversion of existing JP/PNGs use a host feature or a
  lightweight image plugin. (recommended, not hardcoded)
- **Page cache:** enable at host or via a caching plugin. Dynamic bits are cache-safe.

**Lighthouse checklist (run on the client's install):**
- [ ] Homepage mobile ≥ 90; product page ≥ 90.
- [ ] LCP < 2.5s, INP < 200ms, CLS < 0.1 on a mid-range Android/4G profile.
- [ ] Add the woff2 fonts + product images first (missing assets skew LCP).

---

## 10. CRM quickstart (spec §7)

- Activate **Econur CRM** (creates `{$prefix}econur_reminders` + a daily cron).
- A test order → Processing/Completed generates **2 reminder rows per line item**
  (50gm → +15/+30 days, 100gm → +20/+50 days), in **Econur CRM → Reminders** with a
  one-tap WhatsApp link + Mark Sent / Dismiss.
- A customer saving a **special date** (My Account) creates an annual reminder.
- **Semi-automated by design:** WordPress can't send WhatsApp natively. The daily cron
  flags due reminders and fires `do_action('econur_reminder_due', $row)` — wire a
  WhatsApp Business Cloud API / Twilio / 360dialog integration to that hook to auto-send
  later, with no change to the core engine.
- **Reminder offsets** = the `econur_reminder_offsets` filter (one-line change if real
  sizes differ from 50/100gm).

---

## 11. Acceptance checklist (Master Prompt §11) — status

| # | Criterion | Status |
|---|---|---|
| 1 | Exactly 3 templates; no stray pages | ✅ front-page · single-product · page-lab-report (only Lab Report declares a Template Name; index.php is the required fallback) |
| 2 | COD the only checkout payment method | ✅ enforced via `woocommerce_available_payment_gateways` |
| 3 | Guest checkout, zero forced login | ✅ registration never required; guest checkout forced on |
| 4 | Homepage 6 sections in order; filter/search/expand without reload | ✅ carousel → grid (client-side filter+search, inline card buy box) → reviews → ingredients → CTA → footer |
| 5 | Test order → correct reminder rows (2, right offsets) + working wa.me link | ⏳ verify on WP — engine implemented per spec, idempotent |
| 6 | Special date → annual reminder | ⏳ verify on WP — implemented |
| 7 | Lighthouse mobile ≥ 90 (home + product) | ⏳ verify on WP (add fonts + images first) |
| 8 | All colours/fonts trace to CSS variables; no scattered hex in templates | ✅ verified (template hex hits are HTML entities, not colours) |
| 9 | Completely English UI | ✅ (product long-intro preserves optional Bangla *content* per §4.2.2) |
| 10 | E-commerce UI/UX best practices | ✅ rich cards, Order/Add hierarchy, sticky gallery, lean COD checkout |

Items marked ⏳ need a running WordPress+WooCommerce to execute (no PHP/WooCommerce in
the build environment). Everything is implemented and lint-clean — 52 theme+plugin PHP
files parse cleanly; all JS validates.

---

## 12. Post-build code review (UI glitches · UI practices · security)

Security review of all input/output surfaces (AJAX nonces + sanitisation, `$wpdb->prepare`
throughout the CRM, capability + nonce on admin actions, consistent output escaping,
`esc_url` on Customizer URLs) — **no vulnerabilities found**.

Fixes applied:
1. **Mobile menu ✕ was occluded** by the drawer (z-index) → header lifts above the drawer
   while open so the close control is tappable. _(components.css)_
2. **Carousel had no stop control for touch** (WCAG 2.2.2) → any interaction (arrow/dot/
   swipe) now halts auto-advance for good. _(carousel.js)_
3. **Admin Orders screen was O(orders²)** (per-row full re-scan) → precompute the phone→count
   index once. _(class-admin-menu.php)_
4. **`--text-light` failed WCAG AA** (~3.1:1) → darkened to `#478069` (~4.6:1). _(style.css)_
5. **Filter/search now announces results** to screen readers via an `aria-live` region. _(product-grid.php, homepage.js)_
6. **Carousel slides equalised** so shorter (offer) slides no longer leave whitespace. _(components.css)_
7. Added a branded **`screenshot.png`** (theme thumbnail).

Behaviour note (by design, not a bug): **"Order Now" is buy-now** — it adds to the existing
cart and goes straight to checkout.
