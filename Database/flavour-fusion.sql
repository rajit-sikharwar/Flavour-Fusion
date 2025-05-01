-- Create the database
CREATE DATABASE IF NOT EXISTS `flavour-fusion`;
USE `flavour-fusion`;

-- Create states table (for dropdown)
CREATE TABLE IF NOT EXISTS `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create cities table (for dropdown)
CREATE TABLE IF NOT EXISTS `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state_id` (`state_id`),
  CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `alt_phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `landmark` varchar(100) NOT NULL,
  `delivery_time` datetime NOT NULL,
  `delivery_type` enum('normal','express') NOT NULL,
  `instructions` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','preparing','on the way','delivered','cancelled') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create order_items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin table
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial admin user (username: admin, password: admin123)
INSERT INTO `admin` (`username`, `password`, `full_name`, `email`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@flavourfusion.com');

-- Insert sample states and cities (you can add more as needed)
INSERT INTO `states` (`name`) VALUES 
('Delhi'), 
('Maharashtra'), 
('Karnataka'), 
('Tamil Nadu'), 
('West Bengal');

INSERT INTO `cities` (`state_id`, `name`) VALUES 
(1, 'New Delhi'),
(1, 'Noida'),
(1, 'Gurgaon'),
(2, 'Mumbai'),
(2, 'Pune'),
(2, 'Nagpur'),
(3, 'Bangalore'),
(3, 'Mysore'),
(3, 'Hubli'),
(4, 'Chennai'),
(4, 'Coimbatore'),
(4, 'Madurai'),
(5, 'Kolkata'),
(5, 'Howrah'),
(5, 'Durgapur');