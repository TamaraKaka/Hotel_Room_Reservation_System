-- Hotel Room Reservation System: database schema
-- Based on the ERD in the Task 3 design document (six tables).
-- Import this in phpMyAdmin: Import tab, choose this file, click Go.
-- Re-importing drops and rebuilds every table, so it also wipes your test data.

CREATE DATABASE IF NOT EXISTS hotel_reservation
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_reservation;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS room_availability;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS room_types;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Customers and administrators share one table, separated by the role column.
-- is_active is NOT in the ERD: it lets an administrator deactivate a customer
-- (Manage Customers) without deleting their reservation history.
CREATE TABLE users (
    user_id       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(100)  NOT NULL,
    phone         VARCHAR(20)   NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    role          VARCHAR(15)   NOT NULL DEFAULT 'Customer',   -- 'Customer' or 'Admin'
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE room_types (
    room_type_id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    type_name       VARCHAR(50)   NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity        INT UNSIGNED  NOT NULL,
    description     TEXT          NULL,
    PRIMARY KEY (room_type_id)
) ENGINE=InnoDB;

CREATE TABLE rooms (
    room_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_type_id INT UNSIGNED NOT NULL,
    room_number  VARCHAR(10)  NOT NULL,
    floor        INT          NOT NULL DEFAULT 0,
    status       VARCHAR(20)  NOT NULL DEFAULT 'Available',  -- Available, Booked, Maintenance
    PRIMARY KEY (room_id),
    UNIQUE KEY uq_rooms_number (room_number),
    CONSTRAINT fk_rooms_type FOREIGN KEY (room_type_id) REFERENCES room_types (room_type_id)
) ENGINE=InnoDB;

-- Blocked date ranges for a room, e.g. maintenance.
CREATE TABLE room_availability (
    availability_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id         INT UNSIGNED NOT NULL,
    start_date      DATE         NOT NULL,
    end_date        DATE         NOT NULL,
    reason          VARCHAR(100) NULL,
    PRIMARY KEY (availability_id),
    KEY idx_blocked_room_dates (room_id, start_date, end_date),
    CONSTRAINT fk_blocked_room FOREIGN KEY (room_id) REFERENCES rooms (room_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reservations (
    reservation_id INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED  NOT NULL,
    room_id        INT UNSIGNED  NOT NULL,
    check_in_date  DATE          NOT NULL,
    check_out_date DATE          NOT NULL,
    num_guests     INT UNSIGNED  NOT NULL,
    total_amount   DECIMAL(10,2) NOT NULL,
    status         VARCHAR(20)   NOT NULL DEFAULT 'Pending',  -- Pending, Confirmed, Completed, Cancelled
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reservation_id),
    KEY idx_res_room_dates (room_id, check_in_date, check_out_date),
    KEY idx_res_user (user_id),
    CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users (user_id),
    CONSTRAINT fk_res_room FOREIGN KEY (room_id) REFERENCES rooms (room_id)
) ENGINE=InnoDB;

-- A reservation can have many payments (partial payments).
CREATE TABLE payments (
    payment_id     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    reservation_id INT UNSIGNED  NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(30)   NOT NULL,                    -- Mobile money, Card, Cash on arrival
    payment_date   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    receipt_number VARCHAR(30)   NOT NULL,
    status         VARCHAR(20)   NOT NULL DEFAULT 'Paid',
    PRIMARY KEY (payment_id),
    UNIQUE KEY uq_payments_receipt (receipt_number),
    KEY idx_pay_res (reservation_id),
    CONSTRAINT fk_pay_res FOREIGN KEY (reservation_id) REFERENCES reservations (reservation_id)
) ENGINE=InnoDB;
