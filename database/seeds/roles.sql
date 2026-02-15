-- Seed default roles for authentication system
-- Run this after importing schema.sql

USE `digitalmarketing_display`;

INSERT INTO `roles` (`name`, `slug`, `description`) VALUES
('Admin', 'admin', 'Full system access'),
('Business Owner', 'business_owner', 'Can create and manage business listings'),
('User', 'user', 'Standard user account')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);
