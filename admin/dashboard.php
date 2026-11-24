<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireRole('Admin');

$pdo = db();

// Dashboard statistics
$stats = [
    'rooms' => (int) $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    'room_types' => (int) $pdo->query("SELECT COUNT(*) FROM room_types")->fetchColumn(),
    'customers' => (int) $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'reservations' => (int) $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Pending'")->fetchColumn(),
    'payments' => (int) $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid'")->fetchColumn(),
];

// Latest reservations
$sql = "
    SELECT
        res.reservation_id,
        u.full_name,
        r.room_number,
        t.type_name,
        res.check_in_date,
        res.check_out_date,
        res.total_amount,
        res.status
    FROM reservations res
    LEFT JOIN users u ON u.user_id = res.user_id
    LEFT JOIN rooms r ON r.room_id = res.room_id
    LEFT JOIN room_types t ON t.room_type_id = r.room_type_id
    ORDER BY res.created_at DESC
    LIMIT 8
";

$recentReservations = $pdo->query($sql)->fetchAll();

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$user = authCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | HotelHub</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6fa;
            color: #172033;
        }

        header {
            background: #101d35;
            color: white;
            padding: 22px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h1 {
            color: #d7b56d;
            font-size: 25px;
        }

        header p {
            font-size: 13px;
            margin-top: 5px;
            color: #d5dbea;
        }

        nav {
            background: white;
            padding: 15px 5%;
            display: flex;
            gap: 22px;
            flex-wrap: wrap;
            border-bottom: 1px solid #e5e8ef;
        }

        nav a {
            color: #172b4d;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        nav a:hover {
            color: #b18a3d;
        }

        main {
            width: 90%;
            max-width: 1300px;
            margin: 30px auto;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            font-size: 25px;
            margin-bottom: 7px;
        }

        .welcome p {
            color: #687386;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #e8ebf1;
            border-left: 4px solid #c5a15b;
        }

        .stat-card p {
            color: #697589;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .stat-card h3 {
            font-size: 27px;
            color: #142541;
        }

        .panel {
            background: white;
            border-radius: 10px;
            padding: 22px;
            border: 1px solid #e8ebf1;
            overflow-x: auto;
        }

        .panel h2 {
            margin-bottom: 20px;
            font-size: 19px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            text-align: left;
            padding: 13px 12px;
            border-bottom: 1px solid #edf0f4;
            font-size: 13px;
        }

        th {
            background: #f7f8fb;
            color: #526078;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #edf0f5;
        }

        .pending {
            background: #fff2d6;
            color: #956300;
        }

        .confirmed, .paid {
            background: #dff5e7;
            color: #176b38;
        }

        .cancelled, .failed {
            background: #ffe4e4;
            color: #a52b2b;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #697589;
        }

        footer {
            text-align: center;
            color: #8791a1;
            padding: 25px;
            font-size: 13px;
        }
    </style>
</head>

<body>

<header>
    <div>
        <h1>HotelHub Admin</h1>
        <p>Hotel Reservation Management System</p>
    </div>

    <div>
        Welcome, <?= h($user['full_name'] ?? 'Administrator') ?>
    </div>
</header>

<nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="../rooms/index.php">Room Management</a>
    <a href="../customers/index.php">Customer Management</a>
</nav>

<main>

    <section class="welcome">
        <h2>Dashboard Overview</h2>
        <p>Monitor hotel operations, reservations and payments.</p>
    </section>

    <section class="stats">

        <div class="stat-card">
            <p>Total Rooms</p>
            <h3><?= $stats['rooms'] ?></h3>
        </div>

        <div class="stat-card">
            <p>Room Types</p>
            <h3><?= $stats['room_types'] ?></h3>
        </div>

        <div class="stat-card">
            <p>Registered Customers</p>
            <h3><?= $stats['customers'] ?></h3>
        </div>

        <div class="stat-card">
            <p>Total Reservations</p>
            <h3><?= $stats['reservations'] ?></h3>
        </div>

        <div class="stat-card">
            <p>Pending Reservations</p>
            <h3><?= $stats['pending'] ?></h3>
        </div>

        <div class="stat-card">
            <p>Total Payments</p>
            <h3><?= $stats['payments'] ?></h3>
        </div>

        <div class="stat-card">
            <p>Recorded Revenue</p>
            <h3>KSh <?= number_format($stats['revenue'], 2) ?></h3>
        </div>

    </section>

    <section class="panel">
        <h2>Recent Reservations</h2>

        <?php if (!$recentReservations): ?>

            <div class="empty">No reservations have been recorded yet.</div>

        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Room</th>
                        <th>Room Type</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($recentReservations as $reservation): ?>
                        <?php
                            $status = strtolower((string) $reservation['status']);
                            $statusClass = in_array(
                                $status,
                                ['pending', 'confirmed', 'cancelled'],
                                true
                            ) ? $status : '';
                        ?>

                        <tr>
                            <td>#<?= (int) $reservation['reservation_id'] ?></td>
                            <td><?= h($reservation['full_name'] ?? 'Unknown') ?></td>
                            <td><?= h($reservation['room_number'] ?? 'N/A') ?></td>
                            <td><?= h($reservation['type_name'] ?? 'N/A') ?></td>
                            <td><?= h($reservation['check_in_date']) ?></td>
                            <td><?= h($reservation['check_out_date']) ?></td>
                            <td>KSh <?= number_format((float) $reservation['total_amount'], 2) ?></td>
                            <td>
                                <span class="status <?= h($statusClass) ?>">
                                    <?= h($reservation['status']) ?>
                                </span>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </section>

</main>

<footer>
    HotelHub Administration &copy; <?= date('Y') ?>
</footer>

</body>
</html>
