<?php
declare(strict_types=1);

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config.php';
    }

    return $config;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $db = app_config()['db'];
        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Hosted databases need TLS. The official PHP image ships the system CA bundle at this path.
        if ($db['ssl']) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        }

        $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
    }

    return $pdo;
}

// Returns the cached value for $key, or computes it and stores it for $ttl seconds.
// A null result is never cached, so a failed lookup is retried on the next visit.
function cache_remember(string $key, int $ttl, callable $compute): mixed
{
    $lookup = db()->prepare('SELECT payload FROM cache_entries WHERE cache_key = ? AND expires_at > NOW()');
    $lookup->execute([$key]);
    $row = $lookup->fetch();

    if ($row !== false) {
        return json_decode($row['payload'], true);
    }

    $value = $compute();

    if ($value !== null) {
        $store = db()->prepare('REPLACE INTO cache_entries (cache_key, payload, expires_at) VALUES (?, ?, ?)');
        $store->execute([
            $key,
            json_encode($value, JSON_UNESCAPED_UNICODE),
            date('Y-m-d H:i:s', time() + $ttl),
        ]);
    }

    return $value;
}
