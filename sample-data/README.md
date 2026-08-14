# Bulk-filling the product story fields via CSV

The WooCommerce importer handles the Woo-native fields (name, price, images,
categories). It does **not** know about the six "Econur — Product Story" fields
that power sections 3–7 of the single-product template — those live in post meta
(`_econur_*`). This folder ships a CSV that fills them in bulk.

Use this when products are already imported and their pages look incomplete.

---

## The columns

Headers must be spelled exactly as below. WooCommerce auto-maps any
`Meta: <key>` header to that meta key, so no manual field-mapping is needed on
the import screen.

| CSV header | Template section | Format |
|---|---|---|
| `SKU` | — (match key) | Must match the product already in the store |
| `Meta: _econur_positioning` | Hero sub-line | One short line, plain text |
| `Meta: _econur_benefits` | 3 — Benefit chips | **One benefit per line** |
| `Meta: _econur_efficacy` | 4 — Efficacy deep-dive | One per line as `Heading :: 1–2 sentence explanation` |
| `Meta: _econur_ingredients` | 5 — What's inside | **One ingredient per line** |
| `Meta: _econur_best_for` | 6 — Best for | **One tag per line** |
| `Meta: _econur_usage` | 7 — Usage & storage | Free text; blank lines preserved |

Section 2 (the long-form intro) comes from Woo's own **Description** field, not
from meta. To bulk-fill it too, add a plain `Description` column.

Sections 1, 8 and 9 need nothing here — they come from Woo core, the Customizer
and product categories respectively.

### Multi-line cells

Several fields are newline-delimited. In Google Sheets or Excel press
**Alt+Enter** (Mac: **Ctrl+Option+Enter**) for a line break inside one cell, then
export as CSV — the quoting is handled for you. Do not use commas as separators
for these; the parser splits on newlines only.

The importer sanitizes meta with `wp_kses_post`, which preserves line breaks and
allows basic inline HTML. The `::` delimiter is parsed by
`econur_pairs()` in `inc/content-helpers.php`.

---

## Steps

1. **Export what you have** — Products → All Products → **Export**. Include the
   SKU column. This gives you the exact SKU list to work against.
2. **Build the sheet** — open `product-story-import.csv`, keep the header row,
   and write one row per product keyed by SKU. Delete the placeholder example
   row (`ECON-ADB`).
3. **Import** — Products → **Import** → upload the file → on the mapping screen
   tick **"Update existing products"**. This is essential: without it Woo creates
   duplicates instead of updating.
4. **Verify** the mapping screen shows each `Meta: _econur_*` column mapped to
   *Import as meta data* (it should auto-detect), then run the import.
5. Open any product page and confirm sections 3–7 render.

---

## Two things that will bite you

- **Only include columns you intend to change.** A column present but left blank
  overwrites that field with an empty value. If a product's usage text is already
  correct, either fill the cell with the existing text or drop the whole column.
- **"Update existing products" must be ticked.** Unticked, an import keyed by an
  existing SKU is skipped or duplicated rather than merged.

Empty fields are not an error at render time — each section hides itself when its
field is blank, so a partial fill degrades cleanly.

---

## Note on the example row

The `ECON-ADB` row is **PLACEHOLDER** copy written to demonstrate the format. The
ingredient and efficacy claims are illustrative, not verified brand or product
facts. Replace it before importing — do not ship it as real content.
