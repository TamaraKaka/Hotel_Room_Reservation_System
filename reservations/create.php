
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/availability.php';

$user = requireLogin();
$conn = authDb();

$pageTitle = 'Complete Your Reservation';
$error = '';
$successReservation = null;

// Validate date format.
function validBookingDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

// Find the matching reservation if this is the confirmation page.
$reservationId = filter_var(
    $_GET['reservation_id'] ?? null,
    FILTER_VALIDATE_INT
);

if ($reservationId) {
    $confirmationQuery = "
        SELECT
            r.reservation_id,
            r.check_in_date,
            r.check_out_date,
            r.num_guests,
            r.total_amount,
            r.status,
            rooms.room_number,
            room_types.type_name
        FROM reservations r
        JOIN users u ON r.user_id = u.user_id
        JOIN rooms ON r.room_id = rooms.room_id
        JOIN room_types ON rooms.room_type_id = room_types.room_type_id
        WHERE r.reservation_id = :reservation_id
        AND LOWER(u.email) = LOWER(:email)
    ";

    $stmt = $conn->prepare($confirmationQuery);
    $stmt->execute([
        ':reservation_id' => $reservationId,
        ':email' => $user['email']
    ]);

    $successReservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$successReservation) {
        exit('Reservation not found.');
    }
}

// Read booking details from the URL or submitted form.
$roomId = filter_var(
    $_POST['room_id'] ?? $_GET['room_id'] ?? null,
    FILTER_VALIDATE_INT
);

$checkIn = $_POST['check_in'] ?? $_GET['check_in'] ?? '';
$checkOut = $_POST['check_out'] ?? $_GET['check_out'] ?? '';

$guests = filter_var(
    $_POST['guests'] ?? $_GET['guests'] ?? null,
    FILTER_VALIDATE_INT
);

$phone = trim($_POST['phone'] ?? '');

$room = null;
$nights = 0;
$total = 0;
$pricePerNight = 0;

