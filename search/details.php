
<?php

require_once __DIR__ . '/../includes/db.php';

$conn = db();

// Get selected room and booking details
$roomId = filter_var($_GET['room_id'] ?? null, FILTER_VALIDATE_INT);
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = filter_var($_GET['guests'] ?? null, FILTER_VALIDATE_INT);

// Validate booking dates
$checkInDate = DateTime::createFromFormat('!Y-m-d', $checkIn);
$checkOutDate = DateTime::createFromFormat('!Y-m-d', $checkOut);

if (
    !$roomId ||
    !$guests ||
    $guests < 1 ||
    !$checkInDate ||
    !$checkOutDate ||
    $checkInDate->format('Y-m-d') !== $checkIn ||
    $checkOutDate->format('Y-m-d') !== $checkOut ||
    $checkOutDate <= $checkInDate
) {
    exit('Invalid room or booking details. Please return to Search Rooms.');
}

// Retrieve selected room
$sql = "
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
";

$stmt = $conn->prepare($sql);
$stmt->execute([':room_id' => $roomId]);

$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    exit('Room not found.');
}

// Check room capacity and general status
if (
    $room['status'] !== 'Available' ||
    (int)$room['capacity'] < $guests
) {
    exit(
        'This room is not suitable for your booking. Please choose another room.'
    );
}

// Check for overlapping reservations
$availabilitySql = "
    SELECT COUNT(*)
    FROM reservations
    WHERE room_id = :room_id
    AND status IN ('Pending', 'Confirmed')
    AND check_in_date < :check_out
    AND check_out_date > :check_in
";

$availabilityStmt = $conn->prepare($availabilitySql);

$availabilityStmt->execute([
    ':room_id' => $roomId,
    ':check_in' => $checkIn,
    ':check_out' => $checkOut
]);

$reservationCount = (int)$availabilityStmt->fetchColumn();

// Check blocked dates
$blockedSql = "
    SELECT COUNT(*)
    FROM room_availability
    WHERE room_id = :room_id
    AND start_date < :check_out
    AND end_date > :check_in
";

$blockedStmt = $conn->prepare($blockedSql);

$blockedStmt->execute([
    ':room_id' => $roomId,
    ':check_in' => $checkIn,
    ':check_out' => $checkOut
]);

$blockedCount = (int)$blockedStmt->fetchColumn();

// Stop if the room is no longer available
if ($reservationCount > 0 || $blockedCount > 0) {
    exit(
        'Sorry, this room is no longer available for your selected dates. Please return to the search results.'
    );
}

// Calculate booking cost
$nights = (int)$checkInDate->diff($checkOutDate)->days;

$pricePerNight = (float)$room['price_per_night'];

$total = $nights * $pricePerNight;

$pageTitle = "Room Details";

include __DIR__ . '/../includes/header.php';

?>

<section class="room-details-page">

    <div class="details-heading">

        <span class="details-label">
            HOTELHUB ACCOMMODATION
        </span>

        <h1>Room Details</h1>

        <p>
            Explore your selected accommodation before proceeding.
        </p>

    </div>

    <div class="details-layout">

        <!-- ROOM INFORMATION -->

        <div class="details-main">

            <div class="details-image">

                <img
                    src="../assets/images/room-default.jpg"
                    alt="<?= htmlspecialchars($room['type_name']) ?> hotel room"
                >

                <span class="details-availability">
                    Available
                </span>

            </div>

            <div class="details-information">

                <span class="room-category">
                    <?= htmlspecialchars($room['type_name']) ?> ROOM
                </span>

                <h2>
                    <?= htmlspecialchars($room['type_name']) ?>
                    - Room <?= htmlspecialchars($room['room_number']) ?>
                </h2>

                <p class="details-description">
                    <?= htmlspecialchars(
                        $room['description'] ??
                        'A comfortable room for your stay.'
                    ) ?>
                </p>

                <!-- ROOM FEATURES -->

                <div class="details-features">

                    <div class="feature-item">

                        <span>Guest Capacity</span>

                        <strong>
                            <?= (int)$room['capacity'] ?> Guests
                        </strong>

                    </div>

                    <div class="feature-item">

                        <span>Room Number</span>

                        <strong>
                            <?= htmlspecialchars($room['room_number']) ?>
                        </strong>

                    </div>

                    <div class="feature-item">

                        <span>Floor</span>

                        <strong>
                            <?= (int)$room['floor'] ?>
                        </strong>

                    </div>

                    <div class="feature-item">

                        <span>Room Status</span>

                        <strong>Available</strong>

                    </div>

                </div>

                <!-- ROOM PRICE -->

                <div class="details-price">

                    <span>Room price per night</span>

                    <strong>
                        KSh <?= number_format($pricePerNight, 2) ?>
                    </strong>

                </div>

            </div>

        </div>

        <!-- BOOKING SUMMARY -->

        <aside class="booking-details-card">

            <h2>Your Booking Summary</h2>

            <p class="booking-summary-description">
                Review your selected booking information.
            </p>

            <div class="booking-detail-row">

                <span>Check-in</span>

                <strong>
                    <?= htmlspecialchars($checkIn) ?>
                </strong>

            </div>

            <div class="booking-detail-row">

                <span>Check-out</span>

                <strong>
                    <?= htmlspecialchars($checkOut) ?>
                </strong>

            </div>

            <div class="booking-detail-row">

                <span>Number of guests</span>

                <strong>
                    <?= (int)$guests ?>
                </strong>

            </div>

            <div class="booking-detail-row">

                <span>Number of nights</span>

                <strong>
                    <?= $nights ?>
                </strong>

            </div>

            <div class="booking-detail-row">

                <span>Price per night</span>

                <strong>
                    KSh <?= number_format($pricePerNight, 2) ?>
                </strong>

            </div>

            <!-- TOTAL COST -->

            <div class="booking-total">

                <span>Total Booking Cost</span>

                <strong>
                    KSh <?= number_format($total, 2) ?>
                </strong>

            </div>

            <p class="booking-notice">
                Your selected room is ready for booking.
                Continue to provide your guest information and submit
                your reservation.
            </p>

            <!-- BOOK NOW BUTTON -->

            <a
                href="../reservations/create.php?room_id=<?= (int)$roomId ?>&check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= (int)$guests ?>"
                class="confirm-reservation-btn"
            >
                Book Now
            </a>

            <!-- BACK TO RESULTS -->

            <a
                href="results.php?check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= (int)$guests ?>"
                class="back-results-btn"
            >
                Back to Available Rooms
            </a>

        </aside>

    </div>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
