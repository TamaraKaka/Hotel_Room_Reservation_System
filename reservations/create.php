<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/functions.php';

$user = requireLogin();

$pageTitle = 'Create Reservation';

$error = '';

$roomId = (int) ($_GET['room_id'] ?? $_POST['room_id'] ?? 0);

$checkIn = $_GET['check_in'] ?? $_POST['check_in'] ?? '';

$checkOut = $_GET['check_out'] ?? $_POST['check_out'] ?? '';

$guests = (int) ($_GET['guests'] ?? $_POST['guests'] ?? 1);

$room = null;


// Get room details.
try {

    if ($roomId <= 0) {
        throw new RuntimeException('Please select a valid room.');
    }

    $stmt = db()->prepare("
        SELECT
            rooms.room_id,
            rooms.room_number,
            rooms.floor,
            rooms.status,
            room_types.type_name,
            room_types.price_per_night,
            room_types.capacity,
            room_types.description
        FROM rooms
        INNER JOIN room_types
            ON rooms.room_type_id = room_types.room_type_id
        WHERE rooms.room_id = :room_id
    ");

    $stmt->execute([
        ':room_id' => $roomId
    ]);

    $room = $stmt->fetch();

    if (!$room) {
        throw new RuntimeException('Selected room was not found.');
    }

} catch (Throwable $exception) {

    $error = $exception->getMessage();
}


// Process reservation form.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $room) {

    if (!csrfValid($_POST['csrf_token'] ?? '')) {

        $error = 'Invalid form submission. Please try again.';

    } else {

        try {

            $reservationId = createReservation(
                (int) $user['id'],
                $roomId,
                $checkIn,
                $checkOut,
                $guests
            );

            flash(
                'success',
                'Your reservation has been created successfully!'
            );

            redirect('reservations/my-reservations.php');

        } catch (Throwable $exception) {

            $error = $exception->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Complete Your Reservation</h2>

        <p>
            Review the room details and enter your booking information.
        </p>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <?php if ($room): ?>

            <div class="card">

                <div class="card-body">

                    <h3>
                        <?= e($room['type_name']) ?>
                    </h3>

                    <p>
                        <strong>Room Number:</strong>
                        <?= e($room['room_number']) ?>
                    </p>

                    <p>
                        <strong>Floor:</strong>
                        <?= e((string) $room['floor']) ?>
                    </p>

                    <p>
                        <strong>Price per Night:</strong>
                        KSh
                        <?= number_format(
                            (float) $room['price_per_night'],
                            2
                        ) ?>
                    </p>

                    <p>
                        <strong>Maximum Capacity:</strong>
                        <?= (int) $room['capacity'] ?> guests
                    </p>

                    <p>
                        <?= e($room['description'] ?? '') ?>
                    </p>

                </div>

            </div>

            <form method="POST" action="">

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="room_id"
                    value="<?= $roomId ?>"
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
                        max="<?= (int) $room['capacity'] ?>"
                        value="<?= $guests ?>"
                        required
                    >

                </div>

                <button type="submit" class="btn btn-primary">
                    Confirm Reservation
                </button>

            </form>

        <?php endif; ?>

    </div>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>