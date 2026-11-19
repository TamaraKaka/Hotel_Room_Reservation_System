
<?php
require_once __DIR__ . '/../includes/db.php';

$conn = db();

// Get selected room and booking details
$roomId = filter_var($_GET['room_id'] ?? null, FILTER_VALIDATE_INT);
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = filter_var($_GET['guests'] ?? null, FILTER_VALIDATE_INT);

if (!$roomId || !$checkIn || !$checkOut || !$guests) {
    exit('Invalid room details.');
}

// Retrieve room information
$sql = "
    SELECT rooms.*, room_types.type_name,
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

// Calculate booking cost
$nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
$total = $nights * $room['price_per_night'];

$pageTitle = "Room Details";
include __DIR__ . '/../includes/header.php';
?>

<h1>Room Details</h1>

<div class="room-card">

    <h2>
        <?= htmlspecialchars($room['type_name']) ?>
        - Room <?= htmlspecialchars($room['room_number']) ?>
    </h2>

    <p>
        <strong>Description:</strong>
        <?= htmlspecialchars($room['description']) ?>
    </p>

    <p>
        <strong>Capacity:</strong>
        <?= $room['capacity'] ?> guest(s)
    </p>

    <p>
        <strong>Floor:</strong>
        <?= $room['floor'] ?>
    </p>

    <p>
        <strong>Check-in:</strong>
        <?= htmlspecialchars($checkIn) ?>
    </p>

    <p>
        <strong>Check-out:</strong>
        <?= htmlspecialchars($checkOut) ?>
    </p>

    <p>
        <strong>Guests:</strong>
        <?= $guests ?>
    </p>

    <p class="room-price">
        KSh <?= number_format($room['price_per_night'], 2) ?> per night
    </p>

    <h3>
        Total: KSh <?= number_format($total, 2) ?>
    </h3>

    <a href="results.php?check_in=<?= urlencode($checkIn) ?>&check_out=<?= urlencode($checkOut) ?>&guests=<?= $guests ?>"
       class="view-btn">
        Back to Results
    </a>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
