<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

/**
 * Record a customer's payment request.
 *
 * The payment remains Pending until an administrator verifies it.
 */
function createPaymentRequest(
    int $userId,
    int $reservationId,
    float $amount,
    string $paymentMethod
): int {

    $allowedMethods = [
        'Mobile money',
        'Card',
        'Cash on arrival'
    ];

    if ($amount <= 0) {
        throw new InvalidArgumentException(
            'Payment amount must be greater than zero.'
        );
    }

    if (!in_array($paymentMethod, $allowedMethods, true)) {
        throw new InvalidArgumentException(
            'Invalid payment method.'
        );
    }

    $pdo = db();

    $pdo->beginTransaction();

    try {

        // Lock the reservation.
        $stmt = $pdo->prepare("
            SELECT
                reservation_id,
                total_amount,
                status
            FROM reservations
            WHERE reservation_id = :reservation_id
            AND user_id = :user_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':reservation_id' => $reservationId,
            ':user_id' => $userId
        ]);

        $reservation = $stmt->fetch();

        if (!$reservation) {
            throw new RuntimeException(
                'Reservation not found.'
            );
        }

        if (!in_array(
            $reservation['status'],
            ['Pending', 'Confirmed'],
            true
        )) {
            throw new RuntimeException(
                'Payments cannot be made for this reservation.'
            );
        }

        // Calculate verified and pending payments.
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE reservation_id = :reservation_id
            AND status IN ('Paid', 'Pending')
        ");

        $stmt->execute([
            ':reservation_id' => $reservationId
        ]);

        $existingPayments = (float) $stmt->fetchColumn();

        $remainingAmount =
            (float) $reservation['total_amount'] - $existingPayments;

        if ($remainingAmount <= 0) {
            throw new RuntimeException(
                'The reservation has already been fully paid or has pending payments.'
            );
        }

        if ($amount > $remainingAmount) {
            throw new RuntimeException(
                'Payment exceeds the remaining balance of KSh ' .
                number_format($remainingAmount, 2)
            );
        }

        // Generate a unique receipt reference.
        $receiptNumber = 'RCP-' .
            strtoupper(bin2hex(random_bytes(6)));

        // Record payment as Pending.
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                reservation_id,
                amount,
                payment_method,
                receipt_number,
                status
            )
            VALUES (
                :reservation_id,
                :amount,
                :payment_method,
                :receipt_number,
                'Pending'
            )
        ");

        $stmt->execute([
            ':reservation_id' => $reservationId,
            ':amount' => $amount,
            ':payment_method' => $paymentMethod,
            ':receipt_number' => $receiptNumber
        ]);

        $paymentId = (int) $pdo->lastInsertId();

        $pdo->commit();

        return $paymentId;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}


/**
 * Verify a pending payment.
 *
 * The reservation is confirmed when its full amount
 * has been received through verified payments.
 */
function verifyPayment(int $paymentId): bool
{
    $pdo = db();

    $pdo->beginTransaction();

    try {

        // Find and lock the payment.
        $stmt = $pdo->prepare("
            SELECT
                payment_id,
                reservation_id,
                amount,
                status
            FROM payments
            WHERE payment_id = :payment_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':payment_id' => $paymentId
        ]);

        $payment = $stmt->fetch();

        if (!$payment || $payment['status'] !== 'Pending') {
            throw new RuntimeException(
                'Payment not found or already processed.'
            );
        }

        // Lock the related reservation.
        $stmt = $pdo->prepare("
            SELECT
                reservation_id,
                total_amount,
                status
            FROM reservations
            WHERE reservation_id = :reservation_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':reservation_id' => $payment['reservation_id']
        ]);

        $reservation = $stmt->fetch();

        if (!$reservation) {
            throw new RuntimeException(
                'Associated reservation not found.'
            );
        }

        if (!in_array(
            $reservation['status'],
            ['Pending', 'Confirmed'],
            true
        )) {
            throw new RuntimeException(
                'This reservation cannot receive payment.'
            );
        }

        // Calculate previously verified payments.
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE reservation_id = :reservation_id
            AND status = 'Paid'
        ");

        $stmt->execute([
            ':reservation_id' => $payment['reservation_id']
        ]);

        $previouslyPaid = (float) $stmt->fetchColumn();

        $newTotal = $previouslyPaid + (float) $payment['amount'];

        if ($newTotal > (float) $reservation['total_amount']) {
            throw new RuntimeException(
                'Verifying this payment would exceed the reservation amount.'
            );
        }

        // Mark payment as Paid.
        $stmt = $pdo->prepare("
            UPDATE payments
            SET status = 'Paid'
            WHERE payment_id = :payment_id
        ");

        $stmt->execute([
            ':payment_id' => $paymentId
        ]);

        // Confirm reservation only after full payment.
        if (
            $newTotal >= (float) $reservation['total_amount']
            && $reservation['status'] === 'Pending'
        ) {

            $stmt = $pdo->prepare("
                UPDATE reservations
                SET status = 'Confirmed'
                WHERE reservation_id = :reservation_id
                AND status = 'Pending'
            ");

            $stmt->execute([
                ':reservation_id' => $payment['reservation_id']
            ]);
        }

        $pdo->commit();

        return true;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}