<?php
// Order pricing, order numbers and notifications (ports of the Node services/ and utils/).

function delivery_charge()
{
    return num(env('DELIVERY_CHARGE', 50));
}

// Produces readable, sequential order numbers like ORD000001
function generateOrderNumber(): string
{
    $rows = q('SELECT id FROM orders ORDER BY id DESC LIMIT 1');
    $nextId = $rows ? $rows[0]['id'] + 1 : 1;
    return 'ORD' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
}

/**
 * Recomputes order totals strictly from the database - never trusts prices
 * sent by the client. Validates that every variant belongs to a product
 * enabled for the requesting gym and is active before pricing it.
 */
function priceOrderItems($gymId, $items): array
{
    if (!is_list_array($items) || count($items) === 0) {
        throw new HttpError('Order must contain at least one item', 400);
    }

    $lineItems = [];
    $subtotal = 0;

    foreach ($items as $item) {
        $variantId = num(is_array($item) ? ($item['variant_id'] ?? null) : null);
        $quantity = num(is_array($item) ? ($item['quantity'] ?? null) : null);

        if (!t($variantId) || !t($quantity) || $quantity < 1) {
            throw new HttpError('Invalid item in order', 400);
        }

        $rows = q(
            "SELECT
                pv.id AS variant_id, pv.variant_name, pv.status AS variant_status, pv.stock AS base_stock,
                p.id AS product_id, p.name AS product_name, p.status AS product_status,
                gp.status AS gym_product_status,
                gpv.price AS override_price, gpv.stock AS override_stock, gpv.status AS gym_variant_status,
                pv.price AS base_price
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             JOIN gym_products gp ON gp.product_id = p.id AND gp.gym_id = ?
             LEFT JOIN gym_product_variants gpv ON gpv.variant_id = pv.id AND gpv.gym_id = ?
             WHERE pv.id = ?",
            [$gymId, $gymId, $variantId]
        );

        if (!$rows) throw new HttpError('Product is not available for your gym', 400);
        $row = $rows[0];

        if ($row['product_status'] !== 'ACTIVE' || $row['variant_status'] !== 'ACTIVE' || $row['gym_product_status'] !== 'ACTIVE') {
            throw new HttpError("{$row['product_name']} is currently unavailable", 400);
        }
        if ($row['gym_variant_status'] && $row['gym_variant_status'] !== 'ACTIVE') {
            throw new HttpError("{$row['product_name']} is currently unavailable for your gym", 400);
        }

        $unitPrice = $row['override_price'] !== null ? num($row['override_price']) : num($row['base_price']);
        $availableStock = $row['override_stock'] !== null ? num($row['override_stock']) : num($row['base_stock']);

        if ($availableStock < $quantity) {
            throw new HttpError("Insufficient stock for {$row['product_name']} ({$row['variant_name']})", 400);
        }

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
    }

    $subtotal = r2($subtotal);
    $deliveryCharge = delivery_charge();
    $total = r2($subtotal + $deliveryCharge);

    return ['lineItems' => $lineItems, 'subtotal' => $subtotal, 'deliveryCharge' => $deliveryCharge, 'total' => $total];
}

// Creates a notification row. Live delivery to the admin panel happens in socket.php,
// which picks up new rows and pushes them over the Socket.IO polling connection.
// audience: 'ADMIN' (shown in admin panel) or 'USER' (shown in mobile app)
function createNotification(array $n): array
{
    $gymId = $n['gymId'] ?? null;
    $userId = $n['userId'] ?? null;
    $orderId = $n['orderId'] ?? null;
    $type = $n['type'] ?? 'GENERAL';

    $result = q(
        'INSERT INTO notifications (gym_id, user_id, order_id, title, message, type, audience)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$gymId, $userId, $orderId, $n['title'], $n['message'], $type, $n['audience']]
    );

    return [
        'id' => $result['insertId'],
        'gym_id' => $gymId,
        'user_id' => $userId,
        'order_id' => $orderId,
        'title' => $n['title'],
        'message' => $n['message'],
        'type' => $type,
        'audience' => $n['audience'],
        'is_read' => 0,
        'created_at' => gmdate('Y-m-d\TH:i:s.v\Z'),
    ];
}
