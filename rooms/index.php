<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
protect_room_admin();

$db = room_pdo();

$section = (string) ($_GET['section'] ?? 'rooms');
if (!in_array($section, ['rooms', 'types', 'blocks'], true)) {
    $section = 'rooms';
}

$types = $db->query(
    'SELECT * FROM room_types ORDER BY type_name'
)->fetchAll();

$rooms = $db->query(
    'SELECT r.*, t.type_name, t.price_per_night, t.capacity
     FROM rooms r
     JOIN room_types t ON t.room_type_id = r.room_type_id
     ORDER BY r.room_number'
)->fetchAll();

$blocks = $db->query(
    'SELECT b.*, r.room_number
     FROM room_availability b
     JOIN rooms r ON r.room_id = b.room_id
     ORDER BY b.start_date DESC, b.availability_id DESC'
)->fetchAll();

$activeRooms = count(array_filter(
    $rooms,
    static fn(array $room): bool => $room['status'] !== 'Out of Service'
));

$editRoom = null;
$editType = null;

if ($section === 'rooms' && isset($_GET['edit_room'])) {
    $q = $db->prepare('SELECT * FROM rooms WHERE room_id = ?');
    $q->execute([(int) $_GET['edit_room']]);
    $editRoom = $q->fetch() ?: null;
}

if ($section === 'types' && isset($_GET['edit_type'])) {
    $q = $db->prepare('SELECT * FROM room_types WHERE room_type_id = ?');
    $q->execute([(int) $_GET['edit_type']]);
    $editType = $q->fetch() ?: null;
}

$flash = $_SESSION['room_flash'] ?? null;
unset($_SESSION['room_flash']);

