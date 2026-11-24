<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
if (authCurrentUser()) authGo('auth/home.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    authVerifyCsrf();
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (strlen($name) < 2 || strlen($name) > 120) $error = 'Full name must have 2–120 characters.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $error = 'Enter a valid email address.';
    elseif (strlen($password) < 12) $error = 'Use a password of at least 12 characters.';
    elseif ($password !== $confirm) $error = 'Passwords do not match.';
    else {
        try {
            $q = authDb()->prepare('INSERT INTO Customers (full_name,email,password_hash,role) VALUES (?,?,?,\'Customer\')');
            $q->execute([$name,$email,password_hash($password, PASSWORD_DEFAULT)]);
            authFlash('success','Account created successfully. Log in to continue.');
            authGo('auth/login.php');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) == 1062) $error='This email is already registered.';
            else $error='We could not create the account. Please try again.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Create Account · HotelHub</title><link rel="stylesheet" href="style.css"></head><body>
<div class="header"><span class="brand">HOTELHUB</span><a href="login.php">Sign in</a></div>
<main class="panel narrow"><h1>Create customer account</h1><p class="lead">Register to manage your hotel reservations.</p>
<?php if ($error): ?><div class="alert error" role="alert"><?=authEscape($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>">
<label for="name">Full name</label><input required id="name" name="full_name" maxlength="120" value="<?=authEscape((string)($_POST['full_name'] ?? ''))?>">
<label for="email">Email address</label><input required type="email" id="email" name="email" maxlength="190" value="<?=authEscape((string)($_POST['email'] ?? ''))?>">
<label for="pw">Password (minimum 12 characters)</label><input required type="password" minlength="12" id="pw" name="password" autocomplete="new-password">
<label for="confirm">Confirm password</label><input required type="password" minlength="12" id="confirm" name="confirm_password" autocomplete="new-password">
<p><button type="submit">Create account</button></p></form><p>Already registered? <a href="login.php">Sign in</a></p></main></body></html>
