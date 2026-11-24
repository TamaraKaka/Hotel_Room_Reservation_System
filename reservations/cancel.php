<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('reservations/my-reservations.php');
}

if (!csrfValid($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid request. Please try again.');
    redirect('reservations/my-reservations.php');
}

$reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);

if (!$reservationId || $reservationId < 1) {
    flash('error', 'Invalid reservation selected.');
    redirect('reservations/my-reservations.php');
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT reservation_id, status
        FROM reservations
        WHERE reservation_id = :reservation_id
        AND user_id = :user_id
        FOR UPDATE
    ");

    $stmt->execute([
        ':reservation_id' => $reservationId,
        ':user_id' => $user['id']
    ]);

    $reservation = $stmt->fetch();

    if (!$reservation) {
        throw new RuntimeException('Reservation not found.');
    }

    if (!in_array($reservation['status'], ['Pending', 'Confirmed'], true)) {
        throw new RuntimeException('This reservation cannot be cancelled.');
    }

    $update = $pdo->prepare("
        UPDATE reservations
        SET status = 'Cancelled'
        WHERE reservation_id = :reservation_id
        AND user_id = :user_id
    ");

    $update->execute([
        ':reservation_id' => $reservationId,
        ':user_id' => $user['id']
    ]);

    $pdo->commit();

    flash('success', 'Your reservation has been cancelled.');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', $e->getMessage());
}

redirect('reservations/my-reservations.php');