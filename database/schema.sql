-- ============================================================
-- Alam Al-Sinaa Database Schema
-- Enterprise-grade MySQL database for Electrical & Industrial Supplies
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
DROP TABLE IF EXISTS `brand_logos`;
DROP TABLE IF EXISTS `page_images`;
DROP TABLE IF EXISTS `activity_log`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `admin_sessions`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `seo_settings`;
DROP TABLE IF EXISTS `redirects`;
DROP TABLE IF EXISTS `media_folders`;
DROP TABLE IF EXISTS `media_files`;
DROP TABLE IF EXISTS `portfolio_images`;
DROP TABLE IF EXISTS `portfolio`;
DROP TABLE IF EXISTS `portfolio_categories`;
DROP TABLE IF EXISTS `product_relations`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `product_specifications`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `cms_pages`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `site_settings`;
DROP TABLE IF EXISTS `translations`;

-- ============================================================
-- 1. SITE SETTINGS & CONFIGURATION
-- ============================================================

CREATE TABLE `site_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) DEFAULT 'general',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`),
  KEY `idx_setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Site configuration settings';

-- ============================================================
-- 2. TRANSLATIONS
-- ============================================================

CREATE TABLE `translations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `lang_key` varchar(255) NOT NULL,
  `lang` varchar(10) NOT NULL DEFAULT 'ar',
  `value` text NOT NULL,
  `context` varchar(100) DEFAULT 'general',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_key_lang` (`lang_key`, `lang`),
  KEY `idx_context` (`context`),
  KEY `idx_lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Multi-language translations';

-- ============================================================
-- 3. ADMIN USERS
-- ============================================================

CREATE TABLE `admin_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','editor','moderator') DEFAULT 'editor',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `login_count` int unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin user accounts';

-- ============================================================
-- 4. ADMIN SESSIONS
-- ============================================================

CREATE TABLE `admin_sessions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin session management';

-- ============================================================
-- 5. LOGIN ATTEMPTS (Brute Force Protection)
-- ============================================================

