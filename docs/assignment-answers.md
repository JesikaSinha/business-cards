# Ecommerce Developer (Web-to-Print) — Written Responses

Product: Custom Business Cards  
Platform framing: Shopify (configurable product + native variants)

---

## Part 1: Product Configuration

### Product structure

I would model this as **one parent product** — `Custom Business Cards` — not three separate products for 100 / 500 / 1000 packs.

Customers configure a single product (size, paper, quantity, artwork). Quantity is a purchase option on that product, not a catalog item they have to hunt for. The import file in Part 5 (`BC100`, `BC500`, `BC1000`) is a flattened SKU export; it should be **mapped into this one product**, not imported as three unrelated listings.

| Layer | What it is |
|---|---|
| Product | Custom Business Cards (type: Stationery / Business Cards) |
| Options | Size, Paper Type, Quantity |
| Variants | Size × Paper Type × Quantity = **12 sellable combinations** |
| Custom property | Artwork file (not a variant — it is unique per order) |
| Pricing source | Matrix of Quantity × Paper Type (size does not change price) |

Inventory would be **not tracked** (made-to-order / print-on-demand). Fulfillment would treat each order line as a print job with a linked artwork file.

### Product attributes

Shopify product fields plus metafields:

- **Title / handle:** Custom Business Cards (`custom-business-cards`)
- **Product type:** Business Cards
- **Vendor:** in-house print
- **Tags:** `business-card`, `web-to-print`, `custom`
- **Metafields (product):**
  - `custom.artwork_guidelines` — bleed, DPI, color mode (CMYK), safe zone
  - `custom.pricing_matrix` — JSON copy of the Quantity × Paper price table (source of truth for the storefront calculator and for audits)
  - `custom.turnaround_days` — production SLA
  - `custom.allowed_artwork_types` — `pdf,png,jpg,ai`

Variant metafields (optional): `custom.die_size` (`3.5x2` / `2.5x2.5`) so prepress knows the trim without parsing the option title.

### Product options

| Option | Values | Role |
|---|---|---|
| Size | Standard (3.5" × 2"), Square (2.5" × 2.5") | Production / die. **Does not affect price.** |
| Paper Type | Matte, Glossy | Production + **price** |
| Quantity | 100, 500, 1000 | Pack size + **price** |

Artwork is **not** an option. It is a line-item property / file attachment. Making artwork a variant would explode the catalog and is impossible (every file is unique).

### Variant strategy

**Chosen approach: 12 native Shopify variants.**

```
SKU pattern: BC-{SIZE}-{PAPER}-{QTY}

BC-STD-MAT-100   Standard / Matte  / 100   $20
BC-STD-MAT-500   Standard / Matte  / 500   $80
BC-STD-MAT-1000  Standard / Matte  / 1000  $140
BC-STD-GLO-100   Standard / Glossy / 100   $25
BC-STD-GLO-500   Standard / Glossy / 500   $100
BC-STD-GLO-1000  Standard / Glossy / 1000  $180
BC-SQR-MAT-100   Square   / Matte  / 100   $20
… (same prices for Square)
```

Why variants (not a fully custom app) for this matrix:

- Only 12 combinations — well under Shopify’s 100-variant limit.
- Checkout, inventory (if later enabled), and reporting all work without a custom pricing app.
- Size must still be a variant even though price is identical: prepress, packing slips, and reprints need a structured size, not a free-text note.

**Rejected alternative:** 4 variants (Size × Paper) with quantity as a custom property. That would need Shopify Functions or an app to rewrite price at checkout. Unnecessary complexity for three fixed pack sizes.

**Rejected alternative:** three products (`BC100`, `BC500`, `BC1000`) as in the import file. That splits one configurator into three PDPs, duplicates size/paper options, and makes artwork/cart UX worse.

### Pricing logic

Price is a function of **quantity + paper type only**.

```
price = PRICE_TABLE[quantity][paperType]
```