// Process the booking form.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$successReservation) {

    authVerifyCsrf();

    if (
        !$roomId ||
        !$guests ||
        $guests < 1 ||
        !validBookingDate($checkIn) ||
        !validBookingDate($checkOut) ||
        $checkOut <= $checkIn
    ) {
        $error = 'Please check your booking details and try again.';
    } elseif (
        !preg_match('/^[0-9+\s()\-]{7,20}$/', $phone)
    ) {
        $error = 'Please enter a valid phone number.';
    } else {
        try {
            $conn->beginTransaction();

            // Lock the selected room while checking availability.
            $roomQuery = "
                SELECT
                    rooms.*,
                    room_types.type_name,
                    room_types.price_per_night,
                    room_types.capacity,
                    room_types.description
                FROM rooms
                JOIN room_types
                    ON rooms.room_type_id = room_types.room_type_id
                WHERE rooms.room_id = :room_id
                FOR UPDATE
            ";

            $stmt = $conn->prepare($roomQuery);
            $stmt->execute([':room_id' => $roomId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) {
                throw new Exception('The selected room could not be found.');
            }

            if ((int)$room['capacity'] < $guests) {
                throw new Exception(
                    'This room cannot accommodate the selected number of guests.'
                );
            }

            // Check availability again before saving.
            if (!isRoomAvailable($conn, $roomId, $checkIn, $checkOut)) {
                throw new Exception(
                    'Sorry, this room is no longer available for those dates. Please return to the search results.'
                );
            }

            // Find the corresponding account in the users table.
            $userQuery = "
                SELECT user_id
                FROM users
                WHERE LOWER(email) = LOWER(:email)
                LIMIT 1
            ";

            $stmt = $conn->prepare($userQuery);
            $stmt->execute([':email' => $user['email']]);
            $hotelUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($hotelUser) {
                $hotelUserId = (int)$hotelUser['user_id'];
            } else {
                // The users table requires a password hash.
                // Reuse the hash belonging to the logged-in Customers account.
                $customerQuery = "
                    SELECT password_hash
                    FROM Customers
                    WHERE id = :customer_id
                ";

                $stmt = $conn->prepare($customerQuery);
                $stmt->execute([':customer_id' => $user['id']]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$customer) {
                    throw new Exception(
                        'Your customer account could not be verified.'
                    );
                }

                if (
                    mb_strlen($user['full_name'], 'UTF-8') > 100 ||
                    mb_strlen($user['email'], 'UTF-8') > 100
                ) {
                    throw new Exception(
                        'Your account details exceed the current reservation database limits. Please contact the administrator.'
                    );
                }

                $insertUser = "
                    INSERT INTO users
                        (full_name, email, phone, password_hash, role, is_active)
                    VALUES
                        (:full_name, :email, :phone, :password_hash, 'Customer', 1)
                ";

                $stmt = $conn->prepare($insertUser);
                $stmt->execute([
                    ':full_name' => $user['full_name'],
                    ':email' => $user['email'],
                    ':phone' => $phone,
                    ':password_hash' => $customer['password_hash']
                ]);

                $hotelUserId = (int)$conn->lastInsertId();
            }

            // Calculate the total booking cost.
            $checkInDate = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);

            $nights = (int)$checkInDate->diff($checkOutDate)->days;
            $pricePerNight = (float)$room['price_per_night'];
            $total = $nights * $pricePerNight;

            // Save the reservation as Pending.
            $insertReservation = "
                INSERT INTO reservations
                    (
                        user_id,
                        room_id,
                        check_in_date,
                        check_out_date,
                        num_guests,
                        total_amount,
                        status
                    )
                VALUES
                    (?, ?, ?, ?, ?, ?, 'Pending')
            ";

            $stmt = $conn->prepare($insertReservation);
            $stmt->execute([
                $hotelUserId,
                $roomId,
                $checkIn,
                $checkOut,
                $guests,
                $total
            ]);

            $newReservationId = (int)$conn->lastInsertId();

            $conn->commit();

            // Redirect to prevent the form being submitted again on refresh.
            header(
                'Location: create.php?reservation_id=' . $newReservationId,
                true,
                303
            );
            exit;

        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = $e instanceof PDOException
                ? 'We could not save your reservation. Please try again.'
                : $e->getMessage();
        }
    }
}

