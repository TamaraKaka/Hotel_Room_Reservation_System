
<?php
$pageTitle = $pageTitle ?? "HotelHub";
$baseUrl = '/hotel-reservation-system';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | HotelHub</title>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>

<body>

<header class="navbar">
    <a href="<?= $baseUrl ?>/auth/home.php" class="logo">
        HOTEL<span>HUB</span>
    </a>

    <nav class="nav-links">
        <a href="<?= $baseUrl ?>/auth/home.php">Dashboard</a>
        <a href="<?= $baseUrl ?>/search/index.php">Search Rooms</a>
    </nav>

    <div class="nav-actions">
        <a href="<?= $baseUrl ?>/auth/home.php" class="login-btn">
            My Account
        </a>
    </div>
</header>

<main class="container">
