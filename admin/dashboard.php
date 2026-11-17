<?php
declare(strict_types=1);
require_once __DIR__ . '/../rooms/bootstrap.php';
protect_room_admin();
$db = room_pdo();
$today = date('Y-m-d');
$total = (int) $db->query('SELECT COUNT(*) FROM Rooms WHERE is_active=1')->fetchColumn();
$types = (int) $db->query('SELECT COUNT(*) FROM Room_Types')->fetchColumn();
$blocksToday = $db->prepare('SELECT COUNT(DISTINCT r.id) FROM Rooms r JOIN Room_Availability b ON b.room_id=r.id WHERE r.is_active=1 AND ? BETWEEN b.start_date AND b.end_date');
$blocksToday->execute([$today]);
$blocked = (int)$blocksToday->fetchColumn();
$availableQuery = $db->prepare("SELECT COUNT(*) FROM Rooms r WHERE r.is_active=1 AND r.status='Available' AND NOT EXISTS (SELECT 1 FROM Room_Availability b WHERE b.room_id=r.id AND ? BETWEEN b.start_date AND b.end_date) AND NOT EXISTS (SELECT 1 FROM Reservations v WHERE v.room_id=r.id AND v.status IN ('Pending','Confirmed') AND v.check_in <= ? AND v.check_out > ?)");
$availableQuery->execute([$today, $today, $today]);
$available = (int)$availableQuery->fetchColumn();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin Overview | Hotel Reservation System</title><link rel="stylesheet" href="../rooms/style.css"></head><body>
<div class="shell"><aside class="sidebar"><a class="logo" href="dashboard.php"><span class="logo-mark">H</span><span>HOTEL<span class="logo-light">HUB</span><small>MANAGEMENT CONSOLE</small></span></a><p class="side-label">WORKSPACE</p><nav><a class="active" href="dashboard.php">▦ <span>Overview</span></a><a href="../rooms/index.php">▤ <span>Rooms</span></a><a href="../rooms/index.php?section=types">▥ <span>Room Types</span></a><a href="../rooms/index.php?section=blocks">▦ <span>Availability</span></a></nav><div class="side-footer"><span class="dot"></span> Local demo mode <small>Admin login to be integrated</small></div></aside>
<main class="content"><header class="topbar"><span>ADMIN / <strong>OVERVIEW</strong></span><span class="identity">JOYTAA • ROOM MODULE</span></header><div class="main-content"><p class="eyebrow">HOTEL OPERATIONS</p><h1>Dashboard overview</h1><p class="subtitle">Your room inventory at a glance — <?= room_h($today) ?>.</p>
<div class="stat-row"><div class="stat-card"><span>TOTAL ROOMS</span><strong><?= $total ?></strong><small>Active inventory</small></div><div class="stat-card"><span>AVAILABLE TODAY</span><strong><?= $available ?></strong><small>Not occupied, blocked or out of service</small></div><div class="stat-card"><span>ROOM CATEGORIES</span><strong><?= $types ?></strong><small>Configured room types</small></div><div class="stat-card"><span>BLOCKED TODAY</span><strong><?= $blocked ?></strong><small>Maintenance date restrictions</small></div></div>
<section class="panel"><div class="panel-heading"><div><h2>Quick actions</h2><p>Manage inventory and maintenance in a few clicks.</p></div></div><div class="quick-actions"><a class="btn primary" href="../rooms/index.php">Manage rooms</a><a class="btn ghost" href="../rooms/index.php?section=types">Room types</a><a class="btn ghost" href="../rooms/index.php?section=blocks">Block availability</a></div></section>
<p class="footnote">Local demonstration module • Joyline Taa • Dashboard cards are live database counts.</p></div></main></div></body></html>
