# Ecommerce Developer (Web-to-Print) — Assignment Answers

**GitHub (source code for the client):** https://github.com/JesikaSinha/business-cards

Product page (local XAMPP): `http://localhost/shopify/index.php`

This repo is the submission: PHP product page, schema, bonus SQL/PHP, and the written parts below.

---

## Scenario (from the brief)

Custom business cards. Customer picks size, paper, quantity, uploads artwork, and sees price update.

| Size | Dimensions |
|---|---|
| Standard | 3.5" × 2" |
| Square | 2.5" × 2.5" |

Paper: Matte, Glossy

| Quantity | Matte | Glossy |
|---|---|---|
| 100 | $20 | $25 |
| 500 | $80 | $100 |
| 1000 | $140 | $180 |

Size does **not** change the price. Paper and quantity do.

---

## Part 1: Product Configuration

**Question:** Explain how you would configure this product within an e-commerce platform. Include product structure, attributes, options, variant strategy, pricing logic, and assumptions.

### Product structure

One parent product: **Custom Business Cards**.

Not three products (`BC100` / `BC500` / `BC1000`). Quantity is an option on the same product so the customer configures one page.

| Layer | What it is |
|---|---|
| Product | Custom Business Cards |
| Options | Size, Paper Type, Quantity |
| Variants | Size × Paper × Quantity = 12 combinations |
| Artwork | Line-item file, not a variant |
| Price | Quantity × Paper only |

Made to order (do not track inventory).

### Product attributes

- Title: Custom Business Cards
- Type: Business Cards
- Metafields: artwork guidelines, price matrix JSON, allowed file types (`pdf, png, jpg, ai`)

### Product options

| Option | Values | Affects price? |
|---|---|---|
| Size | Standard, Square | No |
| Paper Type | Matte, Glossy | Yes |
| Quantity | 100, 500, 1000 | Yes |

### Variant strategy

12 native variants. SKU pattern: `BC-{SIZE}-{PAPER}-{QTY}`

Example: `BC-STD-MAT-100` = $20, `BC-STD-GLO-100` = $25. Square uses the same prices as Standard.

Artwork cannot be a variant (every file is unique).

### Pricing logic

```
price = table[quantity][paper]
```

Checkout uses the server/variant price, not a number typed in the browser.

### Assumptions

- USD
- No tax/shipping in this demo
- One artwork file (or a sample layout) per line
- Part 5 CSV is Matte-only pack prices, not the full Glossy catalog

---

## Part 2: Frontend Development

**Question:** Create a responsive product page (HTML, CSS, JS) with image, size, paper, quantity, artwork upload, dynamic price, Add to Cart. Price must update when quantity or paper changes.

**Answer (in this repo):**

| Requirement | Where |
|---|---|
| Product / sample cards | `index.php` |
| Size, paper, quantity | `index.php` form |
| Artwork upload | `index.php` + `add-to-cart.php` |
| Dynamic price | `price.php` + `assets/js/app.js` (`calculatePrice` in PHP) |
| Add to Cart | `add-to-cart.php` → `cart.php` |
| Layout | `assets/css/styles.css` |

Demo URL after clone/XAMPP: `http://localhost/shopify/index.php`

---

## Part 3: Database Design

**Question:** Schema for Products, Product Options, Pricing, Orders, Uploaded Artwork. Include PKs, FKs, relationships.

**Full SQL:** `database/schema.sql`

| Table | PK | Notes |
|---|---|---|
| `products` | `id` | Parent product |
| `product_options` | `id` | Size, Paper Type, Quantity |
| `product_option_values` | `id` | e.g. Matte, 100 |
| `product_variants` | `id` | Size × Paper × Qty, unique `sku` |
| `pricing` | `id` | Qty + paper → price |
| `orders` | `id` | Order header |
| `order_items` | `id` | Config snapshot |
| `artwork_files` | `id` | File per order line |

**Foreign keys**

