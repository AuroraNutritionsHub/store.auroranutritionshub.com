<?php
// Socket.IO (Engine.IO v4) long-polling endpoint so the admin panel's live notifications keep
// working unchanged. PHP can't hold websockets on shared hosting, so the handshake offers no
// upgrades; the client stays on HTTP long-polling. New rows in `notifications` are pushed as
// "new_notification" events to sockets in the matching room ("admin" or "user:<id>").

const SOCKET_PING_INTERVAL = 25000;
const SOCKET_PING_TIMEOUT = 20000;
const SOCKET_SESSION_TTL = 60; // seconds without any request before a session is dropped

function socket_dir(): string
{
    $dir = APP_ROOT . '/storage/sockets';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return $dir;
}

function socket_file(string $sid): string
{
    return socket_dir() . '/' . preg_replace('/[^A-Za-z0-9_-]/', '', $sid) . '.json';
}

// Runs $fn(session) under an exclusive lock and saves what it returns (null deletes the session)
function socket_update(string $sid, callable $fn): ?array
{
    $file = socket_file($sid);
    if (!is_file($file)) return null;
    $fh = fopen($file, 'c+');
    if (!$fh) return null;
    flock($fh, LOCK_EX);
    $session = json_decode(stream_get_contents($fh), true);
    $result = null;
    if (is_array($session) && time() - ($session['last_seen'] ?? 0) <= SOCKET_SESSION_TTL) {
        $result = $fn($session);
    }
    if ($result === null) {
        flock($fh, LOCK_UN);
        fclose($fh);
        @unlink($file);
        return null;
    }
    $result['last_seen'] = time();
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($result));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function socket_text(int $status, string $body, string $type = 'text/plain; charset=UTF-8'): void
{
    http_response_code($status);
    header("Content-Type: $type");
    echo $body;
    exit;
}

function socket_error(int $code, string $message): void
{
    socket_text(400, json_encode(['code' => $code, 'message' => $message]), 'application/json');
}

function handle_socket(Req $R): void
{
    header('Access-Control-Allow-Origin: *');
    if ($R->method === 'OPTIONS') {
        header('Access-Control-Allow-Methods: GET,HEAD,PUT,PATCH,POST,DELETE');
        $reqHeaders = request_header('Access-Control-Request-Headers');
        if ($reqHeaders !== '') header("Access-Control-Allow-Headers: $reqHeaders");
        http_response_code(204);
        exit;
    }

    if (($R->query['EIO'] ?? null) !== '4') socket_error(5, 'Unsupported protocol version');
    if (($R->query['transport'] ?? null) !== 'polling') socket_error(0, 'Transport unknown');

    $sid = $R->query['sid'] ?? null;
    if (!$sid) {
        if ($R->method !== 'GET') socket_error(3, 'Bad request');
        socket_handshake();
    }

    if (!is_file(socket_file($sid))) socket_error(1, 'Session ID unknown');
    if ($R->method === 'POST') socket_receive($sid);
    if ($R->method === 'GET') socket_poll($sid);
    socket_error(2, 'Bad handshake method');
}

function socket_handshake(): void
{
    // Drop stale sessions
    foreach (glob(socket_dir() . '/*.json') ?: [] as $f) {
        if (filemtime($f) < time() - SOCKET_SESSION_TTL) @unlink($f);
    }

    $sid = b64url_encode(random_bytes(15));
    $session = [
        'socket_id' => b64url_encode(random_bytes(15)),
        'connected' => false,
        'rooms' => [],
        'queue' => [],
        'last_notification_id' => (int) q('SELECT COALESCE(MAX(id), 0) AS id FROM notifications')[0]['id'],
        'last_seen' => time(),
    ];
    file_put_contents(socket_file($sid), json_encode($session), LOCK_EX);

    socket_text(200, '0' . json_encode([
        'sid' => $sid,
        'upgrades' => [],
        'pingInterval' => SOCKET_PING_INTERVAL,
        'pingTimeout' => SOCKET_PING_TIMEOUT,
        'maxPayload' => 1000000,
    ]));
}

