-- ========================================================
-- Riverside FC Ticketing System - Database Schema (MySQL/MariaDB)
-- Currency: Ghana Cedis (GH₵ / GHS)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `riverside_fc` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `riverside_fc`;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `booking_items`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `ticket_types`;
DROP TABLE IF EXISTS `fixtures`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `phone` VARCHAR(50) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('supporter', 'admin') NOT NULL DEFAULT 'supporter',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `fixtures`
-- --------------------------------------------------------
CREATE TABLE `fixtures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `opponent` VARCHAR(150) NOT NULL,
  `opponent_logo` VARCHAR(255) DEFAULT NULL,
  `competition` VARCHAR(100) NOT NULL DEFAULT 'Ghana Premier League',
  `match_date` DATE NOT NULL,
  `kick_off` TIME NOT NULL,
  `venue` VARCHAR(150) NOT NULL DEFAULT 'Riverside Arena, Accra',
  `status` ENUM('upcoming', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `ticket_types` (Stadium Sections)
-- --------------------------------------------------------
CREATE TABLE `ticket_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fixture_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `gate` VARCHAR(50) DEFAULT 'Gate A',
  `price` DECIMAL(10, 2) NOT NULL,
  `capacity` INT NOT NULL,
  `remaining` INT NOT NULL,
  CONSTRAINT `fk_ticket_types_fixture` FOREIGN KEY (`fixture_id`) REFERENCES `fixtures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bookings`
-- --------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_reference` VARCHAR(64) NOT NULL UNIQUE,
  `user_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_email` VARCHAR(191) NOT NULL,
  `customer_phone` VARCHAR(50) NOT NULL,
  `fixture_id` INT NOT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'momo_mtn',
  `payment_status` ENUM('paid', 'pending', 'cancelled') NOT NULL DEFAULT 'paid',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bookings_fixture` FOREIGN KEY (`fixture_id`) REFERENCES `fixtures` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `booking_items`
-- --------------------------------------------------------
CREATE TABLE `booking_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `ticket_type_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  CONSTRAINT `fk_booking_items_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_items_ticket_type` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `tickets` (Individual E-passes)
-- --------------------------------------------------------
CREATE TABLE `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `ticket_type_id` INT NOT NULL,
  `ticket_code` VARCHAR(64) NOT NULL UNIQUE,
  `qr_data` TEXT NOT NULL,
  `status` ENUM('valid', 'used', 'cancelled') NOT NULL DEFAULT 'valid',
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tickets_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tickets_type` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- Sample Seed Data
-- Passwords:
-- Admin: admin123 (hash below)
-- Supporters: user123 (hash below)
-- ========================================================

-- Users
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Riverside Admin', 'admin@riversidefc.com', '+233245550190', '$2y$12$t47qUq7Vb3bL25zZl1eR..iGq6U6p1j4c6Zc8d9E7mF6G5H4I3J2K', 'admin', NOW()),
(2, 'Kwame Mensah', 'kwame@example.com', '+233241234567', '$2y$12$t47qUq7Vb3bL25zZl1eR..iGq6U6p1j4c6Zc8d9E7mF6G5H4I3J2K', 'supporter', NOW()),
(3, 'Akosua Boateng', 'akosua@example.com', '+233209876543', '$2y$12$t47qUq7Vb3bL25zZl1eR..iGq6U6p1j4c6Zc8d9E7mF6G5H4I3J2K', 'supporter', NOW());

