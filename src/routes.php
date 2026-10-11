<?php
// Same routes, middleware and order as backend/src/routes/*.js (mounted under /api)

function build_router(): Router
{
    $r = new Router();
    $A = 'adminAuth';
    $ADM = 'requireAdmin';

    // authRoutes (mounted at "/")
    $r->mount('/', [], [
        ['POST', '/admin/login', ['adminLogin']],
        ['PUT', '/admin/profile', [$A, 'updateAdminProfile']],
        ['PUT', '/admin/change-password', [$A, 'adminChangePassword']],
        ['GET', '/admin/staff', [$A, $ADM, 'getStaff']],
        ['POST', '/admin/staff', [$A, $ADM, 'createStaff']],
        ['PUT', '/admin/staff/:id/status', [$A, $ADM, 'updateStaffStatus']],
        ['PUT', '/admin/staff/:id/reset-password', [$A, $ADM, 'resetStaffPassword']],
        ['POST', '/auth/login', ['userLogin']],
        ['POST', '/auth/change-password', ['userAuth', 'changePassword']],
        ['GET', '/auth/me', ['userAuth', 'getProfile']],
    ]);

    // Billers can list gyms (needed for the onsite billing gym picker) but nothing else here
    $r->mount('/gyms', [$A], [
        ['GET', '/', ['getGyms']],
        ['POST', '/', [$ADM, upload('gyms', 'logo', 1, true), 'createGym']],
        ['GET', '/:id', [$ADM, 'getGymById']],
        ['PUT', '/:id', [$ADM, upload('gyms', 'logo', 1, true), 'updateGym']],
        ['DELETE', '/:id', [$ADM, 'deleteGym']],
        ['GET', '/:gymId/products', [$ADM, 'getGymProducts']],
        ['POST', '/:gymId/products', [$ADM, 'setGymProducts']],
        ['PUT', '/:gymId/products/:productId', [$ADM, 'toggleGymProduct']],
    ]);

    $r->mount('/users', [$A, $ADM], [
        ['GET', '/', ['getUsers']],
        ['POST', '/', ['createUser']],
        ['GET', '/:id', ['getUserById']],
        ['PUT', '/:id', ['updateUser']],
        ['DELETE', '/:id', ['deleteUser']],
        ['PUT', '/:id/reset-password', ['resetUserPassword']],
    ]);

    $r->mount('/categories', [$A, $ADM], [
        ['GET', '/', ['getCategories']],
        ['POST', '/', [upload('categories', 'image', 1, true), 'createCategory']],
        ['GET', '/:id', ['getCategoryById']],
        ['PUT', '/:id', [upload('categories', 'image', 1, true), 'updateCategory']],
        ['DELETE', '/:id', ['deleteCategory']],
    ]);

    $r->mount('/products', [$A, $ADM], [
        ['GET', '/', ['getProducts']],
        ['POST', '/', [upload('products', 'images', 5, false), 'createProduct']],
        ['GET', '/:id', ['getProductById']],
        ['PUT', '/:id', [upload('products', 'images', 5, false), 'updateProduct']],
        ['DELETE', '/:id', ['deleteProduct']],
        ['DELETE', '/images/:imageId', ['deleteProductImage']],
        ['GET', '/:id/variants', ['getVariants']],
        ['POST', '/:id/variants', ['createVariant']],
    ]);

    $r->mount('/variants', [], [
        ['PUT', '/:id', [$A, $ADM, 'updateVariant']],
        ['DELETE', '/:id', [$A, $ADM, 'deleteVariant']],
    ]);

    $r->mount('/payment-settings', [], [
        ['GET', '/:gymId', [$A, $ADM, 'getPaymentSettings']],
        ['PUT', '/:gymId', [$A, $ADM, upload('qr', 'qr_image', 1, true), 'updatePaymentSettings']],
    ]);

    $r->mount('/cart', ['userAuth'], [
        ['GET', '/', ['getCart']],
        ['POST', '/', ['addToCart']],
        ['PUT', '/items/:id', ['updateCartItem']],
        ['DELETE', '/items/:id', ['removeCartItem']],
        ['DELETE', '/', ['clearCart']],
    ]);

    // staffOrUserAuth (not anyAuth) on GET /:id so a biller can open/print the bill they just created
    $r->mount('/orders', [], [
        ['POST', '/', ['userAuth', 'createOrder']],
        ['GET', '/', ['anyAuth', 'getOrders']],
        ['GET', '/:id', ['staffOrUserAuth', 'getOrderById']],
        ['PUT', '/:id/status', [$A, $ADM, 'updateOrderStatus']],
        ['POST', '/:orderId/payment', ['userAuth', upload('payments', 'screenshot', 1, true), 'submitPayment']],
        ['GET', '/:orderId/payment', ['staffOrUserAuth', 'getPayment']],
    ]);

    $r->mount('/payments', [], [
        ['PUT', '/:id/verify', [$A, $ADM, 'verifyPayment']],
        ['PUT', '/:id/reject', [$A, $ADM, 'rejectPayment']],
    ]);

    $r->mount('/notifications', ['staffOrUserAuth'], [
        ['GET', '/', ['getNotifications']],
        ['PUT', '/read-all', ['markAllAsRead']],
        ['PUT', '/:id/read', ['markAsRead']],
    ]);

    $r->mount('/dashboard', [$A, $ADM], [
        ['GET', '/summary', ['getSummary']],
        ['GET', '/orders', ['getOrdersOverview']],
        ['GET', '/revenue', ['getRevenueOverview']],
        ['GET', '/category-sales', ['getCategorySales']],
        ['GET', '/top-products', ['getTopProducts']],
        ['GET', '/order-status', ['getOrderStatusBreakdown']],
        ['GET', '/recent-orders', ['getRecentOrders']],
        ['GET', '/report', ['getReport']],
    ]);

    $r->mount('/app', ['userAuth'], [
        ['GET', '/categories', ['getMyCategories']],
        ['GET', '/products', ['getMyProducts']],
        ['GET', '/products/:id', ['getMyProductById']],
        ['GET', '/payment-settings', ['getMyPaymentSettings']],
    ]);

    $r->mount('/onsite', [$A], [
        ['GET', '/categories', ['getOnsiteCategories']],
        ['GET', '/products', ['getOnsiteProducts']],
        ['POST', '/bill', ['createOnsiteBill']],
        ['GET', '/recent-bills', ['getRecentBills']],
        ['GET', '/customers', ['searchCustomers']],
    ]);

    // Admin and biller can both read it (needed for sidebar branding + printed bills)
    $r->mount('/shop-settings', [], [
        ['GET', '/', [$A, 'getShopSettings']],
        ['PUT', '/', [$A, $ADM, upload('shop', 'logo', 1, true), 'updateShopSettings']],
    ]);

    $r->mount('/complaints', [], [
        ['POST', '/', ['userAuth', 'createComplaint']],
        ['GET', '/', ['anyAuth', 'getComplaints']],
        ['GET', '/:id', ['anyAuth', 'getComplaintById']],
        ['PUT', '/:id/status', [$A, $ADM, 'updateComplaintStatus']],
    ]);

    return $r;
}
