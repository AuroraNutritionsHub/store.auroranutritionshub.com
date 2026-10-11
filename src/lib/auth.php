<?php
// JWT (HS256, same format as the Node `jsonwebtoken` package) and the auth middlewares.
// Tokens issued by the Node backend keep working here and vice versa when JWT_SECRET matches.

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data)
{
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4), true);
}

// "7d", "12h", "30m", "45s", "2w", "1y" or plain seconds
function expires_in_seconds($value): int
{
    if (is_numeric($value)) return (int) $value;
    if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*(ms|s|sec|secs|m|min|mins|h|hr|hrs|d|day|days|w|week|weeks|y|yr|yrs)?\s*$/i', (string) $value, $m)) {
        $n = (float) $m[1];
        $unit = strtolower($m[2] ?? 'ms');
        $mult = [
            'ms' => 0.001, 's' => 1, 'sec' => 1, 'secs' => 1, 'm' => 60, 'min' => 60, 'mins' => 60,
            'h' => 3600, 'hr' => 3600, 'hrs' => 3600, 'd' => 86400, 'day' => 86400, 'days' => 86400,
            'w' => 604800, 'week' => 604800, 'weeks' => 604800, 'y' => 31557600, 'yr' => 31557600, 'yrs' => 31557600,
        ][$unit];
        return (int) floor($n * $mult);
    }
    return 7 * 86400;
}

function jwt_secret(): string
{
    $secret = env('JWT_SECRET');
    if (!$secret) throw new HttpError('secretOrPrivateKey must have a value', 500);
    return $secret;
}

function generate_token(array $payload): string
{
    $now = time();
    $payload['iat'] = $now;
    $payload['exp'] = $now + expires_in_seconds(env('JWT_EXPIRES_IN', '7d'));
    $header = b64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $body = b64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $sig = b64url_encode(hash_hmac('sha256', "$header.$body", jwt_secret(), true));
    return "$header.$body.$sig";
}

function verify_token(): ?array
{
    $header = request_header('Authorization');
    if (strpos($header, 'Bearer ') !== 0) return null;
    $parts = explode('.', substr($header, 7));
    if (count($parts) !== 3) return null;

    $head = json_decode((string) b64url_decode($parts[0]), true);
    if (!is_array($head) || ($head['alg'] ?? '') !== 'HS256') return null;

    $expected = b64url_encode(hash_hmac('sha256', "$parts[0].$parts[1]", jwt_secret(), true));
    if (!hash_equals($expected, $parts[2])) return null;

    $payload = json_decode((string) b64url_decode($parts[1]), true);
    if (!is_array($payload)) return null;
    $now = time();
    if (isset($payload['exp']) && $now >= $payload['exp']) return null;
    if (isset($payload['nbf']) && $now < $payload['nbf']) return null;
    return $payload;
}

// Protects routes that any logged-in admin-panel staff (full admin or biller) may call
function adminAuth(Req $R): void
{
    $decoded = verify_token();
    if (!$decoded || (($decoded['role'] ?? null) !== 'admin' && ($decoded['role'] ?? null) !== 'biller')) {
        error_res('Unauthorized. Admin login required.', 401);
    }
    $R->admin = $decoded;
}

// Further restricts an adminAuth-protected route to full admins only (not billers)
function requireAdmin(Req $R): void
{
    if (($R->admin['role'] ?? null) !== 'admin') {
        error_res('Forbidden. This action requires an administrator account.', 403);
    }
}

// Protects routes that only logged-in gym users (mobile app) may call
function userAuth(Req $R): void
{
    $decoded = verify_token();
    if (!$decoded || ($decoded['role'] ?? null) !== 'user') {
        error_res('Unauthorized. Please login again.', 401);
    }
    $R->user = $decoded; // { id, gym_id, role }
}

// Protects routes shared by full admins and mobile users, controller branches on $R->auth['role']
function anyAuth(Req $R): void
{
    $decoded = verify_token();
    if (!$decoded || (($decoded['role'] ?? null) !== 'admin' && ($decoded['role'] ?? null) !== 'user')) {
        error_res('Unauthorized. Please login again.', 401);
    }
    $R->auth = $decoded;
}

// Protects routes shared by any logged-in party: full admin, biller or mobile user
function staffOrUserAuth(Req $R): void
{
    $decoded = verify_token();
    if (!$decoded || !in_array($decoded['role'] ?? null, ['admin', 'biller', 'user'], true)) {
        error_res('Unauthorized. Please login again.', 401);
    }
    $R->auth = $decoded;
}

// bcrypt with the "$2a$" prefix bcryptjs writes, so both backends can read each other's hashes
function bcrypt_hash(string $password): string
{
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    return '$2a$' . substr($hash, 4);
}

function bcrypt_compare($password, $hash): bool
{
    return is_string($hash) && password_verify((string) $password, $hash);
}
