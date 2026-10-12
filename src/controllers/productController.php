<?php

function attachVariants(array $products): array
{
    if (!$products) return $products;
    $ids = array_column($products, 'id');
    $variants = q('SELECT * FROM product_variants WHERE product_id IN (?) ORDER BY price ASC', [$ids]);
    return array_map(function ($p) use ($variants) {
        $p['variants'] = array_values(array_filter($variants, fn($v) => $v['product_id'] === $p['id']));
        return $p;
    }, $products);
}

function attachImages(array $products): array
{
    if (!$products) return $products;
    $ids = array_column($products, 'id');
    $images = q('SELECT * FROM product_images WHERE product_id IN (?) ORDER BY sort_order ASC, id ASC', [$ids]);
    return array_map(function ($p) use ($images) {
        $p['images'] = array_values(array_filter($images, fn($img) => $img['product_id'] === $p['id']));
        return $p;
    }, $products);
}

// Saves any uploaded files (field "images") as additional gallery photos for a product.
// If the product has no cover image yet, the first uploaded file becomes the cover
// (products.image) instead of a gallery row.
function saveUploadedImages($productId, array $files, bool $hasCoverAlready): void
{
    if (!$files) return;
    $remaining = $files;

    if (!$hasCoverAlready) {
        $cover = array_shift($remaining);
        q('UPDATE products SET image = ? WHERE id = ?', ['products/' . $cover['filename'], $productId]);
    }

    if ($remaining) {
        $maxOrder = q('SELECT COALESCE(MAX(sort_order), 0) AS maxOrder FROM product_images WHERE product_id = ?', [$productId])[0]['maxOrder'];
        $values = [];
        foreach (array_values($remaining) as $i => $f) {
            $values[] = [$productId, 'products/' . $f['filename'], $maxOrder + $i + 1];
        }
        q('INSERT INTO product_images (product_id, image, sort_order) VALUES ?', [$values]);
    }
}

// GET /api/products
function getProducts(Req $R): void
{
    $search = $R->qp('search');
    $status = $R->qp('status');
    $categoryId = $R->qp('category_id');
    $page = $R->qp('page', 1);
    $limit = $R->qp('limit', 20);
    $offset = (num($page) - 1) * num($limit);

    $where = 'WHERE 1=1';
    $params = [];
    if (t($search)) {
        $where .= ' AND (p.name LIKE ? OR p.brand LIKE ?)';
        array_push($params, like($search), like($search));
    }
    if (t($status)) {
        $where .= ' AND p.status = ?';
        $params[] = $status;
    }
    if (t($categoryId)) {
        $where .= ' AND p.category_id = ?';
        $params[] = $categoryId;
    }

    $rows = q(
        "SELECT p.*, c.name AS category_name
         FROM products p JOIN categories c ON c.id = p.category_id
         $where ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [num($limit), $offset])
    );
    $count = q("SELECT COUNT(*) AS total FROM products p JOIN categories c ON c.id = p.category_id $where", $params);

    $products = attachImages(attachVariants($rows));
    success('Products fetched', ['products' => $products, 'total' => $count[0]['total'], 'page' => num($page), 'limit' => num($limit)]);
}

// GET /api/products/:id
function getProductById(Req $R): void
{
    $rows = q(
        'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?',
        [$R->p('id')]
    );
    if (!$rows) error_res('Product not found', 404);
    $products = attachImages(attachVariants($rows));
    success('Product fetched', $products[0]);
}

