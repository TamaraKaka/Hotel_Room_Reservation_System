
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

    <div class="search-intro">
        <p class="search-eyebrow">DISCOVER YOUR NEXT STAY</p>

        <h1>Find Your Perfect Stay</h1>

        <p>
            Whether you are travelling for business or leisure,
            discover a comfortable room that suits your needs.
        </p>
    </div>

    <div class="search-box">

        <div class="search-box-heading">
            <h2>Search Available Rooms</h2>
            <p>Enter your booking details below.</p>
        </div>

        <form action="results.php" method="GET" class="search-form">

            <div class="form-group">
                <label for="check_in">Check-in Date</label>

                <input
                    type="date"
                    id="check_in"
                    name="check_in"
                    min="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="check_out">Check-out Date</label>

                <input
                    type="date"
                    id="check_out"
                    name="check_out"
                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
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
                <label for="room_type">Preferred Room Type</label>

                <select id="room_type" name="room_type">

                    <option value="">All Room Types</option>

                    <?php foreach ($roomTypes as $type): ?>

                        <option value="<?= htmlspecialchars((string)$type['room_type_id']) ?>">
                            <?= htmlspecialchars((string)$type['type_name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <button type="submit" class="search-btn">
                Search Available Rooms →
            </button>

        </form>

        <div class="search-note">
            <p>Find comfort, convenience and the perfect room for your stay.</p>
        </div>

    </div>

</section>

<script>
const checkIn = document.getElementById('check_in');
const checkOut = document.getElementById('check_out');

checkIn.addEventListener('change', function () {
    const nextDay = new Date(this.value + 'T00:00:00');
    nextDay.setDate(nextDay.getDate() + 1);

    const minDate = [
        nextDay.getFullYear(),
        String(nextDay.getMonth() + 1).padStart(2, '0'),
        String(nextDay.getDate()).padStart(2, '0')
    ].join('-');

    checkOut.min = minDate;

    if (checkOut.value && checkOut.value < minDate) {
        checkOut.value = '';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
