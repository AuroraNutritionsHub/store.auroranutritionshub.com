<?php

// GET /api/gyms
function getGyms(Req $R): void
{
    $search = $R->qp('search');
    $status = $R->qp('status');
    $page = $R->qp('page', 1);
    $limit = $R->qp('limit', 20);
    $offset = (num($page) - 1) * num($limit);

    $where = 'WHERE 1=1';
    $params = [];
    if (t($search)) {
        $where .= ' AND (gym_name LIKE ? OR gym_code LIKE ?)';
        array_push($params, like($search), like($search));
    }
    if (t($status)) {
        $where .= ' AND status = ?';
        $params[] = $status;
    }

    $rows = q(
        "SELECT id, gym_name, gym_code, logo, phone, email, address, status, created_at, updated_at
         FROM gyms $where ORDER BY created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [num($limit), $offset])
    );
    $count = q("SELECT COUNT(*) AS total FROM gyms $where", $params);

    success('Gyms fetched', [
        'gyms' => $rows,
        'total' => $count[0]['total'],
        'page' => num($page),
        'limit' => num($limit),
    ]);
}

// GET /api/gyms/:id
function getGymById(Req $R): void
{
    $rows = q(
        'SELECT id, gym_name, gym_code, logo, phone, email, address, status, created_at, updated_at FROM gyms WHERE id = ?',
        [$R->p('id')]
    );
    if (!$rows) error_res('Gym not found', 404);
    success('Gym fetched', $rows[0]);
}

// POST /api/gyms
function createGym(Req $R): void
{
    $gymName = $R->b('gym_name');
    $gymCode = $R->b('gym_code');
    if (!t($gymName) || !t($gymCode)) error_res('Gym name and gym code are required', 400);

    $logo = $R->file ? 'gyms/' . $R->file['filename'] : null;

    $result = q(
        'INSERT INTO gyms (gym_name, gym_code, logo, phone, email, address, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$gymName, strtoupper((string) $gymCode), $logo, orn($R->b('phone')), orn($R->b('email')), orn($R->b('address')), orv($R->b('status'), 'ACTIVE')]
    );

    success('Gym created successfully', ['id' => $result['insertId']], 201);
}

// PUT /api/gyms/:id
function updateGym(Req $R): void
{
    $existing = q('SELECT * FROM gyms WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Gym not found', 404);

    $logo = $existing[0]['logo'];
    if ($R->file) {
        if ($logo) {
            $oldPath = uploads_root() . '/' . $logo;
            if (is_file($oldPath)) unlink($oldPath);
        }
        $logo = 'gyms/' . $R->file['filename'];
    }

    $gymCode = $R->b('gym_code');
    if ($gymCode === null) throw new HttpError("Cannot read properties of undefined (reading 'toUpperCase')", 500);

    q(
        'UPDATE gyms SET gym_name = ?, gym_code = ?, logo = ?, phone = ?, email = ?, address = ?, status = ?
         WHERE id = ?',
        [$R->b('gym_name'), strtoupper((string) $gymCode), $logo, orn($R->b('phone')), orn($R->b('email')), orn($R->b('address')), $R->b('status'), $R->p('id')]
    );

    success('Gym updated successfully');
}

// DELETE /api/gyms/:id
function deleteGym(Req $R): void
{
    $existing = q('SELECT id FROM gyms WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Gym not found', 404);

    q('DELETE FROM gyms WHERE id = ?', [$R->p('id')]);
    success('Gym deleted successfully');
}

// GET /api/gyms/:gymId/products  - all products with an "enabled" flag for this gym
function getGymProducts(Req $R): void
{
    $gymId = $R->p('gymId');
    $gym = q('SELECT id FROM gyms WHERE id = ?', [$gymId]);
    if (!$gym) error_res('Gym not found', 404);

    $rows = q(
        'SELECT p.id AS product_id, p.name, p.brand, p.image, p.status AS product_status,
                c.name AS category_name,
                gp.id AS gym_product_id, gp.status AS gym_status,
                IF(gp.id IS NULL, 0, 1) AS enabled
         FROM products p
         JOIN categories c ON c.id = p.category_id
         LEFT JOIN gym_products gp ON gp.product_id = p.id AND gp.gym_id = ?
         ORDER BY c.name, p.name',
        [$gymId]
    );

    success('Gym products fetched', $rows);
}

// POST /api/gyms/:gymId/products  - body: { product_ids: [1,2,3] } enable these for the gym
function setGymProducts(Req $R): void
{
    $gymId = $R->p('gymId');
    $productIds = $R->b('product_ids');
    if (!is_list_array($productIds)) error_res('product_ids must be an array', 400);

    $gym = q('SELECT id FROM gyms WHERE id = ?', [$gymId]);
    if (!$gym) error_res('Gym not found', 404);

    db_begin();
    try {
        q('DELETE FROM gym_products WHERE gym_id = ?', [$gymId]);
        if ($productIds) {
            $values = array_map(fn($pid) => [$gymId, $pid, 'ACTIVE'], $productIds);
            q('INSERT INTO gym_products (gym_id, product_id, status) VALUES ?', [$values]);
        }
        db_commit();
    } catch (Throwable $e) {
        db_rollback();
        throw $e;
    }

    success('Gym products updated successfully');
}

// PUT /api/gyms/:gymId/products/:productId  - toggle a single product on/off
function toggleGymProduct(Req $R): void
{
    $gymId = $R->p('gymId');
    $productId = $R->p('productId');

    if (t($R->b('enabled'))) {
        q(
            "INSERT INTO gym_products (gym_id, product_id, status) VALUES (?, ?, 'ACTIVE')
             ON DUPLICATE KEY UPDATE status = 'ACTIVE'",
            [$gymId, $productId]
        );
    } else {
        q('DELETE FROM gym_products WHERE gym_id = ? AND product_id = ?', [$gymId, $productId]);
    }

    success('Gym product updated successfully');
}
