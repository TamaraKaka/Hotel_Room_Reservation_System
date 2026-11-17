<?php
/*
 * Copy this file to config/db.php and edit the values.
 * config/db.php is ignored by Git, so your own settings are never committed.
 *
 * The defaults below are the XAMPP defaults (user root, no password).
 */
return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'hotel_reservation',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // The folder name of the project inside C:\xampp\htdocs
    'base_url' => '/hotel-reservation-system',

    // Where each role lands after logging in (relative to base_url).
    // Change these when the real pages exist:
    //   customer_home => 'search/index.php'
    //   admin_home    => 'admin/rooms.php'
    'customer_home' => 'auth/home.php',
    'admin_home'    => 'auth/home.php',
];
