<?php

$pageTitle = 'Home - Hotel Reservation System';

require_once __DIR__ . '/includes/header.php';

?>

<section class="section">

    <div class="container">

        <h1>Welcome to Hotel Reservation</h1>

        <p>
            Experience comfort, convenience and excellent hospitality.
            Find the perfect room for your next stay with us.
        </p>

        <a
            href="<?= htmlspecialchars(base_url('search/index.php')) ?>"
            class="search-btn"
        >
            Explore Our Rooms
        </a>

    </div>

</section>

<section class="section">

    <div class="container">

        <h2>Why Choose Us?</h2>

        <p>
            We make booking hotel rooms simple and convenient.
            Choose your preferred room, select your dates and
            reserve your stay with ease.
        </p>

    </div>

</section>

<?php

require_once __DIR__ . '/includes/footer.php';

?>