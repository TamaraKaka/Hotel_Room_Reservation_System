<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = requireRole(ROLE_ADMIN);

$pageTitle = 'Manage Reservations';

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

// Retrieve all reservations.
$sql = "
    SELECT
        reservations.reservation_id,
        reservations.check_in_date,
        reservations.check_out_date,
        reservations.num_guests,
        reservations.total_amount,
        reservations.status,
        reservations.created_at,

        users.full_name,
        users.email,

        rooms.room_number,
        room_types.type_name

    FROM reservations

    INNER JOIN users
        ON reservations.user_id = users.user_id

    INNER JOIN rooms
        ON reservations.room_id = rooms.room_id

    INNER JOIN room_types
        ON rooms.room_type_id = room_types.room_type_id
";

if ($status !== 'All') {
    $sql .= " WHERE reservations.status = :status";
}

$sql .= " ORDER BY reservations.created_at DESC";

$stmt = db()->prepare($sql);

if ($status !== 'All') {
    $stmt->execute([
        ':status' => $status
    ]);
} else {
    $stmt->execute();
}

$reservations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Manage Reservations</h2>

        <p>
            View and manage all customer hotel reservations.
        </p>

        <form method="GET" action="">

            <label for="status">
                Filter by Status:
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

        </form>

        <br>

        <?php if (empty($reservations)): ?>

            <p>No reservations found.</p>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Reservation ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Room</th>
                            <th>Room Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Guests</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date Reserved</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($reservations as $reservation): ?>

                            <tr>

                                <td>
                                    <?= (int) $reservation['reservation_id'] ?>
                                </td>

                                <td>
                                    <?= e($reservation['full_name']) ?>
                                </td>

                                <td>
                                    <?= e($reservation['email']) ?>
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
                                    <?= e($reservation['created_at']) ?>
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