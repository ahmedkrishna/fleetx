-- ============================================================
-- FleetX Database Schema — Hostinger MySQL
-- Database: u274391035_fleetx
-- Charset: utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Users (All roles) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`     VARCHAR(120) NOT NULL,
  `mobile`        VARCHAR(20) NOT NULL UNIQUE,
  `email`         VARCHAR(150) UNIQUE,
  `password_hash` VARCHAR(255),
  `role`          ENUM('buyer','seller','inspector','admin') NOT NULL DEFAULT 'buyer',
  `national_id`   VARCHAR(20),
  `city`          VARCHAR(60),
  `nafath_verified` TINYINT(1) DEFAULT 0,
  `sanad_limit`   DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Sanad promissory note bidding limit',
  `is_active`     TINYINT(1) DEFAULT 1,
  `wallet_balance` DECIMAL(12,2) DEFAULT 0.00,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_mobile` (`mobile`),
  INDEX `idx_role`   (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seller Companies (شركات التأجير) ──────────────────────
CREATE TABLE IF NOT EXISTS `seller_companies` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `company_name` VARCHAR(200) NOT NULL,
  `cr_number`    VARCHAR(20) NOT NULL UNIQUE COMMENT 'Commercial Registration',
  `vat_number`   VARCHAR(20),
  `fleet_size`   INT DEFAULT 0,
  `subscription` ENUM('standard','premium','enterprise') DEFAULT 'standard',
  `rating`       DECIMAL(3,2) DEFAULT 0.00,
  `total_auctions` INT DEFAULT 0,
  `is_verified`  TINYINT(1) DEFAULT 0,
  `logo_url`     VARCHAR(500),
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Vehicles ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `seller_id`       INT NOT NULL,
  `vin`             VARCHAR(20) UNIQUE COMMENT 'Vehicle Identification Number',
  `plate_number`    VARCHAR(20),
  `make`            VARCHAR(60) NOT NULL,
  `model`           VARCHAR(60) NOT NULL,
  `year`            YEAR NOT NULL,
  `mileage`         INT NOT NULL,
  `color`           VARCHAR(40),
  `fuel_type`       ENUM('بنزين','ديزل','هجين','كهربائي','غاز') DEFAULT 'بنزين',
  `transmission`    ENUM('أوتوماتيك','يدوي') DEFAULT 'أوتوماتيك',
  `engine_size`     VARCHAR(20),
  `city`            VARCHAR(60) NOT NULL,
  `condition_grade` ENUM('ممتازة','جيدة جداً','جيدة','مقبولة') DEFAULT 'جيدة',
  `description`     TEXT,
  `image_url`       VARCHAR(500),
  `images`          JSON COMMENT 'Array of image URLs',
  `documents`       JSON COMMENT 'Istimara, Insurance docs',
  `autodata_price_min` DECIMAL(12,2),
  `autodata_price_max` DECIMAL(12,2),
  `status`          ENUM('pending','approved','in_auction','sold','withdrawn') DEFAULT 'pending',
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `seller_companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_make_model` (`make`, `model`),
  INDEX `idx_year`       (`year`),
  INDEX `idx_city`       (`city`),
  INDEX `idx_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Inspections ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inspections` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id`        INT NOT NULL UNIQUE,
  `inspector_id`      INT NOT NULL,
  `exterior_score`    TINYINT UNSIGNED DEFAULT 0,
  `interior_score`    TINYINT UNSIGNED DEFAULT 0,
  `mechanical_score`  TINYINT UNSIGNED DEFAULT 0,
  `electronics_score` TINYINT UNSIGNED DEFAULT 0,
  `overall_score`     TINYINT UNSIGNED GENERATED ALWAYS AS (
    ROUND((exterior_score + interior_score + mechanical_score + electronics_score) / 4)
  ) STORED,
  `paint_condition`   ENUM('excellent','good','fair','poor') DEFAULT 'good',
  `accident_history`  TINYINT(1) DEFAULT 0,
  `notes`             TEXT,
  `report_pdf`        VARCHAR(500),
  `photos`            JSON,
  `inspection_date`   DATE,
  `status`            ENUM('pending','in_progress','completed') DEFAULT 'pending',
  `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicle_id`)   REFERENCES `vehicles`(`id`),
  FOREIGN KEY (`inspector_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Auction Events (فعاليات المزادات) ─────────────────────
CREATE TABLE IF NOT EXISTS `auction_events` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `seller_id`    INT NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `description`  TEXT,
  `brochure_pdf` VARCHAR(500),
    `cover_image`  VARCHAR(500) COMMENT 'Header cover image',
  `status`       ENUM('upcoming','active','ended') DEFAULT 'upcoming',
  `start_time`   DATETIME NOT NULL,
  `end_time`     DATETIME NOT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `seller_companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Auctions ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `auctions` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `event_id`       INT DEFAULT NULL,
  `vehicle_id`     INT NOT NULL,
  `seller_id`      INT NOT NULL,
  `title`          VARCHAR(200),
  `type`           ENUM('live','instant','sealed','upcoming') NOT NULL DEFAULT 'live',
  `status`         ENUM('draft','active','live','ended','cancelled') DEFAULT 'draft',
  `starting_price` DECIMAL(12,2) NOT NULL,
  `current_price`  DECIMAL(12,2) NOT NULL,
  `reserve_price`  DECIMAL(12,2) COMMENT 'Minimum acceptable price (hidden)',
  `buy_now_price`  DECIMAL(12,2) COMMENT 'Instant buy price',
  `bid_increment`  DECIMAL(10,2) DEFAULT 500.00,
  `start_time`     DATETIME NOT NULL,
  `end_time`       DATETIME,
  `is_featured`    TINYINT(1) DEFAULT 0,
  `views_count`    INT DEFAULT 0,
  `winner_id`      INT,
  `sale_price`     DECIMAL(12,2),
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `auction_events`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`),
  FOREIGN KEY (`seller_id`)  REFERENCES `seller_companies`(`id`),
  FOREIGN KEY (`winner_id`)  REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_event`     (`event_id`),
  INDEX `idx_status`    (`status`),
  INDEX `idx_type`      (`type`),
  INDEX `idx_end_time`  (`end_time`),
  INDEX `idx_featured`  (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Bids ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bids` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `auction_id` INT NOT NULL,
  `user_id`    INT NOT NULL,
  `amount`     DECIMAL(12,2) NOT NULL,
  `is_auto`    TINYINT(1) DEFAULT 0,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`auction_id`) REFERENCES `auctions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`),
  INDEX `idx_auction` (`auction_id`),
  INDEX `idx_user`    (`user_id`),
  INDEX `idx_amount`  (`amount` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Auto Bids (Proxy Bidding) ──────────────────────────────
CREATE TABLE IF NOT EXISTS `auto_bids` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `auction_id`  INT NOT NULL,
  `user_id`     INT NOT NULL,
  `max_amount`  DECIMAL(12,2) NOT NULL,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`auction_id`) REFERENCES `auctions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Transactions ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transactions` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `auction_id`      INT NOT NULL UNIQUE,
  `buyer_id`        INT NOT NULL,
  `seller_id`       INT NOT NULL,
  `sale_price`      DECIMAL(12,2) NOT NULL,
  `platform_fee`    DECIMAL(12,2) NOT NULL COMMENT '5% of sale price',
  `seller_payout`   DECIMAL(12,2) NOT NULL,
  `payment_method`  ENUM('mada','visa','mastercard','bank_transfer','sadad','apple_pay') DEFAULT 'mada',
  `payment_status`  ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_ref`     VARCHAR(100),
  `paid_at`         TIMESTAMP,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`auction_id`) REFERENCES `auctions`(`id`),
  FOREIGN KEY (`buyer_id`)   REFERENCES `users`(`id`),
  FOREIGN KEY (`seller_id`)  REFERENCES `seller_companies`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Watchlist ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `watchlist` (
  `user_id`    INT NOT NULL,
  `auction_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `auction_id`),
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`auction_id`) REFERENCES `auctions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Activity Log ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `type`       VARCHAR(50) NOT NULL DEFAULT 'system',
  `message`    TEXT NOT NULL,
  `meta`       JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notifications ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `type`       ENUM('bid_placed','outbid','auction_won','auction_end','payment','system') NOT NULL,
  `title`      VARCHAR(200),
  `message`    TEXT,
  `is_read`    TINYINT(1) DEFAULT 0,
  `link`       VARCHAR(500),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── OTP Sessions ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `otp_sessions` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `mobile`     VARCHAR(20) NOT NULL,
  `otp_code`   VARCHAR(6) NOT NULL,
  `purpose`    ENUM('login','register','password_reset') DEFAULT 'login',
  `is_used`    TINYINT(1) DEFAULT 0,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Subscriptions ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `seller_id`   INT NOT NULL,
  `plan`        ENUM('standard','premium','enterprise') NOT NULL,
  `price`       DECIMAL(10,2) NOT NULL,
  `start_date`  DATE NOT NULL,
  `end_date`    DATE NOT NULL,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `seller_companies`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA (Demo)
-- ============================================================
-- Demo password for all accounts: 123456
INSERT IGNORE INTO `users` (`id`, `full_name`, `mobile`, `email`, `password_hash`, `role`, `nafath_verified`, `sanad_limit`, `is_active`, `wallet_balance`) VALUES
(1, 'أحمد المدير',         '0500000001', 'admin@fleetx.sa',    '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'admin',     1, 0,     1, 0),
(2, 'الوطنية للتأجير',     '0500000002', 'watania@fleetx.sa',  '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'seller',    1, 0,     1, 0),
(3, 'بدجت السعودية',       '0500000003', 'budget@fleetx.sa',   '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'seller',    1, 0,     1, 0),
(4, 'محمد المشتري',        '0501111111', 'buyer1@example.com', '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'buyer',     1, 500000, 1, 50000),
(5, 'خالد الزهراني',       '0502222222', 'buyer2@example.com', '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'buyer',     1, 300000, 1, 25000),
(6, 'المفتش الأول',        '0503333333', 'insp@fleetx.sa',     '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'inspector', 1, 0,     1, 0),
(7, 'يلو لتأجير السيارات', '0500000007', 'yellow@fleetx.sa',   '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'seller',    1, 0,     1, 0),
(8, 'ذيب لتأجير السيارات', '0500000008', 'theeb@fleetx.sa',    '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'seller',    1, 0,     1, 0),
(9, 'هرتز السعودية',       '0500000009', 'hertz@fleetx.sa',    '$2y$12$M8aTNM8SRf86ylxSkeiwmOfEnfD70ppN9yYV/Rr1AlUHnIVMUOAF6', 'seller',    1, 0,     1, 0);

INSERT IGNORE INTO `seller_companies` (`id`, `user_id`, `company_name`, `cr_number`, `fleet_size`, `subscription`, `is_verified`, `rating`, `total_auctions`) VALUES
(1, 2, 'الوطنية للتأجير', '1010123456', 350, 'enterprise', 1, 4.90, 234),
(2, 3, 'بدجت السعودية',   '1010654321', 180, 'premium',    1, 4.75, 128),
(3, 7, 'يلو لتأجير السيارات', '1010999888', 500, 'enterprise', 1, 4.80, 412),
(4, 8, 'ذيب لتأجير السيارات', '1010888777', 420, 'enterprise', 1, 4.85, 318),
(5, 9, 'هرتز السعودية',     '1010777666', 220, 'premium',    1, 4.60, 195);

INSERT IGNORE INTO `vehicles` (`id`, `seller_id`, `make`, `model`, `year`, `mileage`, `color`, `fuel_type`, `transmission`, `city`, `condition_grade`, `image_url`, `autodata_price_min`, `autodata_price_max`, `status`) VALUES
(1, 1, 'Toyota',   'Camry',    2023, 45200, 'أبيض لؤلؤي',   'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة جداً', 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80', 85000, 95000, 'approved'),
(2, 1, 'Hyundai',  'Tucson',   2023, 28100, 'رمادي معدني',   'بنزين', 'أوتوماتيك', 'جدة',    'ممتازة',   'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80', 90000, 100000, 'approved'),
(3, 2, 'Kia',      'Sportage', 2022, 38400, 'أبيض',          'بنزين', 'أوتوماتيك', 'الدمام', 'جيدة جداً', 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80', 74000, 82000, 'approved'),
(4, 1, 'Nissan',   'Patrol',   2021, 62800, 'أسود',          'بنزين', 'أوتوماتيك', 'الدمام', 'جيدة',      'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80', 140000, 160000, 'approved'),
(5, 1, 'Toyota',   'RAV4',     2023, 22600, 'أبيض',          'هجين',  'أوتوماتيك', 'الرياض', 'ممتازة',   'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?w=800&q=80', 108000, 118000, 'approved'),
(6, 2, 'Mercedes', 'E200',     2021, 44700, 'أسود',          'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة جداً', 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80', 190000, 210000, 'approved'),
(7, 1, 'Ford',     'Explorer', 2022, 71200, 'أزرق',          'بنزين', 'أوتوماتيك', 'جدة',    'جيدة',      'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80', 125000, 140000, 'approved'),
(8, 2, 'Honda',    'Accord',   2022, 51300, 'فضي',           'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة جداً', 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80', 65000, 72000, 'approved'),
(9, 1, 'BMW',      'X5',       2022, 38000, 'أسود',          'بنزين', 'أوتوماتيك', 'الرياض', 'ممتازة',   'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80', 235000, 255000, 'approved'),
(10, 3, 'Hyundai', 'Elantra',  2022, 58200, 'أبيض',          'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة',      'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80', 52000, 60000, 'approved'),
(11, 3, 'Toyota',  'Yaris',    2023, 34100, 'فضي',           'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة جداً', 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80', 44000, 50000, 'approved'),
(12, 4, 'Toyota',  'Camry',    2022, 61000, 'رمادي',         'بنزين', 'أوتوماتيك', 'جدة',    'جيدة',      'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80', 70000, 78000, 'approved'),
(13, 4, 'Hyundai', 'SantaFe',  2021, 78200, 'رمادي معدني',   'بنزين', 'أوتوماتيك', 'جدة',    'جيدة',      'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80', 78000, 86000, 'approved'),
(14, 5, 'Toyota',  'Corolla',  2022, 52100, 'أزرق',          'بنزين', 'أوتوماتيك', 'الدمام', 'جيدة جداً', 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80', 54000, 62000, 'approved'),
(15, 5, 'Kia',      'Cerato',   2023, 29400, 'أحمر',          'بنزين', 'أوتوماتيك', 'الدمام', 'ممتازة',   'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80', 58000, 66000, 'approved'),
(16, 3, 'Hyundai', 'Accent',   2022, 48200, 'أبيض',          'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة',      'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80', 38000, 44000, 'approved'),
(17, 5, 'Nissan',   'Altima',   2021, 69000, 'فضي',           'بنزين', 'أوتوماتيك', 'مكة المكرمة','جيدة',    'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80', 60000, 68000, 'approved'),
(18, 5, 'BMW',      '520i',     2022, 41200, 'أسود',          'بنزين', 'أوتوماتيك', 'الرياض', 'ممتازة',   'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80', 160000, 175000, 'approved'),
(19, 3, 'Toyota',  'Highlander',2022, 49600, 'كحلي',          'هجين',  'أوتوماتيك', 'المدينة المنورة','ممتازة','https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?w=800&q=80', 110000, 125000, 'approved'),
(20, 1, 'Mercedes', 'C200',     2022, 32000, 'رمادي معدني',   'بنزين', 'أوتوماتيك', 'الرياض', 'ممتازة',   'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80', 175000, 195000, 'approved'),
(21, 2, 'Kia',      'Optima',   2020, 82400, 'أبيض',          'بنزين', 'أوتوماتيك', 'الدمام', 'جيدة',      'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80', 45000, 52000, 'approved'),
(22, 1, 'Toyota',  'LandCruiser',2020, 115000,'أبيض',          'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة جداً', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80', 185000, 205000, 'approved'),
(23, 1, 'Jeep',     'GrandCherokee',2021,56200,'رمادي',        'بنزين', 'أوتوماتيك', 'الرياض', 'جيدة جداً', 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80', 100000, 120000, 'approved'),
(24, 2, 'Kia',      'Seltos',   2022, 38100, 'برتقالي',       'بنزين', 'أوتوماتيك', 'الدمام', 'جيدة جداً', 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80', 58000, 65000, 'approved'),
(25, 3, 'Hyundai',  'Tucson',   2024, 14200, 'أسود',          'بنزين', 'أوتوماتيك', 'جدة',    'ممتازة',   'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80', 85000, 95000, 'approved'),
(26, 3, 'Toyota',  'Camry',    2024, 18900, 'فضي',           'هجين',  'أوتوماتيك', 'الرياض', 'ممتازة',   'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80', 92000, 102000, 'approved'),
(27, 2, 'Toyota',  'Fortuner', 2022, 67000, 'أبيض',          'بنزين', 'أوتوماتيك', 'الدمام', 'جيدة جداً', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80', 94000, 102000, 'approved'),
(28, 1, 'Dodge',    'Charger',  2021, 49200, 'رمادي',         'بنزين', 'أوتوماتيك', 'جدة',    'جيدة جداً', 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80', 90000, 98000, 'approved'),
(29, 1, 'Toyota',  'Hilux',    2021, 92100, 'أبيض',          'ديزل',  'يدوي',      'الدمام', 'جيدة',      'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80', 72000, 82000, 'approved'),
(30, 2, 'Honda',    'Civic',    2022, 38400, 'رمادي معدني',   'بنزين', 'أوتوماتيك', 'جدة',    'جيدة جداً', 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80', 70000, 80000, 'approved');

INSERT IGNORE INTO `inspections` (`id`, `vehicle_id`, `inspector_id`, `exterior_score`, `interior_score`, `mechanical_score`, `electronics_score`, `inspection_date`, `status`, `notes`) VALUES
(1, 1, 6, 88, 90, 85, 92, '2024-11-15', 'completed', 'السيارة بحالة ممتازة. صيانة دورية منتظمة. لا توجد حوادث مسجلة. الإطارات بحالة جيدة.'),
(2, 2, 6, 92, 94, 90, 96, '2024-11-18', 'completed', 'حالة ممتازة جداً. أقل من 30,000 كم. مطابقة لمواصفات المصنع تماماً.'),
(3, 3, 6, 82, 85, 80, 88, '2024-11-20', 'completed', 'حالة جيدة جداً. تغيير الزيت والفلاتر حديث. بعض الخدوش الخفيفة في الهيكل.'),
(4, 4, 6, 78, 80, 82, 85, '2024-11-22', 'completed', 'حالة جيدة مع الأخذ بالاعتبار عدد الكيلومترات. المحرك سليم.'),
(5, 5, 6, 95, 97, 93, 98, '2024-11-25', 'completed', 'سيارة شبه جديدة. هجين بكفاءة عالية. ضمان مصنع لا يزال سارياً.'),
(6, 6, 6, 90, 92, 88, 94, '2024-11-28', 'completed', 'مرسيدس بحالة رائعة. خدمة كاملة في وكالة. جميع الأنظمة تعمل بشكل مثالي.'),
(7, 7, 6, 80, 78, 82, 86, '2024-12-01', 'completed', 'فورد إكسبلورر قوي. مناسب للعائلات والرحلات. يحتاج تلميع خارجي.'),
(8, 8, 6, 85, 87, 83, 90, '2024-12-03', 'completed', 'هوندا أكورد موثوق. محرك توربو 1.5 بكفاءة جيدة. صيانة منتظمة.'),
(9, 9, 6, 93, 95, 91, 97, '2024-12-05', 'completed', 'BMW X5 بمواصفات كاملة. نظام رباعي الدفع. إطارات جديدة.');

INSERT IGNORE INTO `auction_events` (`id`, `seller_id`, `title`, `description`, `brochure_pdf`, `status`, `start_time`, `end_time`) VALUES
(1, 1, 'مزاد الوطنية للسيارات الفاخرة والأساطيل', 'المزاد الكبرى لشركة الوطنية لتصفية أسطول سيارات تويوتا وهيونداي وتوسان الفاخرة للربع الأول من عام 2026.', '/assets/docs/watania_brochure.pdf', 'active', NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 5 HOUR),
(2, 2, 'مزاد تصفية أسطول بدجت السنوي', 'المزاد السنوي الخاص بشركة بدجت لتصفية أسطول سيارات كيا ومرسيدس وشيفروليه وهيونداي المستعملة مع كفالة الفحص المعتمد.', '/assets/docs/budget_brochure.pdf', 'active', NOW() - INTERVAL 2 HOUR, NOW() + INTERVAL 24 HOUR),
(3, 3, 'مزاد الوفاق لتأجير السيارات - المنطقة الوسطى', 'مزاد تصفية أسطول شركة يلو (الوفاق) لسيارات السيدان والاقتصادية الموديلات من 2021 إلى 2024.', '/assets/docs/watania_brochure.pdf', 'active', NOW() - INTERVAL 12 HOUR, NOW() + INTERVAL 48 HOUR),
(4, 4, 'مزاد أسطول ذيب لسيارات السيدان والدفع الرباعي', 'مزاد شركة ذيب لتصفية مجموعة من السيارات العائلية وسيارات الدفع الرباعي الممتازة المفحوصة.', '/assets/docs/budget_brochure.pdf', 'active', NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 HOUR),
(5, 5, 'مزاد هرتز السعودية للسيارات الاقتصادية', 'مزاد تصفية هرتز للسيارات الصغيرة والمتوسطة ذات الاستهلاك الاقتصادي للوقود.', '/assets/docs/watania_brochure.pdf', 'active', NOW() - INTERVAL 48 HOUR, NOW() + INTERVAL 96 HOUR),
(6, 3, 'مزاد المفتاح لتصفية السيارات المستعملة', 'المزاد العام لشركة المفتاح لتصفية أسطولها من سيارات هيونداي وتويوتا في الرياض وجدة.', '/assets/docs/budget_brochure.pdf', 'active', NOW() - INTERVAL 6 HOUR, NOW() + INTERVAL 12 HOUR),
(7, 5, 'مزاد الأفضل لتأجير السيارات - المنطقة الغربية', 'مزاد شركة الأفضل لتصفية أسطول سيارات شيفروليه ونيسان وجي إم سي بالمنطقة الغربية.', '/assets/docs/watania_brochure.pdf', 'active', NOW(), NOW() + INTERVAL 144 HOUR),
(8, 5, 'مزاد أسطول سيكست للسيارات العائلية والفاخرة', 'تصفية أسطول سيكست للسيارات الألمانية والسيارات ذات الحجم العائلي الفسيح.', '/assets/docs/budget_brochure.pdf', 'active', NOW() - INTERVAL 4 HOUR, NOW() + INTERVAL 72 HOUR),
(9, 3, 'مزاد تصفية سيارات شركة هانكو', 'مزاد عام على سيارات هانكو المستعملة المفحوصة والمعتمدة تحت إشراف الفحص الفني الخاص بالمنصة.', '/assets/docs/watania_brochure.pdf', 'active', NOW() - INTERVAL 3 HOUR, NOW() + INTERVAL 8 HOUR),
(10, 1, 'مزاد الفرسان للسيارات المستعملة والفاخرة', 'تصفية أسطول الفرسان من السيارات الفاخرة والمميزة ذات المواصفات العالية.', '/assets/docs/budget_brochure.pdf', 'active', NOW() - INTERVAL 48 HOUR, NOW() + INTERVAL 120 HOUR),
(11, 2, 'مزاد شركة أوتو ستار للسيارات المستردة', 'مزاد مميز للسيارات المستعملة بحالة ممتازة التابعة لشركة أوتو ستار بالمنطقة الشرقية والوسطى.', '/assets/docs/watania_brochure.pdf', 'active', NOW() - INTERVAL 24 HOUR, NOW() + INTERVAL 216 HOUR),
(12, 4, 'مزاد أساطيل الجهات الحكومية والمؤسسات الموحد', 'المزاد الحكومي الموحد لتصفية أساطيل بعض الوزارات والمؤسسات الرسمية بتفاصيل فحص دقيقة.', '/assets/docs/budget_brochure.pdf', 'active', NOW(), NOW() + INTERVAL 240 HOUR),
(13, 1, 'مزاد الشركة المتحدة للسيارات المستعملة', 'مزاد خاص لتصفية سيارات جيب ودودج وكرايسلر التابعة للشركة المتحدة للسيارات.', '/assets/docs/watania_brochure.pdf', 'active', NOW() - INTERVAL 2 HOUR, NOW() + INTERVAL 24 HOUR),
(14, 2, 'مزاد أسطول شركة رينت إيه كار - الشرقية', 'تصفية أسطول شركة رينت إيه كار بالدمام والخبر لسيارات كيا وهيونداي وتويوتا.', '/assets/docs/budget_brochure.pdf', 'active', NOW() - INTERVAL 72 HOUR, NOW() + INTERVAL 168 HOUR),
(15, 3, 'مزاد تصفية سيارات شركة يلو الحديثة 2024', 'مزاد تصفية أسطول شركة يلو لسيارات موديل 2024 لغرض التجديد السنوي للأسطول.', '/assets/docs/watania_brochure.pdf', 'active', NOW() - INTERVAL 2 HOUR, NOW() + INTERVAL 4 HOUR);

INSERT IGNORE INTO `auctions` (`id`, `event_id`, `vehicle_id`, `seller_id`, `title`, `type`, `status`, `starting_price`, `current_price`, `bid_increment`, `start_time`, `end_time`, `is_featured`) VALUES
(1, 1, 1, 1, 'تويوتا كامري 2.5L Prestige 2023',   'live',     'active', 75000,  85000,  500,  NOW() - INTERVAL 2 HOUR, NOW() + INTERVAL 2 HOUR, 1),
(2, 1, 2, 1, 'هيونداي توسان 2.0 AWD 2023',        'live',     'active', 85000,  94000,  500,  NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 5 HOUR, 1),
(3, 2, 3, 2, 'كيا سبورتاج 1.6T AWD 2022',         'live',     'active', 70000,  78500,  500,  NOW() - INTERVAL 3 HOUR, NOW() + INTERVAL 24 HOUR, 0),
(4, 1, 4, 1, 'نيسان باترول 5.6 V8 2021',          'live',     'active', 140000, 153000, 1000, NOW() - INTERVAL 3 HOUR, NOW() + INTERVAL 1 HOUR, 1),
(5, 1, 5, 1, 'تويوتا راف 4 هجين 2023',            'live',     'active', 100000, 112000, 1000, NOW() - INTERVAL 30 MINUTE, NOW() + INTERVAL 30 MINUTE, 1),
(6, 2, 6, 2, 'مرسيدس E200 أوتوماتيك 2021',        'sealed',   'active', 180000, 198000, 2000, NOW(),                   NOW() + INTERVAL 24 HOUR, 0),
(7, 1, 7, 1, 'فورد إكسبلورر XLT 2022',            'live',     'active', 120000, 132000, 1000, NOW() - INTERVAL 4 HOUR, NOW() + INTERVAL 20 HOUR, 0),
(8, 2, 8, 2, 'هوندا أكورد 1.5T Sport 2022',       'instant',  'active', 68000,  68000,  0,    NOW(),                   NULL,                    0),
(9, 1, 9, 1, 'BMW X5 xDrive 30i 2022',             'live',     'active', 230000, 245000, 2000, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 6 DAY,  1),
(10, 3, 10, 3, 'هيونداي إلنترا 1.6L 2022',         'live',     'active', 50000,  55000,  500,  NOW() - INTERVAL 12 HOUR, NOW() + INTERVAL 48 HOUR, 0),
(11, 3, 11, 3, 'تويوتا يارس 1.5L 2023',            'live',     'active', 45000,  48000,  500,  NOW() - INTERVAL 10 HOUR, NOW() + INTERVAL 48 HOUR, 0),
(12, 4, 12, 4, 'تويوتا كامري LE 2022',             'live',     'active', 68000,  73000,  500,  NOW() - INTERVAL 1 HOUR,  NOW() + INTERVAL 3 HOUR,  0),
(13, 4, 13, 4, 'هيونداي سنتافي 2.5L 2021',         'live',     'active', 78000,  82000,  1000, NOW() - INTERVAL 1 HOUR,  NOW() + INTERVAL 3 HOUR,  0),
(14, 5, 14, 5, 'تويوتا كورولا 1.6L 2022',          'live',     'active', 54000,  58000,  500,  NOW() - INTERVAL 24 HOUR, NOW() + INTERVAL 96 HOUR, 0),
(15, 5, 15, 5, 'كيا سيراتو 1.6L 2023',             'live',     'active', 58000,  61000,  500,  NOW() - INTERVAL 20 HOUR, NOW() + INTERVAL 96 HOUR, 0),
(16, 3, 16, 3, 'هيونداي أكسنت 1.4L 2022',          'live',     'active', 38000,  42000,  500,  NOW() - INTERVAL 6 HOUR,  NOW() + INTERVAL 12 HOUR, 0),
(17, 5, 17, 5, 'نيسان ألتيما 2.5L 2021',           'live',     'active', 60000,  64000,  1000, NOW(),                   NOW() + INTERVAL 144 HOUR, 0),
(18, 5, 18, 5, 'BMW 520i 2.0T 2022',               'live',     'active', 160000, 168000, 2000, NOW() - INTERVAL 4 HOUR,  NOW() + INTERVAL 72 HOUR, 0),
(19, 3, 19, 3, 'تويوتا هايلاندر هجين 2022',         'live',     'active', 110000, 118000, 1000, NOW() - INTERVAL 3 HOUR,  NOW() + INTERVAL 8 HOUR,  0),
(20, 1, 20, 1, 'مرسيدس C200 2022',                 'live',     'active', 175000, 185000, 2000, NOW() - INTERVAL 48 HOUR, NOW() + INTERVAL 120 HOUR, 0),
(21, 2, 21, 2, 'كيا أوبتيما 2.4L 2020',            'live',     'active', 45000,  49000,  500,  NOW() - INTERVAL 72 HOUR, NOW() + INTERVAL 168 HOUR, 0),
(22, 1, 22, 1, 'تويوتا لاندكروزر V8 2020',         'live',     'active', 185000, 195000, 2000, NOW(),                   NOW() + INTERVAL 240 HOUR, 0),
(23, 1, 23, 1, 'جيب جراند شيروكي 2021',            'live',     'active', 100000, 110000, 1000, NOW() - INTERVAL 2 HOUR,  NOW() + INTERVAL 24 HOUR, 0),
(24, 2, 24, 2, 'كيا سيلتوس 1.6L 2022',             'live',     'active', 58000,  62000,  500,  NOW() - INTERVAL 72 HOUR, NOW() + INTERVAL 168 HOUR, 0),
(25, 3, 25, 3, 'هيونداي توسان Smart 2024',         'live',     'active', 85000,  89000,  1000, NOW() - INTERVAL 2 HOUR,  NOW() + INTERVAL 4 HOUR,  0),
(26, 3, 26, 3, 'تويوتا كامري Hybrid 2024',         'live',     'active', 92000,  96000,  1000, NOW() - INTERVAL 2 HOUR,  NOW() + INTERVAL 4 HOUR,  0),
(27, 2, 27, 2, 'تويوتا فورتشنر 2.7L 2022',         'live',     'active', 94000,  98000,  1000, NOW() - INTERVAL 72 HOUR, NOW() + INTERVAL 168 HOUR, 0),
(28, 1, 28, 1, 'دودج تشارجر GT 2021',              'live',     'active', 90000,  94000,  1000, NOW() - INTERVAL 2 HOUR,  NOW() + INTERVAL 24 HOUR, 0),
(29, 1, 29, 1, 'تويوتا هيلوكس غمارتين 2021',        'live',     'active', 72000,  78000,  1000, NOW(),                   NOW() + INTERVAL 240 HOUR, 0),
(30, 2, 30, 2, 'هوندا سيفيك 1.5T 2022',             'live',     'active', 70000,  76000,  1000, NOW() - INTERVAL 24 HOUR, NOW() + INTERVAL 216 HOUR, 0);

INSERT IGNORE INTO `bids` (`auction_id`, `user_id`, `amount`, `created_at`) VALUES
(1, 4, 85000, NOW() - INTERVAL 10 MINUTE),
(1, 5, 84000, NOW() - INTERVAL 20 MINUTE),
(1, 4, 83000, NOW() - INTERVAL 30 MINUTE),
(2, 5, 94000, NOW() - INTERVAL 5 MINUTE),
(2, 4, 93000, NOW() - INTERVAL 15 MINUTE),
(4, 5, 153000, NOW() - INTERVAL 15 MINUTE),
(5, 4, 112000, NOW() - INTERVAL 5 MINUTE);

SET FOREIGN_KEY_CHECKS = 1;
