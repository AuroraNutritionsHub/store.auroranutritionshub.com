<?php
// Payment settings (per gym) and shop settings (business identity on bills)

// GET /api/payment-settings/:gymId
function getPaymentSettings(Req $R): void
{
    $rows = q('SELECT * FROM payment_settings WHERE gym_id = ?', [$R->p('gymId')]);
    if (!$rows) success('No payment settings configured yet', null);
    success('Payment settings fetched', $rows[0]);
}

// PUT /api/payment-settings/:gymId
function updatePaymentSettings(Req $R): void
{
    $gymId = $R->p('gymId');

    $gym = q('SELECT id FROM gyms WHERE id = ?', [$gymId]);
    if (!$gym) error_res('Gym not found', 404);

    $existing = q('SELECT * FROM payment_settings WHERE gym_id = ?', [$gymId]);

    $qrImage = $existing ? $existing[0]['qr_image'] : null;
    if ($R->file) {
        delete_upload($qrImage);
        $qrImage = 'qr/' . $R->file['filename'];
    }

    $upi = orn($R->b('upi_id'));
    $account = orn($R->b('account_name'));
    $instructions = orn($R->b('instructions'));
    $status = orv($R->b('status'), 'ACTIVE');

    if ($existing) {
        q(
            'UPDATE payment_settings SET upi_id = ?, account_name = ?, qr_image = ?, instructions = ?, status = ? WHERE gym_id = ?',
            [$upi, $account, $qrImage, $instructions, $status, $gymId]
        );
    } else {
        q(
            'INSERT INTO payment_settings (gym_id, upi_id, account_name, qr_image, instructions, status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$gymId, $upi, $account, $qrImage, $instructions, $status]
        );
    }

    success('Payment settings saved successfully');
}

const ALLOWED_GST_PERCENTS = [5, 18];

const EMPTY_SHOP_SETTINGS = [
    'id' => 1, 'shop_name' => null, 'logo' => null, 'phone' => null, 'address' => null, 'gstin' => null, 'gst_percent' => null,
];

// GET /api/shop-settings  - the business identity printed on every bill (readable by admin panel staff)
function getShopSettings(Req $R): void
{
    $rows = q('SELECT * FROM shop_settings WHERE id = 1');
    success('Shop settings fetched', $rows[0] ?? EMPTY_SHOP_SETTINGS);
}

// PUT /api/shop-settings  - full admin only
function updateShopSettings(Req $R): void
{
    $raw = $R->b('gst_percent');
    $gstPercent = (!$R->has('gst_percent') || $raw === '') ? null : num($raw);
    if ($gstPercent !== null && !in_array($gstPercent, ALLOWED_GST_PERCENTS)) {
        error_res('GST percent must be 5 or 18', 400);
    }

    $existing = q('SELECT logo FROM shop_settings WHERE id = 1');
    $logo = $existing[0]['logo'] ?? null;
    if (!t($logo)) $logo = null;
    if ($R->file) {
        delete_upload($logo);
        $logo = 'shop/' . $R->file['filename'];
    }

    q(
        'INSERT INTO shop_settings (id, shop_name, logo, phone, address, gstin, gst_percent)
         VALUES (1, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           shop_name = VALUES(shop_name), logo = VALUES(logo), phone = VALUES(phone), address = VALUES(address),
           gstin = VALUES(gstin), gst_percent = VALUES(gst_percent)',
        [orn($R->b('shop_name')), $logo, orn($R->b('phone')), orn($R->b('address')), orn($R->b('gstin')), $gstPercent]
    );

    $rows = q('SELECT * FROM shop_settings WHERE id = 1');
    success('Shop settings updated successfully', $rows[0] ?? null);
}