| Quantity | Matte | Glossy |
|---|---|---|
| 100 | $20 | $25 |
| 500 | $80 | $100 |
| 1000 | $140 | $180 |

Implementation:

1. Each of the 12 variants stores the resolved price (Shopify requires a price on the variant).
2. Storefront JS reads the selected quantity + paper (or the selected variant) and updates the displayed price immediately — no page reload.
3. Size changes variant ID but **not** the displayed price.
4. Add to Cart posts the matching `variant_id` plus artwork as a line-item property.
5. Checkout uses the variant price, not the JS display, so customers cannot tamper with price in the browser.

If the catalog later adds coatings, rush fees, or per-side printing, I would move the matrix into a Shopify Function / pricing app and keep variants for production attributes only.

### Assumptions

- Currency is USD; no tax, shipping, or volume discounts beyond the three pack sizes.
- Size does not change price (as specified).
- One artwork file per line item; we do not print until a file is attached.
- Made-to-order: inventory policy is continue / don’t track.
- Allowed artwork: PDF, PNG, JPG, AI; file is stored against the order, not against the product.
- No proofing / design-online editor in this scope — upload only.
- The Part 5 CSV is a **Matte-only flattened export** of quantity SKUs; Glossy and Size are not in that file and must not be inferred as “missing products.”

---

## Part 3: Database Design

Full DDL, keys, and seed data: `database/schema.sql`.

### Tables

| Table | Purpose | Primary key |
|---|---|---|
| `products` | Parent catalog item (Custom Business Cards) | `id` |
| `product_options` | Option names on a product (Size, Paper Type, Quantity) | `id` |
| `product_option_values` | Allowed values for each option | `id` |
| `product_variants` | Sellable combo: Size × Paper × Quantity | `id` |
| `pricing` | Canonical Quantity × Paper price matrix | `id` |
| `orders` | Checkout header | `id` |
| `order_items` | Snapshot of the configured line | `id` |
| `artwork_files` | Uploaded print files | `id` |

### Foreign keys / relationships

```
products 1 ──< product_options 1 ──< product_option_values
products 1 ──< product_variants
products 1 ──< pricing
products 1 ──< order_items

orders 1 ──< order_items 1 ──○ artwork_files
                 └── product_variants (optional FK; snapshot columns remain if the variant is later archived)
```

- `product_options.product_id` → `products.id`
- `product_option_values.option_id` → `product_options.id`
- `product_variants.product_id` → `products.id`
- `pricing.product_id` → `products.id`
- `order_items.order_id` → `orders.id`
- `order_items.product_id` → `products.id`
- `order_items.variant_id` → `product_variants.id` (nullable; `ON DELETE SET NULL`)
- `artwork_files.order_item_id` → `order_items.id` (nullable until checkout completes)

Artwork is attached to the **order line**, not the product. A file can be stored at upload time (`order_item_id` null) and linked when the order is created, so a failed checkout does not lose the file.

Variant price is copied from `pricing` for the `(quantity, paper_type)` pair. Size is stored on the variant and the order line but is not a column on `pricing`.

---

## Part 4: Troubleshooting

### Scenario 1 — Pricing displayed on the website is incorrect

**Possible causes**

1. Storefront JS price table is out of date vs. variant prices in Shopify (or vs. the `pricing` table in a custom stack).
2. Wrong variant selected: size change remaps to a variant whose price was entered incorrectly (e.g. Square/Glossy/500 still at $80).
3. Cached theme / CDN serving an old `product.js` or Liquid snippet.
4. Theme compares option titles case-sensitively (`Glossy` vs `glossy`) and falls through to a default.
5. Currency / money filter mismatch (cents vs dollars, or a market price list overriding USD).
6. A discount, automatic app, or Shopify Function altering price after the PDP calculator runs.
7. Stale browser cache or a service worker on the storefront.

**Investigation steps**

