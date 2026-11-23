<?php
declare(strict_types=1);

/*
 * Creates an administrator account. Nobody can register as an admin on the website,
 * so run this once on each computer (and again on the demo machine).
 *
 *   /c/xampp/php/php database/create_admin.php "Hotel Admin" admin@hrrs.test "Admin1234"
 *
 * Run it from the project folder, in Git Bash.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this from the command line.\n");
}

require_once __DIR__ . '/../includes/db.php';

$name     = trim($argv[1] ?? '');
$email    = strtolower(trim($argv[2] ?? ''));
$password = $argv[3] ?? '';

if ($name === '' || $email === '' || $password === '') {
    exit("Usage: php database/create_admin.php \"Full Name\" email@example.com \"Password\"\n");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("That email address is not valid.\n");
}
if (strlen($password) < 8) {
    exit("Use a password of at least 8 characters.\n");
}

$check = db()->prepare('SELECT 1 FROM users WHERE email = ?');
$check->execute([$email]);
if ($check->fetchColumn()) {
    exit("An account with {$email} already exists.\n");
}

$insert = db()->prepare(
    'INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)'
);
$insert->execute([$name, $email, '+254700000000', password_hash($password, PASSWORD_DEFAULT), 'Admin']);

echo "Administrator created: {$email}\n";
