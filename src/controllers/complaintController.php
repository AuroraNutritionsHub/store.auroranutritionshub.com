<?php

// POST /api/complaints  (user only) - { order_id?, subject, message }
function createComplaint(Req $R): void
{
    $orderId = $R->b('order_id');
    $subject = $R->b('subject');
    $message = $R->b('message');
    if (!t($subject) || trim((string) $subject) === '') error_res('Please enter a subject', 400);
    if (!t($message) || trim((string) $message) === '') error_res('Please describe the issue', 400);

    if (t($orderId)) {
        $orderRows = q('SELECT id FROM orders WHERE id = ? AND user_id = ?', [$orderId, $R->user['id']]);
        if (!$orderRows) error_res('Order not found', 404);
    }

    $result = q(
        'INSERT INTO complaints (gym_id, user_id, order_id, subject, message) VALUES (?, ?, ?, ?, ?)',
        [$R->user['gym_id'], $R->user['id'], orn($orderId), trim($subject), trim($message)]
    );

    $userRow = q1('SELECT name FROM users WHERE id = ?', [$R->user['id']]);
    $senderName = t($userRow['name'] ?? null) ? $userRow['name'] : 'Member';

    createNotification([
        'gymId' => $R->user['gym_id'],
        'orderId' => orn($orderId),
        'title' => 'New Complaint',
        'message' => "$senderName raised: " . trim($subject),
        'type' => 'NEW_COMPLAINT',
        'audience' => 'ADMIN',
    ]);

    success('Complaint submitted successfully', ['id' => $result['insertId']], 201);
}

// GET /api/complaints  - user: their own; admin: everyone's (filterable)
function getComplaints(Req $R): void
{
    $where = 'WHERE 1=1';
    $params = [];

    if ($R->auth['role'] === 'user') {
        $where .= ' AND c.user_id = ?';
        $params[] = $R->auth['id'];
    } else {
        $status = $R->qp('status');
        $gymId = $R->qp('gym_id');
        $search = $R->qp('search');
        if (t($status)) {
            $where .= ' AND c.status = ?';
            $params[] = $status;
        }
        if (t($gymId)) {
            $where .= ' AND c.gym_id = ?';
            $params[] = $gymId;
        }
        if (t($search)) {
            $where .= ' AND (c.subject LIKE ? OR u.name LIKE ? OR o.order_number LIKE ?)';
            array_push($params, like($search), like($search), like($search));
        }
    }

    $rows = q(
        "SELECT c.id, c.subject, c.message, c.status, c.created_at, c.updated_at,
                g.gym_name, u.name AS user_name, o.order_number
         FROM complaints c
         JOIN gyms g ON g.id = c.gym_id
         JOIN users u ON u.id = c.user_id
         LEFT JOIN orders o ON o.id = c.order_id
         $where
         ORDER BY c.created_at DESC",
        $params
    );

    success('Complaints fetched', $rows);
}

// GET /api/complaints/:id  - user can only view their own
function getComplaintById(Req $R): void
{
    $rows = q(
        'SELECT c.*, g.gym_name, u.name AS user_name, u.phone AS user_phone, o.order_number
         FROM complaints c
         JOIN gyms g ON g.id = c.gym_id
         JOIN users u ON u.id = c.user_id
         LEFT JOIN orders o ON o.id = c.order_id
         WHERE c.id = ?',
        [$R->p('id')]
    );
    if (!$rows) error_res('Complaint not found', 404);
    $complaint = $rows[0];

    if ($R->auth['role'] === 'user' && $complaint['user_id'] !== $R->auth['id']) {
        error_res('You are not authorized to view this complaint', 403);
    }

    success('Complaint fetched', $complaint);
}

// PUT /api/complaints/:id/status  (admin only) - { status: 'OPEN' | 'RESOLVED' }
function updateComplaintStatus(Req $R): void
{
    $status = $R->b('status');
    if (!in_array($status, ['OPEN', 'RESOLVED'], true)) error_res('Invalid status', 400);

    $complaint = q1('SELECT * FROM complaints WHERE id = ?', [$R->p('id')]);
    if (!$complaint) error_res('Complaint not found', 404);

    q('UPDATE complaints SET status = ? WHERE id = ?', [$status, $R->p('id')]);

    if ($status === 'RESOLVED') {
        createNotification([
            'gymId' => $complaint['gym_id'],
            'userId' => $complaint['user_id'],
            'title' => 'Complaint resolved',
            'message' => "Your complaint \"{$complaint['subject']}\" has been marked resolved.",
            'type' => 'COMPLAINT_RESOLVED',
            'audience' => 'USER',
        ]);
    }

    success('Status updated successfully');
}