1. Reproduce with the customer’s exact size, paper, quantity. Screenshot PDP vs. cart vs. checkout.
2. Confirm expected value from the pricing matrix (source of truth).
3. In Shopify Admin, open the matching variant and check **price, compare-at, and market prices**.
4. In the theme, inspect the JS price map and the variant JSON (`product.variants`) in the page source.
5. Add to cart and inspect the cart payload: `variant_id`, `price`, properties. If PDP ≠ cart, the calculator is wrong; if cart ≠ checkout, an app/function is mutating price.
6. Disable discounts/apps in a duplicate theme preview; hard-refresh / try incognito.
7. Check recent product imports, bulk price edits, and theme deploys against the time the issue started.

**Resolution approach**

- Treat **variant price in Admin** (or the `pricing` table) as canonical. Fix bad variant rows first.
- Re-sync the storefront matrix from that source (or stop duplicating it and read `variant.price` directly).
- Add a smoke test: all 12 combinations, PDP price === cart price === checkout price.
- After import jobs, run a checksum: every `(quantity, paper)` pair matches the matrix for both sizes.

### Scenario 2 — Artwork uploads succeed, but orders fail at checkout

**Possible causes**

1. File is stored, but checkout cannot attach it: line-item property over Shopify’s size/count limits, or the file URL is a temporary blob that expires before checkout.
2. Required cart attributes / validation (terms, shipping, file-required) failing only on the checkout step.
3. Payment gateway / 3-D Secure / address validation failing — looks like “order failed” to the customer even though upload worked.
4. Custom checkout script or app throwing when a file property is present (null URL, disallowed MIME, unsigned storage URL).
5. Webhook / order-create consumer rejecting the payload (prepress API timeout, missing `order_item` → artwork FK).
6. Inventory or variant no longer available by the time they reach checkout.
7. Session / cart token expired (long time spent on artwork upload).
8. CSRF / cookie / SameSite issue: upload used a different origin than checkout.

**Investigation steps**

1. Get order/cart token, timestamp, browser, and whether payment was attempted.
2. Confirm the file exists in storage (S3 / Shopify Files) and is readable with a durable URL.
3. Reproduce: upload a small JPG vs. a large PDF; try checkout with and without a file.
4. Browser network tab: which request fails (cart.js, checkout, payments, webhook)? Status code and body.
5. Checkout logs / gateway logs / app logs around `orders/create`.
6. Check file size, MIME, and whether the line-item property is still on the cart at checkout.
7. If a custom DB: confirm `artwork_files.order_item_id` is written in the same transaction as the order, not before the order exists.

**Resolution approach**

- Persist artwork to durable storage **on upload**, store a stable file id on the cart, and create the `artwork_files` row when the order is confirmed.
- Enforce file constraints (type, size, virus scan) **before** checkout, with a clear error — not during payment.
- Make checkout resilient: if the file property is missing, block with “Please re-attach artwork” instead of a generic failure.
- Retry/queue any prepress webhook so a downstream timeout cannot roll back a paid order.
- Add monitoring on checkout error rate for carts that have an artwork property.

### Scenario 3 — Site is much slower after importing 10,000 products

**Possible causes**

1. Collection / search pages loading all products without pagination.
2. Missing DB indexes on `sku`, `handle`, `product_id`, `status`.
3. N+1 queries: each product loads all options, variants, and images in a loop.
4. Theme or app rendering 10k items into one JSON blob (`all_products` in Liquid).
5. Autocomplete / mega-menu querying the full catalog on every request.
6. Unoptimized images; no CDN resizing.
7. Search reindex still running; table locks; Autocomplete tables without indexes.
8. Shopify-specific: large collections, unfiltered `catalog.json`, or an app syncing the whole catalog into the storefront on page load.

**Investigation steps**

1. Measure TTFB vs. download vs. JS parse on homepage, collection, and PDP.
2. Slow-query log / New Relic / Shopify Theme Inspector: which view and which query.
3. Check whether the import created 10k products × many variants (row explosion).
4. Confirm pagination, `LIMIT`, and that storefront APIs are not dumping the catalog.
5. Review indexes and EXPLAIN on product listing queries.
6. Compare performance with the new products unpublished (rules out storefront JS pulling draft catalog).

