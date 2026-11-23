
USE hotel_reservation;

-- =====================================
-- 1. SAMPLE USERS
-- =====================================

-- Sample password for all accounts: password
-- The password is stored as a bcrypt hash.

INSERT INTO users
(user_id, full_name, email, phone, password_hash, role)
VALUES
(1, 'Admin User', 'admin@hotel.com', '0711000001',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.pwW9pM9W', 'Admin'),

(2, 'John Kamau', 'john@gmail.com', '0711000002',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.pwW9pM9W', 'Customer'),

(3, 'Mary Wanjiku', 'mary@gmail.com', '0711000003',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.pwW9pM9W', 'Customer');


-- =====================================
-- 2. ROOM TYPES
-- =====================================

INSERT INTO room_types
(room_type_id, type_name, price_per_night, capacity, description)
VALUES
(1, 'Single', 3000.00, 1,
 'A comfortable room suitable for one guest.'),

(2, 'Double', 5000.00, 2,
 'A spacious room suitable for two guests.'),

(3, 'Deluxe', 8000.00, 3,
 'A premium room with additional comfort and space.'),

(4, 'Suite', 12000.00, 4,
 'A luxurious suite suitable for families and groups.');


-- =====================================
-- 3. HOTEL ROOMS
-- =====================================

INSERT INTO rooms
(room_id, room_type_id, room_number, floor, status)
VALUES
(1, 1, '101', 1, 'Available'),
(2, 1, '102', 1, 'Available'),
(3, 2, '201', 2, 'Available'),
(4, 2, '202', 2, 'Available'),
(5, 3, '301', 3, 'Available'),
(6, 3, '302', 3, 'Available'),
(7, 4, '401', 4, 'Available'),
(8, 4, '402', 4, 'Maintenance');


-- =====================================
-- 4. BLOCKED ROOM DATES
-- =====================================

INSERT INTO room_availability
(availability_id, room_id, start_date, end_date, reason)
VALUES
(1, 2, '2026-12-05', '2026-12-09', 'Room maintenance'),
(2, 6, '2026-12-15', '2026-12-18', 'Scheduled repairs');


-- =====================================
-- 5. SAMPLE RESERVATIONS
-- =====================================

INSERT INTO reservations
(reservation_id, user_id, room_id, check_in_date,
 check_out_date, num_guests, total_amount, status)
VALUES

-- Confirmed reservation
(1, 2, 1, '2026-12-10', '2026-12-13',
 1, 9000.00, 'Confirmed'),

-- Pending reservation
(2, 3, 3, '2026-12-20', '2026-12-23',
 2, 15000.00, 'Pending'),

-- Cancelled reservation
(3, 2, 5, '2026-12-10', '2026-12-13',
 2, 24000.00, 'Cancelled'),

-- Completed reservation (past dates)
(4, 3, 7, '2026-10-10', '2026-10-12',
 3, 24000.00, 'Completed');


-- =====================================
-- 6. SAMPLE PAYMENTS
-- =====================================

INSERT INTO payments
(payment_id, reservation_id, amount, payment_method,
 receipt_number, status)
VALUES

(1, 1, 9000.00, 'Mobile money',
 'RCP001', 'Paid'),

(2, 2, 5000.00, 'Card',
 'RCP002', 'Paid'),

(3, 4, 24000.00, 'Cash on arrival',
 'RCP003', 'Paid');
