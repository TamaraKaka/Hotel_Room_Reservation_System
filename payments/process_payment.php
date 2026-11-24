
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/payment_functions.php';

// Only authenticated customers can submit payment requests.
$user = requireRole(ROLE_CUSTOMER);

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify CSRF token.
if (!csrfValid()) {
    http_response_code(403);
    exit('Invalid security token. Please try again.');
} {
    http_response_code(403);
    exit('Invalid security token. Please refresh the page.');
}

// Validate reservation ID.
$reservationId = filter_var(
    $_POST['reservation_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$reservationId || $reservationId <= 0) {
    http_response_code(400);
    exit('Invalid reservation ID.');
}

// Validate amount.
$amount = $_POST['amount'] ?? '';

if (!is_string($amount)) {
    http_response_code(400);
    exit('Invalid payment amount.');
}

// Validate payment method.
$paymentMethod = $_POST['payment_method'] ?? '';

if (!is_string($paymentMethod)) {
    http_response_code(400);
    exit('Invalid payment method.');
}

try {
    validatePayment(
        $reservationId,
        $amount,
        $paymentMethod
    );
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// Do not record payments yet.
//
// Reservation ownership and outstanding-balance checks
// must be integrated before payment insertion is enabled.

http_response_code(503);

exit(
    'Payment submission is temporarily unavailable. ' .
    'Reservation verification is not yet integrated.'
);