-- Fixtures
INSERT INTO `fixtures` (`id`, `opponent`, `opponent_logo`, `competition`, `match_date`, `kick_off`, `venue`, `status`, `description`) VALUES
(1, 'Hearts of Oak', 'hearts_of_oak.svg', 'Ghana Premier League', DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), '15:00:00', 'Riverside Arena, Accra', 'upcoming', 'The Greater Accra derby! High-intensity clash as Riverside FC hosts the Phobians under the floodlights.'),
(2, 'Asante Kotoko SC', 'kotoko.svg', 'Ghana Premier League', DATE_ADD(CURRENT_DATE, INTERVAL 11 DAY), '16:00:00', 'Riverside Arena, Accra', 'upcoming', 'The Porcupine Warriors visit Riverside Arena for a title-defining showdown. Expect a packed stadium!'),
(3, 'Medeama SC', 'medeama.svg', 'MTN FA Cup - Quarter Final', DATE_ADD(CURRENT_DATE, INTERVAL 18 DAY), '15:30:00', 'Riverside Arena, Accra', 'upcoming', 'Cup fever returns to Riverside. Knockout football where only victory secures a semi-final ticket.'),
(4, 'Dreams FC', 'dreams.svg', 'Ghana Premier League', DATE_ADD(CURRENT_DATE, INTERVAL 25 DAY), '15:00:00', 'Riverside Arena, Accra', 'upcoming', 'Sunday afternoon league encounter featuring two attacking sides known for fast, fluid football.'),
(5, 'Great Olympics', 'olympics.svg', 'Ghana Premier League', DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '15:00:00', 'Riverside Arena, Accra', 'completed', 'Riverside FC claimed a decisive 2-1 win over the Wonder Club in front of 18,500 roaring fans.');

-- Ticket Types / Stadium Sections
-- Fixture 1: Hearts of Oak
INSERT INTO `ticket_types` (`id`, `fixture_id`, `name`, `description`, `gate`, `price`, `capacity`, `remaining`) VALUES
(1, 1, 'VIP Executive Lounge', 'Padded luxury seats, match programme, air-conditioned hospitality lounge & refreshments', 'Gate A - VIP', 150.00, 200, 48),
(2, 1, 'Main Grandstand', 'Covered central seating with premium elevated pitch view', 'Gate B - West', 80.00, 3000, 892),
(3, 1, 'Popular Stand (East)', 'Atmospheric covered stand directly opposite the players tunnel', 'Gate C - East', 40.00, 8000, 2410),
(4, 1, 'North Terrace (Ultras Stand)', 'Safe standing & singing section behind the home goal', 'Gate D - North', 30.00, 5000, 1150),
(5, 1, 'Riverside Family Enclosure', 'Alcohol-free family section with dedicated junior amenities', 'Gate E - South', 50.00, 1500, 620);

-- Fixture 2: Asante Kotoko
INSERT INTO `ticket_types` (`id`, `fixture_id`, `name`, `description`, `gate`, `price`, `capacity`, `remaining`) VALUES
(6, 2, 'VIP Executive Lounge', 'Padded luxury seats, match programme & hospitality refreshments', 'Gate A - VIP', 160.00, 200, 110),
(7, 2, 'Main Grandstand', 'Covered central seating with premium elevated view', 'Gate B - West', 85.00, 3000, 1450),
(8, 2, 'Popular Stand (East)', 'Atmospheric stand with energetic home crowd support', 'Gate C - East', 45.00, 8000, 4200),
(9, 2, 'North Terrace (Ultras Stand)', 'Behind the goal dedicated singing stand', 'Gate D - North', 35.00, 5000, 2800),
(10, 2, 'Riverside Family Enclosure', 'Family seating area with entertainment', 'Gate E - South', 55.00, 1500, 940);

-- Fixture 3: Medeama SC (Cup)
INSERT INTO `ticket_types` (`id`, `fixture_id`, `name`, `description`, `gate`, `price`, `capacity`, `remaining`) VALUES
(11, 3, 'VIP Executive Lounge', 'VIP entry, executive match seat and lounge hospitality', 'Gate A - VIP', 140.00, 200, 175),
(12, 3, 'Main Grandstand', 'Elevated prime view of cup knockout action', 'Gate B - West', 75.00, 3000, 2100),
(13, 3, 'Popular Stand (East)', 'Affordable pitch-side general admission', 'Gate C - East', 35.00, 8000, 5600),
(14, 3, 'North Terrace (Ultras Stand)', 'Behind goal energetic terrace', 'Gate D - North', 25.00, 5000, 3900);

