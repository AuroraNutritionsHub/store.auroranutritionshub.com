<?php
// Request object plus body parsing equivalent to express.json(), express.urlencoded() and multer.

class Req
{
    public string $method;
    public string $path;          // path relative to the app, e.g. /api/products/5
    public string $originalUrl;   // path + query string (used in "Route not found" messages)
    public array $query;
    public $body = [];
    public array $params = [];
    public ?array $file = null;   // multer req.file
    public array $files = [];     // multer req.files
    public ?array $admin = null;
    public ?array $user = null;
    public ?array $auth = null;
    private ?array $multipart = null;

    public static function fromGlobals(): self
    {
        $r = new self();
        $r->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $qs = parse_url($uri, PHP_URL_QUERY);

        // Strip the folder the app lives in (e.g. /backend-php when inside XAMPP htdocs)
        $base = self::basePath();
        if ($base !== '' && stripos($path, $base) === 0) $path = substr($path, strlen($base));
        if ($path === '' || $path[0] !== '/') $path = '/' . $path;

        $r->path = $path;
        $r->originalUrl = $path . ($qs !== null && $qs !== '' ? "?$qs" : '');
        $r->query = $_GET;
        return $r;
    }

    // URL folder of index.php: "" at a domain root, "/backend-php" inside htdocs
    private static function basePath(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (basename($script) === 'index.php') return rtrim(dirname($script), '/');

        // php -S reports the requested file as SCRIPT_NAME; derive the folder from the document root
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $appRoot = realpath(APP_ROOT);
        if ($docRoot && $appRoot && stripos($appRoot, $docRoot) === 0) {
            return rtrim(str_replace('\\', '/', substr($appRoot, strlen($docRoot))), '/');
        }
        return '';
    }

    public function contentType(): string
    {
        return strtolower($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    }

    // express.json() + express.urlencoded(): other content types leave the body as {}
    public function parseBody(): void
    {
        $type = $this->contentType();
        if (strpos($type, 'application/json') === 0) {
            $raw = file_get_contents('php://input');
            if (trim($raw) === '') return;
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) throw new HttpError('Invalid JSON: ' . json_last_error_msg(), 400);
            $this->body = $data;
        } elseif (strpos($type, 'application/x-www-form-urlencoded') === 0) {
            if ($this->method === 'POST' && $_POST) {
                $this->body = $_POST;
            } else {
                parse_str(file_get_contents('php://input'), $data);
                $this->body = $data;
            }
        }
    }

    public function isMultipart(): bool
    {
        return strpos($this->contentType(), 'multipart/form-data') === 0;
    }

    // Returns ['fields' => [...], 'files' => [[field, name, type, size, tmp|data, error], ...]] in request order
    public function multipart(): array
    {
        if ($this->multipart !== null) return $this->multipart;
        $raw = file_get_contents('php://input');
        if ($raw !== '' && $raw !== false) {
            $this->multipart = parse_multipart($raw, $this->contentType());
        } else {
            // enable_post_data_reading is on: fall back to PHP's own parsing
            // (note: PHP keeps only the last file when several share one field name without [])
            $files = [];
            foreach ($_FILES as $field => $f) {
                if (is_array($f['name'])) {
                    foreach ($f['name'] as $i => $n) {
                        $files[] = ['field' => $field, 'name' => $n, 'type' => $f['type'][$i], 'size' => $f['size'][$i], 'tmp' => $f['tmp_name'][$i], 'error' => $f['error'][$i]];
                    }
                } else {
                    $files[] = ['field' => $field, 'name' => $f['name'], 'type' => $f['type'], 'size' => $f['size'], 'tmp' => $f['tmp_name'], 'error' => $f['error']];
                }
            }
            $this->multipart = ['fields' => $_POST, 'files' => $files];
        }
        return $this->multipart;
    }

    public function b(string $key)
    {
        return is_array($this->body) && array_key_exists($key, $this->body) ? $this->body[$key] : null;
    }

    public function has(string $key): bool
    {
        return is_array($this->body) && array_key_exists($key, $this->body);
    }

    public function qp(string $key, $default = '')
    {
        return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
    }

    public function p(string $key)
    {
        return $this->params[$key] ?? null;
    }
}

function request_header(string $name): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    if (!empty($_SERVER['REDIRECT_' . $key])) return $_SERVER['REDIRECT_' . $key];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strcasecmp($k, $name) === 0) return $v;
        }
    }
    return '';
}

// Minimal RFC 7578 parser: keeps repeated field names (multer's upload.array('images'))
// and works for PUT, which PHP never parses on its own.
function parse_multipart(string $raw, string $contentType): array
{
    $fields = [];
    $files = [];
    if (!preg_match('/boundary=(?:"([^"]+)"|([^;]+))/i', $_SERVER['CONTENT_TYPE'] ?? $contentType, $m)) {
        return ['fields' => $fields, 'files' => $files];
    }
    $boundary = '--' . ($m[1] !== '' ? $m[1] : trim($m[2]));

    foreach (explode($boundary, $raw) as $part) {
        if ($part === '' || strpos($part, '--') === 0) continue;
        $part = preg_replace('/^\r\n/', '', $part);
        $split = strpos($part, "\r\n\r\n");
        if ($split === false) continue;
        $rawHeaders = substr($part, 0, $split);
        $content = substr($part, $split + 4);
        if (substr($content, -2) === "\r\n") $content = substr($content, 0, -2);

        $headers = [];
        foreach (explode("\r\n", $rawHeaders) as $h) {
            if (strpos($h, ':') !== false) {
                [$k, $v] = explode(':', $h, 2);
                $headers[strtolower(trim($k))] = trim($v);
            }
        }
        $disp = $headers['content-disposition'] ?? '';
        if (!preg_match('/\bname="([^"]*)"/i', $disp, $nm)) continue;
        $name = $nm[1];

        if (preg_match('/\bfilename="([^"]*)"/i', $disp, $fn)) {
            if ($fn[1] === '' && $content === '') continue; // empty <input type=file>
            $files[] = [
                'field' => $name,
                'name' => $fn[1],
                'type' => $headers['content-type'] ?? 'application/octet-stream',
                'size' => strlen($content),
                'data' => $content,
                'error' => UPLOAD_ERR_OK,
            ];
        } elseif (substr($name, -2) === '[]') {
            $fields[substr($name, 0, -2)][] = $content;
        } else {
            $fields[$name] = $content;
        }
    }
    return ['fields' => $fields, 'files' => $files];
}

