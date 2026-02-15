-- Listings module: whatsapp, plan_id, status expired
USE `digitalmarketing_display`;

ALTER TABLE `listings`
    ADD COLUMN `whatsapp` VARCHAR(50) NULL DEFAULT NULL AFTER `phone`,
    ADD COLUMN `plan_id` INT UNSIGNED NULL DEFAULT NULL AFTER `user_id`;

ALTER TABLE `listings`
    MODIFY COLUMN `status` ENUM('draft', 'pending_approval', 'approved', 'rejected', 'expired', 'suspended') NOT NULL DEFAULT 'draft';

ALTER TABLE `listings`
    ADD KEY `idx_listings_plan_id` (`plan_id`),
    ADD CONSTRAINT `fk_listings_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL;
