-- Create Database
CREATE DATABASE IF NOT EXISTS flavour_fusion;
USE flavour_fusion;

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(15),
    alt_phone VARCHAR(15),
    address TEXT,
    state VARCHAR(50),
    city VARCHAR(50),
    postal_code VARCHAR(10),
    landmark VARCHAR(100),
    food_items TEXT,
    total_price DECIMAL(10,2),
    delivery_time DATETIME,
    delivery_type VARCHAR(20),
    instructions TEXT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),  -- Use hashed password
    email VARCHAR(100)
);

-- Insert Default Admin
INSERT INTO admin (username, password, email)
VALUES ('admin', SHA2('admin123', 256), 'admin@flavourfusion.com');