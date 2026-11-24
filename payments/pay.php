<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/functions.php';

$user = requireLogin();

$pageTitle = 'Make Payment';

$error = '';
$success = '';

$reservationId = (int) ($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

// Retrieve the customer's reservation.
$stmt = db()->prepare("
    SELECT
        reservation_id,
        check_in_date,
        check_out_date,
        total_amount,
        status
    FROM reservations
    WHERE reservation_id = :reservation_id
    AND user_id = :user_id
");

$stmt->execute([
    ':reservation_id' => $reservationId,
    ':user_id' => $user['id']
]);

$reservation = $stmt->fetch();

if (!$reservation) {
    flash('error', 'Reservation not found.');
    redirect('reservations/my-reservations.php');
}

// Calculate existing payments.
$stmt = db()->prepare("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Paid' THEN amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_amount,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending' THEN amount
                    ELSE 0
                END
            ),
            0
        ) AS pending_amount

    FROM payments

    WHERE reservation_id = :reservation_id
");

$stmt->execute([
    ':reservation_id' => $reservationId
]);

$paymentSummary = $stmt->fetch();

$paidAmount = (float) $paymentSummary['paid_amount'];

$pendingAmount = (float) $paymentSummary['pending_amount'];

$totalAmount = (float) $reservation['total_amount'];

$remainingAmount = max(
    0,
    $totalAmount - $paidAmount - $pendingAmount
);

// Process payment request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrfValid()) {

        $error = 'Invalid security token. Please refresh the page.';

    } elseif (!in_array(
        $reservation['status'],
        ['Pending', 'Confirmed'],
        true
    )) {

        $error = 'Payments cannot be made for this reservation.';

    } elseif ($remainingAmount <= 0) {

        $error = 'There is no outstanding balance to pay.';

    } else {

        $amount = filter_var(
            $_POST['amount'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $paymentMethod = trim(
            $_POST['payment_method'] ?? ''
        );

        try {

            if ($amount === false || $amount <= 0) {
                throw new InvalidArgumentException(
                    'Enter a valid payment amount.'
                );
            }

            $paymentId = createPaymentRequest(
                (int) $user['id'],
                $reservationId,
                (float) $amount,
                $paymentMethod
            );

            flash(
                'success',
                'Payment request submitted successfully. Awaiting administrator verification.'
            );

            redirect('payments/receipt.php?id=' . $paymentId);

        } catch (Throwable $e) {

            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Make Payment</h2>

        <p>
            Submit a payment request for your reservation.
        </p>

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <div class="card">

            <div class="card-body">

                <h3>
                    Reservation #<?= (int) $reservation['reservation_id'] ?>
                </h3>

                <p>
                    <strong>Check-in:</strong>
                    <?= e($reservation['check_in_date']) ?>
                </p>

                <p>
                    <strong>Check-out:</strong>
                    <?= e($reservation['check_out_date']) ?>
                </p>

                <p>
                    <strong>Reservation Status:</strong>
                    <?= e($reservation['status']) ?>
                </p>

                <hr>

                <p>
                    <strong>Total Amount:</strong>
                    KSh <?= number_format($totalAmount, 2) ?>
                </p>

                <p>
                    <strong>Amount Paid:</strong>
                    KSh <?= number_format($paidAmount, 2) ?>
                </p>

                <p>
                    <strong>Pending Payments:</strong>
                    KSh <?= number_format($pendingAmount, 2) ?>
                </p>

                <p>
                    <strong>Remaining Balance:</strong>
                    KSh <?= number_format($remainingAmount, 2) ?>
                </p>

                <hr>

                <?php if ($remainingAmount > 0): ?>

                    <form method="POST" action="">

                        <?= csrfField() ?>

                        <input
                            type="hidden"
                            name="reservation_id"
                            value="<?= $reservationId ?>"
                        >

                        <div class="form-group">

                            <label for="amount">
                                Payment Amount (KSh)
                            </label>

                            <input
                                type="number"
                                name="amount"
                                id="amount"
                                class="form-control"
                                min="0.01"
                                max="<?= e((string) $remainingAmount) ?>"
                                step="0.01"
                                required
                            >

                        </div>

                        <br>

                        <div class="form-group">

                            <label for="payment_method">
                                Payment Method
                            </label>

                            <select
                                name="payment_method"
                                id="payment_method"
                                class="form-control"
                                required
                            >

                                <option value="">
                                    Select Payment Method
                                </option>

                                <option value="Mobile money">
                                    Mobile Money
                                </option>

                                <option value="Card">
                                    Card
                                </option>

                                <option value="Cash on arrival">
                                    Cash on Arrival
                                </option>

                            </select>

                        </div>

                        <br>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Submit Payment Request
                        </button>

                    </form>

                <?php else: ?>

                    <div class="alert alert-success">
                        Your reservation is fully paid or has pending payments.
                    </div>

                <?php endif; ?>

                <br>

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