// ---- multer equivalent -----------------------------------------------------

const ALLOWED_UPLOAD_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024; // 5MB

// UPLOADS_PATH (relative to the backend-php folder) overrides the default "uploads" folder
function uploads_root(): string
{
    $rel = env('UPLOADS_PATH', 'uploads');
    $root = preg_match('#^([a-zA-Z]:)?[\\\\/]#', $rel) ? $rel : APP_ROOT . '/' . $rel;
    return rtrim(str_replace('\\', '/', $root), '/');
}

// Uploaded images live in MySQL (`uploaded_files`) because the host can't grant the app write
// access to uploads/. Older images shipped in uploads/ are still served from disk first.
function ensure_uploads_table(): void
{
    static $done = false;
    if ($done) return;
    q('CREATE TABLE IF NOT EXISTS uploaded_files (
        id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        path varchar(255) NOT NULL,
        mime varchar(100) NOT NULL,
        size int(10) UNSIGNED NOT NULL,
        data longblob NOT NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id),
        UNIQUE KEY uq_uploaded_files_path (path)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $done = true;
}

// $path is relative to uploads/, e.g. "categories/1712-55.png"
function store_upload(string $path, string $mime, string $data): void
{
    ensure_uploads_table();
    // Sent as base64 so raw bytes never pass through the utf8mb4 connection as a string literal
    q('INSERT INTO uploaded_files (path, mime, size, data) VALUES (?, ?, ?, FROM_BASE64(?))',
        [$path, $mime, strlen($data), base64_encode($data)]);
}

// Removes an uploaded image from the database (images shipped in uploads/ are left alone)
function delete_upload(?string $path): void
{
    if (!$path) return;
    ensure_uploads_table();
    q('DELETE FROM uploaded_files WHERE path = ?', [$path]);
}

// Serves an uploaded image from the database; returns false when it isn't there
function serve_upload_from_db(string $path): bool
{
    ensure_uploads_table();
    $row = q1('SELECT mime, size, data FROM uploaded_files WHERE path = ?', [rawurldecode($path)]);
    if (!$row) return false;
    header('Content-Type: ' . $row['mime']);
    header('Content-Length: ' . $row['size']);
    // File names are unique per upload, so the browser can keep them
    header('Cache-Control: public, max-age=31536000, immutable');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo $row['data'];
    exit;
}

// path.extname: ".png" for "a.png", "" for ".png" or "a"
function js_extname(string $name): string
{
    $base = basename(str_replace('\\', '/', $name));
    $dot = strrpos($base, '.');
    return ($dot === false || $dot === 0) ? '' : substr($base, $dot);
}

// Returns a middleware like upload.single(field) / upload.array(field, max)
function upload(string $subfolder, string $field, int $maxCount, bool $single): callable
{
    return function (Req $R) use ($subfolder, $field, $maxCount, $single) {
        if (!$R->isMultipart()) return;
        $mp = $R->multipart();
        $R->body = $mp['fields'];

        $accepted = [];
        foreach ($mp['files'] as $f) {
            if ($f['field'] !== $field || count($accepted) >= $maxCount) error_res_plain('Unexpected file field', 400);
            if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > MAX_UPLOAD_SIZE) {
                error_res_plain('File too large', 400);
            }
            if ($f['error'] === UPLOAD_ERR_NO_FILE) continue;
            if ($f['error'] !== UPLOAD_ERR_OK) throw new HttpError('File upload failed', 500);
            if (!in_array($f['type'], ALLOWED_UPLOAD_TYPES, true)) {
                throw new HttpError('Only JPG, PNG and WEBP image files are allowed', 500);
            }
            $accepted[] = $f;
        }

        $saved = [];
        foreach ($accepted as $f) {
            $filename = (int) floor(microtime(true) * 1000) . '-' . random_int(0, 1000000000) . js_extname($f['name']);
            $data = $f['data'] ?? @file_get_contents($f['tmp']);
            if ($data === false) throw new HttpError('Could not save uploaded file', 500);
            store_upload("$subfolder/$filename", $f['type'], $data);
            if (!isset($f['data'])) @unlink($f['tmp']);
            $saved[] = [
                'fieldname' => $f['field'], 'originalname' => $f['name'], 'mimetype' => $f['type'],
                'size' => $f['size'], 'filename' => $filename, 'path' => "$subfolder/$filename",
            ];
        }

        if ($single) {
            $R->file = $saved[0] ?? null;
        } else {
            $R->files = $saved;
        }
    };
}

// Multer errors respond with { success, message } only (no "errors" key)
function error_res_plain(string $message, int $status): void
{
    send_json($status, ['success' => false, 'message' => $message]);
}
