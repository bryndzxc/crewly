<?php

/**
 * One-off helper for local dev: creates the `crewly_test` database.
 *
 * This is intentionally tiny and does not run migrations.
 */

function envFromDotenv(string $key, ?string $default = null): ?string
{
    $path = __DIR__ . '/../.env';
    if (!is_file($path)) {
        return $default;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$k, $v] = array_pad(explode('=', $line, 2), 2, null);
        if ($k === null || $v === null) {
            continue;
        }

        $k = trim($k);
        if ($k !== $key) {
            continue;
        }

        $v = trim($v);
        if ($v === '') {
            return $default;
        }

        // Strip optional surrounding quotes.
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }

        return $v;
    }

    return $default;
}

$host = envFromDotenv('DB_HOST', '127.0.0.1');
$port = envFromDotenv('DB_PORT', '3306');
$user = envFromDotenv('DB_USERNAME', 'root');
$pass = envFromDotenv('DB_PASSWORD', '');

$dbName = 'crewly_test';

$pdo = new PDO(
    "mysql:host={$host};port={$port}",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

echo "ok\n";
