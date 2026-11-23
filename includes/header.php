
<?php
$pageTitle = $pageTitle ?? "Hotel Reservation System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="/Hotel_Room_Reservation_System/assets/css/style.css">
</head>

<body>

<header>
    <nav class="navbar">

        <div class="logo">
            <a href="/hotel-reservation/index.php">
                Hotel Reservation
            </a>
        </div>

        <ul class="nav-links">
            <li><a href="/hotel-reservation/index.php">Home</a></li>
            <li><a href="/hotel-reservation/search/index.php">Search Rooms</a></li>
            <li><a href="/hotel-reservation/about.php">About Us</a></li>
            <li><a href="/hotel-reservation/contact.php">Contact</a></li>
        </ul>

        <div class="nav-actions">
            <a href="/hotel-reservation/login.php" class="login-btn">
                Login
            </a>

            <a href="/hotel-reservation/register.php" class="register-btn">
                Register
            </a>
        </div>

    </nav>
</header>

<main class="container">
