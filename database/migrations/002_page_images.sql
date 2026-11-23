-- ============================================================
-- Migration: Page Images (banner/hero images per page section)
-- Safe to run on the LIVE database — does not touch or drop any
-- existing table.
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `page_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `section_key` varchar(100) NOT NULL COMMENT 'Unique code used in templates via get_page_image()',
  `section_label` varchar(255) NOT NULL COMMENT 'Arabic label shown in the admin panel',
  `desktop_image_path` varchar(255) DEFAULT NULL COMMENT 'Relative path under UPLOADS_PATH',
  `mobile_image_path` varchar(255) DEFAULT NULL COMMENT 'Relative path under UPLOADS_PATH, NULL = reuse desktop image',
  `desktop_width` int unsigned NOT NULL COMMENT 'Recommended width in px for the desktop image',
  `desktop_height` int unsigned NOT NULL COMMENT 'Recommended height in px for the desktop image',
  `mobile_width` int unsigned NOT NULL COMMENT 'Recommended width in px for the mobile image',
  `mobile_height` int unsigned NOT NULL COMMENT 'Recommended height in px for the mobile image',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-section page banner/hero images with device-specific fallback';

-- Seed rows for every real image-bearing section identified in the current
-- codebase. Dimensions below were confirmed with the site owner on 2026-07-11:
--   - home_hero: the only section with a real <img> today (index.php hero,
--     currently CSS aspect-ratio 4/3 with no fixed px and no mobile variant).
--   - the other 5 banners do not exist as images yet (page-header sections
--     are currently text-only over a CSS gradient); a uniform banner size
--     was agreed upon since none is implied by existing code.
INSERT IGNORE INTO `page_images`
  (`section_key`, `section_label`, `desktop_width`, `desktop_height`, `mobile_width`, `mobile_height`)
VALUES
  ('home_hero',        'صورة الهيرو - الصفحة الرئيسية', 1200, 900, 800, 600),
  ('about_banner',     'بانر صفحة من نحن',              1920, 400, 800, 300),
  ('services_banner',  'بانر صفحة الخدمات',              1920, 400, 800, 300),
  ('products_banner',  'بانر صفحة المنتجات',             1920, 400, 800, 300),
  ('portfolio_banner', 'بانر صفحة معرض الأعمال',         1920, 400, 800, 300),
  ('contact_banner',   'بانر صفحة اتصل بنا',             1920, 400, 800, 300);
