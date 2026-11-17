<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user=requireLogin();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dashboard · HotelHub</title><link rel="stylesheet" href="style.css"></head><body>
<div class="header"><span class="brand">HOTELHUB</span><span><?=authEscape($user['full_name'])?> · <form class="actionform" action="logout.php" method="post"><input type="hidden" name="csrf" value="<?=authEscape(authCsrf())?>"><button type="submit">Sign out</button></form></span></div>
<main class="wrap"><div class="panel"><h1>Welcome, <?=authEscape($user['full_name'])?>!</h1><p class="lead">Your role: <strong><?=authEscape($user['role'])?></strong></p>
<?php if ($user['role']==='Admin'): ?><p><a class="btn" href="../customers/index.php">Manage customers</a></p><p class="muted">Room Management is in Joyline's separate Git branch and will be connected when the group merges the modules.</p><?php else: ?><p>Customer account created and login session active. Booking pages are being developed by other group members.</p><?php endif; ?>
</div></main></body></html>