$token = room_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Room Management | HotelHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">

    <aside class="sidebar">
        <a class="logo" href="../admin/dashboard.php">
            <span class="logo-mark">H</span>
            <span>HOTEL<span class="logo-light">HUB</span>
                <small>MANAGEMENT CONSOLE</small>
            </span>
        </a>

        <p class="side-label">WORKSPACE</p>

        <nav>
            <a href="../admin/dashboard.php">Overview</a>
            <a class="<?= $section === 'rooms' ? 'active' : '' ?>" href="?section=rooms">Rooms</a>
            <a class="<?= $section === 'types' ? 'active' : '' ?>" href="?section=types">Room Types</a>
            <a class="<?= $section === 'blocks' ? 'active' : '' ?>" href="?section=blocks">Availability</a>
        </nav>

        <div class="side-footer">
            <span class="dot"></span> Connected to HotelHub
            <small>Administrator access</small>
        </div>
    </aside>

    <main class="content">

        <header class="topbar">
            <span>ADMIN / <strong>ROOM MANAGEMENT</strong></span>
            <span class="identity">HOTELHUB</span>
        </header>

        <div class="main-content">

            <p class="eyebrow">OPERATIONS CENTER</p>

            <h1>
                <?= $section === 'rooms'
                    ? 'Room management'
                    : ($section === 'types' ? 'Room types' : 'Availability blocks') ?>
            </h1>

            <p class="subtitle">
                Manage your hotel inventory and keep room availability accurate.
            </p>

            <?php if ($flash): ?>
                <div class="alert <?= room_h((string) $flash['kind']) ?>" role="alert">
                    <?= room_h((string) $flash['text']) ?>
                </div>
            <?php endif; ?>

            <?php if ($section === 'rooms'): ?>

                <div class="stat-row">
                    <div class="stat-card">
                        <span>ACTIVE ROOMS</span>
                        <strong><?= $activeRooms ?></strong>
                        <small>Not marked out of service</small>
                    </div>

                    <div class="stat-card">
                        <span>ROOM TYPES</span>
                        <strong><?= count($types) ?></strong>
                        <small>Available categories</small>
                    </div>

                    <div class="stat-card">
                        <span>MAINTENANCE BLOCKS</span>
                        <strong><?= count($blocks) ?></strong>
                        <small>Scheduled restrictions</small>
                    </div>
                </div>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2><?= $editRoom ? 'Edit room' : 'Add a new room' ?></h2>
                            <p>Every room number must be unique.</p>
                        </div>
                    </div>

                    <?php if (!$types): ?>
                        <div class="alert error">
                            First <a href="?section=types">create a room type</a> before adding rooms.
                        </div>
                    <?php else: ?>

                        <form action="actions.php" method="post" class="form-grid">

                            <input type="hidden" name="csrf" value="<?= room_h($token) ?>">
                            <input type="hidden" name="action" value="save_room">
                            <input type="hidden" name="id" value="<?= (int) ($editRoom['room_id'] ?? 0) ?>">

                            <label>
                                Room number
                                <input
                                    name="room_number"
                                    required
                                    maxlength="10"
                                    pattern="[a-zA-Z0-9-]+"
                                    placeholder="e.g. 101"
                                    value="<?= room_h((string) ($editRoom['room_number'] ?? '')) ?>"
                                >
                            </label>

                            <label>
                                Room type
                                <select name="room_type_id" required>
                                    <option value="">Choose room type</option>
                                    <?php foreach ($types as $type): ?>
                                        <option
                                            value="<?= (int) $type['room_type_id'] ?>"
                                            <?= (int) ($editRoom['room_type_id'] ?? 0) === (int) $type['room_type_id'] ? 'selected' : '' ?>
                                        >
                                            <?= room_h((string) $type['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                Floor
                                <input
                                    type="number"
                                    name="floor"
                                    min="0"
                                    max="200"
                                    required
                                    value="<?= (int) ($editRoom['floor'] ?? 1) ?>"
                                >
                            </label>

                            <?php if ($editRoom): ?>
                                <label>
                                    Status
                                    <select name="status">
                                        <?php foreach (['Available', 'Maintenance', 'Out of Service'] as $status): ?>
                                            <option
                                                value="<?= room_h($status) ?>"
                                                <?= ($editRoom['status'] ?? '') === $status ? 'selected' : '' ?>
                                            >
                                                <?= room_h($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php else: ?>
                                <div class="readonly-status">
                                    <span>Initial status</span>
                                    <strong>Available</strong>
                                </div>
                            <?php endif; ?>

                            <div class="wide actions">
                                <button class="btn primary" type="submit">
                                    <?= $editRoom ? 'Save room changes' : '+ Add room' ?>
                                </button>

                                <?php if ($editRoom): ?>
                                    <a class="btn ghost" href="?section=rooms">Cancel editing</a>
                                <?php endif; ?>
                            </div>

                        </form>
                    <?php endif; ?>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Room inventory</h2>
                            <p>All rooms registered in the hotel.</p>
                        </div>
                        <span class="pill"><?= count($rooms) ?> records</span>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                            <tr>
                                <th>ROOM</th>
                                <th>TYPE</th>
                                <th>PRICE / NIGHT</th>
                                <th>FLOOR</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><strong>#<?= room_h((string) $room['room_number']) ?></strong></td>
                                    <td><?= room_h((string) $room['type_name']) ?></td>
                                    <td>KES <?= number_format((float) $room['price_per_night'], 2) ?></td>
                                    <td><?= (int) $room['floor'] ?></td>
                                    <td>
                                        <span class="status <?= $room['status'] === 'Available' ? 'green' : 'amber' ?>">
                                            <?= room_h((string) $room['status']) ?>
                                        </span>
                                    </td>
                                    <td class="row-actions">
                                        <a href="?section=rooms&amp;edit_room=<?= (int) $room['room_id'] ?>">Edit</a>

                                        <?php if ($room['status'] !== 'Out of Service'): ?>
                                            <form
                                                method="post"
                                                action="actions.php"
                                                onsubmit="return confirm('Mark this room as Out of Service?');"
                                            >
                                                <input type="hidden" name="csrf" value="<?= room_h($token) ?>">
                                                <input type="hidden" name="action" value="archive_room">
                                                <input type="hidden" name="id" value="<?= (int) $room['room_id'] ?>">
                                                <button class="link-danger" type="submit">Remove</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$rooms): ?>
                                <tr><td colspan="6" class="empty">No rooms registered yet.</td></tr>
                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </section>

            <?php elseif ($section === 'types'): ?>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2><?= $editType ? 'Edit room type' : 'Add room type' ?></h2>
                            <p>Set category, nightly price and guest capacity.</p>
                        </div>
                    </div>

                    <form action="actions.php" method="post" class="form-grid">

                        <input type="hidden" name="csrf" value="<?= room_h($token) ?>">
                        <input type="hidden" name="action" value="save_type">
                        <input type="hidden" name="id" value="<?= (int) ($editType['room_type_id'] ?? 0) ?>">

                        <label>
                            Type name
                            <input
                                required
                                maxlength="50"
                                name="type_name"
                                placeholder="e.g. Deluxe"
                                value="<?= room_h((string) ($editType['type_name'] ?? '')) ?>"
                            >
                        </label>

                        <label>
                            Price per night (KES)
                            <input
                                required
                                type="number"
                                min="0.01"
                                step="0.01"
                                name="price_per_night"
                                value="<?= room_h((string) ($editType['price_per_night'] ?? '')) ?>"
                                placeholder="8500.00"
                            >
                        </label>

                        <label>
                            Guest capacity
                            <input
                                required
                                type="number"
                                min="1"
                                max="20"
                                name="capacity"
                                value="<?= (int) ($editType['capacity'] ?? 2) ?>"
                            >
                        </label>

                        <label>
                            Description
                            <input
                                maxlength="1000"
                                name="description"
                                value="<?= room_h((string) ($editType['description'] ?? '')) ?>"
                                placeholder="e.g. Spacious double room"
                            >
                        </label>

                        <div class="wide actions">
                            <button class="btn primary" type="submit">
                                <?= $editType ? 'Save type changes' : '+ Add room type' ?>
                            </button>

                            <?php if ($editType): ?>
                                <a class="btn ghost" href="?section=types">Cancel editing</a>
                            <?php endif; ?>
                        </div>

                    </form>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Room categories</h2>
                            <p>Price and capacity reference for booking and search modules.</p>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                            <tr>
                                <th>ROOM TYPE</th>
                                <th>PRICE / NIGHT</th>
                                <th>CAPACITY</th>
                                <th>DESCRIPTION</th>
                                <th>ACTION</th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php foreach ($types as $type): ?>
                                <tr>
                                    <td><strong><?= room_h((string) $type['type_name']) ?></strong></td>
                                    <td>KES <?= number_format((float) $type['price_per_night'], 2) ?></td>
                                    <td><?= (int) $type['capacity'] ?> guests</td>
                                    <td><?= room_h((string) ($type['description'] ?? '')) ?></td>
                                    <td>
                                        <a href="?section=types&amp;edit_type=<?= (int) $type['room_type_id'] ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$types): ?>
                                <tr><td colspan="5" class="empty">No room types yet.</td></tr>
                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </section>

            <?php else: ?>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Block dates for maintenance</h2>
                            <p>Prevent a room from being booked during maintenance or temporary closure.</p>
                        </div>
                    </div>

                    <form action="actions.php" method="post" class="form-grid">

                        <input type="hidden" name="csrf" value="<?= room_h($token) ?>">
                        <input type="hidden" name="action" value="add_block">

                        <label>
                            Room
                            <select name="room_id" required>
                                <option value="">Select room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <?php if ($room['status'] !== 'Out of Service'): ?>
                                        <option value="<?= (int) $room['room_id'] ?>">
                                            #<?= room_h((string) $room['room_number']) ?>
                                            - <?= room_h((string) $room['type_name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Reason
                            <input name="reason" required maxlength="100" placeholder="e.g. Plumbing repairs">
                        </label>

                        <label>
                            Start date
                            <input name="start_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                        </label>

                        <label>
                            End date (inclusive)
                            <input name="end_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </label>

                        <div class="wide actions">
                            <button class="btn primary" type="submit" <?= !$activeRooms ? 'disabled' : '' ?>>
                                + Block dates
                            </button>
                        </div>

                    </form>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Maintenance calendar</h2>
                            <p>Existing date restrictions for rooms.</p>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                            <tr>
                                <th>ROOM</th>
                                <th>FROM</th>
                                <th>TO</th>
                                <th>REASON</th>
                                <th>ACTION</th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php foreach ($blocks as $block): ?>
                                <tr>
                                    <td><strong>#<?= room_h((string) $block['room_number']) ?></strong></td>
                                    <td><?= room_h((string) $block['start_date']) ?></td>
                                    <td><?= room_h((string) $block['end_date']) ?></td>
                                    <td><?= room_h((string) ($block['reason'] ?? '')) ?></td>
                                    <td>
                                        <form
                                            action="actions.php"
                                            method="post"
                                            onsubmit="return confirm('Remove this availability block?');"
                                        >
                                            <input type="hidden" name="csrf" value="<?= room_h($token) ?>">
                                            <input type="hidden" name="action" value="remove_block">
                                            <input type="hidden" name="id" value="<?= (int) $block['availability_id'] ?>">
                                            <button class="link-danger" type="submit">Unblock</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$blocks): ?>
                                <tr><td colspan="5" class="empty">No maintenance blocks recorded.</td></tr>
                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </section>

            <?php endif; ?>

            <p class="footnote">HotelHub Room Management</p>

        </div>
    </main>
</div>
</body>
</html>