- `product_options.product_id` → `products.id`
- `product_option_values.option_id` → `product_options.id`
- `product_variants.product_id` → `products.id`
- `pricing.product_id` → `products.id`
- `order_items.order_id` → `orders.id`
- `order_items.product_id` → `products.id`
- `order_items.variant_id` → `product_variants.id` (nullable)
- `artwork_files.order_item_id` → `order_items.id` (nullable until checkout)

```
products 1──< product_options 1──< product_option_values
products 1──< product_variants
products 1──< pricing
orders 1──< order_items 1──○ artwork_files
```

---

## Part 4: Troubleshooting

### Scenario 1 — Pricing on the website is incorrect

**Possible causes:** storefront calculator out of date vs DB/variant price; wrong variant; cache; case mismatch (`Glossy` vs `glossy`); discount/app changing checkout price.

**Investigation:** reproduce size/paper/qty; compare PDP vs cart vs checkout; check `pricing` / variant price; inspect request payload.

**Fix:** treat server `calculatePrice` / variant price as source of truth; re-sync display; test all 12 combos (Matte $20 vs Glossy $25 at qty 100, etc.).

### Scenario 2 — Artwork uploads, checkout fails

**Possible causes:** temp file URL expired; cart property too large; payment/address error shown as “order failed”; webhook/prepress timeout; session expired.

**Investigation:** confirm file still on disk/storage; network tab at checkout; logs for `orders/create`; retry with a small JPG vs a large PDF.

**Fix:** save file on upload, attach file id at order create; validate type/size before payment; queue prepress so a timeout does not kill a paid order.

### Scenario 3 — Site slow after importing 10,000 products

**Possible causes:** no pagination; missing indexes; N+1 queries; dumping all products into one page/JSON.

**Investigation:** TTFB vs JS; slow query log; EXPLAIN listing queries.

**Improvements:** paginate; index `sku`, `product_id`, `status`; cache collections; CDN images; batch import then publish.

---

## Part 5: Catalog Management

**Import file**

| SKU | Name | Price |
|---|---|---|
| BC100 | Business Card 100 | $20 |
| BC500 | Business Card 500 | $80 |
| BC1000 | Business Card 1000 | $140 |

### How I would import

Map onto the one Custom Business Cards product (Matte prices for 100/500/1000, both sizes). Keep Glossy from the official table ($25 / $100 / $180). Import as draft, then publish.

### Validate before import

Columns present; unique SKUs in the file; price numeric; qty only 100/500/1000; name matches SKU.

### Checks before going live

12 variants exist; Matte matches this file; Glossy matches the matrix; test add-to-cart; no duplicate SKUs.

### Duplicate SKUs

Reject duplicates in the file. Against live catalog: same SKU + same variant → update; same SKU + different product → block. Do not auto-rename to `BC100-1`.

---

## Bonus

### SQL — duplicate SKUs

See `sql/duplicate_skus.sql`. Short version:

```sql
SELECT sku, COUNT(*) AS occurrence_count
FROM product_variants
WHERE sku IS NOT NULL AND sku <> ''
GROUP BY sku
HAVING COUNT(*) > 1;
```

### PHP — `calculatePrice($quantity, $paperType)`

See `includes/functions.php` and `php/calculatePrice.php`.

```php
function calculatePrice($quantity, $paperType) {
    $prices = array(
        100 => array('matte' => 20, 'glossy' => 25),
        500 => array('matte' => 80, 'glossy' => 100),
        1000 => array('matte' => 140, 'glossy' => 180)
    );
    $quantity = (int)$quantity;
    $paperType = strtolower(trim($paperType));
    if (isset($prices[$quantity][$paperType])) {
        return $prices[$quantity][$paperType];
    }
    return false;
}
```

---

## Submission files

| Path | Part |
|---|---|
| https://github.com/JesikaSinha/business-cards | Full source |
| `index.php` | Product page |
| `database/schema.sql` | Part 3 |
| `sql/duplicate_skus.sql` | Bonus SQL |
| `php/calculatePrice.php` | Bonus PHP |
| `ASSIGNMENT.md` | This document |
