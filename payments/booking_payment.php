
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/payment_functions.php';

// Only authenticated customers may access this page.
$user = requireRole(ROLE_CUSTOMER);

$reservationId = filter_input(
    INPUT_GET,
    'reservation_id',
    FILTER_VALIDATE_INT
);

if (!$reservationId || $reservationId <= 0) {
    http_response_code(400);
    exit('Invalid reservation ID.');
}

// Get successful payment totals.
$summary = getReservationPaymentSummary($reservationId);

// Generate CSRF token.
$csrfToken = csrfToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Payment</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
            color: #333;
        }

        .container {
            max-width: 750px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #243b53;
        }

        .summary {
            background: #f0f4f8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .summary p {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #d9e2ec;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #bcccdc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            background: #243b53;
            color: white;
            border: none;
            padding: 13px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:disabled {
            background: #9fb3c8;
            cursor: not-allowed;
        }

        .notice {
            background: #fff4d6;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            line-height: 1.5;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Booking Payment</h1>

    <p>
        Reservation ID:
        <strong>
            <?= htmlspecialchars((string) $reservationId) ?>
        </strong>
    </p>

    <div class="summary">

        <h2>Payment Summary</h2>

        <p>
            <span>Successful amount paid</span>
            <strong>
                <?= number_format($summary['amount_paid'], 2) ?>
            </strong>
        </p>

        <p>
            <span>Number of successful payments</span>
            <strong>
                <?= $summary['payment_count'] ?>
            </strong>
        </p>

    </div>

    <h2>Make a Payment</h2>

    <form action="process_payment.php" method="POST">

        <input
            type="hidden"
            name="csrf"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
        >

        <input
            type="hidden"
            name="reservation_id"
            value="<?= htmlspecialchars((string) $reservationId) ?>"
        >

        <div class="form-group">

            <label for="amount">Payment Amount</label>

            <input
                type="number"
                id="amount"
                name="amount"
                min="0.01"
                max="99999999.99"
                step="0.01"
                required
                placeholder="Enter payment amount"
            >

        </div>

        <div class="form-group">

            <label for="payment_method">Payment Method</label>

            <select
                id="payment_method"
                name="payment_method"
                required
            >
                <option value="">Select payment method</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="mobile_money">Mobile Money</option>
            </select>

        </div>

        <button type="submit" disabled>
            Payment submission not yet enabled
        </button>

    </form>

    <div class="notice">
        The payment form is being prepared. Payment submission will
        be enabled after reservation ownership, outstanding balance,
        and payment verification are connected.
    </div>

</div>

</body>
</html>
