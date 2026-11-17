-- DEMO-ONLY local database for Joyline's independently runnable Room Management module.
-- The group database/schema.sql belongs to Angela; do not replace it with this file.
CREATE DATABASE IF NOT EXISTS hotel_reservation_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_reservation_system;

CREATE TABLE IF NOT EXISTS Room_Types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_name VARCHAR(80) NOT NULL UNIQUE,
  price_per_night DECIMAL(10,2) NOT NULL,
  capacity SMALLINT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Rooms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(12) NOT NULL UNIQUE,
  room_type_id INT UNSIGNED NOT NULL,
  floor SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('Available','Maintenance','Out of Service') NOT NULL DEFAULT 'Available',
  description VARCHAR(255) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_room_type FOREIGN KEY (room_type_id) REFERENCES Room_Types(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Room_Availability (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id INT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_room_availability (room_id, start_date, end_date),
  CONSTRAINT fk_availability_room FOREIGN KEY (room_id) REFERENCES Rooms(id)
) ENGINE=InnoDB;

-- Temporary table FOR LOCAL DEMO of preventing room deletion with active reservations.
-- In the final group system, Angela/Malvin own the official reservation table definition.
CREATE TABLE IF NOT EXISTS Reservations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id INT UNSIGNED NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  status ENUM('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reservation_overlap (room_id, check_in, check_out, status),
  CONSTRAINT fk_reservation_room FOREIGN KEY (room_id) REFERENCES Rooms(id)
) ENGINE=InnoDB;
