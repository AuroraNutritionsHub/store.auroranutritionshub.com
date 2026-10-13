<?php

const ALLOWED_DISCOUNTS = [0, 5, 10, 15];
const ALLOWED_PAYMENT_METHODS = ['CASH', 'CARD', 'UPI'];

// Onsite billing is not tied to any gym: it lists every active product with its base stock,
// and bills are saved with orders.gym_id = NULL. Older databases have that column NOT NULL,
// so relax it once (runs outside the bill transaction because ALTER TABLE auto-commits).
function ensure_onsite_orders_without_gym(): void
{
    static $done = false;
    if ($done) return;
    $col = q1(
        "SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'gym_id'"
    );
    if ($col && $col['IS_NULLABLE'] === 'NO') q('ALTER TABLE orders MODIFY gym_id int(10) UNSIGNED NULL');
    $done = true;
}

// GET /api/onsite/categories  - categories with at least one active product (onsite billing is not gym-restricted)
function getOnsiteCategories(Req $R): void
{
    $rows = q(
        "SELECT DISTINCT c.* FROM categories c
         JOIN products p ON p.category_id = c.id AND p.status = 'ACTIVE'
         ORDER BY c.name ASC"
    );
    success('Categories fetched', $rows);
}

// GET /api/onsite/products?category_id=  - all active products with onsite price and base stock
function getOnsiteProducts(Req $R): void
{
    $categoryId = $R->qp('category_id');

    $where = "WHERE p.status = 'ACTIVE'";
    $params = [];
    if (t($categoryId)) {
        $where .= ' AND p.category_id = ?';
        $params[] = $categoryId;
    }

    $products = q(
        "SELECT p.id, p.name, p.brand, p.image, c.id AS category_id, c.name AS category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         $where
         ORDER BY p.name ASC",
        $params
    );
    if (!$products) success('Products fetched', []);

    $variants = q(
        "SELECT pv.id, pv.product_id, pv.variant_name, pv.unit, pv.price, pv.onsite_price, pv.stock
         FROM product_variants pv
         WHERE pv.product_id IN (?) AND pv.status = 'ACTIVE'
         ORDER BY pv.price ASC",
        [array_column($products, 'id')]
    );

    $resolved = [];
    foreach ($variants as $v) {
        $resolved[] = [
            'id' => $v['id'],
            'product_id' => $v['product_id'],
            'variant_name' => $v['variant_name'],
            'unit' => $v['unit'],
            'price' => $v['onsite_price'] !== null ? num($v['onsite_price']) : num($v['price']),
            'stock' => num($v['stock']),
        ];
    }

    $result = [];
    foreach ($products as $p) {
        $p['variants'] = array_values(array_filter($resolved, fn($v) => $v['product_id'] === $p['id']));
        if ($p['variants']) $result[] = $p;
    }

    success('Products fetched', $result);
}

