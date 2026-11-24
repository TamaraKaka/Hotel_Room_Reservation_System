<?php

require_once __DIR__ . '/../includes/db.php';

$conn = db();

// Get search details
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = filter_var(
    $_GET['guests'] ?? null,
    FILTER_VALIDATE_INT
);
$roomType = $_GET['room_type'] ?? '';

// Validate search details
if (
    !$checkIn ||
    !$checkOut ||
    !strtotime($checkIn) ||
    !strtotime($checkOut) ||
    $checkOut <= $checkIn ||
    !$guests ||
    $guests < 1
) {
    exit('Invalid search details. Please go back and try again.');
}

// Find available rooms
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

    WHERE rooms.status = 'Available'

    AND room_types.capacity >= :guests

    AND rooms.room_id NOT IN (
        SELECT room_id
        FROM reservations
        WHERE status IN ('Pending', 'Confirmed')
        AND check_in_date < :check_out
        AND check_out_date > :check_in
    )

    AND rooms.room_id NOT IN (
        SELECT room_id
        FROM room_availability
        WHERE start_date < :blocked_checkout
        AND end_date > :blocked_checkin
    )
";

// Filter by room type if selected
if ($roomType !== '') {
    $sql .= " AND rooms.room_type_id = :room_type";
}

$sql .= " ORDER BY room_types.price_per_night ASC";

$stmt = $conn->prepare($sql);

$params = [
    ':guests' => $guests,
    ':check_in' => $checkIn,
    ':check_out' => $checkOut,
    ':blocked_checkout' => $checkOut,
    ':blocked_checkin' => $checkIn
];

if ($roomType !== '') {
    $params[':room_type'] = $roomType;
}

$stmt->execute($params);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate number of nights
$nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;

$pageTitle = "Available Rooms";

require_once __DIR__ . '/../includes/header.php';

?>

<section class="section">

    <div class="container">

        <h1>Available Rooms</h1>

        <p>
            Check-in: <?= htmlspecialchars($checkIn) ?><br>

            Check-out: <?= htmlspecialchars($checkOut) ?><br>

            Guests: <?= htmlspecialchars((string) $guests) ?>
        </p>

        <?php if (count($rooms) > 0): ?>

            <p>
                <?= count($rooms) ?> room(s) found.
            </p>

            <?php foreach ($rooms as $room): ?>

                <div class="room-card">

                    <img
                        src="<?= htmlspecialchars(base_url('assets/images/room-default.jpg')) ?>"
                        alt="Hotel room"
                        class="room-image"
                    >

                    <h2>
                        <?= htmlspecialchars($room['type_name']) ?>

                        - Room
                        <?= htmlspecialchars($room['room_number']) ?>
                    </h2>

                    <p>
                        Capacity:
                        <?= htmlspecialchars((string) $room['capacity']) ?>
                        guest(s)
                    </p>

                    <p>
                        Floor:
                        <?= htmlspecialchars((string) $room['floor']) ?>
                    </p>

                    <p>
                        <?= htmlspecialchars($room['description'] ?? '') ?>
                    </p>

                    <p class="room-price">

                        KSh
                        <?= number_format(
                            (float) $room['price_per_night'],
                            2
                        ) ?>

                        per night

                    </p>

                    <p>

                        Total for <?= (int) $nights ?> night(s):

                        <strong>

                            KSh
                            <?= number_format(
                                (float) $room['price_per_night'] * $nights,
                                2
                            ) ?>

                        </strong>

                    </p>

                    <div class="room-actions">

                        <a
                            href="<?= htmlspecialchars(
                                base_url(
                                    'search/details.php?room_id=' .
                                    (int) $room['room_id'] .
                                    '&check_in=' . urlencode($checkIn) .
                                    '&check_out=' . urlencode($checkOut) .
                                    '&guests=' . $guests
                                )
                            ) ?>"
                            class="view-btn"
                        >
                            View Details
                        </a>

                        <a
                            href="<?= htmlspecialchars(
                                base_url(
                                    'reservations/create.php?room_id=' .
                                    (int) $room['room_id'] .
                                    '&check_in=' . urlencode($checkIn) .
                                    '&check_out=' . urlencode($checkOut) .
                                    '&guests=' . $guests
                                )
                            ) ?>"
                            class="book-btn"
                        >
                            Book Now
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                No rooms are available for your selected dates.
            </p>

        <?php endif; ?>

    </div>

</section>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>