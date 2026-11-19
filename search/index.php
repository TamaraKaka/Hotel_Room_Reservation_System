
<?php

require_once __DIR__ . '/../includes/db.php';

$conn = db();
// Retrieve available room types
$query = "SELECT * FROM room_types ORDER BY type_name";
$stmt = $conn->query($query);
$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Search Rooms";

include '../includes/header.php';

?>

<section class="search-section">

    <h1>Find Your Perfect Room</h1>
    <p>Enter your booking details to find available rooms.</p>

    <form action="results.php" method="GET" class="search-form">

        <div class="form-group">
            <label for="check_in">Check-in Date</label>

            <input
                type="date"
                id="check_in"
                name="check_in"
                min="<?php echo date('Y-m-d'); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="check_out">Check-out Date</label>

            <input
                type="date"
                id="check_out"
                name="check_out"
                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="guests">Number of Guests</label>

            <input
                type="number"
                id="guests"
                name="guests"
                min="1"
                value="1"
                required
            >
        </div>

        <div class="form-group">
            <label for="room_type">Room Type</label>

            <select id="room_type" name="room_type">

                <option value="">All Room Types</option>

                <?php foreach ($roomTypes as $type): ?>

                    <option value="<?php echo $type['room_type_id']; ?>">
                        <?php echo htmlspecialchars($type['type_name']); ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <button type="submit" class="search-btn">
            Search Rooms
        </button>

    </form>

</section>

<?php include '../includes/footer.php'; ?>
