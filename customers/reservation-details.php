<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

// Only logged-in customers can view reservation details.
$user = requireLogin();

$conn = authDb();

// Get the reservation ID from the URL.
$reservationId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);

$reservation = null;

// Find the matching hotel system user.
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

// Retrieve the reservation only if it belongs to this customer.
if ($hotelUser && $reservationId !== false && $reservationId !== null && $reservationId > 0) {

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
        WHERE r.reservation_id = :reservation_id
        AND r.user_id = :user_id
        LIMIT 1
    ");

    $reservationQuery->execute([
        ':reservation_id' => $reservationId,
        ':user_id' => $hotelUser['user_id']
    ]);

    $reservation = $reservationQuery->fetch(PDO::FETCH_ASSOC);
}

// Calculate the length of the stay.
$nights = 0;

if ($reservation) {
    $checkInDate = new DateTime($reservation['check_in_date']);
    $checkOutDate = new DateTime($reservation['check_out_date']);

    $nights = $checkInDate->diff($checkOutDate)->days;
}

// Prepare status styling.
$statusClass = '';

if ($reservation) {
    $statusClass = strtolower(
        preg_replace(
            '/[^a-zA-Z0-9]+/',
            '-',
            $reservation['status']
        )
    );
}

$pageTitle = 'Reservation Details';

include __DIR__ . '/../includes/header.php';
?>

<section class="reservation-details-page">

    <div class="reservation-details-heading">

        <span class="details-label">HOTELHUB CUSTOMER PORTAL</span>

        <h1>Reservation Details</h1>

        <p>
            View the information associated with your hotel booking.
        </p>

    </div>

    <?php if (!$reservation): ?>

        <div class="reservation-not-found">

            <h2>Reservation Not Found</h2>

            <p>
                We could not find this reservation under your account.
                It may not exist, or you may not have permission to view it.
            </p>

            <a href="my-reservations.php" class="back-reservations-btn">
                Back to My Reservations
            </a>

        </div>

    <?php else: ?>

        <div class="reservation-details-card">

            <div class="reservation-details-top">

                <div>
                    <span class="reference-label">RESERVATION REFERENCE</span>

                    <h2>
                        #<?= (int)$reservation['reservation_id'] ?>
                    </h2>
                </div>

                <span class="reservation-status <?= htmlspecialchars($statusClass) ?>">
                    <?= htmlspecialchars($reservation['status']) ?>
                </span>

            </div>

            <div class="reservation-details-content">

                <div class="reservation-detail-section">

                    <h3>Room Information</h3>

                    <div class="reservation-detail-grid">

                        <div class="detail-item">
                            <span>Room Type</span>
                            <strong>
                                <?= htmlspecialchars($reservation['type_name']) ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Room Number</span>
                            <strong>
                                <?= htmlspecialchars($reservation['room_number']) ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Price Per Night</span>
                            <strong>
                                KSh <?= number_format(
                                    (float)$reservation['price_per_night'],
                                    2
                                ) ?>
                            </strong>
                        </div>

                    </div>

                </div>

                <div class="reservation-detail-section">

                    <h3>Stay Information</h3>

                    <div class="reservation-detail-grid">

                        <div class="detail-item">
                            <span>Check-in Date</span>
                            <strong>
                                <?= htmlspecialchars($reservation['check_in_date']) ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Check-out Date</span>
                            <strong>
                                <?= htmlspecialchars($reservation['check_out_date']) ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Number of Nights</span>
                            <strong>
                                <?= (int)$nights ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Number of Guests</span>
                            <strong>
                                <?= (int)$reservation['num_guests'] ?>
                            </strong>
                        </div>

                    </div>

                </div>

                <div class="reservation-detail-section">

                    <h3>Payment Summary</h3>

                    <div class="payment-summary">

                        <div>
                            <span>Room Rate</span>
                            <strong>
                                KSh <?= number_format(
                                    (float)$reservation['price_per_night'],
                                    2
                                ) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Number of Nights</span>
                            <strong>
                                <?= (int)$nights ?>
                            </strong>
                        </div>

                        <div class="payment-total">
                            <span>Total Reservation Amount</span>

                            <strong>
                                KSh <?= number_format(
                                    (float)$reservation['total_amount'],
                                    2
                                ) ?>
                            </strong>
                        </div>

                    </div>

                </div>

                <div class="reservation-detail-section">

                    <h3>Booking Information</h3>

                    <div class="detail-item">
                        <span>Reservation Date</span>

                        <strong>
                            <?= htmlspecialchars($reservation['created_at']) ?>
                        </strong>
                    </div>

                </div>

            </div>

        </div>

        <div class="reservation-details-actions">

            <a href="my-reservations.php" class="back-reservations-btn">
                Back to My Reservations
            </a>

            <a href="../search/index.php" class="new-booking-btn">
                Book Another Room
            </a>

        </div>

    <?php endif; ?>

