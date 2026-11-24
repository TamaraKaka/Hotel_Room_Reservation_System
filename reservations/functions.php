<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';


/**
 * Creates a reservation.
 * Checks room availability and prevents double booking.
 */
function createReservation(
    int $userId,
    int $roomId,
    string $checkIn,
    string $checkOut,
    int $guests
): int {

    $pdo = db();

    // Validate dates.
    $checkInDate = DateTimeImmutable::createFromFormat('!Y-m-d', $checkIn);
    $checkOutDate = DateTimeImmutable::createFromFormat('!Y-m-d', $checkOut);

    if (
        !$checkInDate ||
        !$checkOutDate ||
        $checkInDate->format('Y-m-d') !== $checkIn ||
        $checkOutDate->format('Y-m-d') !== $checkOut
    ) {
        throw new InvalidArgumentException('Invalid reservation dates.');
    }

    if ($checkInDate < new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('Check-in cannot be in the past.');
    }

    if ($checkOutDate <= $checkInDate) {
        throw new InvalidArgumentException('Check-out must be after check-in.');
    }

    if ($userId <= 0 || $roomId <= 0 || $guests < 1) {
        throw new InvalidArgumentException('Invalid reservation details.');
    }

    $pdo->beginTransaction();

    try {

        // Lock the room while checking availability.
        $stmt = $pdo->prepare("
            SELECT
                rooms.room_id,
                rooms.status,
                room_types.price_per_night,
                room_types.capacity
            FROM rooms
            INNER JOIN room_types
                ON rooms.room_type_id = room_types.room_type_id
            WHERE rooms.room_id = :room_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':room_id' => $roomId
        ]);

        $room = $stmt->fetch();

        if (!$room) {
            throw new RuntimeException('Room not found.');
        }

        if ($room['status'] !== 'Available') {
            throw new RuntimeException('This room is unavailable.');
        }

        if ($guests > (int) $room['capacity']) {
            throw new RuntimeException('Guest number exceeds room capacity.');
        }

        // Check existing reservations.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM reservations
            WHERE room_id = :room_id
            AND status IN ('Pending', 'Confirmed')
            AND check_in_date < :check_out
            AND check_out_date > :check_in
        ");

        $stmt->execute([
            ':room_id' => $roomId,
            ':check_in' => $checkIn,
            ':check_out' => $checkOut
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException(
                'This room is already reserved for those dates.'
            );
        }

        // Check maintenance or blocked dates.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM room_availability
            WHERE room_id = :room_id
            AND start_date < :check_out
            AND end_date > :check_in
        ");

        $stmt->execute([
            ':room_id' => $roomId,
            ':check_in' => $checkIn,
            ':check_out' => $checkOut
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException(
                'This room is blocked for the selected dates.'
            );
        }

        // Calculate reservation cost.
        $nights = $checkInDate->diff($checkOutDate)->days;

        $totalAmount = $nights * (float) $room['price_per_night'];

        // Insert reservation.
        $stmt = $pdo->prepare("
            INSERT INTO reservations (
                user_id,
                room_id,
                check_in_date,
                check_out_date,
                num_guests,
                total_amount,
                status
            )
            VALUES (
                :user_id,
                :room_id,
                :check_in,
                :check_out,
                :guests,
                :total_amount,
                'Pending'
            )
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':room_id' => $roomId,
            ':check_in' => $checkIn,
            ':check_out' => $checkOut,
            ':guests' => $guests,
            ':total_amount' => $totalAmount
        ]);

        $reservationId = (int) $pdo->lastInsertId();

        $pdo->commit();

        return $reservationId;

    } catch (Throwable $exception) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}


/**
 * Confirms a pending reservation after successful payment.
 */
function confirmReservation(int $reservationId): bool
{
    if ($reservationId <= 0) {
        return false;
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET status = 'Confirmed'
        WHERE reservation_id = :reservation_id
        AND status = 'Pending'
    ");

    $stmt->execute([
        ':reservation_id' => $reservationId
    ]);

    if ($stmt->rowCount() > 0) {
        return true;
    }

    // Check whether it was already confirmed.
    $stmt = $pdo->prepare("
        SELECT status
        FROM reservations
        WHERE reservation_id = :reservation_id
    ");

    $stmt->execute([
        ':reservation_id' => $reservationId
    ]);

    return $stmt->fetchColumn() === 'Confirmed';
}