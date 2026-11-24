<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = requireLogin();

$pageTitle = 'My Reservations';

// Get selected status filter.
$status = $_GET['status'] ?? 'All';

$allowedStatuses = [
    'All',
    'Pending',
    'Confirmed',
    'Completed',
    'Cancelled'
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'All';
}

// Get the customer's reservations.
$sql = "
    SELECT
        reservations.reservation_id,
        reservations.check_in_date,
        reservations.check_out_date,
        reservations.num_guests,
        reservations.total_amount,
        reservations.status,
        reservations.created_at,

        rooms.room_number,
        room_types.type_name,

        (
            SELECT COALESCE(SUM(payments.amount), 0)
            FROM payments
            WHERE payments.reservation_id = reservations.reservation_id
            AND payments.status = 'Paid'
        ) AS paid_amount,

        (
            SELECT COALESCE(SUM(payments.amount), 0)
            FROM payments
            WHERE payments.reservation_id = reservations.reservation_id
            AND payments.status = 'Pending'
        ) AS pending_amount

    FROM reservations

    INNER JOIN rooms
        ON reservations.room_id = rooms.room_id

    INNER JOIN room_types
        ON rooms.room_type_id = room_types.room_type_id

    WHERE reservations.user_id = :user_id
";

$params = [
    ':user_id' => $user['id']
];

// Apply status filter.
if ($status !== 'All') {
    $sql .= " AND reservations.status = :status";
    $params[':status'] = $status;
}

$sql .= " ORDER BY reservations.created_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);

$reservations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>My Reservations</h2>

        <p>
            View and manage your hotel room reservations.
        </p>

        <!-- Flash Messages -->

        <?php $messages = takeFlash(); ?>

        <?php if (!empty($messages['success'])): ?>

            <div class="alert alert-success">
                <?= e($messages['success']) ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($messages['error'])): ?>

            <div class="alert alert-danger">
                <?= e($messages['error']) ?>
            </div>

        <?php endif; ?>

        <!-- Reservation Status Filter -->

        <form method="GET" action="">

            <div class="form-group">

                <label for="status">
                    Filter by Status
                </label>

                <select
                    name="status"
                    id="status"
                    onchange="this.form.submit()"
                >

                    <?php foreach ($allowedStatuses as $option): ?>

                        <option
                            value="<?= e($option) ?>"
                            <?= $status === $option ? 'selected' : '' ?>
                        >
                            <?= e($option) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </form>

        <?php if (empty($reservations)): ?>

            <div class="alert alert-info">
                You currently have no reservations under this status.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Reservation ID</th>

                            <th>Room</th>

                            <th>Room Type</th>

                            <th>Check-in</th>

                            <th>Check-out</th>

                            <th>Guests</th>

                            <th>Total Amount</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($reservations as $reservation): ?>

                            <?php

                            $remainingBalance = max(
                                0,
                                (float) $reservation['total_amount']
                                - (float) $reservation['paid_amount']
                                - (float) $reservation['pending_amount']
                            );

                            ?>

                            <tr>

                                <td>
                                    #<?= (int) $reservation['reservation_id'] ?>
                                </td>

                                <td>
                                    <?= e($reservation['room_number']) ?>
                                </td>

                                <td>
                                    <?= e($reservation['type_name']) ?>
                                </td>

                                <td>
                                    <?= e($reservation['check_in_date']) ?>
                                </td>

                                <td>
                                    <?= e($reservation['check_out_date']) ?>
                                </td>

                                <td>
                                    <?= (int) $reservation['num_guests'] ?>
                                </td>

                                <td>
                                    KSh
                                    <?= number_format(
                                        (float) $reservation['total_amount'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($reservation['status']) ?>
                                </td>

                                <td>

                                    <!-- Details Button -->

                                    <a
                                        href="<?= e(base_url(
                                            'reservations/details.php?id=' .
                                            (int) $reservation['reservation_id']
                                        )) ?>"
                                        class="btn btn-primary"
                                    >
                                        Details
                                    </a>

                                    <!-- Make Payment Button -->

                                    <?php if (
                                        in_array(
                                            $reservation['status'],
                                            ['Pending', 'Confirmed'],
                                            true
                                        )
                                        && $remainingBalance > 0
                                    ): ?>

                                        <a
                                            href="<?= e(base_url(
                                                'payments/pay.php?reservation_id=' .
                                                (int) $reservation['reservation_id']
                                            )) ?>"
                                            class="btn btn-success"
                                        >
                                            Make Payment
                                        </a>

                                    <?php endif; ?>

                                    <!-- Edit Button -->

                                    <?php if ($reservation['status'] === 'Pending'): ?>

                                        <a
                                            href="<?= e(base_url(
                                                'reservations/edit.php?id=' .
                                                (int) $reservation['reservation_id']
                                            )) ?>"
                                            class="btn btn-primary"
                                        >
                                            Edit
                                        </a>

                                    <?php endif; ?>

                                    <!-- Cancel Button -->

                                    <?php if (
                                        in_array(
                                            $reservation['status'],
                                            ['Pending', 'Confirmed'],
                                            true
                                        )
                                    ): ?>

                                        <form
                                            method="POST"
                                            action="<?= e(base_url('reservations/cancel.php')) ?>"
                                            onsubmit="return confirm('Are you sure you want to cancel this reservation?');"
                                        >

                                            <?= csrfField() ?>

                                            <input
                                                type="hidden"
                                                name="reservation_id"
                                                value="<?= (int) $reservation['reservation_id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-danger"
                                            >
                                                Cancel
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
