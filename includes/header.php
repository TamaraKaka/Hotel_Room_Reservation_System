<?php
$pageTitle = $pageTitle ?? "HotelHub";
$baseUrl = '/hotel-reservation-system';
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pageTitle = $pageTitle ?? 'Hotel Reservation System';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | HotelHub</title>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <title>
        <?= htmlspecialchars($pageTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(base_url('assets/css/style.css')) ?>"
    >

</head>

<body>

<header class="navbar">
    <a href="<?= $baseUrl ?>/auth/home.php" class="logo">
        HOTEL<span>HUB</span>
    </a>
<header>

    <nav class="navbar">

        <div class="logo">

            <a href="<?= htmlspecialchars(base_url('index.php')) ?>">
                Hotel Reservation
            </a>

        </div>

        <ul class="nav-links">

            <li>
                <a href="<?= htmlspecialchars(base_url('index.php')) ?>">
                    Home
                </a>
            </li>

            <li>
                <a href="<?= htmlspecialchars(base_url('search/')) ?>">
                    Search Rooms
                </a>
            </li>

        </ul>

        <div class="nav-actions">

            <a
                href="<?= htmlspecialchars(base_url('auth/login.php')) ?>"
                class="login-btn"
            >
                Login
            </a>

            <a
                href="<?= htmlspecialchars(base_url('auth/register.php')) ?>"
                class="register-btn"
            >
                Register
            </a>

        </div>

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
