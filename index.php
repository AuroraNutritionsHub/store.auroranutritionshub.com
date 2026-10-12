<?php
// Gym Store API - PHP version of backend/ (same routes, request and response formats).
// Every request is routed here by .htaccess (Apache) or `php -S localhost:8000 index.php`.

define('APP_ROOT', str_replace('\\', '/', __DIR__));

require APP_ROOT . '/src/lib/core.php';
require APP_ROOT . '/src/lib/db.php';
require APP_ROOT . '/src/lib/request.php';
require APP_ROOT . '/src/lib/auth.php';
require APP_ROOT . '/src/lib/router.php';
require APP_ROOT . '/src/services/services.php';
foreach (glob(APP_ROOT . '/src/controllers/*.php') as $controller) require $controller;
require APP_ROOT . '/src/routes.php';

load_env(APP_ROOT . '/.env');
ini_set('display_errors', '0');

const MIME_TYPES = [
    'html' => 'text/html; charset=UTF-8', 'js' => 'application/javascript; charset=UTF-8', 'css' => 'text/css; charset=UTF-8',
    'json' => 'application/json', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
    'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
    'ttf' => 'font/ttf', 'txt' => 'text/plain; charset=UTF-8', 'map' => 'application/json',
];

// Serves $relative from inside $root (no path traversal); returns false when missing
function serve_static(string $root, string $relative): bool
{
    $base = realpath($root);
    $file = $base ? realpath($base . '/' . rawurldecode(ltrim($relative, '/'))) : false;
    if (!$base || !$file || !is_file($file) || strpos(str_replace('\\', '/', $file), str_replace('\\', '/', $base) . '/') !== 0) {
        return false;
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . (MIME_TYPES[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: public, max-age=0');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') readfile($file);
    exit;
}

function not_found(Req $R): void
{
    send_json(404, ['success' => false, 'message' => 'Route not found: ' . $R->originalUrl]);
}

function handle_error(Throwable $e): void
{
    error_log('[gym-store-api] ' . $e);
    if ($e instanceof PDOException) {
        $code = $e->errorInfo[1] ?? null;
        if ($code === 1062) send_json(409, ['success' => false, 'message' => 'Duplicate entry. This record already exists.']);
        if ($code === 1452 || $code === 1216) send_json(400, ['success' => false, 'message' => 'Referenced record does not exist.']);
        if ($code === 1451 || $code === 1217) send_json(409, ['success' => false, 'message' => 'This record is in use and cannot be deleted.']);
        send_json(500, ['success' => false, 'message' => $e->errorInfo[2] ?? $e->getMessage()]);
    }
    $status = $e instanceof HttpError ? $e->status : 500;
    send_json($status, ['success' => false, 'message' => $e->getMessage() ?: 'Internal server error']);
}

$R = Req::fromGlobals();

try {
    $path = $R->path;

    // cors()
    header('Access-Control-Allow-Origin: *');
    if ($R->method === 'OPTIONS') {
        header('Access-Control-Allow-Methods: GET,HEAD,PUT,PATCH,POST,DELETE');
        $reqHeaders = request_header('Access-Control-Request-Headers');
        if ($reqHeaders !== '') {
            header("Access-Control-Allow-Headers: $reqHeaders");
            header('Vary: Access-Control-Request-Headers');
        }
        header('Content-Length: 0');
        http_response_code(204);
        exit;
    }

    $R->parseBody();

    // Serve uploaded images/files
    if (in_array($R->method, ['GET', 'HEAD'], true) && preg_match('#^/uploads/(.+)$#i', $path, $m)) {
        serve_static(uploads_root(), $m[1]);
    }

    if (in_array($R->method, ['GET', 'HEAD'], true) && preg_match('#^/api/health/?$#i', $path)) {
        send_json(200, ['success' => true, 'message' => 'Gym Store API is running']);
    }

    if (preg_match('#^/api(/.*)?$#i', $path, $m)) {
        if (build_router()->dispatch($R, $m[1] ?? '/')) exit;
    }

    // Optional admin panel build in public/ (same as the Node server): static files, then
    // index.html for any other GET so React Router routes survive a page refresh.
    if (in_array($R->method, ['GET', 'HEAD'], true)) {
        $publicDir = APP_ROOT . '/public';
        if ($path !== '/') serve_static($publicDir, $path);
        if (preg_match('#^/(?!api/|uploads/|socket\.io/)#i', $path)) serve_static($publicDir, 'index.html');
    }

    not_found($R);
} catch (Throwable $e) {
    handle_error($e);
}
