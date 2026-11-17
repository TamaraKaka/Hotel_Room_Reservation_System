<?php
/** Run from PHP CLI: php rooms/tests/test_validation.php (no database required). */
declare(strict_types=1);
require_once __DIR__ . '/../validation.php';

$tests = [
    'Accept valid room number' => valid_room_number('101'),
    'Reject room number with spaces' => !valid_room_number('1 01'),
    'Reject empty room number' => !valid_room_number(''),
    'Accept valid nightly price' => valid_room_price('8500.00'),
    'Reject negative nightly price' => !valid_room_price('-200'),
    'Accept 2 guests' => valid_room_capacity(2),
    'Reject zero guests' => !valid_room_capacity(0),
    'Accept valid maintenance date range' => valid_block_range('2026-12-01', '2026-12-03'),
    'Reject reversed maintenance dates' => !valid_block_range('2026-12-03', '2026-12-01'),
    'Reject invalid calendar date' => !valid_block_range('2026-02-30', '2026-03-05'),
];
$failures = 0;
foreach ($tests as $name => $pass) {
    echo ($pass ? 'PASS' : 'FAIL') . ' - ' . $name . PHP_EOL;
    if (!$pass) { $failures++; }
}
echo (count($tests) - $failures) . '/' . count($tests) . ' tests passed.' . PHP_EOL;
exit($failures ? 1 : 0);
