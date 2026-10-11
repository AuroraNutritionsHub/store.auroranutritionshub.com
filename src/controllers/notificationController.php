<?php

function is_staff(Req $R): bool
{
    return in_array($R->auth['role'] ?? null, ['admin', 'biller'], true);
}

// GET /api/notifications
function getNotifications(Req $R): void
{
    $page = $R->qp('page', 1);
    $limit = $R->qp('limit', 30);
    $dateFrom = $R->qp('date_from');
    $dateTo = $R->qp('date_to');
    $offset = (num($page) - 1) * num($limit);

    if (is_staff($R)) {
        $where = "WHERE audience = 'ADMIN'";
        $params = [];
    } else {
        $where = "WHERE audience = 'USER' AND user_id = ?";
        $params = [$R->auth['id']];
    }

    if (t($dateFrom)) {
        $where .= ' AND DATE(created_at) >= ?';
        $params[] = $dateFrom;
    }
    if (t($dateTo)) {
        $where .= ' AND DATE(created_at) <= ?';
        $params[] = $dateTo;
    }

    $rows = q("SELECT * FROM notifications $where ORDER BY created_at DESC LIMIT ? OFFSET ?", array_merge($params, [num($limit), $offset]));
    $unread = q("SELECT COUNT(*) AS unread FROM notifications $where AND is_read = 0", $params);

    success('Notifications fetched', ['notifications' => $rows, 'unread' => $unread[0]['unread']]);
}

// PUT /api/notifications/:id/read
function markAsRead(Req $R): void
{
    q('UPDATE notifications SET is_read = 1 WHERE id = ?', [$R->p('id')]);
    success('Notification marked as read');
}

// PUT /api/notifications/read-all
function markAllAsRead(Req $R): void
{
    if (is_staff($R)) {
        q("UPDATE notifications SET is_read = 1 WHERE audience = 'ADMIN'");
    } else {
        q("UPDATE notifications SET is_read = 1 WHERE audience = 'USER' AND user_id = ?", [$R->auth['id']]);
    }
    success('All notifications marked as read');
}