CREATE TABLE `login_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `attempt_count` int unsigned DEFAULT 1,
  `last_attempt` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_locked` tinyint(1) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ip_username` (`ip_address`, `username`),
  KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Login attempt tracking for brute force protection';

-- ============================================================
-- 6. PASSWORD RESETS
-- ============================================================

CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reset_token` (`reset_token`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password reset tokens';

-- ============================================================
-- 7. ACTIVITY LOG
-- ============================================================

CREATE TABLE `activity_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int unsigned DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin activity audit log';

-- ============================================================
-- 8. CATEGORIES (Hierarchical)
-- ============================================================

CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name_ar` varchar(200) NOT NULL,
  `name_en` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description_ar` text,
  `description_en` text,
  `icon` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int unsigned DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `meta_title_ar` varchar(200) DEFAULT NULL,
  `meta_title_en` varchar(200) DEFAULT NULL,
  `meta_description_ar` text DEFAULT NULL,
  `meta_description_en` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product categories with hierarchy';

-- ============================================================
-- 9. PRODUCTS
-- ============================================================

CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description_ar` text,
  `description_en` text,
  `short_description_ar` text,
  `short_description_en` text,
  `features_ar` text,
  `features_en` text,
  `applications_ar` text,
  `applications_en` text,
  `main_image` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'YER',
  `sku` varchar(100) DEFAULT NULL,
  `stock_status` enum('in_stock','low_stock','out_of_stock','on_demand') DEFAULT 'in_stock',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int unsigned DEFAULT 0,
  `meta_title_ar` varchar(200) DEFAULT NULL,
  `meta_title_en` varchar(200) DEFAULT NULL,
  `meta_description_ar` text DEFAULT NULL,
  `meta_description_en` text DEFAULT NULL,
  `schema_markup` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_stock_status` (`stock_status`),
  KEY `idx_sku` (`sku`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product catalog';

-- ============================================================
-- 10. PRODUCT SPECIFICATIONS
-- ============================================================

CREATE TABLE `product_specifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `spec_name_ar` varchar(200) NOT NULL,
  `spec_name_en` varchar(200) NOT NULL,
  `spec_value_ar` varchar(500) NOT NULL,
  `spec_value_en` varchar(500) NOT NULL,
  `sort_order` int unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_spec_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product technical specifications';

-- ============================================================
-- 11. PRODUCT IMAGES
-- ============================================================

CREATE TABLE `product_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text_ar` varchar(255) DEFAULT NULL,
  `alt_text_en` varchar(255) DEFAULT NULL,
  `sort_order` int unsigned DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_is_primary` (`is_primary`),
  CONSTRAINT `fk_image_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product image gallery';

-- ============================================================
-- 12. PRODUCT RELATIONS
-- ============================================================

CREATE TABLE `product_relations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `related_product_id` int unsigned NOT NULL,
  `relation_type` enum('related','accessory','alternative','complementary') DEFAULT 'related',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product_relation` (`product_id`, `related_product_id`),
  KEY `idx_related_product_id` (`related_product_id`),
  CONSTRAINT `fk_relation_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_relation_related` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Related products relationships';

-- ============================================================
-- 13. PORTFOLIO CATEGORIES
-- ============================================================

CREATE TABLE `portfolio_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(200) NOT NULL,
  `name_en` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `sort_order` int unsigned DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Portfolio/project categories';

-- ============================================================
-- 14. PORTFOLIO
-- ============================================================

CREATE TABLE `portfolio` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description_ar` text,
  `description_en` text,
  `client_name` varchar(200) DEFAULT NULL,
  `location_ar` varchar(255) DEFAULT NULL,
  `location_en` varchar(255) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `meta_title_ar` varchar(200) DEFAULT NULL,
  `meta_title_en` varchar(200) DEFAULT NULL,
  `meta_description_ar` text DEFAULT NULL,
  `meta_description_en` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_featured` (`is_featured`),
  CONSTRAINT `fk_portfolio_category` FOREIGN KEY (`category_id`) REFERENCES `portfolio_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Portfolio projects';

-- ============================================================
-- 15. PORTFOLIO IMAGES
-- ============================================================

CREATE TABLE `portfolio_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `portfolio_id` int unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `caption_ar` varchar(255) DEFAULT NULL,
  `caption_en` varchar(255) DEFAULT NULL,
  `sort_order` int unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_portfolio_id` (`sort_order`),
  CONSTRAINT `fk_portfolio_image` FOREIGN KEY (`portfolio_id`) REFERENCES `portfolio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Portfolio project images';

-- ============================================================
-- 16. CMS PAGES
-- ============================================================

CREATE TABLE `cms_pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `page_key` varchar(100) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content_ar` longtext,
  `content_en` longtext,
  `excerpt_ar` text,
  `excerpt_en` text,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int unsigned DEFAULT 0,
  `meta_title_ar` varchar(200) DEFAULT NULL,
  `meta_title_en` varchar(200) DEFAULT NULL,
  `meta_description_ar` text DEFAULT NULL,
  `meta_description_en` text DEFAULT NULL,
  `schema_markup` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_key` (`page_key`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CMS-managed pages';

-- ============================================================
-- 17. CONTACTS
-- ============================================================

CREATE TABLE `contacts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('contact','product_inquiry','service_request','quote_request','consultation','whatsapp') DEFAULT 'contact',
  `product_id` int unsigned DEFAULT NULL,
  `status` enum('new','in_progress','completed','archived') DEFAULT 'new',
  `is_read` tinyint(1) DEFAULT 0,
  `admin_notes` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contact form submissions';

-- ============================================================
-- 18. MEDIA FOLDERS
-- ============================================================

CREATE TABLE `media_folders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Media library folders';

-- ============================================================
-- 19. MEDIA FILES
-- ============================================================

CREATE TABLE `media_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `folder_id` int unsigned DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int unsigned DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `width` int unsigned DEFAULT NULL,
  `height` int unsigned DEFAULT NULL,
  `alt_text_ar` varchar(255) DEFAULT NULL,
  `alt_text_en` varchar(255) DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_in` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_folder_id` (`folder_id`),
  KEY `idx_file_type` (`file_type`),
  KEY `idx_is_used` (`is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Media library files';

-- ============================================================
-- 20. SEO SETTINGS
-- ============================================================

CREATE TABLE `seo_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `page_key` varchar(100) NOT NULL,
  `meta_title_ar` varchar(200) DEFAULT NULL,
  `meta_title_en` varchar(200) DEFAULT NULL,
  `meta_description_ar` text DEFAULT NULL,
  `meta_description_en` text DEFAULT NULL,
  `og_title_ar` varchar(200) DEFAULT NULL,
  `og_title_en` varchar(200) DEFAULT NULL,
  `og_description_ar` text DEFAULT NULL,
  `og_description_en` text DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `og_type` varchar(50) DEFAULT 'website',
  `twitter_card` varchar(50) DEFAULT 'summary_large_image',
  `canonical_url` varchar(500) DEFAULT NULL,
  `schema_type` varchar(100) DEFAULT NULL,
  `schema_markup` text DEFAULT NULL,
  `robots_meta` varchar(100) DEFAULT 'index,follow',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_key` (`page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO settings per page';

-- ============================================================
-- 21. REDIRECTS
-- ============================================================

CREATE TABLE `redirects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `old_url` varchar(500) NOT NULL,
  `new_url` varchar(500) NOT NULL,
  `status_code` int DEFAULT 301,
  `is_active` tinyint(1) DEFAULT 1,
  `hit_count` int unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_old_url` (`old_url`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='URL redirects';

-- ============================================================
-- 22. PAGE IMAGES (banner/hero images per page section)
-- ============================================================

CREATE TABLE `page_images` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-section page banner/hero images with device-specific fallback';

INSERT INTO `page_images`
  (`section_key`, `section_label`, `desktop_image_path`, `mobile_image_path`, `desktop_width`, `desktop_height`, `mobile_width`, `mobile_height`)
VALUES
  ('home_hero',        'صورة الهيرو - الصفحة الرئيسية', 'page_images/home_hero_desktop.webp',      'page_images/home_hero_mobile.webp',      1200, 900, 800, 600),
  ('about_banner',     'بانر صفحة من نحن',              'page_images/about_banner_desktop.webp',     'page_images/about_banner_mobile.webp',     1920, 400, 800, 300),
  ('services_banner',  'بانر صفحة الخدمات',              'page_images/services_banner_desktop.webp',  'page_images/services_banner_mobile.webp',  1920, 400, 800, 300),
  ('products_banner',  'بانر صفحة المنتجات',             'page_images/products_banner_desktop.webp',  'page_images/products_banner_mobile.webp',  1920, 400, 800, 300),
  ('portfolio_banner', 'بانر صفحة معرض الأعمال',         'page_images/portfolio_banner_desktop.webp', 'page_images/portfolio_banner_mobile.webp', 1920, 400, 800, 300),
  ('contact_banner',   'بانر صفحة اتصل بنا',             'page_images/contact_banner_desktop.webp',   'page_images/contact_banner_mobile.webp',   1920, 400, 800, 300),
  ('about_secondary_image', 'صورة صفحة من نحن (بجانب النص)', 'page_images/about_secondary_image_desktop.webp', 'page_images/about_secondary_image_mobile.webp', 800, 600, 500, 375);

-- ============================================================
-- 23. BRAND LOGOS (homepage marquee strip)
-- ============================================================

CREATE TABLE `brand_logos` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Brand/partner logos shown in the homepage marquee strip';

SET FOREIGN_KEY_CHECKS = 1;
