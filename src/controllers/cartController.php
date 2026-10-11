<?php

function getOrCreateCartId($userId): int
{
    $rows = q('SELECT id FROM carts WHERE user_id = ?', [$userId]);
    if ($rows) return $rows[0]['id'];
    return q('INSERT INTO carts (user_id) VALUES (?)', [$userId])['insertId'];
}

function buildCartResponse($cartId, $gymId): array
{
    $items = q(
        'SELECT ci.id, ci.variant_id, ci.quantity,
                pv.variant_name, pv.unit, pv.stock AS base_stock, pv.price AS base_price, pv.status AS variant_status,
                p.id AS product_id, p.name AS product_name, p.image AS product_image,
                gpv.price AS override_price, gpv.stock AS override_stock
         FROM cart_items ci
         JOIN product_variants pv ON pv.id = ci.variant_id
         JOIN products p ON p.id = pv.product_id
         LEFT JOIN gym_product_variants gpv ON gpv.variant_id = pv.id AND gpv.gym_id = ?
         WHERE ci.cart_id = ?
         ORDER BY ci.created_at DESC',
        [$gymId, $cartId]
    );

    $resolved = array_map(function ($item) {
        $price = $item['override_price'] !== null ? num($item['override_price']) : num($item['base_price']);
        return [
            'id' => $item['id'],
            'variant_id' => $item['variant_id'],
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'product_image' => $item['product_image'],
            'variant_name' => $item['variant_name'],
            'unit' => $item['unit'],
            'quantity' => $item['quantity'],
            'unit_price' => $price,
            'total_price' => r2($price * $item['quantity']),
        ];
    }, $items);

    $subtotal = r2(array_sum(array_column($resolved, 'total_price')));
    $deliveryCharge = delivery_charge();
    $total = $resolved ? r2($subtotal + $deliveryCharge) : 0;

    return ['items' => $resolved, 'subtotal' => $subtotal, 'delivery_charge' => $resolved ? $deliveryCharge : 0, 'total' => $total];
}

// GET /api/cart
function getCart(Req $R): void
{
    $cartId = getOrCreateCartId($R->user['id']);
    success('Cart fetched', buildCartResponse($cartId, $R->user['gym_id']));
}

// POST /api/cart  - body: { variant_id, quantity }
function addToCart(Req $R): void
{
    $variantId = $R->b('variant_id');
    $quantity = $R->has('quantity') ? $R->b('quantity') : 1;
    if (!t($variantId)) error_res('variant_id is required', 400);

    $check = q(
        "SELECT pv.id FROM product_variants pv
         JOIN gym_products gp ON gp.product_id = pv.product_id AND gp.gym_id = ? AND gp.status = 'ACTIVE'
         WHERE pv.id = ? AND pv.status = 'ACTIVE'",
        [$R->user['gym_id'], $variantId]
    );
    if (!$check) error_res('This product is not available for your gym', 400);

    $cartId = getOrCreateCartId($R->user['id']);

    q(
        'INSERT INTO cart_items (cart_id, variant_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)',
        [$cartId, $variantId, num($quantity)]
    );

    success('Item added to cart', buildCartResponse($cartId, $R->user['gym_id']));
}

// PUT /api/cart/items/:id  - body: { quantity }
function updateCartItem(Req $R): void
{
    $quantity = $R->b('quantity');
    if (!t($quantity) || $quantity < 1) error_res('Quantity must be at least 1', 400);

    $cartId = getOrCreateCartId($R->user['id']);
    $result = q('UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?', [$quantity, $R->p('id'), $cartId]);
    if (!$result['affectedRows']) error_res('Cart item not found', 404);

    success('Cart updated', buildCartResponse($cartId, $R->user['gym_id']));
}

// DELETE /api/cart/items/:id
function removeCartItem(Req $R): void
{
    $cartId = getOrCreateCartId($R->user['id']);
    q('DELETE FROM cart_items WHERE id = ? AND cart_id = ?', [$R->p('id'), $cartId]);
    success('Item removed from cart', buildCartResponse($cartId, $R->user['gym_id']));
}

// DELETE /api/cart
function clearCart(Req $R): void
{
    $cartId = getOrCreateCartId($R->user['id']);
    q('DELETE FROM cart_items WHERE cart_id = ?', [$cartId]);
    success('Cart cleared');
}
