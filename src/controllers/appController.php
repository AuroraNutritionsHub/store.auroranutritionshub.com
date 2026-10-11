<?php
// Mobile app endpoints (/api/app/*) - scoped to the logged-in user's gym

// GET /api/app/categories - every category with at least one active product enabled for
// the user's gym, INCLUDING inactive categories (the app shows them dimmed/"coming soon").
function getMyCategories(Req $R): void
{
    $rows = q(
        "SELECT DISTINCT c.* FROM categories c
         JOIN products p ON p.category_id = c.id AND p.status = 'ACTIVE'
         JOIN gym_products gp ON gp.product_id = p.id AND gp.gym_id = ? AND gp.status = 'ACTIVE'
         ORDER BY c.name ASC",
        [$R->user['gym_id']]
    );
    success('Categories fetched', $rows);
}

function resolveVariant(array $v): array
{
    return [
        'id' => $v['id'],
        'product_id' => $v['product_id'],
        'variant_name' => $v['variant_name'],
        'unit' => $v['unit'],
        'mrp' => $v['mrp'] !== null ? num($v['mrp']) : null,
        'price' => $v['override_price'] !== null ? num($v['override_price']) : num($v['base_price']),
        'stock' => $v['override_stock'] !== null ? num($v['override_stock']) : num($v['base_stock']),
    ];
}

function active_for_gym(array $v): bool
{
    return !t($v['gym_variant_status']) || $v['gym_variant_status'] === 'ACTIVE';
}

// GET /api/app/products?category_id=&search=  - products enabled for the user's gym, with resolved price/stock
function getMyProducts(Req $R): void
{
    $gymId = $R->user['gym_id'];
    $categoryId = $R->qp('category_id');
    $search = $R->qp('search');

    $where = "WHERE gp.gym_id = ? AND gp.status = 'ACTIVE' AND p.status = 'ACTIVE'";
    $params = [$gymId];
    if (t($categoryId)) {
        $where .= ' AND p.category_id = ?';
        $params[] = $categoryId;
    }
    if (t($search)) {
        $where .= ' AND (p.name LIKE ? OR p.brand LIKE ?)';
        array_push($params, like($search), like($search));
    }

    $products = q(
        "SELECT p.id, p.name, p.brand, p.description, p.image, c.id AS category_id, c.name AS category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         JOIN gym_products gp ON gp.product_id = p.id
         $where
         ORDER BY p.name ASC",
        $params
    );

    if (!$products) success('Products fetched', []);

    $variants = q(
        "SELECT pv.id, pv.product_id, pv.variant_name, pv.unit, pv.mrp, pv.stock AS base_stock, pv.price AS base_price, pv.status,
                gpv.price AS override_price, gpv.stock AS override_stock, gpv.status AS gym_variant_status
         FROM product_variants pv
         LEFT JOIN gym_product_variants gpv ON gpv.variant_id = pv.id AND gpv.gym_id = ?
         WHERE pv.product_id IN (?) AND pv.status = 'ACTIVE'
         ORDER BY pv.price ASC",
        [$gymId, array_column($products, 'id')]
    );

    $resolved = array_map('resolveVariant', array_values(array_filter($variants, 'active_for_gym')));

    $result = [];
    foreach ($products as $p) {
        $mine = array_values(array_filter($resolved, fn($v) => $v['product_id'] === $p['id']));
        if (!$mine) continue;
        $p['variants'] = $mine;
        $p['starting_price'] = min(array_column($mine, 'price'));
        $result[] = $p;
    }

    success('Products fetched', $result);
}

// GET /api/app/products/:id
function getMyProductById(Req $R): void
{
    $gymId = $R->user['gym_id'];
    $products = q(
        "SELECT p.id, p.name, p.brand, p.description, p.image, c.id AS category_id, c.name AS category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         JOIN gym_products gp ON gp.product_id = p.id AND gp.gym_id = ? AND gp.status = 'ACTIVE'
         WHERE p.id = ? AND p.status = 'ACTIVE'",
        [$gymId, $R->p('id')]
    );
    if (!$products) error_res('Product not found or unavailable for your gym', 404);

    $variants = q(
        "SELECT pv.id, pv.product_id, pv.variant_name, pv.unit, pv.mrp, pv.stock AS base_stock, pv.price AS base_price,
                gpv.price AS override_price, gpv.stock AS override_stock, gpv.status AS gym_variant_status
         FROM product_variants pv
         LEFT JOIN gym_product_variants gpv ON gpv.variant_id = pv.id AND gpv.gym_id = ?
         WHERE pv.product_id = ? AND pv.status = 'ACTIVE'
         ORDER BY pv.price ASC",
        [$gymId, $R->p('id')]
    );

    $product = $products[0];
    $product['variants'] = array_map('resolveVariant', array_values(array_filter($variants, 'active_for_gym')));
    $product['gallery'] = q('SELECT id, image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC', [$R->p('id')]);

    success('Product fetched', $product);
}

// GET /api/app/payment-settings
function getMyPaymentSettings(Req $R): void
{
    $rows = q('SELECT * FROM payment_settings WHERE gym_id = ?', [$R->user['gym_id']]);
    if (!$rows) success('No payment settings configured', null);
    success('Payment settings fetched', $rows[0]);
}
