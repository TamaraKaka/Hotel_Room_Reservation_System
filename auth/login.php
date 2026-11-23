<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
if (authCurrentUser()) authGo('auth/home.php');
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    authVerifyCsrf();
    $email=strtolower(trim((string)($_POST['email']??'')));
    $password=(string)($_POST['password']??'');
    $blockedUntil=(int)($_SESSION['auth_blocked_until']??0);
    if ($blockedUntil>time()) $error='Too many attempts. Try again in a few minutes.';
    else {
        $q=authDb()->prepare('SELECT id,password_hash,is_active FROM Customers WHERE email=?');
        $q->execute([$email]);
        $u=$q->fetch();
        if (!$u || (int)$u['is_active']!==1 || !password_verify($password,$u['password_hash'])) {
            $_SESSION['auth_failures']=(int)($_SESSION['auth_failures']??0)+1;
            if ((int)$_SESSION['auth_failures']>=5) {$_SESSION['auth_blocked_until']=time()+300;$_SESSION['auth_failures']=0;}
            $error='Invalid email or password.';
        } else {
            unset($_SESSION['auth_failures'],$_SESSION['auth_blocked_until']);
            authLogIn((int)$u['id']);
            if (!empty($_POST['remember'])) authSetRemember((int)$u['id']);
            authGo('auth/home.php');
        }
    }
}
$f=authGetFlash();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · HotelHub</title><link rel="stylesheet" href="style.css"></head><body>
<div class="header"><span class="brand">HOTELHUB</span><a href="register.php">Register</a></div><main class="panel narrow"><h1>Welcome back</h1><p class="lead">Sign in to your HotelHub account.</p>
<?php if ($f): ?><div class="alert <?=authEscape($f['type'])?>"><?=authEscape($f['message'])?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error" role="alert"><?=authEscape($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>">
<label for="email">Email address</label><input required type="email" id="email" name="email" autocomplete="username" value="<?=authEscape((string)($_POST['email']??''))?>">
<label for="pw">Password</label><input required type="password" id="pw" name="password" autocomplete="current-password">
<label class="check"><input type="checkbox" name="remember" value="1"> Remember me for 14 days</label>
<p><button type="submit">Sign in</button></p></form>
<div class="row spread"><a href="forgot.php">Forgot password?</a><a href="register.php">Create account</a></div></main></body></html>
