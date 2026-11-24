<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

// Require an authenticated account.
$user = requireLogin();

// Admin accounts must use the admin dashboard.
if ($user['role'] === 'Admin') {
    authGo('admin/dashboard.php');
}

// Only customers can access this dashboard.
requireRole('Customer');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Customer Dashboard | HotelHub</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <a href="../index.php" class="dashboard-logo">
        HOTEL<span>HUB</span>
    </a>

    <nav class="dashboard-nav">
        <a href="home.php" class="active">Dashboard</a>
        <a href="../search/index.php">Search Rooms</a>
        <a href="../customers/my-reservations.php">My Reservations</a>
    </nav>

    <div class="dashboard-user">

        <span>
            <?= authEscape($user['full_name']) ?>
        </span>

        <form action="logout.php" method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= authEscape(authCsrf()) ?>"
            >

            <button type="submit" class="dashboard-logout">
                Sign out
            </button>

        </form>

    </div>

</header>

<main class="dashboard-container">

    <section class="dashboard-welcome">

        <div>

            <p class="dashboard-eyebrow">
                CUSTOMER DASHBOARD
            </p>

            <h1>
                Welcome back, <?= authEscape($user['full_name']) ?>!
            </h1>

            <p>
                Your next memorable stay starts here.
                Explore our rooms and find a place that feels like home.
            </p>

            <a
                href="../search/index.php"
                class="dashboard-primary-btn"
            >
                Explore Available Rooms
            </a>

        </div>

        <div class="welcome-decoration">

            <span>HOTELHUB</span>

            <p>Comfort. Luxury. Experience.</p>

        </div>

    </section>

    <section class="dashboard-section">

        <div class="dashboard-section-heading">

            <div>

                <p class="dashboard-eyebrow">
                    YOUR ACCOUNT
                </p>

                <h2>Quick Overview</h2>

            </div>

            <span class="account-status">
                Account Active
            </span>

        </div>

        <div class="dashboard-cards">

            <article class="dashboard-card">

                <div class="dashboard-card-icon">
                    &#x1F3E8;
                </div>

                <h3>Find a Room</h3>

                <p>
                    Explore available rooms and discover the right stay for you.
                </p>

                <a href="../search/index.php">
                    Search Rooms &rarr;
                </a>

            </article>

            <article class="dashboard-card">

                <div class="dashboard-card-icon">
                    &#x1F4C5;
                </div>

                <h3>My Reservations</h3>

                <p>
                    View your bookings and check your reservation details.
                </p>

                <a href="../customers/my-reservations.php">
                    View Reservations &rarr;
                </a>

            </article>

            <article class="dashboard-card">

                <div class="dashboard-card-icon">
                    &#x1F464;
                </div>

                <h3>My Profile</h3>

                <p>
                    View your HotelHub account information.
                </p>

                <span class="coming-soon">
                    Coming soon
                </span>

            </article>

        </div>

    </section>

    <section class="dashboard-account">

        <div>

            <p class="dashboard-eyebrow">
                ACCOUNT INFORMATION
            </p>

            <h2>Your HotelHub Account</h2>

            <p>
                You are signed in and ready to explore our hotel services.
            </p>

        </div>

        <div class="account-details">

            <div>
                <span>Full name</span>

                <strong>
                    <?= authEscape($user['full_name']) ?>
                </strong>
            </div>

            <div>
                <span>Email address</span>

                <strong>
                    <?= authEscape($user['email']) ?>
                </strong>
            </div>

            <div>
                <span>Account role</span>

                <strong>
                    <?= authEscape($user['role']) ?>
                </strong>
            </div>

        </div>

    </section>

</main>

<footer class="dashboard-footer">

    <p>
        &copy; <?= date('Y') ?> HotelHub. All rights reserved.
    </p>

    <p>
        Making every stay memorable.
    </p>

</footer>

</body>
</html>