// POST /api/products  (multipart, field "images" - up to 5 files)
function createProduct(Req $R): void
{
    $categoryId = $R->b('category_id');
    $name = $R->b('name');
    if (!t($categoryId) || !t($name)) error_res('Category and product name are required', 400);

    $files = $R->files;
    $coverImage = $files ? 'products/' . $files[0]['filename'] : null;

    $result = q(
        'INSERT INTO products (category_id, name, brand, description, image, status) VALUES (?, ?, ?, ?, ?, ?)',
        [$categoryId, $name, orn($R->b('brand')), orn($R->b('description')), $coverImage, orv($R->b('status'), 'ACTIVE')]
    );
    $productId = $result['insertId'];

    if (count($files) > 1) {
        $values = [];
        foreach (array_slice($files, 1) as $i => $f) {
            $values[] = [$productId, 'products/' . $f['filename'], $i + 1];
        }
        q('INSERT INTO product_images (product_id, image, sort_order) VALUES ?', [$values]);
    }

    success('Product created successfully', ['id' => $productId], 201);
}

// PUT /api/products/:id  (multipart, field "images" - any new files are appended to the gallery)
function updateProduct(Req $R): void
{
    $existing = q('SELECT * FROM products WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Product not found', 404);

    q(
        'UPDATE products SET category_id = ?, name = ?, brand = ?, description = ?, status = ? WHERE id = ?',
        [$R->b('category_id'), $R->b('name'), orn($R->b('brand')), orn($R->b('description')), $R->b('status'), $R->p('id')]
    );

    saveUploadedImages($R->p('id'), $R->files, t($existing[0]['image']));

    success('Product updated successfully');
}

// DELETE /api/products/:id
function deleteProduct(Req $R): void
{
    $existing = q('SELECT id, image FROM products WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Product not found', 404);
    $gallery = q('SELECT image FROM product_images WHERE product_id = ?', [$R->p('id')]);
    q('DELETE FROM products WHERE id = ?', [$R->p('id')]);
    delete_upload($existing[0]['image']);
    foreach ($gallery as $img) delete_upload($img['image']);
    success('Product deleted successfully');
}

// DELETE /api/products/images/:imageId  (removes one gallery photo)
function deleteProductImage(Req $R): void
{
    $rows = q('SELECT * FROM product_images WHERE id = ?', [$R->p('imageId')]);
    if (!$rows) error_res('Image not found', 404);

    delete_upload($rows[0]['image']);

    q('DELETE FROM product_images WHERE id = ?', [$R->p('imageId')]);
    success('Image removed');
}

// GET /api/products/:id/variants
function getVariants(Req $R): void
{
    $rows = q('SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC', [$R->p('id')]);
    success('Variants fetched', $rows);
}

// POST /api/products/:id/variants
function createVariant(Req $R): void
{
    $variantName = $R->b('variant_name');
    if (!t($variantName) || !$R->has('price')) error_res('Variant name and selling price are required', 400);

    $product = q('SELECT id FROM products WHERE id = ?', [$R->p('id')]);
    if (!$product) error_res('Product not found', 404);

    $result = q(
        'INSERT INTO product_variants (product_id, variant_name, unit, mrp, price, onsite_price, stock, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$R->p('id'), $variantName, orn($R->b('unit')), orn($R->b('mrp')), $R->b('price'), orn($R->b('onsite_price')), orv($R->b('stock'), 0), orv($R->b('status'), 'ACTIVE')]
    );

    success('Variant created successfully', ['id' => $result['insertId']], 201);
}

// PUT /api/variants/:id
function updateVariant(Req $R): void
{
    $existing = q('SELECT id FROM product_variants WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Variant not found', 404);

    q(
        'UPDATE product_variants SET variant_name = ?, unit = ?, mrp = ?, price = ?, onsite_price = ?, stock = ?, status = ? WHERE id = ?',
        [$R->b('variant_name'), orn($R->b('unit')), orn($R->b('mrp')), $R->b('price'), orn($R->b('onsite_price')), orv($R->b('stock'), 0), $R->b('status'), $R->p('id')]
    );

    success('Variant updated successfully');
}

// DELETE /api/variants/:id
function deleteVariant(Req $R): void
{
    $existing = q('SELECT id FROM product_variants WHERE id = ?', [$R->p('id')]);
    if (!$existing) error_res('Variant not found', 404);
    q('DELETE FROM product_variants WHERE id = ?', [$R->p('id')]);
    success('Variant deleted successfully');
}
