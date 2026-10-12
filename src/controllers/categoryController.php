<?php

function getCategories(Req $R): void
{
    $search = $R->qp('search');
    $status = $R->qp('status');
    $where = 'WHERE 1=1';
    $params = [];
    if (t($search)) {
        $where .= ' AND name LIKE ?';
        $params[] = like($search);
    }
    if (t($status)) {
        $where .= ' AND status = ?';
        $params[] = $status;
    }
    $rows = q("SELECT * FROM categories $where ORDER BY name ASC", $params);
    success('Categories fetched', $rows);
}

function getCategoryById(Req $R): void
{
    $rows = q('SELECT * FROM categories WHERE id = ?', [$R->p('id')]);
    if (!$rows) error_res('Category not found', 404);
    success('Category fetched', $rows[0]);
}

function createCategory(Req $R): void
{
    $name = $R->b('name');
    if (!t($name)) error_res('Category name is required', 400);
    $image = $R->file ? 'categories/' . $R->file['filename'] : null;

    $result = q(
        'INSERT INTO categories (name, description, image, status) VALUES (?, ?, ?, ?)',
        [$name, orn($R->b('description')), $image, orv($R->b('status'), 'ACTIVE')]
    );
    success('Category created successfully', ['id' => $result['insertId']], 201);
}

function updateCategory(Req $R): void
{
    $existing = q('SELECT * FROM categories WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Category not found', 404);

    $image = $existing[0]['image'];
    if ($R->file) {
        delete_upload($image);
        $image = 'categories/' . $R->file['filename'];
    }

    q(
        'UPDATE categories SET name = ?, description = ?, image = ?, status = ? WHERE id = ?',
        [$R->b('name'), orn($R->b('description')), $image, $R->b('status'), $R->p('id')]
    );
    success('Category updated successfully');
}

function deleteCategory(Req $R): void
{
    $existing = q('SELECT id, image FROM categories WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Category not found', 404);
    q('DELETE FROM categories WHERE id = ?', [$R->p('id')]);
    delete_upload($existing[0]['image']);
    success('Category deleted successfully');
}