// Retrieve room information for the booking form.
if (!$successReservation && $roomId && $checkIn && $checkOut && $guests) {

    if (
        validBookingDate($checkIn) &&
        validBookingDate($checkOut) &&
        $checkOut > $checkIn &&
        $guests > 0
    ) {
        $stmt = $conn->prepare("
            SELECT
                rooms.*,
                room_types.type_name,
                room_types.price_per_night,
                room_types.capacity,
                room_types.description
            FROM rooms
            JOIN room_types
                ON rooms.room_type_id = room_types.room_type_id
            WHERE rooms.room_id = ?
        ");

        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($room) {
            $nights = (int)(new DateTime($checkIn))
                ->diff(new DateTime($checkOut))->days;

            $pricePerNight = (float)$room['price_per_night'];
            $total = $nights * $pricePerNight;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="reservation-page">

    <div class="details-heading">
        <span class="details-label">HOTELHUB</span>

        <?php if ($successReservation): ?>
            <h1>Reservation Submitted</h1>
            <p>Your reservation has been successfully recorded.</p>
        <?php else: ?>
            <h1>Complete Your Reservation</h1>
            <p>Review your booking details before submitting.</p>
        <?php endif; ?>
    </div>

    <?php if ($successReservation): ?>

        <div class="reservation-confirmation">

            <div class="confirmation-icon">✓</div>

            <h2>Thank you, <?= htmlspecialchars($user['full_name']) ?>!</h2>

            <p>
                Your reservation has been received and is awaiting confirmation.
            </p>

            <div class="confirmation-reference">
                <span>Reservation Reference</span>
                <strong>
                    #<?= (int)$successReservation['reservation_id'] ?>
                </strong>
            </div>

            <div class="reservation-confirmation-details">
                <p>
                    <strong>Room:</strong>
                    <?= htmlspecialchars($successReservation['type_name']) ?>
                    - <?= htmlspecialchars($successReservation['room_number']) ?>
                </p>

                <p>
                    <strong>Check-in:</strong>
                    <?= htmlspecialchars($successReservation['check_in_date']) ?>
                </p>

                <p>
                    <strong>Check-out:</strong>
                    <?= htmlspecialchars($successReservation['check_out_date']) ?>
                </p>

                <p>
                    <strong>Guests:</strong>
                    <?= (int)$successReservation['num_guests'] ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    KSh <?= number_format((float)$successReservation['total_amount'], 2) ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= htmlspecialchars($successReservation['status']) ?>
                </p>
            </div>

            <a href="../search/index.php" class="back-results-btn">
                Return to Search Rooms
            </a>

        </div>

    <?php elseif (!$room): ?>

        <div class="reservation-form-card">
            <p>
                The booking details are missing or invalid.
                Please return to Search Rooms and select a room again.
            </p>

            <a href="../search/index.php" class="back-results-btn">
                Search Rooms
            </a>
        </div>

    <?php else: ?>

        <div class="reservation-layout">

            <div class="reservation-form-card">

                <h2>Guest Information</h2>

                <p>
                    Your account information is shown below.
                    Please provide a contact phone number.
                </p>

                <?php if ($error): ?>
                    <div class="reservation-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="create.php">

                    <input type="hidden" name="csrf"
                           value="<?= htmlspecialchars(authCsrf()) ?>">

                    <input type="hidden" name="room_id"
                           value="<?= (int)$roomId ?>">

                    <input type="hidden" name="check_in"
                           value="<?= htmlspecialchars($checkIn) ?>">

                    <input type="hidden" name="check_out"
                           value="<?= htmlspecialchars($checkOut) ?>">

                    <input type="hidden" name="guests"
                           value="<?= (int)$guests ?>">

                    <div class="reservation-field">
                        <label>Full Name</label>
                        <input type="text"
                               value="<?= htmlspecialchars($user['full_name']) ?>"
                               readonly>
                    </div>

                    <div class="reservation-field">
                        <label>Email Address</label>
                        <input type="email"
                               value="<?= htmlspecialchars($user['email']) ?>"
                               readonly>
                    </div>

                    <div class="reservation-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               maxlength="20"
                               placeholder="e.g. 0712345678"
                               value="<?= htmlspecialchars($phone) ?>"
                               required>
                    </div>

                    <button type="submit" class="confirm-reservation-btn">
                        Confirm Reservation
                    </button>

                </form>

            </div>

            <aside class="reservation-summary-card">

                <h2>Booking Summary</h2>

                <h3>
                    <?= htmlspecialchars($room['type_name']) ?>
                    - Room <?= htmlspecialchars($room['room_number']) ?>
                </h3>

                <div class="booking-detail-row">
                    <span>Check-in</span>
                    <strong><?= htmlspecialchars($checkIn) ?></strong>
                </div>

                <div class="booking-detail-row">
                    <span>Check-out</span>
                    <strong><?= htmlspecialchars($checkOut) ?></strong>
                </div>

                <div class="booking-detail-row">
                    <span>Guests</span>
                    <strong><?= (int)$guests ?></strong>
                </div>

                <div class="booking-detail-row">
                    <span>Nights</span>
                    <strong><?= $nights ?></strong>
                </div>

                <div class="booking-detail-row">
                    <span>Price per night</span>
                    <strong>
                        KSh <?= number_format($pricePerNight, 2) ?>
                    </strong>
                </div>

                <div class="booking-total">
                    <span>Total Amount</span>
                    <strong>
                        KSh <?= number_format($total, 2) ?>
                    </strong>
                </div>

                <p class="booking-notice">
                    Your reservation will remain Pending until it is confirmed.
                </p>

            </aside>

        </div>

    <?php endif; ?>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
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
