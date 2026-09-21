-- Bonus: identify duplicate SKUs
-- Parent catalog SKUs
SELECT
  sku,
  COUNT(*) AS occurrence_count,
  GROUP_CONCAT(id ORDER BY id) AS product_ids
FROM products
WHERE sku IS NOT NULL
  AND sku <> ''
GROUP BY sku
HAVING COUNT(*) > 1;

-- Variant SKUs (the ones that must be globally unique at checkout)
SELECT
  sku,
  COUNT(*) AS occurrence_count,
  GROUP_CONCAT(id ORDER BY id) AS variant_ids,
  GROUP_CONCAT(product_id ORDER BY id) AS product_ids
FROM product_variants
WHERE sku IS NOT NULL
  AND sku <> ''
GROUP BY sku
HAVING COUNT(*) > 1;

-- Cross-table collisions (parent SKU equal to a variant SKU)
SELECT
  p.sku,
  p.id AS product_id,
  v.id AS variant_id,
  v.product_id AS variant_product_id
FROM products p
INNER JOIN product_variants v ON v.sku = p.sku
WHERE p.sku IS NOT NULL
  AND p.sku <> '';
