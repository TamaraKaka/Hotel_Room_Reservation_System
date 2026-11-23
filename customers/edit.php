<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('Admin');
$id=(int)($_GET['id']??($_POST['id']??0));
$q=authDb()->prepare("SELECT id,full_name,email,role,is_active,created_at FROM Customers WHERE id=? AND role='Customer'");
$q->execute([$id]);
$c=$q->fetch();
if (!$c) {http_response_code(404); exit('Customer not found.');}
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    authVerifyCsrf();
    $name=trim((string)($_POST['full_name']??''));
    $email=strtolower(trim((string)($_POST['email']??'')));
    if (strlen($name)<2 || strlen($name)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) $error='Enter a valid name and email address.';
    else {
        try {
            $u=authDb()->prepare("UPDATE Customers SET full_name=?,email=? WHERE id=? AND role='Customer'");
            $u->execute([$name,$email,$id]);
            authFlash('success','Customer information saved.');
            authGo('customers/index.php');
        } catch(PDOException $e) {
            $error=(($e->errorInfo[1]??null)==1062) ? 'That email is already in use.' : 'Could not save customer.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Edit Customer · HotelHub</title><link rel="stylesheet" href="../auth/style.css"></head><body><div class="header"><span class="brand">HOTELHUB / ADMIN</span><a href="index.php">Back to customers</a></div>
<main class="panel narrow"><h1>Edit customer</h1><p class="lead">Customer #<?=(int)$c['id']?> · <?=authEscape($c['is_active']?'Active':'Inactive')?></p>
<?php if ($error): ?><div class="alert error" role="alert"><?=authEscape($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>"><input type="hidden" name="id" value="<?=(int)$c['id']?>">
<label>Full name<input required name="full_name" maxlength="120" value="<?=authEscape((string)($_POST['full_name']??$c['full_name']))?>"></label>
<label>Email address<input required type="email" name="email" maxlength="190" value="<?=authEscape((string)($_POST['email']??$c['email']))?>"></label>
<p><button type="submit">Save changes</button> <a class="btn secondary" href="index.php">Cancel</a></p></form></main></body></html>
