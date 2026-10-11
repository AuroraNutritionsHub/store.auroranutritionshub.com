<?php
// MySQL access with the same behaviour as the Node backend's mysql2 pool:
// ints come back as numbers, DECIMAL and dates as strings, `IN (?)` / bulk `VALUES ?`
// accept arrays, and UPDATE reports matched rows (mysql2's FOUND_ROWS default).

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    $url = env('DATABASE_URL');
    if ($url) {
        $u = parse_url($url);
        $host = $u['host'] ?? 'localhost';
        $port = $u['port'] ?? 3306;
        $name = ltrim($u['path'] ?? '', '/');
        $user = urldecode($u['user'] ?? '');
        $pass = urldecode($u['pass'] ?? '');
    } else {
        $host = env('DB_HOST', 'localhost');
        $port = env('DB_PORT', 3306);
        $name = env('DB_NAME', 'gym_store');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASSWORD', '');
    }

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Send the final SQL text like mysql2's pool.query() does, so MySQL picks the same plan
        // (same row order for ORDER BY ties). PHP 8.1+ still returns ints as ints in this mode.
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
    ]);
    return $pdo;
}

// Expands array params: [1,2,3] -> "?,?,?" and [[a,b],[c,d]] -> "(?,?),(?,?)"
function expand_params(string $sql, array $params): array
{
    $out = '';
    $flat = [];
    $i = 0;
    $len = strlen($sql);
    for ($pos = 0; $pos < $len; $pos++) {
        $ch = $sql[$pos];
        if ($ch !== '?') {
            $out .= $ch;
            continue;
        }
        $p = $params[$i++] ?? null;
        if (is_array($p)) {
            if ($p === []) {
                $out .= 'NULL';
            } elseif (is_array(reset($p))) {
                $groups = [];
                foreach ($p as $row) {
                    $groups[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
                    foreach ($row as $v) $flat[] = $v;
                }
                $out .= implode(',', $groups);
            } else {
                $out .= implode(',', array_fill(0, count($p), '?'));
                foreach ($p as $v) $flat[] = $v;
            }
        } else {
            $out .= '?';
            $flat[] = $p;
        }
    }
    return [$out, $flat];
}

// SELECT -> list of rows; anything else -> ['insertId' => .., 'affectedRows' => ..]
function q(string $sql, array $params = [])
{
    [$sql, $flat] = expand_params($sql, $params);
    $st = db()->prepare($sql);
    foreach ($flat as $idx => $v) {
        if (is_bool($v)) $v = (int) $v;
        if (is_float($v) && is_nan($v)) $v = 'NaN';
        $type = is_int($v) ? PDO::PARAM_INT : ($v === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $st->bindValue($idx + 1, is_float($v) ? (string) $v : $v, $type);
    }
    $st->execute();
    if ($st->columnCount() > 0) return $st->fetchAll();
    return ['insertId' => (int) db()->lastInsertId(), 'affectedRows' => $st->rowCount()];
}

function q1(string $sql, array $params = []): ?array
{
    $rows = q($sql, $params);
    return $rows[0] ?? null;
}

function db_begin(): void
{
    db()->beginTransaction();
}

function db_commit(): void
{
    db()->commit();
}

function db_rollback(): void
{
    if (db()->inTransaction()) db()->rollBack();
}
