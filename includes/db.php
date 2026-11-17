<?php
declare(strict_types=1);

/*
 * Shared by every module.
 *   app_config()  returns the settings from config/db.php
 *   base_url()    builds a URL inside the project, e.g. base_url('auth/login.php')
 *   db()          returns the one PDO connection (always use prepared statements)
 */

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/../config/db.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('Missing config/db.php. Copy config/db.example.php to config/db.php and check the values.');
        }
        $config = require $file;
    }
    return $config;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) (app_config()['base_url'] ?? ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = app_config()['db'];
        $dsn = $c['dsn'] ?? sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $c['host'],
            $c['name'],
            $c['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $c['user'] ?? null, $c['pass'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