-- Fixture 4: Dreams FC
INSERT INTO `ticket_types` (`id`, `fixture_id`, `name`, `description`, `gate`, `price`, `capacity`, `remaining`) VALUES
(15, 4, 'VIP Executive Lounge', 'VIP Box hospitality', 'Gate A - VIP', 130.00, 200, 190),
(16, 4, 'Main Grandstand', 'Grandstand seating', 'Gate B - West', 70.00, 3000, 2600),
(17, 4, 'Popular Stand (East)', 'East stand admission', 'Gate C - East', 35.00, 8000, 7100),
(18, 4, 'North Terrace (Ultras Stand)', 'North terrace general access', 'Gate D - North', 25.00, 5000, 4500);

-- Fixture 5: Great Olympics (Completed)
INSERT INTO `ticket_types` (`id`, `fixture_id`, `name`, `description`, `gate`, `price`, `capacity`, `remaining`) VALUES
(19, 5, 'VIP Executive Lounge', 'VIP Seating', 'Gate A - VIP', 150.00, 200, 0),
(20, 5, 'Main Grandstand', 'Main Seating', 'Gate B - West', 80.00, 3000, 120),
(21, 5, 'Popular Stand (East)', 'East Stand', 'Gate C - East', 40.00, 8000, 450);

-- Sample Initial Bookings for Reports & Charts
INSERT INTO `bookings` (`id`, `booking_reference`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `fixture_id`, `total_amount`, `payment_method`, `payment_status`, `created_at`) VALUES
(1, 'RFC-2026-A109B', 2, 'Kwame Mensah', 'kwame@example.com', '+233241234567', 1, 160.00, 'momo_mtn', 'paid', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'RFC-2026-B882C', 3, 'Akosua Boateng', 'akosua@example.com', '+233209876543', 1, 150.00, 'momo_telecel', 'paid', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'RFC-2026-C994D', NULL, 'Kofi Addo', 'kofi.addo@gmail.com', '+233540001122', 2, 170.00, 'card', 'paid', NOW());

-- Booking Items
INSERT INTO `booking_items` (`id`, `booking_id`, `ticket_type_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 2, 2, 80.00, 160.00),
(2, 2, 1, 1, 150.00, 150.00),
(3, 3, 7, 2, 85.00, 170.00);

-- Sample Digital Tickets
INSERT INTO `tickets` (`id`, `booking_id`, `ticket_type_id`, `ticket_code`, `qr_data`, `status`, `created_at`) VALUES
(1, 1, 2, 'RFC-TKT-KM01A', 'RFC-TKT-KM01A|REF:RFC-2026-A109B|FIX:1|STAND:Main Grandstand|HOLDER:Kwame Mensah', 'valid', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 2, 'RFC-TKT-KM02B', 'RFC-TKT-KM02B|REF:RFC-2026-A109B|FIX:1|STAND:Main Grandstand|HOLDER:Kwame Mensah', 'valid', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 1, 'RFC-TKT-AB01X', 'RFC-TKT-AB01X|REF:RFC-2026-B882C|FIX:1|STAND:VIP Executive Lounge|HOLDER:Akosua Boateng', 'valid', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 3, 7, 'RFC-TKT-KA01Y', 'RFC-TKT-KA01Y|REF:RFC-2026-C994D|FIX:2|STAND:Main Grandstand|HOLDER:Kofi Addo', 'valid', NOW()),
(5, 3, 7, 'RFC-TKT-KA02Z', 'RFC-TKT-KA02Z|REF:RFC-2026-C994D|FIX:2|STAND:Main Grandstand|HOLDER:Kofi Addo', 'valid', NOW());
