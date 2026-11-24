<?php
declare(strict_types=1);

/*
 * Shared bootstrap for the Room Management module.
 * Uses the main project database connection and authentication.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

date_default_timezone_set('Africa/Nairobi');

/**
 * Return the shared project database connection.
 */
function room_pdo(): PDO
{
    return db();
}

/**
 * Only authenticated administrators may access room management.
 */
function protect_room_admin(): void
{
    requireRole('Admin');
}

/**
 * Safely escape output.
 */
function room_h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Generate a CSRF token for room-management forms.
 */
function room_token(): string
{
    if (empty($_SESSION['room_csrf'])) {
        $_SESSION['room_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['room_csrf'];
}

/**
 * Verify submitted CSRF token.
 */
function room_verify_token(): void
{
    $sent = $_POST['csrf'] ?? '';

    if (
        !is_string($sent) ||
        !hash_equals(room_token(), $sent)
    ) {
        http_response_code(403);
        exit('Security check failed. Refresh the page and try again.');
    }
}

/**
 * Store a message to display after redirect.
 */
function room_message(string $kind, string $text): void
{
    $_SESSION['room_flash'] = [
        'kind' => $kind,
        'text' => $text
    ];
}

/**
 * Redirect to an allowed room-management section.
 */
function room_redirect(string $section): never
{
    $allowed = ['rooms', 'types', 'blocks'];

    if (!in_array($section, $allowed, true)) {
        $section = 'rooms';
    }

    header('Location: index.php?section=' . rawurlencode($section));
    exit;
}
