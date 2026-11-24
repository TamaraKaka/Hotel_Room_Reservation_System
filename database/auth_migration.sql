-- Authentication tables for local demo; safe to import into Joyline's existing database.
-- Does NOT drop or replace Rooms, Room_Types, Room_Availability, or Reservations.
-- Angela and the group must align the final official schema with the ERD.
CREATE DATABASE IF NOT EXISTS hotel_reservation_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_reservation_system;
CREATE TABLE IF NOT EXISTS Customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Customer','Admin') NOT NULL DEFAULT 'Customer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS Auth_Remember_Tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  selector CHAR(24) NOT NULL UNIQUE,
  validator_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  INDEX idx_customer (customer_id),
  CONSTRAINT fk_remember_customer FOREIGN KEY (customer_id) REFERENCES Customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS Auth_Password_Resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  INDEX idx_reset_customer (customer_id),
  CONSTRAINT fk_reset_customer FOREIGN KEY (customer_id) REFERENCES Customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
