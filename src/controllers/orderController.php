<?php

const STATUS_MESSAGES = [
    'CONFIRMED' => 'Your order has been confirmed.',
    'PREPARING' => 'Your order is being prepared.',
    'READY_FOR_DELIVERY' => 'Your order is ready for delivery.',
    'OUT_FOR_DELIVERY' => 'Your order is on the way.',
    'DELIVERED' => 'Your order has been delivered.',
    'CANCELLED' => 'Your order has been cancelled.',
];

// POST /api/orders  (user only)
function createOrder(Req $R): void
{
    $gymId = $R->user['gym_id'];
    $userId = $R->user['id'];

    $priced = priceOrderItems($gymId, $R->b('items'));
    $total = $priced['total'];
    $orderNumber = generateOrderNumber();

    db_begin();
    try {
        $orderId = q(
            "INSERT INTO orders (order_number, gym_id, user_id, subtotal, delivery_charge, total_amount, payment_status, order_status, delivery_address)
             VALUES (?, ?, ?, ?, ?, ?, 'PENDING', 'PLACED', ?)",
            [$orderNumber, $gymId, $userId, $priced['subtotal'], $priced['deliveryCharge'], $total, orn($R->b('delivery_address'))]
        )['insertId'];

        foreach ($priced['lineItems'] as $item) {
            q(
                'INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_name, quantity, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$orderId, $item['product_id'], $item['variant_id'], $item['product_name'], $item['variant_name'], $item['quantity'], $item['unit_price'], $item['total_price']]
            );
        }

        q("INSERT INTO payments (order_id, amount, payment_method, payment_status) VALUES (?, ?, 'UPI_QR', 'PENDING')", [$orderId, $total]);

        $cartId = getOrCreateCartId($userId);
        q('DELETE FROM cart_items WHERE cart_id = ?', [$cartId]);

        db_commit();

        $userRow = q1('SELECT name FROM users WHERE id = ?', [$userId]);
        createNotification([
            'gymId' => $gymId,
            'orderId' => $orderId,
            'title' => 'New Order',
            'message' => "Order $orderNumber received from " . (t($userRow['name'] ?? null) ? $userRow['name'] : 'a customer') . ' - ₹' . js_str($total),
            'type' => 'NEW_ORDER',
            'audience' => 'ADMIN',
        ]);
        createNotification([
            'gymId' => $gymId,
            'userId' => $userId,
            'orderId' => $orderId,
            'title' => 'Order Placed',
            'message' => "Your order $orderNumber has been received.",
            'type' => 'ORDER_PLACED',
            'audience' => 'USER',
        ]);
    } catch (Throwable $e) {
        db_rollback();
        throw $e;
    }

    success('Order created successfully', [
        'id' => $orderId,
        'order_number' => $orderNumber,
        'subtotal' => $priced['subtotal'],
        'delivery_charge' => $priced['deliveryCharge'],
        'total_amount' => $total,
    ], 201);
}

// GET /api/orders
function getOrders(Req $R): void
{
    $status = $R->qp('status');
    $paymentStatus = $R->qp('payment_status');
    $gymId = $R->qp('gym_id');
    $search = $R->qp('search');
    $dateFrom = $R->qp('date_from');
    $dateTo = $R->qp('date_to');
    $page = $R->qp('page', 1);
    $limit = $R->qp('limit', 20);
    $offset = (num($page) - 1) * num($limit);

    $where = 'WHERE 1=1';
    $params = [];

    if (t($dateFrom)) {
        $where .= ' AND DATE(o.created_at) >= ?';
        $params[] = $dateFrom;
    }
    if (t($dateTo)) {
        $where .= ' AND DATE(o.created_at) <= ?';
        $params[] = $dateTo;
    }

    if ($R->auth['role'] === 'user') {
        $where .= ' AND o.user_id = ?';
        $params[] = $R->auth['id'];
    } elseif (t($gymId)) {
        $where .= ' AND o.gym_id = ?';
        $params[] = $gymId;
    }

    if (t($status)) {
        $where .= ' AND o.order_status = ?';
        $params[] = $status;
    }
    if (t($paymentStatus)) {
        $where .= ' AND o.payment_status = ?';
        $params[] = $paymentStatus;
    }
    if (t($search)) {
        $where .= ' AND (o.order_number LIKE ? OR u.name LIKE ? OR o.customer_name LIKE ?)';
        array_push($params, like($search), like($search), like($search));
    }

    $rows = q(
        "SELECT o.*, COALESCE(u.name, o.customer_name) AS customer_name,
                CASE WHEN o.source = 'ONSITE' THEN 'Onsite Purchase' ELSE g.gym_name END AS gym_name
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         LEFT JOIN gyms g ON g.id = o.gym_id
         $where ORDER BY o.created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [num($limit), $offset])
    );
    $count = q("SELECT COUNT(*) AS total FROM orders o LEFT JOIN users u ON u.id = o.user_id LEFT JOIN gyms g ON g.id = o.gym_id $where", $params);

    success('Orders fetched', ['orders' => $rows, 'total' => $count[0]['total'], 'page' => num($page), 'limit' => num($limit)]);
}

