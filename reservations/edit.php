<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = requireLogin();

$pageTitle = 'Edit Reservation';

$error = '';

$reservationId = (int) ($_GET['id'] ?? $_POST['reservation_id'] ?? 0);

if ($reservationId <= 0) {
    redirect('reservations/my-reservations.php');
}


// Retrieve the customer's reservation.
$stmt = db()->prepare("
    SELECT
        reservations.*,
        rooms.room_number,
        rooms.status AS room_status,
        room_types.type_name,
        room_types.price_per_night,
        room_types.capacity
    FROM reservations
    INNER JOIN rooms
        ON reservations.room_id = rooms.room_id
    INNER JOIN room_types
        ON rooms.room_type_id = room_types.room_type_id
    WHERE reservations.reservation_id = :reservation_id
    AND reservations.user_id = :user_id
");

$stmt->execute([
    ':reservation_id' => $reservationId,
    ':user_id' => $user['id']
]);

$reservation = $stmt->fetch();

if (!$reservation) {
    http_response_code(404);
    exit('Reservation not found.');
}


// Only pending reservations can be modified.
if ($reservation['status'] !== 'Pending') {
    exit('Only pending reservations can be modified.');
}


$checkIn = $reservation['check_in_date'];
$checkOut = $reservation['check_out_date'];
$guests = (int) $reservation['num_guests'];


// Process update form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');
    $guests = (int) ($_POST['guests'] ?? 0);

    if (!csrfValid($_POST['csrf_token'] ?? '')) {

        $error = 'Invalid form submission. Please try again.';

    } else {

        try {

            $checkInDate = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $checkIn
            );

            $checkOutDate = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $checkOut
            );

            if (
                !$checkInDate ||
                !$checkOutDate ||
                $checkInDate->format('Y-m-d') !== $checkIn ||
                $checkOutDate->format('Y-m-d') !== $checkOut
            ) {
                throw new RuntimeException('Please enter valid dates.');
            }

            if ($checkInDate < new DateTimeImmutable('today')) {
                throw new RuntimeException(
                    'Check-in date cannot be in the past.'
                );
            }

            if ($checkOutDate <= $checkInDate) {
                throw new RuntimeException(
                    'Check-out must be after check-in.'
                );
            }

            if (
                $guests < 1 ||
                $guests > (int) $reservation['capacity']
            ) {
                throw new RuntimeException(
                    'Invalid number of guests for this room.'
                );
            }

            $pdo = db();

            $pdo->beginTransaction();

            // Lock the reservation and confirm ownership.
            $stmt = $pdo->prepare("
                SELECT reservation_id, room_id, status
                FROM reservations
                WHERE reservation_id = :reservation_id
                AND user_id = :user_id
                FOR UPDATE
            ");

            $stmt->execute([
                ':reservation_id' => $reservationId,
                ':user_id' => $user['id']
            ]);

            $currentReservation = $stmt->fetch();

            if (
                !$currentReservation ||
                $currentReservation['status'] !== 'Pending'
            ) {
                throw new RuntimeException(
                    'This reservation can no longer be modified.'
                );
            }

            // Lock the room.
            $stmt = $pdo->prepare("
                SELECT
                    rooms.status,
                    room_types.price_per_night,
                    room_types.capacity
                FROM rooms
                INNER JOIN room_types
                    ON rooms.room_type_id = room_types.room_type_id
                WHERE rooms.room_id = :room_id
                FOR UPDATE
            ");

            $stmt->execute([
                ':room_id' => $currentReservation['room_id']
            ]);

            $room = $stmt->fetch();

            if (!$room || $room['status'] !== 'Available') {
                throw new RuntimeException(
                    'This room is currently unavailable.'
                );
            }

            if ($guests > (int) $room['capacity']) {
                throw new RuntimeException(
                    'Guest number exceeds room capacity.'
                );
            }

            // Check overlapping reservations, excluding this reservation.
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM reservations
                WHERE room_id = :room_id
                AND reservation_id != :reservation_id
                AND status IN ('Pending', 'Confirmed')
                AND check_in_date < :check_out
                AND check_out_date > :check_in
            ");

            $stmt->execute([
                ':room_id' => $currentReservation['room_id'],
                ':reservation_id' => $reservationId,
                ':check_in' => $checkIn,
                ':check_out' => $checkOut
            ]);

            if ((int) $stmt->fetchColumn() > 0) {
                throw new RuntimeException(
                    'The room is already reserved for those dates.'
                );
            }

            // Check blocked dates.
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM room_availability
                WHERE room_id = :room_id
                AND start_date < :check_out
                AND end_date > :check_in
            ");

            $stmt->execute([
                ':room_id' => $currentReservation['room_id'],
                ':check_in' => $checkIn,
                ':check_out' => $checkOut
            ]);

            if ((int) $stmt->fetchColumn() > 0) {
                throw new RuntimeException(
                    'The room is blocked for the selected dates.'
                );
            }

            // Recalculate the total amount.
            $nights = $checkInDate->diff($checkOutDate)->days;

            $totalAmount = $nights * (float) $room['price_per_night'];

            // Update reservation.
            $stmt = $pdo->prepare("
                UPDATE reservations
                SET
                    check_in_date = :check_in,
                    check_out_date = :check_out,
                    num_guests = :guests,
                    total_amount = :total_amount
                WHERE reservation_id = :reservation_id
                AND user_id = :user_id
            ");

            $stmt->execute([
                ':check_in' => $checkIn,
                ':check_out' => $checkOut,
                ':guests' => $guests,
                ':total_amount' => $totalAmount,
                ':reservation_id' => $reservationId,
                ':user_id' => $user['id']
            ]);

            $pdo->commit();

            flash('success', 'Your reservation was updated successfully.');

            redirect('reservations/my-reservations.php');

        } catch (Throwable $exception) {

            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Edit Reservation</h2>

        <p>
            Update the dates or number of guests for your reservation.
        </p>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <div class="card">

            <div class="card-body">

                <h3>
                    <?= e($reservation['type_name']) ?>
                </h3>

                <p>
                    <strong>Room Number:</strong>
                    <?= e($reservation['room_number']) ?>
                </p>

                <p>
                    <strong>Price per Night:</strong>
                    KSh
                    <?= number_format(
                        (float) $reservation['price_per_night'],
                        2
                    ) ?>
                </p>

                <form method="POST" action="">

                    <?= csrfField() ?>

                    <input
                        type="hidden"
                        name="reservation_id"
                        value="<?= $reservationId ?>"
                    >

                    <div class="form-group">

                        <label for="check_in">
                            Check-in Date
                        </label>

                        <input
                            type="date"
                            id="check_in"
                            name="check_in"
                            value="<?= e($checkIn) ?>"
                            min="<?= date('Y-m-d') ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="check_out">
                            Check-out Date
                        </label>

                        <input
                            type="date"
                            id="check_out"
                            name="check_out"
                            value="<?= e($checkOut) ?>"
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="guests">
                            Number of Guests
                        </label>

                        <input
                            type="number"
                            id="guests"
                            name="guests"
                            min="1"
                            max="<?= (int) $reservation['capacity'] ?>"
                            value="<?= $guests ?>"
                            required
                        >

                    </div>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>

                    <a
                        href="<?= e(base_url('reservations/my-reservations.php')) ?>"
                        class="btn btn-secondary"
                    >
                        Back
                    </a>

                </form>

            </div>

        </div>

    </div>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>