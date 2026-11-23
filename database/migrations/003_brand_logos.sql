-- ============================================================
-- Migration: Brand Logos (dynamic marquee strip on the homepage)
-- Safe to run on the LIVE database — does not touch or drop any
-- existing table.
--
-- Replaces the hardcoded $partnerBrands array in index.php with a
-- database-driven list managed from Admin > File Library > Brand Logos.
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `brand_logos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(150) NOT NULL COMMENT 'Brand name shown as alt text / fallback label',
  `logo_path` varchar(255) NOT NULL COMMENT 'Relative path under UPLOADS_PATH',
  `display_order` int unsigned NOT NULL DEFAULT 0 COMMENT 'Ascending sort order in the marquee',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Show/hide in the marquee without deleting',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Brand/partner logos shown in the homepage marquee strip';
