<?php
declare(strict_types=1);
/** Shared authentication helpers for the Hotel Reservation System. Local XAMPP demo. */
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
function authDb(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = getenv('HOTEL_DB_HOST') ?: '127.0.0.1';
    $name = getenv('HOTEL_DB_NAME') ?: 'hotel_reservation_system';
    $user = getenv('HOTEL_DB_USER') ?: 'root';
    $pass = getenv('HOTEL_DB_PASS') ?: '';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Database connection failed. Verify MySQL and import database/auth_migration.sql.');
    }
    return $pdo;
}
function authEscape(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function authCsrf(): string {
    if (!isset($_SESSION['auth_csrf'])) $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['auth_csrf'];
}
function authVerifyCsrf(): void {
    if (!hash_equals(authCsrf(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Security check failed. Refresh this page and try again.');
    }
}
function authFlash(string $type, string $message): void {
    $_SESSION['auth_flash'] = ['type'=>$type, 'message'=>$message];
}
function authGetFlash(): ?array {
    $f = $_SESSION['auth_flash'] ?? null;
    unset($_SESSION['auth_flash']);
    return $f;
}
function authUrl(string $suffix): string {
    // The project directory is the parent of auth/, customers/, admin/ or rooms/.
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/Hotel_Room_Reservation_System/auth/login.php'));
    $base = preg_replace('~/(auth|customers|admin|rooms)(/.*)?$~', '/', $script);
    $base = $base ?: '/Hotel_Room_Reservation_System/';
    return rtrim($base, '/') . '/' . ltrim($suffix, '/');
}
function authGo(string $suffix): never {
    header('Location: ' . authUrl($suffix), true, 303);
    exit;
}
function authCookiePath(): string {
    return authUrl('');
}
function authCurrentUser(): ?array {
    if (!isset($_SESSION['auth_user_id'])) authTryRemember();
    $id = (int)($_SESSION['auth_user_id'] ?? 0);
    if ($id < 1) return null;
    $q = authDb()->prepare('SELECT id, full_name, email, role, is_active FROM Customers WHERE id = ?');
    $q->execute([$id]);
    $user = $q->fetch();
    if (!$user || (int)$user['is_active'] !== 1) {
        unset($_SESSION['auth_user_id']);
        return null;
    }
    return $user;
}
function requireLogin(): array {
    $user = authCurrentUser();
    if (!$user) authGo('auth/login.php');
    return $user;
}
function requireRole(string $role): array {
    $user = requireLogin();
    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('Access denied. This page requires the ' . authEscape($role) . ' role.');
    }
    return $user;
}
function authLogIn(int $userId): void {
    session_regenerate_id(true);
    $_SESSION['auth_user_id'] = $userId;
}
function authCookieOptions(int $expires): array {
    return [
        'expires' => $expires, 'path' => authCookiePath(),
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax',
    ];
}
function authForgetRememberCookie(): void {
    $cookie = $_COOKIE['hotel_remember'] ?? '';
    if (is_string($cookie) && str_contains($cookie, ':')) {
        $selector = explode(':', $cookie, 2)[0];
        if (preg_match('/^[a-f0-9]{24}$/', $selector)) {
            $q = authDb()->prepare('DELETE FROM Auth_Remember_Tokens WHERE selector = ?');
            $q->execute([$selector]);
        }
    }
    setcookie('hotel_remember', '', authCookieOptions(time() - 3600));
    unset($_COOKIE['hotel_remember']);
}
function authSetRemember(int $userId): void {
    authForgetRememberCookie();
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expiry = time() + 60 * 60 * 24 * 14; // 14 days
    $q = authDb()->prepare('INSERT INTO Auth_Remember_Tokens (customer_id, selector, validator_hash, expires_at) VALUES (?,?,?,?)');
    $q->execute([$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expiry)]);
    setcookie('hotel_remember', $selector . ':' . $validator, authCookieOptions($expiry));
}
function authTryRemember(): void {
    $cookie = $_COOKIE['hotel_remember'] ?? '';
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m)) return;
    $q = authDb()->prepare('SELECT t.customer_id, t.validator_hash FROM Auth_Remember_Tokens t JOIN Customers c ON c.id=t.customer_id WHERE t.selector=? AND t.expires_at > NOW() AND c.is_active=1');
    $q->execute([$m[1]]);
    $row = $q->fetch();
    if (!$row || !hash_equals($row['validator_hash'], hash('sha256', $m[2]))) {
        authForgetRememberCookie();
        return;
    }
    authLogIn((int)$row['customer_id']);
    authSetRemember((int)$row['customer_id']); // Rotate token after use.
}
function authLogout(): void {
    authForgetRememberCookie();
    unset($_SESSION['auth_user_id']);
    session_regenerate_id(true);
}
