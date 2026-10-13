<?php

function count_of(string $sql): int
{
    return q($sql)[0]['total'];
}

// GET /api/dashboard/summary
function getSummary(Req $R): void
{
    success('Dashboard summary fetched', [
        'total_gyms' => count_of("SELECT COUNT(*) AS total FROM gyms WHERE status = 'ACTIVE'"),
        'total_users' => count_of("SELECT COUNT(*) AS total FROM users WHERE status = 'ACTIVE'"),
        'total_products' => count_of("SELECT COUNT(*) AS total FROM products WHERE status = 'ACTIVE'"),
        'total_orders' => count_of('SELECT COUNT(*) AS total FROM orders'),
        'pending_orders' => count_of("SELECT COUNT(*) AS total FROM orders WHERE order_status NOT IN ('DELIVERED','CANCELLED')"),
        'today_orders' => count_of('SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = CURDATE()'),
        'today_revenue' => num(q("SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'VERIFIED'")[0]['total']),
        'total_revenue' => num(q("SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE payment_status = 'VERIFIED'")[0]['total']),
    ]);
}

function overview_days(Req $R): int
{
    return num($R->qp('days', null)) === 30 ? 30 : 7;
}

// GET /api/dashboard/orders?days=7|30
function getOrdersOverview(Req $R): void
{
    $rows = q(
        'SELECT DATE(created_at) AS date, COUNT(*) AS orders
         FROM orders
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(created_at) ORDER BY date ASC',
        [overview_days($R) - 1]
    );
    success('Orders overview fetched', $rows);
}

// GET /api/dashboard/revenue?days=7|30
function getRevenueOverview(Req $R): void
{
    $rows = q(
        "SELECT DATE(created_at) AS date, COALESCE(SUM(total_amount),0) AS revenue
         FROM orders
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND payment_status = 'VERIFIED'
         GROUP BY DATE(created_at) ORDER BY date ASC",
        [overview_days($R) - 1]
    );
    success('Revenue overview fetched', $rows);
}

// GET /api/dashboard/category-sales
function getCategorySales(Req $R): void
{
    $rows = q(
        "SELECT c.name AS category, COALESCE(SUM(oi.total_price),0) AS revenue, COALESCE(SUM(oi.quantity),0) AS units
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id
         LEFT JOIN order_items oi ON oi.product_id = p.id
         LEFT JOIN orders o ON o.id = oi.order_id AND o.payment_status = 'VERIFIED'
         GROUP BY c.id, c.name
         ORDER BY revenue DESC"
    );
    success('Category sales fetched', $rows);
}

// GET /api/dashboard/top-products
function getTopProducts(Req $R): void
{
    $rows = q(
        "SELECT p.id, p.name, p.image, COALESCE(SUM(oi.quantity),0) AS units_sold, COALESCE(SUM(oi.total_price),0) AS revenue
         FROM products p
         LEFT JOIN order_items oi ON oi.product_id = p.id
         LEFT JOIN orders o ON o.id = oi.order_id AND o.payment_status = 'VERIFIED'
         GROUP BY p.id, p.name, p.image
         ORDER BY units_sold DESC
         LIMIT 5"
    );
    success('Top products fetched', $rows);
}

// GET /api/dashboard/order-status
function getOrderStatusBreakdown(Req $R): void
{
    success('Order status breakdown fetched', q('SELECT order_status, COUNT(*) AS total FROM orders GROUP BY order_status'));
}

// GET /api/dashboard/recent-orders
function getRecentOrders(Req $R): void
{
    $rows = q(
        "SELECT o.id, o.order_number, o.total_amount, o.payment_status, o.order_status, o.created_at,
                COALESCE(u.name, o.customer_name) AS customer_name,
                CASE WHEN o.source = 'ONSITE' THEN 'Onsite Purchase' ELSE g.gym_name END AS gym_name
         FROM orders o LEFT JOIN users u ON u.id = o.user_id LEFT JOIN gyms g ON g.id = o.gym_id
         ORDER BY o.created_at DESC LIMIT 10"
    );
    success('Recent orders fetched', $rows);
}

// GET /api/dashboard/report?gym_id=&source=&date_from=&date_to=  - filterable report (capped at 500 rows)
function getReport(Req $R): void
{
    $where = 'WHERE 1=1';
    $params = [];
    if (t($R->qp('gym_id'))) {
        $where .= ' AND o.gym_id = ?';
        $params[] = $R->qp('gym_id');
    }
    if (t($R->qp('source'))) {
        $where .= ' AND o.source = ?';
        $params[] = $R->qp('source');
    }
    if (t($R->qp('date_from'))) {
        $where .= ' AND DATE(o.created_at) >= ?';
        $params[] = $R->qp('date_from');
    }
    if (t($R->qp('date_to'))) {
        $where .= ' AND DATE(o.created_at) <= ?';
        $params[] = $R->qp('date_to');
    }

    $summary = q(
        "SELECT COUNT(*) AS total_orders,
                COALESCE(SUM(o.total_amount),0) AS total_amount,
                COALESCE(SUM(CASE WHEN o.payment_status = 'VERIFIED' THEN o.total_amount ELSE 0 END),0) AS verified_revenue
         FROM orders o $where",
        $params
    )[0];

    $orders = q(
        "SELECT o.id, o.order_number, o.total_amount, o.payment_status, o.order_status, o.source, o.created_at,
                COALESCE(u.name, o.customer_name) AS customer_name,
                CASE WHEN o.source = 'ONSITE' THEN 'Onsite Purchase' ELSE g.gym_name END AS gym_name
         FROM orders o LEFT JOIN users u ON u.id = o.user_id LEFT JOIN gyms g ON g.id = o.gym_id
         $where ORDER BY o.created_at DESC LIMIT 500",
        $params
    );

    success('Report generated', [
        'total_orders' => $summary['total_orders'],
        'total_amount' => num($summary['total_amount']),
        'verified_revenue' => num($summary['verified_revenue']),
        'orders' => $orders,
    ]);
}
