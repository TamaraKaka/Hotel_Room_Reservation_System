<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    authVerifyCsrf();
    $email=strtolower(trim((string)($_POST['email']??'')));
    // Always return the same message, whether or not the email exists.
    $message='If the address is registered and email delivery is configured, a reset link will be sent.';
    if (filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $q=authDb()->prepare('SELECT id FROM Customers WHERE email=? AND is_active=1');
        $q->execute([$email]);
        $id=(int)$q->fetchColumn();
        if ($id>0) {
            $token=bin2hex(random_bytes(32));
            $db=authDb();
            $db->prepare('DELETE FROM Auth_Password_Resets WHERE customer_id=?')->execute([$id]);
            $db->prepare('INSERT INTO Auth_Password_Resets (customer_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))')->execute([$id,hash('sha256',$token)]);
            // Local XAMPP often has no SMTP configured; this call may return false.
            $url='http://localhost' . authUrl('auth/reset.php?token='.$token);
            @mail($email,'HotelHub password reset',"Open this link within 30 minutes:\n".$url,"From: no-reply@localhost\r\n");
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Forgot password · HotelHub</title><link rel="stylesheet" href="style.css"></head><body><div class="header"><span class="brand">HOTELHUB</span><a href="login.php">Sign in</a></div><main class="panel narrow"><h1>Reset your password</h1>
<p class="lead">Enter your account email address.</p><?php if($message):?><div class="alert"><?=authEscape($message)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>"><label>Email address<input type="email" name="email" required></label><p><button type="submit">Request reset link</button></p></form><div class="note"><p><strong>Local demo note:</strong> Sending email requires a configured SMTP service. This feature is not considered tested until a real reset email and full reset flow work.</p></div></main></body></html>
