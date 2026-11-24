<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../payments/functions.php';

$user = requireRole(ROLE_ADMIN);

$pageTitle = 'Manage Payments';

$error = '';

// Process payment verification.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrfValid()) {

        $error = 'Invalid security token. Please try again.';

    } else {

        $paymentId = (int) ($_POST['payment_id'] ?? 0);

        try {

            if ($paymentId <= 0) {
                throw new RuntimeException('Invalid payment ID.');
            }

            verifyPayment($paymentId);

            flash(
                'success',
                'Payment verified successfully.'
            );

            header(
                'Location: ' . base_url('admin/payments.php')
            );

            exit;

        } catch (Throwable $e) {

            $error = $e->getMessage();
        }
    }
}

// Retrieve all payments.
$sql = "
    SELECT
        payments.payment_id,
        payments.reservation_id,
        payments.amount,
        payments.payment_method,
        payments.payment_date,
        payments.receipt_number,
        payments.status,

        reservations.total_amount,
        reservations.status AS reservation_status,

        users.full_name,
        users.email,

        rooms.room_number

    FROM payments

    INNER JOIN reservations
        ON payments.reservation_id = reservations.reservation_id

    INNER JOIN users
        ON reservations.user_id = users.user_id

    INNER JOIN rooms
        ON reservations.room_id = rooms.room_id

    ORDER BY payments.payment_date DESC
";

$stmt = db()->query($sql);

$payments = $stmt->fetchAll();

$messages = takeFlash();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Manage Payments</h2>

        <p>
            View customer payment requests and verify payments
            after confirming that the money has been received.
        </p>

        <?php if (!empty($messages['success'])): ?>

            <p>
                <?= e($messages['success']) ?>
            </p>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <p>
                <?= e($error) ?>
            </p>

        <?php endif; ?>

        <?php if (empty($payments)): ?>

            <p>No payment records found.</p>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Payment ID</th>
                            <th>Receipt Number</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Reservation ID</th>
                            <th>Room</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Payment Date</th>
                            <th>Payment Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($payments as $payment): ?>

                            <tr>

                                <td>
                                    <?= (int) $payment['payment_id'] ?>
                                </td>

                                <td>
                                    <?= e($payment['receipt_number']) ?>
                                </td>

                                <td>
                                    <?= e($payment['full_name']) ?>
                                </td>

                                <td>
                                    <?= e($payment['email']) ?>
                                </td>

                                <td>
                                    <?= (int) $payment['reservation_id'] ?>
                                </td>

                                <td>
                                    <?= e($payment['room_number']) ?>
                                </td>

                                <td>
                                    KSh
                                    <?= number_format(
                                        (float) $payment['amount'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($payment['payment_method']) ?>
                                </td>

                                <td>
                                    <?= e($payment['payment_date'] ?? 'Not recorded') ?>
                                </td>

                                <td>
                                    <?= e($payment['status']) ?>
                                </td>

                                <td>

                                    <?php if ($payment['status'] === 'Pending'): ?>

                                        <form method="POST" action="">

                                            <?= csrfField() ?>

                                            <input
                                                type="hidden"
                                                name="payment_id"
                                                value="<?= (int) $payment['payment_id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                onclick="return confirm('Have you confirmed that this payment was received?');"
                                            >
                                                Verify Payment
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        No action available.

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>