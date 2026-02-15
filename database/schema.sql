-- =====================================================
-- Digital Marketing Display Database Schema
-- Database: digitalmarketing_display
-- Engine: InnoDB
-- Charset: utf8mb4
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `digitalmarketing_display` 
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digitalmarketing_display`;

-- =====================================================
-- USERS & ROLES
-- =====================================================

CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL DEFAULT NULL,
    `avatar_url` VARCHAR(500) NULL DEFAULT NULL,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `remember_token` VARCHAR(100) NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_users_email` (`email`),
    UNIQUE KEY `idx_users_slug` (`slug`),
    KEY `idx_users_deleted_at` (`deleted_at`),
    KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_roles_name` (`name`),
    UNIQUE KEY `idx_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    KEY `idx_user_roles_role_id` (`role_id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) 
        REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CATEGORIES (Nested Hierarchy)
-- =====================================================

CREATE TABLE `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_categories_slug` (`slug`),
    KEY `idx_categories_parent_id` (`parent_id`),
    KEY `idx_categories_deleted_at` (`deleted_at`),
    KEY `idx_categories_is_active` (`is_active`),
    KEY `idx_categories_sort_order` (`sort_order`),
    CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) 
        REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LOCATIONS (Hierarchical)
-- =====================================================

CREATE TABLE `locations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `type` ENUM('country', 'state', 'district', 'taluka', 'village', 'area', 'locality') NOT NULL,
    `code` VARCHAR(50) NULL DEFAULT NULL,
    `latitude` DECIMAL(10, 8) NULL DEFAULT NULL,
    `longitude` DECIMAL(11, 8) NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_locations_slug` (`slug`),
    KEY `idx_locations_parent_id` (`parent_id`),
    KEY `idx_locations_type` (`type`),
    KEY `idx_locations_type_active` (`type`, `is_active`, `deleted_at`),
    KEY `idx_locations_code` (`code`),
    KEY `idx_locations_name` (`name`(100)),
    KEY `idx_locations_deleted_at` (`deleted_at`),
    KEY `idx_locations_coordinates` (`latitude`, `longitude`),
    CONSTRAINT `fk_locations_parent` FOREIGN KEY (`parent_id`) 
        REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LISTINGS (Core Business Directory)
-- =====================================================

CREATE TABLE `listings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` INT UNSIGNED NULL DEFAULT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `location_id` INT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `short_description` VARCHAR(500) NULL DEFAULT NULL,
    `address` TEXT NULL DEFAULT NULL,
    `phone` VARCHAR(50) NULL DEFAULT NULL,
    `whatsapp` VARCHAR(50) NULL DEFAULT NULL,
    `email` VARCHAR(255) NULL DEFAULT NULL,
    `website` VARCHAR(500) NULL DEFAULT NULL,
    `status` ENUM('draft', 'pending_approval', 'approved', 'rejected', 'expired', 'suspended') NOT NULL DEFAULT 'draft',
    `featured` TINYINT(1) NOT NULL DEFAULT 0,
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_listings_slug` (`slug`),
    KEY `idx_listings_user_id` (`user_id`),
    KEY `idx_listings_plan_id` (`plan_id`),
    KEY `idx_listings_category_id` (`category_id`),
    KEY `idx_listings_location_id` (`location_id`),
    KEY `idx_listings_status` (`status`),
    KEY `idx_listings_featured` (`featured`),
    KEY `idx_listings_deleted_at` (`deleted_at`),
    KEY `idx_listings_view_count` (`view_count`),
    KEY `idx_listings_created_at` (`created_at`),
    KEY `idx_listings_search` (`status`, `featured`, `deleted_at`),
    CONSTRAINT `fk_listings_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_listings_plan` FOREIGN KEY (`plan_id`) 
        REFERENCES `plans` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_listings_category` FOREIGN KEY (`category_id`) 
        REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_listings_location` FOREIGN KEY (`location_id`) 
        REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PRODUCTS
-- =====================================================

CREATE TABLE `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `listing_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `price` DECIMAL(10, 2) NULL DEFAULT NULL,
    `sku` VARCHAR(100) NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_products_listing_slug` (`listing_id`, `slug`),
    KEY `idx_products_listing_id` (`listing_id`),
    KEY `idx_products_is_active` (`is_active`),
    KEY `idx_products_deleted_at` (`deleted_at`),
    KEY `idx_products_sku` (`sku`),
    CONSTRAINT `fk_products_listing` FOREIGN KEY (`listing_id`) 
        REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MEDIA (Polymorphic)
-- =====================================================

CREATE TABLE `media` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mediable_type` VARCHAR(100) NOT NULL,
    `mediable_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('image', 'video', 'document') NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NULL DEFAULT NULL,
    `size` BIGINT UNSIGNED NULL DEFAULT NULL,
    `alt_text` VARCHAR(255) NULL DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_media_mediable` (`mediable_type`, `mediable_id`),
    KEY `idx_media_type` (`type`),
    KEY `idx_media_deleted_at` (`deleted_at`),
    KEY `idx_media_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUBSCRIPTIONS & PAYMENTS
-- =====================================================

CREATE TABLE `plans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `billing_interval` ENUM('monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    `listing_limit` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = unlimited',
    `feature_list` JSON NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_plans_slug` (`slug`),
    KEY `idx_plans_is_active` (`is_active`),
    KEY `idx_plans_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` INT UNSIGNED NOT NULL,
    `status` ENUM('active', 'cancelled', 'expired', 'past_due') NOT NULL DEFAULT 'active',
    `starts_at` TIMESTAMP NOT NULL,
    `ends_at` TIMESTAMP NULL DEFAULT NULL,
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
    `trial_ends_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_subscriptions_user_id` (`user_id`),
    KEY `idx_subscriptions_plan_id` (`plan_id`),
    KEY `idx_subscriptions_status` (`status`),
    KEY `idx_subscriptions_ends_at` (`ends_at`),
    KEY `idx_subscriptions_deleted_at` (`deleted_at`),
    KEY `idx_subscriptions_active` (`user_id`, `status`, `ends_at`),
    CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_subscriptions_plan` FOREIGN KEY (`plan_id`) 
        REFERENCES `plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subscription_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `gateway` VARCHAR(50) NOT NULL,
    `gateway_reference` VARCHAR(255) NULL DEFAULT NULL,
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    `metadata` JSON NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payments_subscription_id` (`subscription_id`),
    KEY `idx_payments_user_id` (`user_id`),
    KEY `idx_payments_status` (`status`),
    KEY `idx_payments_gateway` (`gateway`),
    KEY `idx_payments_created_at` (`created_at`),
    KEY `idx_payments_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_payments_subscription` FOREIGN KEY (`subscription_id`) 
        REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REVIEWS
-- =====================================================

CREATE TABLE `reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `listing_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `title` VARCHAR(255) NULL DEFAULT NULL,
    `body` TEXT NULL DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_reviews_user_listing` (`user_id`, `listing_id`),
    KEY `idx_reviews_listing_id` (`listing_id`),
    KEY `idx_reviews_user_id` (`user_id`),
    KEY `idx_reviews_rating` (`rating`),
    KEY `idx_reviews_status` (`status`),
    KEY `idx_reviews_deleted_at` (`deleted_at`),
    KEY `idx_reviews_created_at` (`created_at`),
    CONSTRAINT `fk_reviews_listing` FOREIGN KEY (`listing_id`) 
        REFERENCES `listings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PROMOTIONS
-- =====================================================

CREATE TABLE `promotions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `listing_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = site-wide promotion',
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('featured', 'spotlight', 'banner', 'discount') NOT NULL,
    `discount_value` DECIMAL(10, 2) NULL DEFAULT NULL,
    `discount_type` ENUM('percentage', 'fixed') NULL DEFAULT NULL,
    `starts_at` TIMESTAMP NOT NULL,
    `ends_at` TIMESTAMP NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_promotions_listing_id` (`listing_id`),
    KEY `idx_promotions_type` (`type`),
    KEY `idx_promotions_dates` (`starts_at`, `ends_at`),
    KEY `idx_promotions_is_active` (`is_active`),
    KEY `idx_promotions_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_promotions_listing` FOREIGN KEY (`listing_id`) 
        REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ANALYTICS
-- =====================================================

CREATE TABLE `analytics_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `session_id` VARCHAR(255) NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` TEXT NULL DEFAULT NULL,
    `referer` VARCHAR(500) NULL DEFAULT NULL,
    `metadata` JSON NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_analytics_entity` (`entity_type`, `entity_id`),
    KEY `idx_analytics_user_id` (`user_id`),
    KEY `idx_analytics_event_type` (`event_type`),
    KEY `idx_analytics_created_at` (`created_at`),
    KEY `idx_analytics_session` (`session_id`),
    CONSTRAINT `fk_analytics_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS
-- =====================================================

CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NULL DEFAULT NULL,
    `data` JSON NULL DEFAULT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_id` (`user_id`),
    KEY `idx_notifications_read_at` (`read_at`),
    KEY `idx_notifications_created_at` (`created_at`),
    KEY `idx_notifications_unread` (`user_id`, `read_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- APPROVAL LOGS
-- =====================================================

CREATE TABLE `approval_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `approvable_type` VARCHAR(100) NOT NULL,
    `approvable_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `action` ENUM('approved', 'rejected') NOT NULL,
    `notes` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_approval_logs_approvable` (`approvable_type`, `approvable_id`),
    KEY `idx_approval_logs_user_id` (`user_id`),
    KEY `idx_approval_logs_action` (`action`),
    KEY `idx_approval_logs_created_at` (`created_at`),
    CONSTRAINT `fk_approval_logs_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS: Prevent Circular Parent References
-- =====================================================

DELIMITER $$

-- Prevent circular parent in categories (deep check)
CREATE TRIGGER `trg_categories_before_insert` 
BEFORE INSERT ON `categories`
FOR EACH ROW
BEGIN
    IF NEW.parent_id IS NOT NULL THEN
        -- Check if parent_id equals id (self-reference)
        IF NEW.parent_id = NEW.id THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category cannot be its own parent';
        END IF;
        
        -- Check if parent_id is a descendant (would create cycle)
        SET @current_id = NEW.parent_id;
        SET @depth = 0;
        
        WHILE @current_id IS NOT NULL AND @depth < 100 DO
            IF @current_id = NEW.id THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Circular reference detected: parent chain would create a cycle';
            END IF;
            SELECT parent_id INTO @current_id FROM categories WHERE id = @current_id;
            SET @depth = @depth + 1;
        END WHILE;
    END IF;
END$$

CREATE TRIGGER `trg_categories_before_update` 
BEFORE UPDATE ON `categories`
FOR EACH ROW
BEGIN
    IF NEW.parent_id IS NOT NULL AND NEW.parent_id != OLD.parent_id THEN
        -- Check if parent_id equals id (self-reference)
        IF NEW.parent_id = NEW.id THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category cannot be its own parent';
        END IF;
        
        -- Check if parent_id is a descendant (would create cycle)
        SET @current_id = NEW.parent_id;
        SET @depth = 0;
        
        WHILE @current_id IS NOT NULL AND @depth < 100 DO
            IF @current_id = NEW.id THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Circular reference detected: parent chain would create a cycle';
            END IF;
            SELECT parent_id INTO @current_id FROM categories WHERE id = @current_id;
            SET @depth = @depth + 1;
        END WHILE;
    END IF;
END$$

-- Prevent circular parent in locations (deep check)
CREATE TRIGGER `trg_locations_before_insert` 
BEFORE INSERT ON `locations`
FOR EACH ROW
BEGIN
    IF NEW.parent_id IS NOT NULL THEN
        IF NEW.parent_id = NEW.id THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Location cannot be its own parent';
        END IF;
        
        SET @current_id = NEW.parent_id;
        SET @depth = 0;
        
        WHILE @current_id IS NOT NULL AND @depth < 100 DO
            IF @current_id = NEW.id THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Circular reference detected: parent chain would create a cycle';
            END IF;
            SELECT parent_id INTO @current_id FROM locations WHERE id = @current_id;
            SET @depth = @depth + 1;
        END WHILE;
    END IF;
END$$

CREATE TRIGGER `trg_locations_before_update` 
BEFORE UPDATE ON `locations`
FOR EACH ROW
BEGIN
    IF NEW.parent_id IS NOT NULL AND NEW.parent_id != OLD.parent_id THEN
        IF NEW.parent_id = NEW.id THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Location cannot be its own parent';
        END IF;
        
        SET @current_id = NEW.parent_id;
        SET @depth = 0;
        
        WHILE @current_id IS NOT NULL AND @depth < 100 DO
            IF @current_id = NEW.id THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Circular reference detected: parent chain would create a cycle';
            END IF;
            SELECT parent_id INTO @current_id FROM locations WHERE id = @current_id;
            SET @depth = @depth + 1;
        END WHILE;
    END IF;
END$$

DELIMITER ;
