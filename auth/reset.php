<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$token=(string)($_GET['token']??$_POST['token']??'');
$validToken=boolval(preg_match('/^[a-f0-9]{64}$/',$token));
$row=null;
if ($validToken) {
    $q=authDb()->prepare('SELECT customer_id FROM Auth_Password_Resets WHERE token_hash=? AND expires_at>NOW()');
    $q->execute([hash('sha256',$token)]);
    $row=$q->fetch() ?: null;
}
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    authVerifyCsrf();
    $pw=(string)($_POST['password']??'');
    if (!$row) $error='This reset link is invalid or has expired.';
    elseif (strlen($pw)<12 || $pw!==(string)($_POST['confirm_password']??'')) $error='Use matching passwords of at least 12 characters.';
    else {
        $db=authDb();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE Customers SET password_hash=? WHERE id=?')->execute([password_hash($pw,PASSWORD_DEFAULT),(int)$row['customer_id']]);
            $db->prepare('DELETE FROM Auth_Password_Resets WHERE customer_id=?')->execute([(int)$row['customer_id']]);
            $db->prepare('DELETE FROM Auth_Remember_Tokens WHERE customer_id=?')->execute([(int)$row['customer_id']]);
            $db->commit();
            authFlash('success','Password reset successfully. Please sign in.');
            authGo('auth/login.php');
        } catch(Throwable $e) {
            $db->rollBack();
            $error='Could not reset the password. Try again.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reset password · HotelHub</title><link rel="stylesheet" href="style.css"></head><body><div class="header"><span class="brand">HOTELHUB</span></div><main class="panel narrow"><h1>Choose a new password</h1>
<?php if($error):?><div class="alert error"><?=authEscape($error)?></div><?php endif;?>
<?php if(!$row):?><p>This reset link is invalid or has expired.</p><a href="forgot.php">Request a new link</a><?php else:?>
<form method="post"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>"><input type="hidden" name="token" value="<?=authEscape($token)?>"><label>New password (12+ characters)<input required type="password" name="password" minlength="12" autocomplete="new-password"></label><label>Confirm password<input required type="password" name="confirm_password" minlength="12" autocomplete="new-password"></label><p><button type="submit">Change password</button></p></form><?php endif;?></main></body></html>
