
<?php

function isRoomAvailable($conn, $roomId, $checkIn, $checkOut)
{
    // Check whether the room has an overlapping reservation
    $reservationQuery = "
        SELECT COUNT(*)
        FROM reservations
        WHERE room_id = :room_id
        AND status IN ('Pending', 'Confirmed')
        AND check_in_date < :check_out
        AND check_out_date > :check_in
    ";

    $stmt = $conn->prepare($reservationQuery);

    $stmt->execute([
        ':room_id' => $roomId,
        ':check_in' => $checkIn,
        ':check_out' => $checkOut
    ]);

    $existingReservations = $stmt->fetchColumn();

    // Check whether the room is blocked
    $blockedQuery = "
        SELECT COUNT(*)
        FROM room_availability
        WHERE room_id = :room_id
        AND start_date < :check_out
        AND end_date > :check_in
    ";

    $stmt = $conn->prepare($blockedQuery);

    $stmt->execute([
        ':room_id' => $roomId,
        ':check_in' => $checkIn,
        ':check_out' => $checkOut
    ]);

    $blockedDates = $stmt->fetchColumn();

    // Check the room's general status
    $statusQuery = "
        SELECT status
        FROM rooms
        WHERE room_id = :room_id
    ";

    $stmt = $conn->prepare($statusQuery);
    $stmt->execute([':room_id' => $roomId]);

    $roomStatus = $stmt->fetchColumn();

    // Room is available only if all checks pass
    return $roomStatus === 'Available'
        && $existingReservations == 0
        && $blockedDates == 0;
}

?>
