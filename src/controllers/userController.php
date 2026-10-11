<?php

// GET /api/users
function getUsers(Req $R): void
{
    $search = $R->qp('search');
    $status = $R->qp('status');
    $gymId = $R->qp('gym_id');
    $page = $R->qp('page', 1);
    $limit = $R->qp('limit', 20);
    $offset = (num($page) - 1) * num($limit);

    $where = 'WHERE 1=1';
    $params = [];
    if (t($search)) {
        $where .= ' AND (u.name LIKE ? OR u.username LIKE ? OR u.phone LIKE ?)';
        array_push($params, like($search), like($search), like($search));
    }
    if (t($status)) {
        $where .= ' AND u.status = ?';
        $params[] = $status;
    }
    if (t($gymId)) {
        $where .= ' AND u.gym_id = ?';
        $params[] = $gymId;
    }

    $rows = q(
        "SELECT u.id, u.name, u.username, u.phone, u.email, u.status, u.created_at,
                g.id AS gym_id, g.gym_name, g.gym_code
         FROM users u JOIN gyms g ON g.id = u.gym_id
         $where ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [num($limit), $offset])
    );
    $count = q("SELECT COUNT(*) AS total FROM users u JOIN gyms g ON g.id = u.gym_id $where", $params);

    success('Users fetched', ['users' => $rows, 'total' => $count[0]['total'], 'page' => num($page), 'limit' => num($limit)]);
}

// GET /api/users/:id
function getUserById(Req $R): void
{
    $rows = q(
        'SELECT u.id, u.name, u.username, u.phone, u.email, u.status, u.created_at,
                g.id AS gym_id, g.gym_name, g.gym_code
         FROM users u JOIN gyms g ON g.id = u.gym_id WHERE u.id = ?',
        [$R->p('id')]
    );
    if (!$rows) error_res('User not found', 404);
    success('User fetched', $rows[0]);
}

// POST /api/users
function createUser(Req $R): void
{
    $gymId = $R->b('gym_id');
    $name = $R->b('name');
    $username = $R->b('username');
    $password = $R->b('password');
    if (!t($gymId) || !t($name) || !t($username) || !t($password)) {
        error_res('Gym, name, username and password are required', 400);
    }

    $result = q(
        'INSERT INTO users (gym_id, name, username, password, phone, email, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$gymId, $name, $username, bcrypt_hash((string) $password), orn($R->b('phone')), orn($R->b('email')), orv($R->b('status'), 'ACTIVE')]
    );

    q('INSERT INTO carts (user_id) VALUES (?)', [$result['insertId']]);

    success('User created successfully', ['id' => $result['insertId']], 201);
}

// PUT /api/users/:id
function updateUser(Req $R): void
{
    $existing = q('SELECT id FROM users WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('User not found', 404);

    q(
        'UPDATE users SET name = ?, username = ?, phone = ?, email = ?, status = ?, gym_id = ? WHERE id = ?',
        [$R->b('name'), $R->b('username'), orn($R->b('phone')), orn($R->b('email')), $R->b('status'), $R->b('gym_id'), $R->p('id')]
    );

    success('User updated successfully');
}

// DELETE /api/users/:id
function deleteUser(Req $R): void
{
    $existing = q('SELECT id FROM users WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('User not found', 404);

    q('DELETE FROM users WHERE id = ?', [$R->p('id')]);
    success('User deleted successfully');
}

// PUT /api/users/:id/reset-password
function resetUserPassword(Req $R): void
{
    $password = $R->b('password');
    if (!t($password) || mb_strlen((string) $password) < 6) error_res('Password must be at least 6 characters', 400);

    $result = q('UPDATE users SET password = ? WHERE id = ?', [bcrypt_hash((string) $password), $R->p('id')]);
    if (!$result['affectedRows']) error_res('User not found', 404);

    success('Password reset successfully');
}
