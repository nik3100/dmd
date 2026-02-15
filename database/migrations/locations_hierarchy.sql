-- Locations table: add is_active, extend type ENUM for full hierarchy
-- Structure: Country → State → District → Taluka → Village → Area → Locality
-- Run after schema.sql. If locations table has existing 'city' data, update first:
--   UPDATE locations SET type = 'district' WHERE type = 'city';

USE `digitalmarketing_display`;

-- Add is_active for enable/disable (e.g. disable country initially)
ALTER TABLE `locations` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `type`;

-- Extend type ENUM to full hierarchy
ALTER TABLE `locations`
    MODIFY COLUMN `type` ENUM('country', 'state', 'district', 'taluka', 'village', 'area', 'locality') NOT NULL;

-- Index for search (type + active + deleted)
ALTER TABLE `locations` ADD KEY `idx_locations_type_active` (`type`, `is_active`, `deleted_at`);

-- Index for name search
ALTER TABLE `locations` ADD KEY `idx_locations_name` (`name`(100));