**Performance improvements**

- Paginate all listings (24–48 per page). Never render the full catalog in Liquid/JS.
- Index `products(status, created_at)`, `products(sku)`, `product_variants(product_id, sku)`, `pricing(product_id, quantity, paper_type)`.
- Eager-load variants/images in one query; cache collection JSON.
- Use a CDN and responsive images; lazy-load below the fold.
- Keep search on a dedicated index (Storefront API, Algolia, etc.), not `LIKE '%term%'` on 10k rows.
- Import in batches; publish after validation, not during the load.
- On Shopify: split collections, avoid `collections.all.products`, use Storefront API with `first:` pagination.

---

## Part 5: Catalog Management

Given file:

| SKU | Name | Price |
|---|---|---|
| BC100 | Business Card 100 | $20 |
| BC500 | Business Card 500 | $80 |
| BC1000 | Business Card 1000 | $140 |

This file is a **quantity-level Matte price list**, not a full product catalog. It has no size, no glossy column, and names are ambiguous.

### How I would import

1. **Do not import these as three separate storefront products** unless the business explicitly wants three PDPs.
2. Map each row onto the **Custom Business Cards** product:
   - `BC100` → quantity 100, paper Matte, price $20 — apply to **both** Standard and Square variants.
   - `BC500` → quantity 500, Matte, $80
   - `BC1000` → quantity 1000, Matte, $140
3. Keep Glossy prices from the official matrix ($25 / $100 / $180); this file does not define them.
4. Load via Shopify Admin CSV / Matrixify / a custom importer that:
   - upserts by SKU
   - writes variant price
   - does not create a new product when the parent already exists
5. Run the import in a **draft / unpublished** state, then publish after checks.

If the merchant insisted on one-row-one-product (matching the file literally), I would still attach Size and Paper as options on each, and I would flag that Glossy is missing.

### Validate before import

- Required columns present: `SKU`, `Name`, `Price`.
- SKU format / uniqueness in the file itself (`GROUP BY sku HAVING COUNT(*) > 1`).
- Price is numeric, `> 0`, no currency symbols left in the numeric column (`$20` parsed as 20.00).
- Quantity parsed from SKU or name (`100`, `500`, `1000` only — reject `250` unless the matrix is extended).
- Name does not contradict SKU (e.g. SKU `BC100` named “Business Card 500”).
- Encoding (UTF-8), no blank rows, no Excel-stripped leading zeros.
- Row count matches the expected 3 rows for this file; extra rows are a stop-the-line event.
- Diff against current catalog: new vs. update vs. unchanged.

### Checks before publishing live

- All 12 variants exist; Matte prices match this file; Glossy prices match the matrix.
- PDP calculator === variant price === test cart for a sample of combinations (at least 100 Matte, 1000 Glossy, Square vs Standard).
- Images, description, artwork-upload field, and guidelines are present.
- SKUs unique across the whole catalog (not only this file).
- Test order in a development store / unpublished product: upload file, checkout, confirm order metafields / artwork record.
- 404s / redirects if old SKUs (`BC100` as a product handle) were replaced by the single configurator.
- No draft duplicates left from a previous import.

### Duplicate SKUs

1. **In the import file:** reject or merge before load. Two rows with `BC100` and different prices is an error — do not last-write-wins silently.
2. **Against the live catalog:** lookup by SKU.
   - Same SKU, same product/variant: **update** price/title if the payload is valid.
   - Same SKU, different product: **block** and report. SKUs must be globally unique.
3. Never auto-suffix SKUs (`BC100-1`) without a merchandiser decision — that creates ghost catalog entries.
4. After import, run the duplicate-SKU query (see `sql/duplicate_skus.sql`) and fail the publish checklist if any rows return.

---

## Bonus

- SQL: `sql/duplicate_skus.sql`
- PHP: `php/calculatePrice.php`
