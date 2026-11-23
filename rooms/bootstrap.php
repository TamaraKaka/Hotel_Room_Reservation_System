<?php
/** Room Management local demo bootstrap. Replace the connection with the team's shared config when available. */
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('Africa/Nairobi');

function room_pdo(): PDO
{
    static $db = null;
    if ($db instanceof PDO) {
        return $db;
    }
    $host = getenv('HOTEL_DB_HOST') ?: '127.0.0.1';
    $dbname = getenv('HOTEL_DB_NAME') ?: 'hotel_reservation_system';
    $user = getenv('HOTEL_DB_USER') ?: 'root';
    $password = getenv('HOTEL_DB_PASS') ?: '';

    try {
        $db = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $db;
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Database connection failed. Start MySQL in XAMPP and import rooms/demo_schema.sql. Check README instructions.');
    }
}

/**
 * Until Angela's role helpers are available, permit viewing this DEMO only via localhost.
 * This is deliberately NOT a production authentication solution.
 */
function protect_room_admin(): void
{
    if (function_exists('requireRole')) {
        requireRole('Admin');
        return;
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Demo-only module. Connect the group Admin login/role guard before deploying.');
    }
}

function room_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function room_token(): string
{
    if (empty($_SESSION['room_csrf'])) {
        $_SESSION['room_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['room_csrf'];
}

function room_verify_token(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(room_token(), $sent)) {
        http_response_code(403);
        exit('Security check failed. Refresh the page and try again.');
    }
}

function room_message(string $kind, string $text): void
{
    $_SESSION['room_flash'] = ['kind' => $kind, 'text' => $text];
}

function room_redirect(string $section): never
{
    $allowed = ['rooms', 'types', 'blocks'];
    if (!in_array($section, $allowed, true)) {
        $section = 'rooms';
    }
    header('Location: index.php?section=' . rawurlencode($section));
    exit;
}
