<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = requireLogin();

$pageTitle = 'Payment Receipt';

$paymentId = (int) ($_GET['id'] ?? 0);

if ($paymentId <= 0) {
    flash('error', 'Invalid payment selected.');
    redirect('reservations/my-reservations.php');
}

// Retrieve payment and reservation details.
$stmt = db()->prepare("
    SELECT
        payments.payment_id,
        payments.reservation_id,
        payments.amount,
        payments.payment_method,
        payments.payment_date,
        payments.receipt_number,
        payments.status,

        reservations.total_amount,
        reservations.check_in_date,
        reservations.check_out_date

    FROM payments

    INNER JOIN reservations
        ON payments.reservation_id = reservations.reservation_id

    WHERE payments.payment_id = :payment_id
    AND reservations.user_id = :user_id
");

$stmt->execute([
    ':payment_id' => $paymentId,
    ':user_id' => $user['id']
]);

$payment = $stmt->fetch();

if (!$payment) {
    flash('error', 'Payment receipt not found.');
    redirect('reservations/my-reservations.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Payment Receipt</h2>

        <p>
            Payment information for your reservation.
        </p>

        <div class="card">

            <div class="card-body">

                <h3>
                    Receipt: <?= e($payment['receipt_number']) ?>
                </h3>

                <hr>

                <h4>Payment Information</h4>

                <p>
                    <strong>Payment ID:</strong>
                    <?= (int) $payment['payment_id'] ?>
                </p>

                <p>
                    <strong>Reservation ID:</strong>
                    <?= (int) $payment['reservation_id'] ?>
                </p>

                <p>
                    <strong>Amount:</strong>
                    KSh <?= number_format(
                        (float) $payment['amount'],
                        2
                    ) ?>
                </p>

                <p>
                    <strong>Payment Method:</strong>
                    <?= e($payment['payment_method']) ?>
                </p>

                <p>
                    <strong>Payment Date:</strong>
                    <?= e($payment['payment_date']) ?>
                </p>

                <p>
                    <strong>Payment Status:</strong>
                    <?= e($payment['status']) ?>
                </p>

                <hr>

                <h4>Reservation Information</h4>

                <p>
                    <strong>Check-in:</strong>
                    <?= e($payment['check_in_date']) ?>
                </p>

                <p>
                    <strong>Check-out:</strong>
                    <?= e($payment['check_out_date']) ?>
                </p>

                <p>
                    <strong>Total Reservation Cost:</strong>
                    KSh <?= number_format(
                        (float) $payment['total_amount'],
                        2
                    ) ?>
                </p>

                <hr>

                <?php if ($payment['status'] === 'Pending'): ?>

                    <div class="alert alert-warning">
                        Your payment is awaiting administrator verification.
                    </div>

                <?php elseif ($payment['status'] === 'Paid'): ?>

                    <div class="alert alert-success">
                        Your payment has been verified successfully.
                    </div>

                <?php endif; ?>

                <button
                    onclick="window.print()"
                    class="btn btn-primary"
                >
                    Print Receipt
                </button>

                <a
                    href="<?= e(base_url('reservations/my-reservations.php')) ?>"
                    class="btn btn-secondary"
                >
                    Back to My Reservations
                </a>

            </div>

        </div>

    </div>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>