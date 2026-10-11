<?php
// Core helpers: .env loading, JSON responses, errors and JavaScript-style value checks.
// The helpers mirror the Node backend's behaviour so responses stay byte-for-byte compatible.

class HttpError extends Exception
{
    public int $status;

    public function __construct(string $message, int $status = 500)
    {
        parent::__construct($message);
        $this->status = $status;
    }
}

function load_env(string $file): void
{
    if (!is_file($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        // Like dotenv: real environment variables win over the file
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, $default = null)
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function send_json(int $status, $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

function success(string $message, $data = null, int $status = 200): void
{
    send_json($status, ['success' => true, 'message' => $message, 'data' => $data]);
}

function error_res(string $message, int $status = 400, $errors = null): void
{
    send_json($status, ['success' => false, 'message' => $message, 'errors' => $errors]);
}

// ---- JavaScript-compatible value helpers ---------------------------------

// JS truthiness: '', 0, null, false and NaN are falsy; "0" and [] are truthy
function t($v): bool
{
    if ($v === null || $v === false || $v === '' || $v === 0) return false;
    if (is_float($v)) return $v != 0.0 && !is_nan($v);
    return true;
}

// JS `v || null`
function orn($v)
{
    return t($v) ? $v : null;
}

// JS `v || fallback`
function orv($v, $fallback)
{
    return t($v) ? $v : $fallback;
}

// JS Number(v): ints stay ints, numeric strings become int/float, anything else NaN
function num($v)
{
    if (is_int($v) || is_float($v)) return $v;
    if ($v === null || $v === false) return 0;
    if ($v === true) return 1;
    if (is_string($v)) {
        $s = trim($v);
        if ($s === '') return 0;
        if (preg_match('/^-?\d+$/', $s) && strlen($s) < 16) return (int) $s;
        if (is_numeric($s)) return (float) $s;
    }
    return NAN;
}

// JS Number(x.toFixed(2))
function r2($x)
{
    $r = round((float) $x, 2);
    return ($r == (int) $r && abs($r) < PHP_INT_MAX) ? (int) $r : $r;
}

// JS Array.isArray for decoded JSON
function is_list_array($v): bool
{
    if (!is_array($v)) return false;
    return $v === [] || array_keys($v) === range(0, count($v) - 1);
}

// JS template-literal string conversion (null -> "null")
function js_str($v): string
{
    if ($v === null) return 'null';
    if ($v === true) return 'true';
    if ($v === false) return 'false';
    return (string) $v;
}

function like(string $s): string
{
    return "%$s%";
}
