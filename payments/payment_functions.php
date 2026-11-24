
<?php

require_once __DIR__ . '/../includes/auth.php';

/**
 * Generate a unique receipt number.
 */
function generateReceiptNumber(): string
{
    return 'RCT-' . date('Ymd') . '-' .
        strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Validate payment details before saving.
 */
function validatePayment(
    int $reservationId,
    string $amount,
    string $paymentMethod
): void {
    if ($reservationId <= 0) {
        throw new InvalidArgumentException('Invalid reservation ID.');
    }

    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount)) {
        throw new InvalidArgumentException('Invalid payment amount.');
    }

    if ((float) $amount <= 0) {
        throw new InvalidArgumentException(
            'Payment amount must be greater than zero.'
        );
    }

    $allowedMethods = [
        'cash',
        'card',
        'bank_transfer',
        'mobile_money'
    ];

    if (!in_array($paymentMethod, $allowedMethods, true)) {
        throw new InvalidArgumentException('Invalid payment method.');
    }
}

/**
 * Save a payment as pending.
 */
function recordPendingPayment(
    int $reservationId,
    string $amount,
    string $paymentMethod
): array {
    validatePayment($reservationId, $amount, $paymentMethod);

    $pdo = db();
    $receiptNumber = generateReceiptNumber();

    $stmt = $pdo->prepare(
        'INSERT INTO payments
            (reservation_id, receipt_number, amount,
             payment_method, payment_status)
         VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $reservationId,
        $receiptNumber,
        $amount,
        $paymentMethod,
        'pending'
    ]);

    return [
        'payment_id' => (int) $pdo->lastInsertId(),
        'receipt_number' => $receiptNumber
    ];
}

/**
 * Mark a pending payment as successful.
 *
 * IMPORTANT: Call this only after trusted payment verification.
 * Never expose it directly through a customer form.
 */
function markPaymentSuccessful(int $paymentId): bool
{
    if ($paymentId <= 0) {
        throw new InvalidArgumentException('Invalid payment ID.');
    }

    $stmt = db()->prepare(
        "UPDATE payments
         SET payment_status = 'successful'
         WHERE payment_id = ?
           AND payment_status = 'pending'"
    );

    $stmt->execute([$paymentId]);

    return $stmt->rowCount() === 1;
}

/**
 * Get the successful payments total for a reservation.
 */
function getReservationPaymentSummary(int $reservationId): array
{
    if ($reservationId <= 0) {
        throw new InvalidArgumentException('Invalid reservation ID.');
    }

    $stmt = db()->prepare(
        "SELECT
            COALESCE(SUM(amount), 0) AS amount_paid,
            COUNT(*) AS payment_count
         FROM payments
         WHERE reservation_id = ?
           AND payment_status = 'successful'"
    );

    $stmt->execute([$reservationId]);

    $result = $stmt->fetch();

    return [
        'amount_paid' => (float) $result['amount_paid'],
        'payment_count' => (int) $result['payment_count']
    ];
}
