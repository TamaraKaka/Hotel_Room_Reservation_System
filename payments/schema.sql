CREATE TABLE IF NOT EXISTS payments (
    payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    reservation_id BIGINT UNSIGNED NOT NULL,

    receipt_number VARCHAR(40) NOT NULL UNIQUE,

    amount DECIMAL(10, 2) NOT NULL,

    payment_method VARCHAR(30) NOT NULL,

    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_payments_reservation (reservation_id),
    INDEX idx_payments_date (payment_date),
    INDEX idx_payments_status (payment_status),

    CONSTRAINT chk_payment_amount
        CHECK (amount > 0),

    CONSTRAINT chk_payment_status
        CHECK (
            payment_status IN (
                'pending',
                'successful',
                'failed',
                'refunded'
            )
        )
);