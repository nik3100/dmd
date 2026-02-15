-- Category Suggestions Table
-- User-submitted categories awaiting admin approval

USE `digitalmarketing_display`;

CREATE TABLE IF NOT EXISTS `category_suggestions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `parent_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Suggested parent category',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Admin user who approved/rejected',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category_suggestions_user_id` (`user_id`),
    KEY `idx_category_suggestions_parent_id` (`parent_id`),
    KEY `idx_category_suggestions_status` (`status`),
    KEY `idx_category_suggestions_created_at` (`created_at`),
    CONSTRAINT `fk_category_suggestions_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_category_suggestions_parent` FOREIGN KEY (`parent_id`) 
        REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_category_suggestions_approved_by` FOREIGN KEY (`approved_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