// Client -> server packets (separated by \x1e)
function socket_receive(string $sid): void
{
    $packets = explode("\x1e", (string) file_get_contents('php://input'));
    socket_update($sid, function (array $s) use ($packets) {
        foreach ($packets as $p) {
            if ($p === '') continue;
            $type = $p[0];
            if ($type === '1') return null;              // close
            if ($type !== '4') continue;                 // 3 = pong, nothing to do
            $sio = substr($p, 1);
            $sioType = $sio[0] ?? '';
            $rest = substr($sio, 1);
            $nsp = '/';
            if (($rest[0] ?? '') === '/') {
                $comma = strpos($rest, ',');
                $nsp = $comma === false ? $rest : substr($rest, 0, $comma);
                $rest = $comma === false ? '' : substr($rest, $comma + 1);
            }

            if ($sioType === '0') {                      // CONNECT
                if ($nsp !== '/') {
                    $s['queue'][] = '44' . $nsp . ',' . json_encode(['message' => 'Invalid namespace']);
                } else {
                    $s['connected'] = true;
                    $s['queue'][] = '40' . json_encode(['sid' => $s['socket_id']]);
                }
            } elseif ($sioType === '1') {                // DISCONNECT
                $s['connected'] = false;
                $s['rooms'] = [];
            } elseif ($sioType === '2') {                // EVENT ["name", ...args]
                $rest = preg_replace('/^\d+/', '', $rest); // ack id
                $event = json_decode($rest, true);
                if (!is_array($event) || !isset($event[0])) continue;
                $arg = $event[1] ?? null;
                $room = null;
                if ($event[0] === 'join_admin') $room = 'admin';
                if ($event[0] === 'join_gym') $room = 'gym:' . js_str($arg);
                if ($event[0] === 'join_user') $room = 'user:' . js_str($arg);
                if ($room && !in_array($room, $s['rooms'], true)) $s['rooms'][] = $room;
            }
        }
        return $s;
    });

    socket_text(200, 'ok', 'text/html');
}

function socket_notifications(array $s): array
{
    $conditions = [];
    $params = [$s['last_notification_id']];
    $userIds = [];
    foreach ($s['rooms'] as $room) {
        if ($room === 'admin') $conditions[] = "audience = 'ADMIN'";
        if (strpos($room, 'user:') === 0) $userIds[] = substr($room, 5);
    }
    if ($userIds) {
        $conditions[] = "(audience = 'USER' AND user_id IN (?))";
        $params[] = $userIds;
    }
    if (!$conditions) return [];

    return q(
        'SELECT id, gym_id, user_id, order_id, title, message, type, audience, UNIX_TIMESTAMP(created_at) AS ts
         FROM notifications WHERE id > ? AND (' . implode(' OR ', $conditions) . ') ORDER BY id ASC LIMIT 50',
        $params
    );
}

// Server -> client: waits until there is something to send, or answers with a ping
function socket_poll(string $sid): void
{
    $wait = (int) env('SOCKET_POLL_SECONDS', 20);
    @set_time_limit($wait + 15);
    $deadline = time() + $wait;

    do {
        $out = [];
        $session = socket_update($sid, function (array $s) use (&$out) {
            $out = $s['queue'];
            $s['queue'] = [];
            if ($s['connected'] && $s['rooms']) {
                foreach (socket_notifications($s) as $n) {
                    $ts = (int) $n['ts'];
                    unset($n['ts']);
                    $n['is_read'] = 0;
                    $n['created_at'] = gmdate('Y-m-d\TH:i:s.000\Z', $ts);
                    $out[] = '42' . json_encode(['new_notification', $n], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $s['last_notification_id'] = $n['id'];
                }
            } else {
                // Not in a room yet: skip notifications that exist before joining
                $s['last_notification_id'] = (int) q('SELECT COALESCE(MAX(id), 0) AS id FROM notifications')[0]['id'];
            }
            return $s;
        });

        if ($session === null) socket_error(1, 'Session ID unknown');
        if ($out) socket_text(200, implode("\x1e", $out));
        if (time() >= $deadline) break;
        sleep(1);
    } while (true);

    socket_text(200, '2'); // ping; the client answers with "3" (pong) on its next POST
}