// POST /api/onsite/bill  - { customer_name, customer_phone, customer_address, items:[{variant_id,quantity}], discount_percent, payment_method }
function createOnsiteBill(Req $R): void
{
    $items = $R->b('items');
    $discountPercent = $R->has('discount_percent') ? $R->b('discount_percent') : 0;
    $paymentMethod = $R->b('payment_method');
    $customerName = trim((string) orv($R->b('customer_name'), ''));
    $customerPhone = trim((string) orv($R->b('customer_phone'), ''));
    $customerAddress = trim((string) orv($R->b('customer_address'), ''));

    if ($customerName === '') error_res('Customer name is required', 400);
    if (!is_list_array($items) || count($items) === 0) error_res('Bill must contain at least one item', 400);
    if (!in_array(num($discountPercent), ALLOWED_DISCOUNTS)) error_res('Invalid discount percentage', 400);
    if (!in_array($paymentMethod, ALLOWED_PAYMENT_METHODS, true)) error_res('Please select a valid payment method (Cash, Card or UPI)', 400);

    ensure_onsite_orders_without_gym();

    db_begin();
    try {
        $subtotal = 0;
        $lineItems = [];

        foreach ($items as $item) {
            $variantId = num(is_array($item) ? ($item['variant_id'] ?? null) : null);
            $quantity = num(is_array($item) ? ($item['quantity'] ?? null) : null);
            if (!t($variantId) || !t($quantity) || $quantity < 1) throw new HttpError('Invalid item in bill', 400);

            $rows = q(
                'SELECT pv.id AS variant_id, pv.variant_name, pv.status AS variant_status, pv.price, pv.onsite_price,
                        pv.stock, p.id AS product_id, p.name AS product_name, p.status AS product_status
                 FROM product_variants pv
                 JOIN products p ON p.id = pv.product_id
                 WHERE pv.id = ?
                 FOR UPDATE',
                [$variantId]
            );

            if (!$rows) throw new HttpError('Product not found', 400);
            $row = $rows[0];

            if ($row['product_status'] !== 'ACTIVE' || $row['variant_status'] !== 'ACTIVE') {
                throw new HttpError("{$row['product_name']} is currently unavailable", 400);
            }
            if (num($row['stock']) < $quantity) {
                throw new HttpError("Insufficient stock for {$row['product_name']} ({$row['variant_name']})", 400);
            }

            $unitPrice = $row['onsite_price'] !== null ? num($row['onsite_price']) : num($row['price']);
            $totalPrice = r2($unitPrice * $quantity);
            $subtotal += $totalPrice;

            $lineItems[] = [
                'product_id' => $row['product_id'],
                'variant_id' => $row['variant_id'],
                'product_name' => $row['product_name'],
                'variant_name' => $row['variant_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];

            q('UPDATE product_variants SET stock = stock - ? WHERE id = ?', [$quantity, $variantId]);
        }

        $subtotal = r2($subtotal);
        $discountAmount = r2($subtotal * (num($discountPercent) / 100));
        $totalAmount = r2($subtotal - $discountAmount);
        $orderNumber = generateOrderNumber();

        $orderId = q(
            "INSERT INTO orders (order_number, gym_id, user_id, customer_name, customer_phone, source, subtotal, discount_percent, discount_amount, delivery_charge, total_amount, payment_status, order_status, delivery_address)
             VALUES (?, NULL, NULL, ?, ?, 'ONSITE', ?, ?, ?, 0, ?, 'VERIFIED', 'DELIVERED', ?)",
            [$orderNumber, $customerName, $customerPhone !== '' ? $customerPhone : null, $subtotal, $discountPercent, $discountAmount, $totalAmount, $customerAddress !== '' ? $customerAddress : 'Onsite / Walk-in Purchase']
        )['insertId'];

        foreach ($lineItems as $item) {
            q(
                'INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_name, quantity, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$orderId, $item['product_id'], $item['variant_id'], $item['product_name'], $item['variant_name'], $item['quantity'], $item['unit_price'], $item['total_price']]
            );
        }

        q(
            "INSERT INTO payments (order_id, amount, payment_method, payment_status, verified_by, verified_at)
             VALUES (?, ?, ?, 'VERIFIED', ?, NOW())",
            [$orderId, $totalAmount, $paymentMethod, $R->admin['id']]
        );

        db_commit();
    } catch (Throwable $e) {
        db_rollback();
        throw $e;
    }

    success('Bill generated successfully', ['id' => $orderId, 'order_number' => $orderNumber, 'total_amount' => $totalAmount], 201);
}

// GET /api/onsite/recent-bills?limit=  - bills the logged-in staff member personally created, most recent first
function getRecentBills(Req $R): void
{
    $limit = num($R->qp('limit', null));
    $limit = min(t($limit) ? $limit : 10, 50);
    $rows = q(
        "SELECT o.id, o.order_number, o.customer_name, o.customer_phone, o.total_amount, o.created_at
         FROM orders o
         JOIN payments p ON p.order_id = o.id
         WHERE o.source = 'ONSITE' AND p.verified_by = ?
         ORDER BY o.created_at DESC
         LIMIT ?",
        [$R->admin['id'], $limit]
    );
    success('Recent bills fetched', $rows);
}

// GET /api/onsite/customers?search=  - past walk-in customers matching the name, for autofill
function searchCustomers(Req $R): void
{
    $search = trim((string) orv($R->qp('search', null), ''));
    if (mb_strlen($search) < 2) success('Customers fetched', []);

    $rows = q(
        "SELECT o.customer_name, o.customer_phone, o.delivery_address
         FROM orders o
         INNER JOIN (
           SELECT customer_name, MAX(created_at) AS max_created
           FROM orders
           WHERE source = 'ONSITE' AND customer_name LIKE ?
           GROUP BY customer_name
         ) latest ON latest.customer_name = o.customer_name AND latest.max_created = o.created_at
         ORDER BY o.created_at DESC
         LIMIT 8",
        [like($search)]
    );

    $customers = array_map(fn($r) => [
        'customer_name' => $r['customer_name'],
        'customer_phone' => t($r['customer_phone']) ? $r['customer_phone'] : '',
        // The placeholder text stored when no address was given at billing time isn't a real address
        'customer_address' => $r['delivery_address'] === 'Onsite / Walk-in Purchase' ? '' : (t($r['delivery_address']) ? $r['delivery_address'] : ''),
    ], $rows);

    success('Customers fetched', $customers);
}
