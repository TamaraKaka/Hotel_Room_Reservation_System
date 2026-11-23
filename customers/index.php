<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$admin=requireRole('Admin');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    authVerifyCsrf();
    $id=(int)($_POST['id']??0);
    $action=(string)($_POST['action']??'');
    if ($id===(int)$admin['id']) $error='You cannot deactivate your own administrator account.';
    elseif ($id<1 || !in_array($action,['deactivate','reactivate'],true)) $error='Invalid customer action.';
    else {
        $state=$action==='reactivate' ? 1 : 0;
        $q=authDb()->prepare("UPDATE Customers SET is_active=? WHERE id=? AND role='Customer'");
        $q->execute([$state,$id]);
        authFlash('success',$q->rowCount() ? 'Customer status updated.' : 'No matching customer found.');
        authGo('customers/index.php');
    }
}
$q=authDb()->query('SELECT id,full_name,email,role,is_active,created_at FROM Customers ORDER BY created_at DESC,id DESC');
$customers=$q->fetchAll();
$active=count(array_filter($customers,fn($c)=>$c['role']==='Customer' && (int)$c['is_active']===1));
$f=authGetFlash();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage Customers · HotelHub</title><link rel="stylesheet" href="../auth/style.css"></head><body>
<div class="header"><span class="brand">HOTELHUB / ADMIN</span><span><a href="../auth/home.php">Dashboard</a> · <form class="actionform" action="../auth/logout.php" method="post"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>"><button type="submit">Sign out</button></form></span></div>
<main class="wrap"><h1>Manage customers</h1><p class="lead">Review, edit and deactivate customer accounts.</p>
<div class="cards"><div class="card">Total accounts<strong><?=count($customers)?></strong></div><div class="card">Active customers<strong><?=$active?></strong></div></div>
<div class="panel"><?php if ($f): ?><div class="alert <?=authEscape($f['type'])?>"><?=authEscape($f['message'])?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error" role="alert"><?=authEscape($error)?></div><?php endif; ?>
<div class="tablewrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead><tbody>
<?php foreach($customers as $c): ?><tr><td><?=authEscape($c['full_name'])?></td><td><?=authEscape($c['email'])?></td><td><?=authEscape($c['role'])?></td><td><?=(int)$c['is_active']===1?'Active':'Inactive'?></td><td><?=authEscape($c['created_at'])?></td><td>
<?php if ($c['role']==='Customer'): ?><a class="btn secondary" href="edit.php?id=<?=(int)$c['id']?>">View / Edit</a>
<form class="actionform" method="post" onsubmit="return confirm('Change this customer account status?')"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>"><input type="hidden" name="id" value="<?=(int)$c['id']?>"><input type="hidden" name="action" value="<?=(int)$c['is_active']===1?'deactivate':'reactivate'?>"><button type="submit"><?=(int)$c['is_active']===1?'Deactivate':'Reactivate'?></button></form><?php else: ?><span class="muted">Admin</span><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$customers): ?><tr><td colspan="6">No accounts yet.</td></tr><?php endif; ?>
</tbody></table></div></div></main></body></html>
