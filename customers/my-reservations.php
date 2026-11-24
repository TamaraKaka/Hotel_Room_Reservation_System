<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can access their reservations.
$user = requireLogin();

$conn = authDb();

// Find the matching account in the hotel users table.
$userQuery = $conn->prepare("
    SELECT user_id
    FROM users
    WHERE LOWER(email) = LOWER(:email)
    LIMIT 1
");

$userQuery->execute([
    ':email' => $user['email']
]);

$hotelUser = $userQuery->fetch(PDO::FETCH_ASSOC);

$reservations = [];

if ($hotelUser) {

    // Retrieve only reservations belonging to this customer.
    $reservationQuery = $conn->prepare("
        SELECT
            r.reservation_id,
            r.check_in_date,
            r.check_out_date,
            r.num_guests,
            r.total_amount,
            r.status,
            r.created_at,
            rooms.room_number,
            room_types.type_name,
            room_types.price_per_night
        FROM reservations r
        JOIN rooms
            ON r.room_id = rooms.room_id
        JOIN room_types
            ON rooms.room_type_id = room_types.room_type_id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC, r.reservation_id DESC
    ");

    $reservationQuery->execute([
        ':user_id' => $hotelUser['user_id']
    ]);

    $reservations = $reservationQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate reservation statistics.
$totalReservations = count($reservations);

$pendingReservations = 0;
$confirmedReservations = 0;
$completedReservations = 0;

foreach ($reservations as $reservation) {

    $status = strtolower($reservation['status']);

    if ($status === 'pending') {
        $pendingReservations++;
    } elseif ($status === 'confirmed') {
        $confirmedReservations++;
    } elseif ($status === 'completed') {
        $completedReservations++;
    }
}

$pageTitle = 'My Reservations';

include __DIR__ . '/../includes/header.php';
?>

<section class="my-reservations-page">

    <!-- PAGE HEADING -->

    <div class="reservations-heading">

        <span class="details-label">HOTELHUB CUSTOMER PORTAL</span>

        <h1>My Reservations</h1>

        <p>
            Welcome, <?= htmlspecialchars($user['full_name']) ?>.
            View and manage your hotel reservations.
        </p>

    </div>

    <!-- RESERVATION STATISTICS -->

    <div class="reservation-stats">

        <div class="reservation-stat-card">
            <span>Total Reservations</span>
            <strong><?= $totalReservations ?></strong>
        </div>

        <div class="reservation-stat-card">
            <span>Pending</span>
            <strong><?= $pendingReservations ?></strong>
        </div>

        <div class="reservation-stat-card">
            <span>Confirmed</span>
            <strong><?= $confirmedReservations ?></strong>
        </div>

        <div class="reservation-stat-card">
            <span>Completed</span>
            <strong><?= $completedReservations ?></strong>
        </div>

    </div>

    <!-- RESERVATION LIST -->

    <div class="reservations-section">

        <div class="reservations-section-heading">

            <div>
                <h2>Booking History</h2>

                <p>
                    All reservations associated with your account.
                </p>
            </div>

            <a href="../search/index.php" class="new-booking-btn">
                + Book a Room
            </a>

        </div>

        <?php if (!$hotelUser): ?>

            <div class="no-reservations">

                <h3>Account not linked</h3>

                <p>
                    Your customer account has not yet been linked
                    to the hotel reservation system.
                    Please contact the administrator.
                </p>

            </div>

        <?php elseif (empty($reservations)): ?>

            <div class="no-reservations">

                <div class="empty-reservation-icon">⌂</div>

                <h3>No Reservations Yet</h3>

                <p>
                    You have not made any hotel reservations.
                    Explore our available rooms and book your stay.
                </p>

                <a href="../search/index.php" class="new-booking-btn">
                    Explore Available Rooms
                </a>

            </div>

        <?php else: ?>

            <!-- DESKTOP RESERVATION TABLE -->

            <div class="reservations-table-wrapper">

                <table class="my-reservations-table">

                    <thead>

                        <tr>
                            <th>Reference</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Guests</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($reservations as $reservation): ?>

                            <?php
                            $statusClass = strtolower(
                                preg_replace(
                                    '/[^a-zA-Z0-9]+/',
                                    '-',
                                    $reservation['status']
                                )
                            );

                            $detailsUrl = 'reservation-details.php?id='
                                . (int)$reservation['reservation_id'];
                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        #<?= (int)$reservation['reservation_id'] ?>
                                    </strong>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($reservation['type_name']) ?>
                                    </strong>

                                    <small>
                                        Room <?= htmlspecialchars($reservation['room_number']) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= htmlspecialchars($reservation['check_in_date']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($reservation['check_out_date']) ?>
                                </td>

                                <td>
                                    <?= (int)$reservation['num_guests'] ?>
                                </td>

                                <td>
                                    <strong>
                                        KSh <?= number_format(
                                            (float)$reservation['total_amount'],
                                            2
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="reservation-status <?= htmlspecialchars($statusClass) ?>">
                                        <?= htmlspecialchars($reservation['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="<?= htmlspecialchars($detailsUrl) ?>"
                                        class="view-details-btn"
                                    >
                                        View Details
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <!-- MOBILE RESERVATION CARDS -->

            <div class="mobile-reservations">

                <?php foreach ($reservations as $reservation): ?>

                    <?php
                    $statusClass = strtolower(
                        preg_replace(
                            '/[^a-zA-Z0-9]+/',
                            '-',
                            $reservation['status']
                        )
                    );

                    $detailsUrl = 'reservation-details.php?id='
                        . (int)$reservation['reservation_id'];
                    ?>

                    <div class="mobile-reservation-card">

                        <div class="mobile-reservation-header">

                            <strong>
                                Reservation #<?= (int)$reservation['reservation_id'] ?>
                            </strong>

                            <span class="reservation-status <?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars($reservation['status']) ?>
                            </span>

                        </div>

                        <h3>
                            <?= htmlspecialchars($reservation['type_name']) ?>
                            - Room <?= htmlspecialchars($reservation['room_number']) ?>
                        </h3>

                        <p>
                            <span>Check-in:</span>
                            <?= htmlspecialchars($reservation['check_in_date']) ?>
                        </p>

                        <p>
                            <span>Check-out:</span>
                            <?= htmlspecialchars($reservation['check_out_date']) ?>
                        </p>

                        <p>
                            <span>Guests:</span>
                            <?= (int)$reservation['num_guests'] ?>
                        </p>

                        <div class="mobile-reservation-total">

                            <span>Total Amount</span>

                            <strong>
                                KSh <?= number_format(
                                    (float)$reservation['total_amount'],
                                    2
                                ) ?>
                            </strong>

                        </div>

                        <a
                            href="<?= htmlspecialchars($detailsUrl) ?>"
                            class="view-details-btn mobile-view-details"
                        >
                            View Details
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</section>

<style>

/* MY RESERVATIONS PAGE */

.my-reservations-page {
    max-width: 1200px;
    margin: 50px auto;
    padding: 0 22px;
}

.reservations-heading {
    margin-bottom: 30px;
}

.reservations-heading h1 {
    color: #1e2b45;
    font-size: 34px;
    margin: 12px 0;
}

.reservations-heading p {
    color: #6b7280;
    line-height: 1.6;
}

/* STATISTICS */

.reservation-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.reservation-stat-card {
    padding: 23px;
    background: #fff;
    border: 1px solid #e8e5df;
    border-radius: 10px;
}

.reservation-stat-card span {
    display: block;
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 12px;
}

.reservation-stat-card strong {
    color: #1e2b45;
    font-size: 28px;
}

/* SECTION HEADING */

.reservations-section {
    background: #fff;
    border: 1px solid #e8e5df;
    border-radius: 12px;
    padding: 25px;
}

.reservations-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.reservations-section-heading h2 {
    color: #1e2b45;
    margin: 0 0 8px;
}

.reservations-section-heading p {
    color: #6b7280;
    margin: 0;
    font-size: 14px;
}

/* NEW BOOKING BUTTON */

.new-booking-btn {
    display: inline-block;
    background: #1e2b45;
    color: #fff;
    padding: 12px 18px;
    border-radius: 7px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
}

.new-booking-btn:hover {
    background: #b89555;
    color: #fff;
}

/* RESERVATIONS TABLE */

.reservations-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.my-reservations-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.my-reservations-table th {
    background: #f7f5f0;
    color: #1e2b45;
    text-align: left;
    padding: 15px 12px;
    font-size: 13px;
}

.my-reservations-table td {
    padding: 17px 12px;
    border-bottom: 1px solid #eee;
    color: #4b5563;
    font-size: 14px;
}

.my-reservations-table td small {
    display: block;
    color: #888;
    margin-top: 5px;
}

.my-reservations-table td strong {
    color: #1e2b45;
}

/* VIEW DETAILS BUTTON */

.view-details-btn {
    display: inline-block;
    background: #b89555;
    color: #fff;
    padding: 9px 13px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}

.view-details-btn:hover {
    background: #1e2b45;
    color: #fff;
}

/* RESERVATION STATUS */

.reservation-status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}

.reservation-status.pending {
    background: #fff3d9;
    color: #946200;
}

.reservation-status.confirmed {
    background: #e4f5e9;
    color: #24733c;
}

.reservation-status.cancelled {
    background: #fde8e8;
    color: #a52c2c;
}

.reservation-status.completed {
    background: #e7edfa;
    color: #31558e;
}

/* EMPTY STATE */

.no-reservations {
    text-align: center;
    padding: 55px 20px;
}

.empty-reservation-icon {
    font-size: 45px;
    color: #b89555;
    margin-bottom: 15px;
}

.no-reservations h3 {
    color: #1e2b45;
}

.no-reservations p {
    color: #6b7280;
    max-width: 480px;
    margin: 12px auto 25px;
    line-height: 1.6;
}

/* MOBILE CARDS */

.mobile-reservations {
    display: none;
}

.mobile-reservation-card {
    border: 1px solid #e8e5df;
    border-radius: 10px;
    padding: 18px;
    margin-bottom: 15px;
}

.mobile-reservation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.mobile-reservation-header strong {
    color: #1e2b45;
}

.mobile-reservation-card h3 {
    color: #1e2b45;
    margin: 18px 0;
}

.mobile-reservation-card p {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    color: #6b7280;
    font-size: 14px;
}

.mobile-reservation-card p span {
    font-weight: 600;
    color: #1e2b45;
}

.mobile-reservation-total {
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #eee;
    padding-top: 15px;
    margin-top: 15px;
}

.mobile-reservation-total strong {
    color: #1e2b45;
}

.mobile-view-details {
    display: block;
    margin-top: 18px;
}

/* RESPONSIVE DESIGN */

@media (max-width: 850px) {

    .reservation-stats {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 600px) {

    .reservations-section-heading {
        flex-direction: column;
        align-items: flex-start;
    }

    .reservations-section-heading .new-booking-btn {
        width: 100%;
        box-sizing: border-box;
    }

    .reservations-table-wrapper {
        display: none;
    }

    .mobile-reservations {
        display: block;
    }

    .reservation-stats {
        gap: 12px;
    }

    .reservation-stat-card {
        padding: 17px;
    }

    .reservation-stat-card strong {
        font-size: 23px;
    }

    .my-reservations-page {
        margin: 30px auto;
    }

}

</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
