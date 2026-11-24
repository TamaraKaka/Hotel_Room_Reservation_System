<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = requireLogin();

$pageTitle = 'Reservation Details';

// Get reservation ID.
$reservationId = (int) ($_GET['id'] ?? 0);

if ($reservationId <= 0) {

    flash('error', 'Invalid reservation selected.');

    redirect('reservations/my-reservations.php');
}

// Retrieve reservation details.
$stmt = db()->prepare("
    SELECT
        reservations.reservation_id,
        reservations.check_in_date,
        reservations.check_out_date,
        reservations.num_guests,
        reservations.total_amount,
        reservations.status,
        reservations.created_at,

        rooms.room_number,
        rooms.floor,

        room_types.type_name,
        room_types.price_per_night,
        room_types.capacity,
        room_types.description,

        users.full_name,
        users.email

    FROM reservations

    INNER JOIN rooms
        ON reservations.room_id = rooms.room_id

    INNER JOIN room_types
        ON rooms.room_type_id = room_types.room_type_id

    INNER JOIN users
        ON reservations.user_id = users.user_id

    WHERE reservations.reservation_id = :reservation_id
    AND reservations.user_id = :user_id
");

$stmt->execute([
    ':reservation_id' => $reservationId,
    ':user_id' => $user['id']
]);

$reservation = $stmt->fetch();

// Ensure the reservation exists and belongs to the logged-in user.
if (!$reservation) {

    flash('error', 'Reservation not found.');

    redirect('reservations/my-reservations.php');
}

// Calculate number of nights.
$checkIn = new DateTime($reservation['check_in_date']);

$checkOut = new DateTime($reservation['check_out_date']);

$nights = $checkIn->diff($checkOut)->days;

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Reservation Details</h2>

        <p>
            View the complete information about your hotel reservation.
        </p>

        <div class="card">

            <div class="card-body">

                <h3>
                    Reservation #<?= (int) $reservation['reservation_id'] ?>
                </h3>

                <h4>Customer Information</h4>

                <p>
                    <strong>Full Name:</strong>
                    <?= e($reservation['full_name']) ?>
                </p>

                <p>
                    <strong>Email Address:</strong>
                    <?= e($reservation['email']) ?>
                </p>

                <hr>

                <h4>Room Information</h4>

                <p>
                    <strong>Room Number:</strong>
                    <?= e($reservation['room_number']) ?>
                </p>

                <p>
                    <strong>Room Type:</strong>
                    <?= e($reservation['type_name']) ?>
                </p>

                <p>
                    <strong>Floor:</strong>
                    <?= e((string) $reservation['floor']) ?>
                </p>

                <p>
                    <strong>Price Per Night:</strong>
                    KSh
                    <?= number_format(
                        (float) $reservation['price_per_night'],
                        2
                    ) ?>
                </p>

                <p>
                    <strong>Room Description:</strong>
                    <?= e($reservation['description'] ?? '') ?>
                </p>

                <hr>

                <h4>Booking Information</h4>

                <p>
                    <strong>Check-in Date:</strong>
                    <?= e($reservation['check_in_date']) ?>
                </p>

                <p>
                    <strong>Check-out Date:</strong>
                    <?= e($reservation['check_out_date']) ?>
                </p>

                <p>
                    <strong>Number of Nights:</strong>
                    <?= $nights ?>
                </p>

                <p>
                    <strong>Number of Guests:</strong>
                    <?= (int) $reservation['num_guests'] ?>
                </p>

                <p>
                    <strong>Total Amount:</strong>
                    KSh
                    <?= number_format(
                        (float) $reservation['total_amount'],
                        2
                    ) ?>
                </p>

                <p>
                    <strong>Reservation Status:</strong>
                    <?= e($reservation['status']) ?>
                </p>

                <p>
                    <strong>Date Reserved:</strong>
                    <?= e($reservation['created_at']) ?>
                </p>

                <hr>

                <a
                    href="<?= e(base_url('reservations/my-reservations.php')) ?>"
                    class="btn btn-primary"
                >
                    Back to My Reservations
                </a>

            </div>

        </div>

    </div>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>