<?php
declare(strict_types=1);

/*
 * User Authentication module (owner: Angela).
 * Every page that needs a login does:
 *
 *     require_once __DIR__ . '/../includes/auth.php';
 *     $user = requireLogin();            // any logged-in user
 *     $user = requireRole(ROLE_ADMIN);   // administrators only
 *
 * $user is an array: ['id' => 3, 'name' => '...', 'email' => '...', 'role' => 'Customer']
 */

require_once __DIR__ . '/db.php';

const ROLE_CUSTOMER   = 'Customer';
const ROLE_ADMIN      = 'Admin';
const REMEMBER_SECONDS = 30 * 24 * 60 * 60; // "Remember me" keeps the login for 30 days

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string) REMEMBER_SECONDS);
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ---------- small helpers ---------- */

/** Escape text before printing it in HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function takeFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF protection for every POST form ---------- */

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function csrfValid(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

/* ---------- who is logged in ---------- */

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function homePathFor(string $role): string
{
    $config = app_config();
    return $role === ROLE_ADMIN
        ? ($config['admin_home'] ?? 'auth/home.php')
        : ($config['customer_home'] ?? 'auth/home.php');
}

function redirectToHome(array $user): never
{
    redirect(homePathFor($user['role']));
}

/**
 * Stops the page unless someone is logged in.
 * It also re-checks the database, so a customer deactivated by an admin
 * is logged out on their next click.
 */
function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        flash('error', 'Please log in to continue.');
        redirect('auth/login.php');
    }

    $stmt = db()->prepare('SELECT is_active FROM users WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || (int) $row['is_active'] !== 1) {
        $_SESSION = [];
        flash('error', 'Your account is not active. Please contact the hotel.');
        redirect('auth/login.php');
    }

    return $user;
}

/** Stops the page unless the logged-in user has the given role. */
function requireRole(string $role): array
{
    $user = requireLogin();
    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('<h1>403 Forbidden</h1><p>You do not have permission to open this page.</p>');
    }
    return $user;
}

function loginUser(array $row, bool $remember): void
{
    session_regenerate_id(true); // new session id after login blocks session fixation
    $_SESSION['user'] = [
        'id'    => (int) $row['user_id'],
        'name'  => $row['full_name'],
        'email' => $row['email'],
        'role'  => $row['role'],
    ];

    if ($remember) {
        setcookie(session_name(), session_id(), [
            'expires'  => time() + REMEMBER_SECONDS,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function logoutUser(): void
{
    $_SESSION = [];
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
    session_destroy();
    session_start(); // fresh session so we can show "You have been logged out"
}

/* ---------- login ---------- */

/**
 * Checks an email and password.
 * Returns ['ok' => true, 'user' => row] or ['ok' => false, 'error' => message].
 */
function attemptLogin(string $email, string $password): array
{
    $stmt = db()->prepare(
        'SELECT user_id, full_name, email, password_hash, role, is_active FROM users WHERE email = ?'
    );
    $stmt->execute([strtolower(trim($email))]);
    $row = $stmt->fetch();

    // Same message for "no such email" and "wrong password" so nobody can probe for accounts.
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
    if ((int) $row['is_active'] !== 1) {
        return ['ok' => false, 'error' => 'Your account has been deactivated. Please contact the hotel.'];
    }
    return ['ok' => true, 'user' => $row];
}

/* ---------- registration ---------- */

/**
 * Validates the registration form.
 * Returns [errors, clean]: errors is field => message (empty when all is fine).
 */
function validateRegistration(array $in): array
{
    $errors = [];

    $name     = trim((string) ($in['full_name'] ?? ''));
    $email    = strtolower(trim((string) ($in['email'] ?? '')));
    $phoneRaw = preg_replace('/[\s\-]/', '', (string) ($in['phone'] ?? ''));
    $password = (string) ($in['password'] ?? '');
    $confirm  = (string) ($in['confirm_password'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['full_name'] = 'Enter your full name (2 to 100 characters).';
    } elseif (!preg_match("/^[\p{L}][\p{L}\s.'\-]*$/u", $name)) {
        $errors['full_name'] = 'Names can only contain letters, spaces, dots, apostrophes and hyphens.';
    }

    if ($email === '' || mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    // Kenyan mobile numbers: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX or 2547XXXXXXXX
    $phone = '';
    if (preg_match('/^(?:\+254|254|0)([17]\d{8})$/', $phoneRaw, $m)) {
        $phone = '+254' . $m[1];
    } else {
        $errors['phone'] = 'Enter a valid Kenyan phone number, e.g. 0712 345 678.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Use 72 characters or fewer.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Include at least one letter and one number.';
    }

    if (!isset($errors['password']) && $password !== $confirm) {
        $errors['confirm_password'] = 'The two passwords do not match.';
    }

    return [$errors, [
        'full_name' => $name,
        'email'     => $email,
        'phone'     => $phone,
        'password'  => $password,
    ]];
}

function emailExists(string $email): bool
{
    $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Saves a new customer. Returns '' on success or 'email_taken'.
 * The role is always Customer, so nobody can register themselves as an administrator.
 */
function registerUser(array $clean): string
{
    if (emailExists($clean['email'])) {
        return 'email_taken';
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $clean['full_name'],
            $clean['email'],
            $clean['phone'],
            password_hash($clean['password'], PASSWORD_DEFAULT),
            ROLE_CUSTOMER,
        ]);
    } catch (PDOException $ex) {
        // Two people registering the same email at the same moment hit the UNIQUE rule.
        if ($ex->getCode() === '23000') {
            return 'email_taken';
        }
        throw $ex;
    }
    return '';
}