// GET /api/orders/:id
function getOrderById(Req $R): void
{
    $orders = q(
        'SELECT o.*, COALESCE(u.name, o.customer_name) AS customer_name, COALESCE(u.phone, o.customer_phone) AS customer_phone, u.email AS customer_email,
                g.gym_name, g.gym_code, g.logo AS gym_logo, g.address AS gym_address, g.phone AS gym_phone, g.email AS gym_email
         FROM orders o LEFT JOIN users u ON u.id = o.user_id LEFT JOIN gyms g ON g.id = o.gym_id
         WHERE o.id = ?',
        [$R->p('id')]
    );
    if (!$orders) error_res('Order not found', 404);
    $order = $orders[0];

    if ($R->auth['role'] === 'user' && $order['user_id'] !== $R->auth['id']) {
        error_res('You are not authorized to view this order', 403);
    }

    $order['items'] = q('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
    $payments = q('SELECT * FROM payments WHERE order_id = ?', [$order['id']]);
    $order['payment'] = $payments[0] ?? null;

    success('Order fetched', $order);
}

// PUT /api/orders/:id/status  (admin only)
function updateOrderStatus(Req $R): void
{
    $orderStatus = $R->b('order_status');
    $deliveryDate = $R->b('delivery_date');
    $deliveryTime = $R->b('delivery_time');

    $order = q1('SELECT * FROM orders WHERE id = ?', [$R->p('id')]);
    if (!$order) error_res('Order not found', 404);

    $fields = [];
    $params = [];
    if (t($orderStatus)) {
        $fields[] = 'order_status = ?';
        $params[] = $orderStatus;
    }
    if (t($deliveryDate)) {
        $fields[] = 'delivery_date = ?';
        $params[] = $deliveryDate;
    }
    if (t($deliveryTime)) {
        $fields[] = 'delivery_time = ?';
        $params[] = $deliveryTime;
    }
    if (!$fields) error_res('Nothing to update', 400);

    $params[] = $order['id'];
    q('UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);

    if (t($orderStatus) && is_string($orderStatus) && isset(STATUS_MESSAGES[$orderStatus])) {
        createNotification([
            'gymId' => $order['gym_id'],
            'userId' => $order['user_id'],
            'orderId' => $order['id'],
            'title' => str_replace('_', ' ', $orderStatus),
            'message' => STATUS_MESSAGES[$orderStatus] . " (Order {$order['order_number']})",
            'type' => 'ORDER_STATUS',
            'audience' => 'USER',
        ]);
    }
    if (t($deliveryDate) || t($deliveryTime)) {
        $date = t($deliveryDate) ? $deliveryDate : $order['delivery_date'];
        $time = t($deliveryTime) ? $deliveryTime : (t($order['delivery_time']) ? $order['delivery_time'] : '');
        createNotification([
            'gymId' => $order['gym_id'],
            'userId' => $order['user_id'],
            'orderId' => $order['id'],
            'title' => 'Delivery Scheduled',
            'message' => "Delivery for order {$order['order_number']} is scheduled on " . js_str($date) . ' ' . js_str($time),
            'type' => 'DELIVERY_SCHEDULED',
            'audience' => 'USER',
        ]);
    }

    success('Order updated successfully');
}

// POST /api/orders/:orderId/payment  (user submits transaction id + screenshot)
function submitPayment(Req $R): void
{
    $transactionId = $R->b('transaction_id');
    $order = q1('SELECT * FROM orders WHERE id = ?', [$R->p('orderId')]);
    if (!$order) error_res('Order not found', 404);

    if ($order['user_id'] !== $R->user['id']) error_res('You are not authorized to update this order', 403);
    if (!t($transactionId)) error_res('Transaction ID is required', 400);
    if (!$R->file) error_res('Payment screenshot is required', 400);

    $screenshot = 'payments/' . $R->file['filename'];

    q("UPDATE payments SET transaction_id = ?, screenshot = ?, payment_status = 'SUBMITTED' WHERE order_id = ?", [$transactionId, $screenshot, $order['id']]);
    q("UPDATE orders SET payment_status = 'SUBMITTED', order_status = 'PAYMENT_SUBMITTED' WHERE id = ?", [$order['id']]);

    createNotification([
        'gymId' => $order['gym_id'],
        'orderId' => $order['id'],
        'title' => 'Payment Submitted',
        'message' => "Payment screenshot submitted for order {$order['order_number']} - ₹" . js_str($order['total_amount']),
        'type' => 'PAYMENT_SUBMITTED',
        'audience' => 'ADMIN',
    ]);

    success('Payment details submitted. Waiting for admin verification.');
}

// GET /api/orders/:orderId/payment
function getPayment(Req $R): void
{
    $order = q1('SELECT * FROM orders WHERE id = ?', [$R->p('orderId')]);
    if (!$order) error_res('Order not found', 404);

    if (($R->auth['role'] ?? null) === 'user' && $order['user_id'] !== $R->auth['id']) {
        error_res('You are not authorized to view this payment', 403);
    }

    $rows = q('SELECT * FROM payments WHERE order_id = ?', [$R->p('orderId')]);
    success('Payment fetched', $rows[0] ?? null);
}

// PUT /api/payments/:id/verify  (admin)
function verifyPayment(Req $R): void
{
    $payment = q1('SELECT * FROM payments WHERE id = ?', [$R->p('id')]);
    if (!$payment) error_res('Payment not found', 404);

    q("UPDATE payments SET payment_status = 'VERIFIED', verified_by = ?, verified_at = NOW() WHERE id = ?", [$R->admin['id'], $payment['id']]);
    q("UPDATE orders SET payment_status = 'VERIFIED', order_status = 'CONFIRMED' WHERE id = ?", [$payment['order_id']]);

    $order = q1('SELECT * FROM orders WHERE id = ?', [$payment['order_id']]);

    createNotification([
        'gymId' => $order['gym_id'],
        'userId' => $order['user_id'],
        'orderId' => $order['id'],
        'title' => 'Payment Verified',
        'message' => "Your payment for order {$order['order_number']} has been verified. Your order is confirmed.",
        'type' => 'PAYMENT_VERIFIED',
        'audience' => 'USER',
    ]);

    success('Payment verified successfully');
}

// PUT /api/payments/:id/reject  (admin)
function rejectPayment(Req $R): void
{
    $reason = $R->b('reason');
    $payment = q1('SELECT * FROM payments WHERE id = ?', [$R->p('id')]);
    if (!$payment) error_res('Payment not found', 404);

    q("UPDATE payments SET payment_status = 'REJECTED', verified_by = ?, verified_at = NOW() WHERE id = ?", [$R->admin['id'], $payment['id']]);
    q("UPDATE orders SET payment_status = 'REJECTED' WHERE id = ?", [$payment['order_id']]);

    $order = q1('SELECT * FROM orders WHERE id = ?', [$payment['order_id']]);

    createNotification([
        'gymId' => $order['gym_id'],
        'userId' => $order['user_id'],
        'orderId' => $order['id'],
        'title' => 'Payment Rejected',
        'message' => "Your payment for order {$order['order_number']} was rejected." . (t($reason) ? ' Reason: ' . js_str($reason) : '') . ' Please contact the gym or resubmit.',
        'type' => 'PAYMENT_REJECTED',
        'audience' => 'USER',
    ]);

    success('Payment rejected');
}
