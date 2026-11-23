
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/payment_functions.php';

// Require a logged-in user.
$user = requireLogin();

// Validate payment ID.
$paymentId = filter_input(
    INPUT_GET,
    'payment_id',
    FILTER_VALIDATE_INT
);

if (!$paymentId || $paymentId <= 0) {
    http_response_code(400);
    exit('Invalid payment ID.');
}

// Retrieve payment details.
$stmt = db()->prepare(
    'SELECT
        payment_id,
        reservation_id,
        receipt_number,
        amount,
        payment_method,
        payment_date,
        payment_status
     FROM payments
     WHERE payment_id = ?'
);

$stmt->execute([$paymentId]);

$payment = $stmt->fetch();

if (!$payment) {
    http_response_code(404);
    exit('Receipt not found.');
}

// Do not display receipts for unsuccessful payments.
if ($payment['payment_status'] !== 'successful') {
    http_response_code(403);
    exit('A receipt is available only for successful payments.');
}

// TODO: Verify that this payment belongs to the logged-in
// customer, or require an administrator role, once the
// reservation ownership contract is available.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Receipt</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
            color: #333;
        }

        .receipt {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            color: #243b53;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 13px 0;
            border-bottom: 1px solid #ddd;
        }

        .receipt-row strong {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .status {
            color: #16803c;
            font-weight: bold;
        }

        .print-button {
            display: block;
            margin: 25px auto 0;
            padding: 12px 25px;
            background: #243b53;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt {
                box-shadow: none;
            }

            .print-button {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="receipt">

    <h1>Payment Receipt</h1>

    <p style="text-align:center;">
        Thank you for your payment.
    </p>

    <div class="receipt-row">
        <span>Receipt Number</span>
        <strong>
            <?= htmlspecialchars($payment['receipt_number']) ?>
        </strong>
    </div>

    <div class="receipt-row">
        <span>Payment ID</span>
        <strong>
            <?= (int) $payment['payment_id'] ?>
        </strong>
    </div>

    <div class="receipt-row">
        <span>Reservation ID</span>
        <strong>
            <?= (int) $payment['reservation_id'] ?>
        </strong>
    </div>

    <div class="receipt-row">
        <span>Amount Paid</span>
        <strong>
            <?= number_format((float) $payment['amount'], 2) ?>
        </strong>
    </div>

    <div class="receipt-row">
        <span>Payment Method</span>
        <strong>
            <?= htmlspecialchars($payment['payment_method']) ?>
        </strong>
    </div>

    <div class="receipt-row">
        <span>Payment Date</span>
        <strong>
            <?= htmlspecialchars($payment['payment_date']) ?>
        </strong>
    </div>

    <div class="receipt-row">
        <span>Payment Status</span>
        <strong class="status">
            <?= htmlspecialchars(ucfirst($payment['payment_status'])) ?>
        </strong>
    </div>

    <button class="print-button" onclick="window.print()">
        Print Receipt
    </button>

</div>

</body>
</html>
