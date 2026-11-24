
<?php
require_once __DIR__ . '/../includes/db.php';

$conn = db();

// Get search details
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = filter_var($_GET['guests'] ?? null, FILTER_VALIDATE_INT);
$roomType = $_GET['room_type'] ?? '';

// Validate search details
if (
    !$checkIn || !$checkOut ||
    !strtotime($checkIn) || !strtotime($checkOut) ||
    $checkOut <= $checkIn ||
    !$guests || $guests < 1
) {
    exit('Invalid search details. Please go back and try again.');
}

// Find available rooms
$sql = "
    SELECT rooms.*, room_types.type_name,
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
$nights = (int) ((strtotime($checkOut) - strtotime($checkIn)) / 86400);

$pageTitle = "Available Rooms";
include __DIR__ . '/../includes/header.php';
?>

<section class="results-page">

    <div class="results-heading">
        <span class="results-label">HOTELHUB ACCOMMODATION</span>

        <h1>Available Rooms</h1>

        <p>
            Discover a comfortable space for your next stay.
        </p>
    </div>

    <div class="booking-summary">

        <div class="summary-item">
            <span>CHECK-IN</span>
            <strong><?= htmlspecialchars($checkIn) ?></strong>
        </div>

        <div class="summary-item">
            <span>CHECK-OUT</span>
            <strong><?= htmlspecialchars($checkOut) ?></strong>
        </div>

        <div class="summary-item">
            <span>GUESTS</span>
            <strong><?= htmlspecialchars((string) $guests) ?></strong>
        </div>

        <div class="summary-item">
            <span>DURATION</span>
            <strong><?= $nights ?> night(s)</strong>
        </div>

        <a href="index.php" class="modify-search">
            Modify Search
        </a>

    </div>

    <div class="results-count">
        <h2>Our Available Accommodation</h2>

        <p>
            <?= count($rooms) ?> room(s) found for your selected dates.
        </p>
    </div>

    <?php if (count($rooms) > 0): ?>

        <div class="rooms-grid">

            <?php foreach ($rooms as $room): ?>

                <article class="hotel-room-card">

                    <div class="room-image-container">

                        <img
                            src="../assets/images/room-default.jpg"
                            alt="<?= htmlspecialchars($room['type_name']) ?> hotel room"
                            class="hotel-room-image"
                        >

                        <span class="availability-badge">
                            Available
                        </span>

                    </div>

                    <div class="room-card-content">

                        <span class="room-category">
                            <?= htmlspecialchars($room['type_name']) ?> ROOM
                        </span>

                        <h3>
                            <?= htmlspecialchars($room['type_name']) ?>
                            - Room <?= htmlspecialchars($room['room_number']) ?>
                        </h3>

                        <div class="room-information">

                            <span>
                                <strong>Capacity:</strong>
                                <?= (int) $room['capacity'] ?> guest(s)
                            </span>

                            <span>
                                <strong>Floor:</strong>
                                <?= (int) $room['floor'] ?>
                            </span>

                        </div>

                        <p class="room-description">
                            <?= htmlspecialchars(
                                $room['description'] ?? 'A comfortable room for your stay.'
                            ) ?>
                        </p>

                        <div class="room-pricing">

                            <div>
                                <span>Price per night</span>

                                <strong>
                                    KSh <?= number_format(
                                        (float) $room['price_per_night'],
                                        2
                                    ) ?>
                                </strong>
                            </div>

                            <div class="total-price">
                                <span>Total for <?= $nights ?> night(s)</span>

                                <strong>
                                    KSh <?= number_format(
                                        (float) $room['price_per_night'] * $nights,
                                        2
                                    ) ?>
                                </strong>
                            </div>

                        </div>

                        <div class="room-actions">

                            <a
                                href="details.php?room_id=<?= (int) $room['room_id'] ?>&check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= (int) $guests ?>"
                                class="view-room-btn"
                            >
                                View Details
                            </a>

                            <a
                                href="details.php?room_id=<?= (int) $room['room_id'] ?>&check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= (int) $guests ?>"
                                class="book-room-btn"
                            >
                                Book Now
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="no-rooms">

            <div class="no-rooms-icon">⌂</div>

            <h2>No Rooms Available</h2>

            <p>
                Unfortunately, no rooms match your selected dates
                and guest requirements.
            </p>

            <a href="index.php" class="modify-search">
                Try Another Search
            </a>

        </div>

    <?php endif; ?>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
