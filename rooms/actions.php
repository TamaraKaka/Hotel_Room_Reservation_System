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
            if (strlen($name) < 2 || strlen($name) > 80 || !valid_room_price($price) ||
                !valid_room_capacity($capacity) || strlen($description) > 255) {
                throw new DomainException('Enter a valid type name, price greater than zero, capacity (1–20), and description under 256 characters.');
            }
            if ($id > 0) {
                $q = $db->prepare('UPDATE Room_Types SET type_name=?, price_per_night=?, capacity=?, description=? WHERE id=?');
                $q->execute([$name, $price, $capacity, $description, $id]);
            } else {
                $q = $db->prepare('INSERT INTO Room_Types (type_name, price_per_night, capacity, description) VALUES (?,?,?,?)');
                $q->execute([$name, $price, $capacity, $description]);
            }
            room_message('success', $id ? 'Room type updated.' : 'Room type added.');
            break;

        case 'save_room':
            $id = (int) ($_POST['id'] ?? 0);
            $number = strtoupper(trim((string) ($_POST['room_number'] ?? '')));
            $typeId = (int) ($_POST['room_type_id'] ?? 0);
            $floor = (int) ($_POST['floor'] ?? 0);
            $description = trim((string) ($_POST['description'] ?? ''));
            $status = $id > 0 ? (string) ($_POST['status'] ?? 'Available') : 'Available';
            if (!valid_room_number($number) || $typeId <= 0 || $floor < 0 || $floor > 200 ||
                strlen($description) > 255 || !in_array($status, ['Available', 'Maintenance', 'Out of Service'], true)) {
                throw new DomainException('Use a valid room number (letters, digits, dash), room type, floor (0–200), and status.');
            }
            $q = $db->prepare('SELECT COUNT(*) FROM Room_Types WHERE id=?');
            $q->execute([$typeId]);
            if ((int) $q->fetchColumn() === 0) {
                throw new DomainException('Select an existing room type.');
            }
            $q = $db->prepare('SELECT COUNT(*) FROM Rooms WHERE room_number=? AND id<>?');
            $q->execute([$number, $id]);
            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException('Room number already exists. Choose a unique number.');
            }
            if ($id > 0) {
                $q = $db->prepare('UPDATE Rooms SET room_number=?, room_type_id=?, floor=?, status=?, description=? WHERE id=? AND is_active=1');
                $q->execute([$number, $typeId, $floor, $status, $description, $id]);
                if ($q->rowCount() === 0) {
                    $check = $db->prepare('SELECT id FROM Rooms WHERE id=? AND is_active=1');
                    $check->execute([$id]);
                    if (!$check->fetch()) {
                        throw new DomainException('Room not found or already archived.');
                    }
                }
            } else {
                $q = $db->prepare('INSERT INTO Rooms (room_number, room_type_id, floor, status, description) VALUES (?,?,?,?,?)');
                $q->execute([$number, $typeId, $floor, 'Available', $description]);
            }
            room_message('success', $id ? 'Room updated.' : 'Room added with Available status.');
            break;

        case 'archive_room':
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new DomainException('Choose a valid room.');
            }
            // Check that no current/future pending or confirmed reservation is attached.
            $q = $db->prepare("SELECT COUNT(*) FROM Reservations WHERE room_id=? AND status IN ('Pending','Confirmed') AND check_out > ?");
            $q->execute([$id, date('Y-m-d')]);
            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException('Cannot remove this room: it has an active reservation.');
            }
            // Soft delete preserves booking and payment history.
            $q = $db->prepare('UPDATE Rooms SET is_active=0 WHERE id=? AND is_active=1');
            $q->execute([$id]);
            if ($q->rowCount() === 0) {
                throw new DomainException('Room not found or already removed.');
            }
            room_message('success', 'Room removed from the active room list (history retained).');
            break;

        case 'add_block':
            $section = 'blocks';
            $id = (int) ($_POST['room_id'] ?? 0);
            $from = trim((string) ($_POST['start_date'] ?? ''));
            $to = trim((string) ($_POST['end_date'] ?? ''));
            $reason = trim((string) ($_POST['reason'] ?? ''));
            if ($id <= 0 || !valid_block_range($from, $to) || $to < date('Y-m-d') ||
                $reason === '' || strlen($reason) > 255) {
                throw new DomainException('Choose a room, a valid current/future date range, and a maintenance reason (max 255 characters).');
            }
            $q = $db->prepare('SELECT id FROM Rooms WHERE id=? AND is_active=1');
            $q->execute([$id]);
            if (!$q->fetch()) {
                throw new DomainException('Room is not active.');
            }
            $q = $db->prepare('SELECT COUNT(*) FROM Room_Availability WHERE room_id=? AND start_date <= ? AND end_date >= ?');
            $q->execute([$id, $to, $from]);
            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException('The selected dates overlap an existing maintenance block.');
            }
            $endExclusive = (new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
            $q = $db->prepare("SELECT COUNT(*) FROM Reservations WHERE room_id=? AND status IN ('Pending','Confirmed') AND check_in < ? AND check_out > ?");
            $q->execute([$id, $endExclusive, $from]);
            if ((int) $q->fetchColumn() > 0) {
                throw new DomainException('Cannot block dates that overlap an active reservation.');
            }
            $q = $db->prepare('INSERT INTO Room_Availability (room_id,start_date,end_date,reason) VALUES (?,?,?,?)');
            $q->execute([$id, $from, $to, $reason]);
            room_message('success', 'Availability blocked for maintenance.');
            break;

        case 'remove_block':
            $section = 'blocks';
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new DomainException('Choose a valid block.');
            }
            $q = $db->prepare('DELETE FROM Room_Availability WHERE id=?');
            $q->execute([$id]);
            room_message('success', 'Maintenance block removed.');
            break;

        default:
            throw new DomainException('Unknown action.');
    }
} catch (DomainException $e) {
    room_message('error', $e->getMessage());
} catch (PDOException $e) {
    // Never expose raw SQL errors/connection secrets to web visitors.
    room_message('error', $e->getCode() === '23000' ? 'Duplicate or referenced record. Check your values.' : 'A database error occurred. Check the demo schema and try again.');
}
room_redirect($section);
