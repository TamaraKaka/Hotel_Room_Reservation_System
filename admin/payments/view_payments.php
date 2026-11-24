<?php

require_once __DIR__ . '/../../includes/auth.php';

// Only administrators can access this page.
$user = requireRole(ROLE_ADMIN);

$pdo = db();

// Validate the selected payment status.
$allowedStatuses = [
    'pending',
    'successful',
    'failed',
    'refunded'
];

$status = $_GET['status'] ?? '';

if (!is_string($status) || !in_array($status, $allowedStatuses, true)) {
    $status = '';
}

// Build the query using prepared statements.
$sql = '
    SELECT
        payment_id,
        reservation_id,
        receipt_number,
        amount,
        payment_method,
        payment_date,
        payment_status
    FROM payments
';

$params = [];

if ($status !== '') {
    $sql .= ' WHERE payment_status = ?';
    $params[] = $status;
}

$sql .= ' ORDER BY payment_date DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$payments = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin - View Payments</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #243b53;
        }

        .filter-form {
            margin: 20px 0;
        }

        select, button {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #bcccdc;
        }

        button {
            background: #243b53;
            color: white;
            cursor: pointer;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #243b53;
            color: white;
        }

        .status {
            font-weight: bold;
        }

        .successful {
            color: #16803c;
        }

        .pending {
            color: #b7791f;
        }

        .failed, .refunded {
            color: #c53030;
        }

        .empty {
            padding: 20px;
            text-align: center;
            background: #f0f4f8;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Payment Management</h1>

    <p>View and monitor customer payment records.</p>

    <form method="GET" class="filter-form">

        <label for="status">Filter by payment status:</label>

        <select name="status" id="status">
            <option value="">All payments</option>

            <?php foreach ($allowedStatuses as $option): ?>
                <option
                    value="<?= htmlspecialchars($option) ?>"
                    <?= $status === $option ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(ucfirst($option)) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <button type="submit">Apply Filter</button>

        <a href="view_payments.php">Clear Filter</a>

    </form>

    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Reservation ID</th>
                    <th>Receipt Number</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($payments)): ?>

                    <tr>
                        <td colspan="7" class="empty">
                            No payment records found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($payments as $payment): ?>

                        <tr>
                            <td>
                                <?= (int) $payment['payment_id'] ?>
                            </td>

                            <td>
                                <?= (int) $payment['reservation_id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($payment['receipt_number']) ?>
                            </td>

                            <td>
                                <?= number_format((float) $payment['amount'], 2) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($payment['payment_method']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($payment['payment_date']) ?>
                            </td>

                            <td class="status <?= htmlspecialchars($payment['payment_status']) ?>">
                                <?= htmlspecialchars(ucfirst($payment['payment_status'])) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <p>
        Showing up to 100 most recent payment records.
    </p>

</div>

</body>
</html>
