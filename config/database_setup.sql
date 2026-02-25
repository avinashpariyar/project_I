-- Database Setup Script for Vehicle Job Card System
-- Run this script in your MySQL/MariaDB database to create the necessary tables

-- Create database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS vehicle_jobcard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE vehicle_jobcard;

-- Create users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example: Insert a test user (password: 'password123')
-- Password is hashed using password_hash() PHP function
-- Default password hash for 'password123': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- You can create your own password hash using: password_hash('your_password', PASSWORD_DEFAULT)

-- Note: Replace the password hash below with your own hashed password
-- INSERT INTO users (first_name, last_name, email, password)  
-- VALUES ('Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create job cards table for vehicle service entries
CREATE TABLE IF NOT EXISTS job_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_card_no VARCHAR(50) NOT NULL UNIQUE,
    vehicle_number VARCHAR(50) NOT NULL,
    vehicle_model VARCHAR(100) NOT NULL,
    service_date DATE NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    kms INT NOT NULL,
    customer_address TEXT NOT NULL,
    inventory_items JSON NULL,
    fuel_level ENUM('Empty', '1/4', '1/2', '3/4', 'Full') NOT NULL,
    demanded_jobs TEXT NOT NULL,
    recommended_jobs TEXT NULL,
    submitted_by VARCHAR(150) NOT NULL,
    mechanic_name VARCHAR(150) NULL,
    bay_code VARCHAR(20) NOT NULL,
    job_status ENUM('pending', 'in-progress', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vehicle_number (vehicle_number),
    INDEX idx_customer_name (customer_name),
    INDEX idx_mechanic_name (mechanic_name),
    INDEX idx_service_date (service_date),
    INDEX idx_bay_code (bay_code),
    INDEX idx_job_status (job_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

