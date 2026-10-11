<?php

// POST /api/admin/login
function adminLogin(Req $R): void
{
    $email = $R->b('email');
    $password = $R->b('password');
    if (!t($email) || !t($password)) error_res('Email and password are required', 400);

    $admin = q1('SELECT * FROM admins WHERE email = ?', [$email]);
    if (!$admin || $admin['status'] !== 'ACTIVE') error_res('Invalid credentials', 401);

    if (!bcrypt_compare($password, $admin['password'])) error_res('Invalid credentials', 401);

    $token = generate_token(['id' => $admin['id'], 'role' => $admin['role'] === 'BILLER' ? 'biller' : 'admin']);
    success('Login successful', [
        'token' => $token,
        'admin' => ['id' => $admin['id'], 'name' => $admin['name'], 'email' => $admin['email'], 'role' => $admin['role']],
    ]);
}

// POST /api/auth/login  (gym user - mobile app)
function userLogin(Req $R): void
{
    $gymCode = $R->b('gym_code');
    $username = $R->b('username');
    $password = $R->b('password');
    if (!t($gymCode) || !t($username) || !t($password)) {
        error_res('Gym code, username and password are required', 400);
    }

    $gym = q1('SELECT * FROM gyms WHERE gym_code = ?', [strtoupper(trim((string) $gymCode))]);
    if (!$gym || $gym['status'] !== 'ACTIVE') error_res('Invalid gym code', 401);

    $user = q1('SELECT * FROM users WHERE gym_id = ? AND username = ?', [$gym['id'], trim((string) $username)]);
    if (!$user || $user['status'] !== 'ACTIVE') error_res('Invalid username or password', 401);

    if (!bcrypt_compare($password, $user['password'])) error_res('Invalid username or password', 401);

    $token = generate_token(['id' => $user['id'], 'gym_id' => $gym['id'], 'role' => 'user']);
    success('Login successful', [
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'phone' => $user['phone'],
            'email' => $user['email'],
        ],
        'gym' => [
            'id' => $gym['id'],
            'gym_name' => $gym['gym_name'],
            'gym_code' => $gym['gym_code'],
        ],
    ]);
}

// POST /api/auth/change-password
function changePassword(Req $R): void
{
    $current = $R->b('current_password');
    $new = $R->b('new_password');
    if (!t($current) || !t($new)) error_res('Both current and new password are required', 400);
    if (mb_strlen((string) $new) < 6) error_res('New password must be at least 6 characters', 400);

    $user = q1('SELECT * FROM users WHERE id = ?', [$R->user['id']]);
    if (!$user) error_res('User not found', 404);

    if (!bcrypt_compare($current, $user['password'])) error_res('Current password is incorrect', 401);

    q('UPDATE users SET password = ? WHERE id = ?', [bcrypt_hash((string) $new), $user['id']]);
    success('Password changed successfully');
}

// GET /api/auth/me
function getProfile(Req $R): void
{
    $rows = q(
        'SELECT u.id, u.name, u.username, u.phone, u.email, u.status,
                g.id AS gym_id, g.gym_name, g.gym_code
         FROM users u JOIN gyms g ON g.id = u.gym_id
         WHERE u.id = ?',
        [$R->user['id']]
    );
    if (!$rows) error_res('User not found', 404);
    success('Profile fetched', $rows[0]);
}

// PUT /api/admin/profile
function updateAdminProfile(Req $R): void
{
    $name = $R->b('name');
    $email = $R->b('email');
    if (!t($name) || !t($email)) error_res('Name and email are required', 400);

    $existing = q('SELECT id FROM admins WHERE email = ? AND id != ?', [$email, $R->admin['id']]);
    if ($existing) error_res('Another admin already uses this email', 409);

    q('UPDATE admins SET name = ?, email = ? WHERE id = ?', [$name, $email, $R->admin['id']]);
    $rows = q('SELECT id, name, email FROM admins WHERE id = ?', [$R->admin['id']]);

    success('Profile updated successfully', $rows[0] ?? null);
}

// PUT /api/admin/change-password
function adminChangePassword(Req $R): void
{
    $current = $R->b('current_password');
    $new = $R->b('new_password');
    if (!t($current) || !t($new)) error_res('Both current and new password are required', 400);
    if (mb_strlen((string) $new) < 6) error_res('New password must be at least 6 characters', 400);

    $admin = q1('SELECT * FROM admins WHERE id = ?', [$R->admin['id']]);
    if (!$admin) error_res('Admin not found', 404);

    if (!bcrypt_compare($current, $admin['password'])) error_res('Current password is incorrect', 401);

    q('UPDATE admins SET password = ? WHERE id = ?', [bcrypt_hash((string) $new), $admin['id']]);
    success('Password changed successfully');
}

// GET /api/admin/staff  - list admin-panel logins (full admin only)
function getStaff(Req $R): void
{
    $rows = q('SELECT id, name, email, role, status, created_at FROM admins ORDER BY created_at ASC');
    success('Staff fetched', $rows);
}

// POST /api/admin/staff  - create a new admin-panel login (full admin only)
function createStaff(Req $R): void
{
    $name = $R->b('name');
    $email = $R->b('email');
    $password = $R->b('password');
    $role = $R->has('role') ? $R->b('role') : 'BILLER';
    if (!t($name) || !t($email) || !t($password)) error_res('Name, email and password are required', 400);
    if (mb_strlen((string) $password) < 6) error_res('Password must be at least 6 characters', 400);
    if (!in_array($role, ['ADMIN', 'BILLER'], true)) error_res('Invalid role', 400);

    $existing = q('SELECT id FROM admins WHERE email = ?', [$email]);
    if ($existing) error_res('An account with this email already exists', 409);

    $result = q(
        'INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)',
        [$name, $email, bcrypt_hash((string) $password), $role]
    );

    success('Login created successfully', [
        'id' => $result['insertId'], 'name' => $name, 'email' => $email, 'role' => $role, 'status' => 'ACTIVE',
    ], 201);
}

// PUT /api/admin/staff/:id/status  - activate/deactivate a login (full admin only)
function updateStaffStatus(Req $R): void
{
    $status = $R->b('status');
    if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) error_res('Invalid status', 400);
    if (num($R->p('id')) === $R->admin['id']) error_res('You cannot change your own status', 400);

    $rows = q('SELECT id FROM admins WHERE id = ?', [$R->p('id')]);
    if (!$rows) error_res('Login not found', 404);

    q('UPDATE admins SET status = ? WHERE id = ?', [$status, $R->p('id')]);
    success('Status updated successfully');
}

// PUT /api/admin/staff/:id/reset-password  - reset another login's password (full admin only)
function resetStaffPassword(Req $R): void
{
    $new = $R->b('new_password');
    if (!t($new) || mb_strlen((string) $new) < 6) error_res('New password must be at least 6 characters', 400);

    $rows = q('SELECT id FROM admins WHERE id = ?', [$R->p('id')]);
    if (!$rows) error_res('Login not found', 404);

    q('UPDATE admins SET password = ? WHERE id = ?', [bcrypt_hash((string) $new), $R->p('id')]);
    success('Password reset successfully');
}