</section>

<style>

.reservation-details-page {
    max-width: 1000px;
    margin: 50px auto;
    padding: 0 22px;
}

.reservation-details-heading {
    margin-bottom: 30px;
}

.reservation-details-heading h1 {
    color: #1e2b45;
    font-size: 34px;
    margin: 12px 0;
}

.reservation-details-heading p {
    color: #6b7280;
    line-height: 1.6;
}

.reservation-details-card,
.reservation-not-found {
    background: #fff;
    border: 1px solid #e8e5df;
    border-radius: 12px;
    padding: 28px;
}

.reservation-details-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    padding-bottom: 22px;
    border-bottom: 1px solid #eee;
}

.reference-label {
    color: #8b8b8b;
    font-size: 12px;
    letter-spacing: 1px;
    font-weight: 600;
}

.reservation-details-top h2 {
    color: #1e2b45;
    margin: 8px 0 0;
    font-size: 28px;
}

.reservation-detail-section {
    padding: 25px 0;
    border-bottom: 1px solid #eee;
}

.reservation-detail-section:last-child {
    border-bottom: none;
}

.reservation-detail-section h3 {
    color: #1e2b45;
    margin: 0 0 20px;
}

.reservation-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 22px;
}

.detail-item span {
    display: block;
    color: #777;
    font-size: 13px;
    margin-bottom: 7px;
}

.detail-item strong {
    color: #1e2b45;
    font-size: 15px;
}

.payment-summary {
    background: #f7f5f0;
    padding: 20px;
    border-radius: 8px;
}

.payment-summary > div {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 15px;
    color: #6b7280;
}

.payment-summary strong {
    color: #1e2b45;
}

.payment-summary .payment-total {
    border-top: 1px solid #ddd;
    padding-top: 17px;
    margin: 0;
    color: #1e2b45;
    font-weight: 600;
}

.payment-summary .payment-total strong {
    color: #b89555;
    font-size: 19px;
}

.reservation-details-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 25px;
}

.back-reservations-btn,
.new-booking-btn {
    display: inline-block;
    padding: 12px 18px;
    border-radius: 7px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
}

.back-reservations-btn {
    background: #f1eee7;
    color: #1e2b45;
}

.back-reservations-btn:hover {
    background: #e5dfd2;
}

.new-booking-btn {
    background: #1e2b45;
    color: #fff;
}

.new-booking-btn:hover {
    background: #b89555;
    color: #fff;
}

.reservation-not-found {
    text-align: center;
    padding: 45px 25px;
}

.reservation-not-found h2 {
    color: #1e2b45;
}

.reservation-not-found p {
    color: #6b7280;
    line-height: 1.7;
    margin-bottom: 25px;
}

@media (max-width: 600px) {

    .reservation-details-page {
        margin: 30px auto;
    }

    .reservation-details-card {
        padding: 20px;
    }

    .reservation-detail-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .reservation-details-actions {
        flex-direction: column;
    }

    .reservation-details-actions a {
        width: 100%;
        box-sizing: border-box;
    }

    .payment-summary > div {
        font-size: 13px;
    }

}

</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
