<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/validation.php';

protect_room_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use the forms in Room Management.');
}

room_verify_token();

$db = room_pdo();
$action = (string) ($_POST['action'] ?? '');
$section = 'rooms';

try {
    switch ($action) {

        case 'save_type':
            $section = 'types';

            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['type_name'] ?? ''));
            $price = trim((string) ($_POST['price_per_night'] ?? ''));
            $capacity = (int) ($_POST['capacity'] ?? 0);
            $description = trim((string) ($_POST['description'] ?? ''));

            if (
                strlen($name) < 2 ||
                strlen($name) > 50 ||
                !valid_room_price($price) ||
                !valid_room_capacity($capacity) ||
                strlen($description) > 1000
            ) {
                throw new DomainException(
                    'Enter a valid type name, price, capacity and description.'
                );
            }

            if ($id > 0) {
                $q = $db->prepare(
                    'UPDATE room_types
                     SET type_name = ?, price_per_night = ?, capacity = ?, description = ?
                     WHERE room_type_id = ?'
                );
                $q->execute([$name, $price, $capacity, $description, $id]);
            } else {
                $q = $db->prepare(
                    'INSERT INTO room_types
                     (type_name, price_per_night, capacity, description)
                     VALUES (?, ?, ?, ?)'
                );
                $q->execute([$name, $price, $capacity, $description]);
            }

            room_message('success', $id > 0 ? 'Room type updated.' : 'Room type added.');
            break;


        case 'save_room':
            $id = (int) ($_POST['id'] ?? 0);
            $number = strtoupper(trim((string) ($_POST['room_number'] ?? '')));
            $typeId = (int) ($_POST['room_type_id'] ?? 0);
            $floor = (int) ($_POST['floor'] ?? 0);
            $status = $id > 0
                ? (string) ($_POST['status'] ?? 'Available')
                : 'Available';

            if (
                !valid_room_number($number) ||
                strlen($number) > 10 ||
                $typeId <= 0 ||
                $floor < 0 ||
                $floor > 200 ||
                !in_array($status, ['Available', 'Maintenance', 'Out of Service'], true)
            ) {
                throw new DomainException(
                    'Enter a valid room number, room type, floor and status.'
                );
            }

            $q = $db->prepare(
                'SELECT COUNT(*) FROM room_types WHERE room_type_id = ?'
            );
            $q->execute([$typeId]);

            if ((int) $q->fetchColumn() === 0) {
                throw new DomainException('Select an existing room type.');
            }

            $q = $db->prepare(
                'SELECT COUNT(*) FROM rooms WHERE room_number = ? AND room_id <> ?'
            );
            $q->execute([$number, $id]);

            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException('Room number already exists. Choose another number.');
            }

            if ($id > 0) {
                $q = $db->prepare(
                    'SELECT room_id FROM rooms WHERE room_id = ?'
                );
                $q->execute([$id]);

                if (!$q->fetch()) {
                    throw new DomainException('Room not found.');
                }

                if ($status === 'Out of Service') {
                    $q = $db->prepare(
                        "SELECT COUNT(*)
                         FROM reservations
                         WHERE room_id = ?
                         AND status IN ('Pending', 'Confirmed')
                         AND check_out_date > ?"
                    );
                    $q->execute([$id, date('Y-m-d')]);

                    if ((int) $q->fetchColumn() > 0) {
                        throw new DomainException(
                            'Cannot mark this room Out of Service because it has an active reservation.'
                        );
                    }
                }

                $q = $db->prepare(
                    'UPDATE rooms
                     SET room_number = ?, room_type_id = ?, floor = ?, status = ?
                     WHERE room_id = ?'
                );
                $q->execute([$number, $typeId, $floor, $status, $id]);

            } else {
                $q = $db->prepare(
                    'INSERT INTO rooms (room_number, room_type_id, floor, status)
                     VALUES (?, ?, ?, ?)'
                );
                $q->execute([$number, $typeId, $floor, 'Available']);
            }

            room_message('success', $id > 0 ? 'Room updated.' : 'Room added successfully.');
            break;


        case 'archive_room':
            $id = (int) ($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new DomainException('Choose a valid room.');
            }

            $q = $db->prepare(
                "SELECT COUNT(*)
                 FROM reservations
                 WHERE room_id = ?
                 AND status IN ('Pending', 'Confirmed')
                 AND check_out_date > ?"
            );
            $q->execute([$id, date('Y-m-d')]);

            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException(
                    'Cannot remove this room because it has an active reservation.'
                );
            }

            $q = $db->prepare(
                "UPDATE rooms SET status = 'Out of Service' WHERE room_id = ?"
            );
            $q->execute([$id]);

            if ($q->rowCount() === 0) {
                $check = $db->prepare('SELECT room_id FROM rooms WHERE room_id = ?');
                $check->execute([$id]);

                if (!$check->fetch()) {
                    throw new DomainException('Room not found.');
                }
            }

            room_message(
                'success',
                'Room marked Out of Service. Its booking history has been preserved.'
            );
            break;


        case 'add_block':
            $section = 'blocks';

            $roomId = (int) ($_POST['room_id'] ?? 0);
            $from = trim((string) ($_POST['start_date'] ?? ''));
            $to = trim((string) ($_POST['end_date'] ?? ''));
            $reason = trim((string) ($_POST['reason'] ?? ''));

            if (
                $roomId <= 0 ||
                !valid_block_range($from, $to) ||
                $to < date('Y-m-d') ||
                $reason === '' ||
                strlen($reason) > 100
            ) {
                throw new DomainException(
                    'Choose a room, valid dates and a reason of at most 100 characters.'
                );
            }

            $q = $db->prepare(
                "SELECT room_id FROM rooms
                 WHERE room_id = ? AND status <> 'Out of Service'"
            );
            $q->execute([$roomId]);

            if (!$q->fetch()) {
                throw new DomainException('The selected room is not available for maintenance scheduling.');
            }

            $q = $db->prepare(
                'SELECT COUNT(*)
                 FROM room_availability
                 WHERE room_id = ?
                 AND start_date <= ?
                 AND end_date >= ?'
            );
            $q->execute([$roomId, $to, $from]);

            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException(
                    'The selected dates overlap an existing maintenance block.'
                );
            }

            $q = $db->prepare(
                "SELECT COUNT(*)
                 FROM reservations
                 WHERE room_id = ?
                 AND status IN ('Pending', 'Confirmed')
                 AND check_in_date <= ?
                 AND check_out_date > ?"
            );
            $q->execute([$roomId, $to, $from]);

            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException(
                    'Cannot block dates that overlap an active reservation.'
                );
            }

            $q = $db->prepare(
                'INSERT INTO room_availability
                 (room_id, start_date, end_date, reason)
                 VALUES (?, ?, ?, ?)'
            );
            $q->execute([$roomId, $from, $to, $reason]);

            room_message('success', 'Availability blocked for maintenance.');
            break;


        case 'remove_block':
            $section = 'blocks';

            $id = (int) ($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new DomainException('Choose a valid availability block.');
            }

            $q = $db->prepare(
                'DELETE FROM room_availability WHERE availability_id = ?'
            );
            $q->execute([$id]);

            if ($q->rowCount() === 0) {
                throw new DomainException('Availability block not found.');
            }

            room_message('success', 'Maintenance block removed.');
            break;


        default:
            throw new DomainException('Unknown action.');
    }

} catch (DomainException $e) {
    room_message('error', $e->getMessage());

} catch (PDOException $e) {
    room_message(
        'error',
        $e->getCode() === '23000'
            ? 'Duplicate or referenced record. Check your values.'
            : 'A database error occurred. Please check your entries and try again.'
    );
}

room_redirect($section);
