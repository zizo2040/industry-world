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

-- ============================================================
-- Alam Al-Sinaa for Electricity Trading - Seed Data
-- Initial data for production-ready deployment
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- SITE SETTINGS
-- ============================================================

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name_ar', 'عالم الصناعة لتجارة الكهرباء', 'general'),
('site_name_en', 'Alam Al-Sinaa for Electricity Trading', 'general'),
('site_tagline_ar', 'شريككم الموثوق في الحلول الكهربائية والصناعية المتكاملة', 'general'),
('site_tagline_en', 'Your Trusted Partner for Integrated Electrical & Industrial Solutions', 'general'),
('site_logo', 'logo.png', 'general'),
('site_favicon', 'favicon.ico', 'general'),
('site_font', 'Tajawal', 'appearance'),
('company_address_ar', 'تعز - الحوبان - مقابل البيت الحديث - بالقرب من الجوبي للمفروشات', 'general'),
('company_address_en', 'Taiz - Al-Hawban - Opposite Al-Bayt Al-Hadeeth - Near Al-Jawbi Furniture', 'general'),
('company_email', 'info@alamalsinaa.com', 'general'),
('whatsapp_primary', '781067910', 'general'),
('whatsapp_secondary_1', '734009848', 'general'),
('whatsapp_secondary_2', '783678560', 'general'),
('facebook_url', 'https://facebook.com/alamalsinaa', 'social'),
('twitter_url', '', 'social'),
('instagram_url', '', 'social'),
('linkedin_url', '', 'social'),
('youtube_url', '', 'social'),
('default_language', 'ar', 'general'),
('default_theme', 'light', 'general'),
('items_per_page', '12', 'general'),
('enable_dark_mode', '1', 'general'),
('enable_search', '1', 'general'),
('enable_whatsapp', '1', 'general'),
('contact_form_email', 'info@alamalsinaa.com', 'general'),
('maintenance_mode', '0', 'general'),
('google_analytics_id', '', 'seo'),
('google_tag_manager', '', 'seo'),
('site_verification_google', '', 'seo'),
('default_meta_title_ar', 'عالم الصناعة لتجارة الكهرباء - مستلزمات كهربائية وصناعية في تعز', 'seo'),
('default_meta_title_en', 'Alam Al-Sinaa for Electricity Trading - Electrical & Industrial Supplies in Taiz', 'seo'),
('default_meta_description_ar', 'عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية سابقاً) - مولدات كهرباء وقطع غيارها، انفرترات اونجريد اوف جريد وهيبرد، أجهزة تحكم صناعي PLC، منظمات، كيبلات، لوحات كهرباء ومفاتيح، أجهزة قياس كهربائية، مستلزمات مصانع البلاستيك والتعبئة والتغليف والأغذية، مصانع البلك والاسمنت والكسارات، مستلزمات المخابز الآلية، مستلزمات تبريد وسخانات، أدوات إنارة وكشافات طاقة، حساسات حرارة، وأنظمة الطاقة المتجددة في تعز، اليمن', 'seo'),
('default_meta_description_en', 'Alam Al-Sinaa for Electricity Trading (formerly Alam Al-Sinaa for Electrical & Industrial Supplies) - generators & spare parts, on-grid/off-grid/hybrid inverters, PLC industrial control, voltage regulators, cables, electrical panels, measuring devices, plastic factory & packaging supplies, food factory equipment, block/cement/crusher equipment, bakery ovens, cooling supplies, heaters, modern lighting & floodlights, temperature sensors, renewable energy in Taiz, Yemen', 'seo'),
('default_meta_keywords_ar', 'عالم الصناعة لتجارة الكهرباء، عالم الصناعة للكهرباء والمستلزمات الصناعية، قطع مستلزمات كهرباء، الطاقة المتجددة، تحكم صناعي، قطع صناعية، قطع غيار مولدات كهرباء، مولدات كهرباء، اجهزة قياس كهربائية، انفرترات اونجريد اوف جريد، انفرترات محركات، انفرترات هيبرد، مستلزمات المصانع، مستلزمات مصانع البلاستيك، مكائن الفلم والاكياس، مستلزمات مصانع و خطوط انتاج التعبئه و التغليف مصانع الأغذية و ادوات السلامه والامان، مصانع البلك و الاسمنت و الكسارات، مستلزمات المخابز و الافران الاليه، مستلزمات المحركات الكهربائيه والتبريد، منظمات، كيبلات، اجهزة تحكم PLC، مستلزمات المصانع وخطوط الانتاج والمعدات، لوحات كهرباء طبلونات، مفاتيح كهرباء، قطع الكترونيات، مستلزمات تبريد، سخانات، ادوات انارة حديثه كشافات طاقه، حساسات حرارة', 'seo'),
('default_meta_keywords_en', 'Alam Al-Sinaa Electricity Trading, electrical industrial supplies Yemen, renewable energy, industrial control, generator spare parts, electric generators, electrical measuring devices, on-grid off-grid inverters, motor inverters, hybrid inverters, factory supplies, plastic factory equipment, film and bag machines, packaging line supplies, food factory equipment, safety tools, block and cement factory equipment, crushers, automatic bakery ovens, electric motors, cooling equipment, voltage regulators, cables, PLC controllers, electrical panels, electrical switches, electronic parts, refrigeration supplies, heaters, modern lighting floodlights, temperature sensors', 'seo'),
('og_image', 'og-default.jpg', 'seo'),
('smtp_host', '', 'email'),
('smtp_port', '587', 'email'),
('smtp_username', '', 'email'),
('smtp_password', '', 'email'),
('smtp_encryption', 'tls', 'email'),
('backup_enabled', '1', 'backup'),
('backup_frequency', 'weekly', 'backup'),
('last_backup', NULL, 'backup'),
('contact_phone', '', 'general'),
('apple_touch_icon', '', 'general');

-- ============================================================
-- ADMIN USER (Default: admin / admin123)
-- Password will be hashed by PHP - this is a placeholder
-- ============================================================

INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('admin', 'admin@alamalsinaa.com', '$2y$12$dG8Nx8ab5F3v7B62qU/uc.2dJlh3RNJlTfuy1jF34lCXMhdgp0y/O', 'System Administrator', 'super_admin', 1);

-- ============================================================
-- CATEGORIES
-- ============================================================

INSERT INTO `categories` (`parent_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `icon`, `image`, `sort_order`, `is_active`) VALUES
(NULL, 'أنظمة الطاقة والطاقة المتجددة', 'Power Systems & Renewable Energy', 'power-systems', 'حلول طاقة متكاملة تشمل المولدات، الطاقة الشمسية، والكابلات', 'Integrated power solutions including generators, solar systems, and cables', 'zap', 'categories/power-systems.webp', 1, 1),
(NULL, 'التحكم والحلول الصناعية', 'Control & Industrial Solutions', 'industrial-control', 'أجهزة تحكم صناعية، PLC، حساسات، ولوحات كهربائية', 'Industrial control devices, PLCs, sensors, and electrical panels', 'cpu', 'categories/industrial-control.webp', 2, 1),
(NULL, 'توريدات خطوط الإنتاج والمصانع', 'Production Line & Factory Supplies', 'factory-supplies', 'مستلزمات مصانع كاملة، معدات إنتاج، وآلات صناعية', 'Complete factory supplies, production equipment, and industrial machines', 'factory', 'categories/factory-supplies.webp', 3, 1),
(NULL, 'حلول الإنارة', 'Lighting Solutions', 'lighting-solutions', 'أنظمة إنارة حديثة وكشافات طاقة للمنشآت الصناعية', 'Modern lighting systems and power floodlights for industrial facilities', 'lightbulb', 'categories/lighting-solutions.webp', 4, 1);

-- Sub-categories for Power Systems
INSERT INTO `categories` (`parent_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `icon`, `sort_order`, `is_active`) VALUES
(1, 'مولدات الكهرباء', 'Electric Generators', 'electric-generators', 'مولدات كهربائية بقدرات متنوعة للمصانع والمنشآت', 'Electric generators with various capacities for factories and facilities', 'power', 1, 1),
(1, 'قطع غيار المولدات', 'Generator Spare Parts', 'generator-parts', 'قطع غيار أصلية لجميع أنواع المولدات', 'Original spare parts for all types of generators', 'settings', 2, 1),
(1, 'أنظمة الطاقة الشمسية', 'Solar Power Systems', 'solar-systems', 'أنظمة طاقة شمسية متكاملة للمنشآت الصناعية', 'Integrated solar power systems for industrial facilities', 'sun', 3, 1),
(1, 'انفرترات', 'Inverters', 'inverters', 'انفرترات On-Grid وOff-Grid وHybrid', 'On-Grid, Off-Grid, and Hybrid inverters', 'toggle-right', 4, 1),
(1, 'منظمات الجهد', 'Voltage Regulators', 'voltage-regulators', 'منظمات جهد أوتوماتيكية بقدرات متنوعة', 'Automatic voltage regulators with various capacities', 'activity', 5, 1),
(1, 'الكابلات', 'Cables', 'cables', 'كابلات صناعية بجميع المقاسات والمواصفات', 'Industrial cables in all sizes and specifications', 'git-branch', 6, 1);

-- Sub-categories for Industrial Control
INSERT INTO `categories` (`parent_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `icon`, `sort_order`, `is_active`) VALUES
(2, 'أجهزة تحكم PLC', 'PLC Controllers', 'plc-controllers', 'أجهزة تحكم منطقي مبرمج بماركات عالمية', 'Programmable logic controllers from international brands', 'server', 1, 1),
(2, 'حساسات الحرارة', 'Temperature Sensors', 'temperature-sensors', 'حساسات حرارة صناعية دقيقة', 'Precision industrial temperature sensors', 'thermometer', 2, 1),
(2, 'أنظمة التحكم الصناعي', 'Industrial Control Systems', 'control-systems', 'أنظمة تحكم صناعي متكاملة', 'Integrated industrial control systems', 'sliders', 3, 1),
(2, 'محركات الانفرتر', 'Inverter Motors', 'inverter-motors', 'محركات متزامنة مع انفرترات بقدرات متنوعة', 'Inverter-compatible motors with various capacities', 'refresh-cw', 4, 1),
(2, 'لوحات الكهرباء', 'Electrical Panels', 'electrical-panels', 'لوحات كهربائية توزيع وتحكم', 'Distribution and control electrical panels', 'layout', 5, 1),
(2, 'مفاتيح الحماية', 'Protection Switches', 'protection-switches', 'مفاتيح حماية من تيار زائد وهبوط جهد', 'Protection switches for overcurrent and undervoltage', 'shield', 6, 1),
(2, 'قطع الإلكترونيات', 'Electronic Components', 'electronic-components', 'قطع إلكترونية صناعية بجودة عالية', 'High-quality industrial electronic components', 'radio', 7, 1),
(2, 'المعدات الصناعية', 'Industrial Equipment', 'industrial-equipment', 'معدات صناعية عامة ومتخصصة', 'General and specialized industrial equipment', 'tool', 8, 1);

-- Sub-categories for Factory Supplies
INSERT INTO `categories` (`parent_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `icon`, `sort_order`, `is_active`) VALUES
(3, 'مستلزمات مصانع البلاستيك', 'Plastic Factory Supplies', 'plastic-factory', 'مكائن ومعدات مصانع البلاستيك', 'Machinery and equipment for plastic factories', 'box', 1, 1),
(3, 'مكائن الفلم والأكياس', 'Film & Bag Machines', 'film-bag-machines', 'مكائن إنتاج الأفلام البلاستيكية والأكياس', 'Plastic film and bag production machines', 'film', 2, 1),
(3, 'خطوط إنتاج التعبئة والتغليف', 'Packaging Lines', 'packaging-lines', 'خطوط إنتاج كاملة للتعبئة والتغليف', 'Complete packaging and wrapping production lines', 'package', 3, 1),
(3, 'مصانع الأغذية', 'Food Processing', 'food-processing', 'معدات وآلات مصانع تصنيع الأغذية', 'Equipment and machines for food processing factories', 'coffee', 4, 1),
(3, 'معدات مصانع البلك', 'Brick Factory Equipment', 'brick-factory', 'معدات إنتاج البلك والطوب', 'Brick and block production equipment', 'square', 5, 1),
(3, 'معدات مصانع الأسمنت', 'Cement Factory Equipment', 'cement-factory', 'معدات وقطع غيار مصانع الأسمنت', 'Equipment and spare parts for cement factories', 'database', 6, 1),
(3, 'معدات الكسارات', 'Crusher Equipment', 'crusher-equipment', 'معدات كسارات ومحاجر', 'Crusher and quarry equipment', 'aperture', 7, 1),
(3, 'تجهيزات المخابز', 'Bakery Equipment', 'bakery-equipment', 'أفران ومعدات مخابز آلية', 'Automatic bakery ovens and equipment', 'flame', 8, 1),
(3, 'مستلزمات التبريد', 'Refrigeration Supplies', 'refrigeration', 'معدات التبريد الصناعي والتكييف', 'Industrial refrigeration and air conditioning equipment', 'snowflake', 9, 1),
(3, 'المحركات الكهربائية', 'Electric Motors', 'electric-motors', 'محركات كهربائية صناعية بجميع القدرات', 'Industrial electric motors in all capacities', 'rotate-cw', 10, 1),
(3, 'أدوات السلامة والأمان المهني', 'Safety Equipment', 'safety-equipment', 'أدوات ومعدات السلامة المهنية', 'Occupational safety tools and equipment', 'hard-hat', 11, 1);

-- Sub-categories for Lighting Solutions
INSERT INTO `categories` (`parent_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `icon`, `sort_order`, `is_active`) VALUES
(4, 'أدوات الإنارة الحديثة', 'Modern Lighting', 'modern-lighting', 'أنظمة إنارة LED حديثة للمنشآت الصناعية', 'Modern LED lighting systems for industrial facilities', 'sunrise', 1, 1),
(4, 'كشافات الطاقة', 'Power Floodlights', 'power-floodlights', 'كشافات طاقة عالية للإنارة الخارجية', 'High-power floodlights for outdoor lighting', 'sun', 2, 1);

-- ============================================================
-- CMS PAGES
-- ============================================================

INSERT INTO `cms_pages` (`page_key`, `title_ar`, `title_en`, `slug`, `content_ar`, `content_en`, `excerpt_ar`, `excerpt_en`, `is_active`, `sort_order`) VALUES
('home', 'الرئيسية', 'Home', 'home', NULL, NULL, NULL, NULL, 1, 1),
('about', 'من نحن', 'About Us', 'about', 
'<p>عالم الصناعة لتجارة الكهرباء شركة رائدة في مجال توفير الحلول الكهربائية والصناعية المتكاملة. نحن نخدم القطاع الصناعي منذ سنوات بتفانٍ واحترافية.</p>
<h2>رؤيتنا</h2>
<p>أن نكون الشريك الأول للمصانع والمنشآت الصناعية في اليمن في توفير الحلول الكهربائية والصناعية المتكاملة.</p>
<h2>رسالتنا</h2>
<p>توفير منتجات وخدمات عالية الجودة تلبي احتياجات عملائنا الصناعيين بكفاءة واحترافية، مع ضمان أفضل الأسعار وأسرع وقت للتسليم.</p>
<h2>قيمنا</h2>
<ul>
<li>الجودة: نقدم فقط المنتجات المعتمدة والمختبرة</li>
<li>الموثوقية: نفي بالتزاماتنا بدقة وفي الوقت المحدد</li>
<li>الابتكار: نبحث دائماً عن أحدث التقنيات والحلول</li>
<li>الشراكة: نرى عملاءنا كشركاء نجاح</li>
</ul>',
'<p>Alam Al-Sinaa for Electricity Trading is a leading provider of integrated electrical and industrial solutions. We have been serving the industrial sector for years with dedication and professionalism.</p>
<h2>Our Vision</h2>
<p>To be the first choice for factories and industrial facilities in Yemen for integrated electrical and industrial solutions.</p>
<h2>Our Mission</h2>
<p>To provide high-quality products and services that meet the needs of our industrial clients efficiently and professionally, ensuring the best prices and fastest delivery times.</p>
<h2>Our Values</h2>
<ul>
<li>Quality: We only offer certified and tested products</li>
<li>Reliability: We fulfill our commitments accurately and on time</li>
<li>Innovation: We always seek the latest technologies and solutions</li>
<li>Partnership: We see our clients as partners in success</li>
</ul>',
'شركة رائدة في مجال الحلول الكهربائية والصناعية المتكاملة',
'A leading company in integrated electrical and industrial solutions',
1, 2),
('contact', 'اتصل بنا', 'Contact Us', 'contact',
'<p>نحن هنا لمساعدتك. يمكنك التواصل معنا عبر أي من القنوات التالية:</p>
<h2>عنواننا</h2>
<p>تعز - الحوبان - مقابل البيت الحديث - بالقرب من الجوبي للمفروشات</p>
<h2>أرقام الواتساب</h2>
<ul>
<li>781067910</li>
<li>734009848</li>
<li>783678560</li>
</ul>',
'<p>We are here to help. You can reach us through any of the following channels:</p>
<h2>Our Address</h2>
<p>Taiz - Al-Hawban - Opposite Al-Bayt Al-Hadeeth - Near Al-Jawbi Furniture</p>
<h2>WhatsApp Numbers</h2>
<ul>
<li>781067910</li>
<li>734009848</li>
<li>783678560</li>
</ul>',
NULL, NULL, 1, 3),
('privacy', 'سياسة الخصوصية', 'Privacy Policy', 'privacy',
'<h2>سياسة الخصوصية</h2>
<p>نحن نحرص على حماية خصوصيتك. لا نقوم بجمع أو مشاركة أي معلومات شخصية دون إذنك الصريح.</p>
<h3>المعلومات التي نجمعها</h3>
<p>نقوم بجمع المعلومات التي تقدمها لنا طواعية عند ملء نماذج الاتصال أو طلب عروض الأسعار.</p>
<h3>كيف نستخدم المعلومات</h3>
<p>نستخدم المعلومات فقط للرد على استفساراتك وتقديم الخدمات المطلوبة.</p>',
'<h2>Privacy Policy</h2>
<p>We are committed to protecting your privacy. We do not collect or share any personal information without your explicit consent.</p>
<h3>Information We Collect</h3>
<p>We collect information that you voluntarily provide when filling out contact forms or requesting quotes.</p>
<h3>How We Use Information</h3>
<p>We use the information solely to respond to your inquiries and provide the requested services.</p>',
NULL, NULL, 1, 4),
('terms', 'الشروط والأحكام', 'Terms and Conditions', 'terms',
'<h2>الشروط والأحكام</h2>
<p>باستخدامك لهذا الموقع، فإنك توافق على الشروط والأحكام التالية:</p>
<h3>المنتجات والخدمات</h3>
<p>جميع المنتجات والخدمات المقدمة خاضعة للتوافر والتأكيد النهائي.</p>
<h3>الأسعار</h3>
<p>الأسعار المعروضة قابلة للتغيير دون إشعار مسبق. يرجى التواصل معنا للحصول على عرض سعر محدث.</p>',
'<h2>Terms and Conditions</h2>
<p>By using this website, you agree to the following terms and conditions:</p>
<h3>Products and Services</h3>
<p>All products and services provided are subject to availability and final confirmation.</p>
<h3>Prices</h3>
<p>Displayed prices are subject to change without prior notice. Please contact us for an updated quote.</p>',
NULL, NULL, 1, 5),
('faq', 'الأسئلة الشائعة', 'FAQ', 'faq',
NULL, NULL, NULL, NULL, 1, 6),
('services', 'خدماتنا', 'Our Services', 'services',
'<h2>خدماتنا</h2>
<p>نقدم مجموعة شاملة من الخدمات للقطاع الصناعي:</p>
<h3>الاستشارات الفنية</h3>
<p>فريقنا الهندسي جاهز لتقديم استشارات فنية متخصصة لمشاريعكم.</p>
<h3>تركيب وتشغيل</h3>
<p>نقدم خدمات التركيب والتشغيل لجميع الأنظمة والمعدات.</p>
<h3>الصيانة الدورية</h3>
<p>عقود صيانة دورية لضمان استمرارية عمل منشآتكم.</p>
<h3>قطع الغيار</h3>
<p>نوفر قطع غيار أصلية لجميع المعدات والأنظمة التي نوردها.</p>',
'<h2>Our Services</h2>
<p>We offer a comprehensive range of services for the industrial sector:</p>
<h3>Technical Consultations</h3>
<p>Our engineering team is ready to provide specialized technical consultations for your projects.</p>
<h3>Installation & Commissioning</h3>
<p>We provide installation and commissioning services for all systems and equipment.</p>
<h3>Periodic Maintenance</h3>
<p>Periodic maintenance contracts to ensure the continuity of your operations.</p>
<h3>Spare Parts</h3>
<p>We provide original spare parts for all equipment and systems we supply.</p>',
NULL, NULL, 1, 7);

-- ============================================================
-- PORTFOLIO CATEGORIES
-- ============================================================

INSERT INTO `portfolio_categories` (`name_ar`, `name_en`, `slug`, `sort_order`, `is_active`) VALUES
('تركيب مولدات', 'Generator Installation', 'generator-installation', 1, 1),
('أنظمة طاقة شمسية', 'Solar Power Systems', 'solar-projects', 2, 1),
('لوحات تحكم', 'Control Panels', 'control-panels', 3, 1),
('تجهيز مصانع', 'Factory Setup', 'factory-setup', 4, 1),
('صيانة', 'Maintenance', 'maintenance', 5, 1);

-- ============================================================
-- SAMPLE PRODUCTS
-- ============================================================

INSERT INTO `products` (`category_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `short_description_ar`, `short_description_en`, `features_ar`, `features_en`, `sku`, `stock_status`, `is_featured`, `is_active`) VALUES
(5, 'مولد كهربائي 50 كيلوواط', '50 kW Electric Generator', 'generator-50kw', 
'مولد كهربائي بقوة 50 كيلوواط، مناسب للمصانع المتوسطة والمنشآت الصناعية. يتميز بكفاءة عالية واستهلاك منخفض للوقود.',
'50 kW electric generator, suitable for medium-sized factories and industrial facilities. Features high efficiency and low fuel consumption.',
'مولد 50 كيلوواط للمصانع المتوسطة',
'50 kW generator for medium factories',
'- محرك ديزل عالي الكفاءة\n- نظام تحكم أوتوماتيكي\n- حماية من الزيادة في الحمل\n- خزان وقود سعة 100 لتر',
'- High-efficiency diesel engine\n- Automatic control system\n- Overload protection\n- 100-liter fuel tank',
'GEN-050-001', 'in_stock', 1, 1);

INSERT INTO `products` (`category_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `short_description_ar`, `short_description_en`, `features_ar`, `features_en`, `sku`, `stock_status`, `is_featured`, `is_active`) VALUES
(7, 'نظام طاقة شمسية 10 كيلوواط', '10 kW Solar Power System', 'solar-system-10kw',
'نظام طاقة شمسية متكامل بقدرة 10 كيلوواط للمنشآت الصناعية. يشمل الألواح الشمسية، الانفرتر، ومنظمات الجهد.',
'Integrated 10 kW solar power system for industrial facilities. Includes solar panels, inverter, and voltage regulators.',
'نظام شمسي متكامل 10 كيلوواط',
'Complete 10 kW solar system',
'- ألواح شمسية أحادية البلورة\n- انفرتر هجين 10 كيلوواط\n- بطاريات تخزين عالية السعة\n- نظام مراقبة ذكي',
'- Monocrystalline solar panels\n- 10 kW hybrid inverter\n- High-capacity storage batteries\n- Smart monitoring system',
'SOL-010-001', 'in_stock', 1, 1);

INSERT INTO `products` (`category_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `short_description_ar`, `short_description_en`, `features_ar`, `features_en`, `sku`, `stock_status`, `is_featured`, `is_active`) VALUES
(9, 'جهاز تحكم PLC سيمنز S7-1200', 'Siemens S7-1200 PLC Controller', 'plc-s7-1200',
'جهاز تحكم منطقي مبرمج من سيمنز من سلسلة S7-1200. مثالي للتطبيقات الصناعية المتوسطة والمعقدة.',
'Siemens S7-1200 series programmable logic controller. Ideal for medium to complex industrial applications.',
'PLC سيمنز S7-1200',
'Siemens S7-1200 PLC',
'- معالج عالي السرعة\n- دعم بروتوكولات اتصال متعددة\n- توسيع سهل بالوحدات الإضافية\n- برمجة عبر TIA Portal',
'- High-speed processor\n- Multiple communication protocols\n- Easy expansion with add-on modules\n- TIA Portal programming',
'PLC-S7-001', 'in_stock', 1, 1);

INSERT INTO `products` (`category_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `short_description_ar`, `short_description_en`, `features_ar`, `features_en`, `sku`, `stock_status`, `is_featured`, `is_active`) VALUES
(5, 'مولد كهربائي 100 كيلوواط', '100 kW Electric Generator', 'generator-100kw',
'مولد كهربائي صناعي بقوة 100 كيلوواط للمصانع الكبيرة. محرك ثقيل الأداء مع نظام تبريد متطور.',
'100 kW industrial electric generator for large factories. Heavy-duty engine with advanced cooling system.',
'مولد صناعي 100 كيلوواط',
'100 kW industrial generator',
'- محرك ديزل صناعي ثقيل\n- مبرد مائي متطور\n- لوحة تحكم رقمية\n- عازل صوتي',
'- Heavy-duty diesel engine\n- Advanced water cooling\n- Digital control panel\n- Sound insulation',
'GEN-100-001', 'in_stock', 1, 1);

INSERT INTO `products` (`category_id`, `name_ar`, `name_en`, `slug`, `description_ar`, `description_en`, `short_description_ar`, `short_description_en`, `features_ar`, `features_en`, `sku`, `stock_status`, `is_featured`, `is_active`) VALUES
(6, 'قطع غيار مولدات - فلاتر وشمعات', 'Generator Spare Parts - Filters & Spark Plugs', 'generator-parts-filters',
'قطع غيار أصلية للمولدات تشمل الفلاتر والشمعات والحزام والبطاريات. متوافقة مع معظم الماركات العالمية.',
'Original generator spare parts including filters, spark plugs, belts, and batteries. Compatible with most international brands.',
'قطع غيار أصلية للمولدات',
'Original generator spare parts',
'- فلاتر هواء وزيت\n- شمعات إشعال عالية الجودة\n- بطاريات صيانة مجانية\n- حزام مولد متين',
'- Air and oil filters\n- High-quality spark plugs\n- Maintenance-free batteries\n- Durable generator belt',
'GEN-PARTS-001', 'in_stock', 0, 1);

-- ============================================================
-- SAMPLE PORTFOLIO
-- ============================================================

INSERT INTO `portfolio` (`category_id`, `title_ar`, `title_en`, `slug`, `description_ar`, `description_en`, `client_name`, `location_ar`, `location_en`, `completion_date`, `is_featured`, `is_active`) VALUES
(1, 'تركيب مولد 200 كيلوواط لمصنع بلاستيك', '200 kW Generator Installation for Plastic Factory', 'plastic-factory-generator',
'تم تركيب مولد كهربائي بقوة 200 كيلوواط لأحد مصانع البلاستيك الرائدة في تعز. شمل المشروع التوريد والتركيب والتشغيل.',
'Installation of a 200 kW electric generator for a leading plastic factory in Taiz. The project included supply, installation, and commissioning.',
'مصنع النهضة للبلاستيك', 'تعز - المنصورة', 'Taiz - Al-Mansoura', '2024-01-15', 1, 1);

INSERT INTO `portfolio` (`category_id`, `title_ar`, `title_en`, `slug`, `description_ar`, `description_en`, `client_name`, `location_ar`, `location_en`, `completion_date`, `is_featured`, `is_active`) VALUES
(2, 'نظام طاقة شمسية 50 كيلوواط لمصنع أغذية', '50 kW Solar System for Food Factory', 'food-factory-solar',
'توريد وتركيب نظام طاقة شمسية بقدرة 50 كيلوواط لمصنع تصنيع الأغذية. يوفر النظام 80% من استهلاك المصنع.',
'Supply and installation of a 50 kW solar power system for a food processing factory. The system provides 80% of the factory energy needs.',
'مصنع الغذاء الطيب', 'تعز - الحوبان', 'Taiz - Al-Hawban', '2024-03-20', 1, 1);

INSERT INTO `portfolio` (`category_id`, `title_ar`, `title_en`, `slug`, `description_ar`, `description_en`, `client_name`, `location_ar`, `location_en`, `completion_date`, `is_featured`, `is_active`) VALUES
(3, 'لوحات تحكم كهربائية لخط إنتاج', 'Electrical Control Panels for Production Line', 'control-panels-production',
'تصميم وتصنيع لوحات تحكم كهربائية مخصصة لخط إنتاج مصنع تعبئة. شملت اللوحات أنظمة PLC وحساسات وحماية.',
'Design and manufacturing of custom electrical control panels for a packaging factory production line. The panels included PLC systems, sensors, and protection.',
'مصنع التعبئة الحديث', 'تعز - صالة', 'Taiz - Salah', '2024-02-10', 1, 1);

INSERT INTO `portfolio` (`category_id`, `title_ar`, `title_en`, `slug`, `description_ar`, `description_en`, `client_name`, `location_ar`, `location_en`, `completion_date`, `is_featured`, `is_active`) VALUES
(4, 'تجهيز مصنع بلك أوتوماتيكي', 'Automatic Brick Factory Setup', 'brick-factory-setup',
'تجهيز كامل لمصنع بلك أوتوماتيكي تشمل توريد المكائن والتركيب والتدريب. القدرة الإنتاجية: 5000 بلوكة يومياً.',
'Complete setup of an automatic brick factory including machinery supply, installation, and training. Production capacity: 5,000 blocks per day.',
'مصنع البناء المتطور', 'تعز - المواسط', 'Taiz - Al-Mawasit', '2024-04-05', 1, 1);

-- ============================================================
-- SEO SETTINGS
-- ============================================================

INSERT INTO `seo_settings` (`page_key`, `meta_title_ar`, `meta_title_en`, `meta_description_ar`, `meta_description_en`, `og_type`, `twitter_card`, `robots_meta`) VALUES
('home', 'عالم الصناعة لتجارة الكهرباء - مستلزمات كهربائية وصناعية في تعز', 'Alam Al-Sinaa for Electricity Trading - Electrical & Industrial Supplies in Taiz', 'عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية سابقاً) - مولدات كهرباء، انفرترات اونجريد اوف جريد وهيبرد، أجهزة تحكم صناعي PLC، منظمات، كيبلات، مستلزمات مصانع، معدات تعبئة وتغليف في تعز', 'Alam Al-Sinaa for Electricity Trading (formerly Alam Al-Sinaa for Electrical & Industrial Supplies) - Generators, on-grid/off-grid/hybrid inverters, PLC industrial controls, voltage regulators, cables, factory equipment in Taiz', 'website', 'summary_large_image', 'index,follow'),
('about', 'من نحن - عالم الصناعة لتجارة الكهرباء', 'About Us - Alam Al-Sinaa for Electricity Trading', 'تعرف على عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية سابقاً) - شريككم الموثوق في الحلول الكهربائية والصناعية المتكاملة في تعز', 'Learn about Alam Al-Sinaa for Electricity Trading - Your trusted partner for integrated electrical and industrial solutions in Taiz', 'website', 'summary_large_image', 'index,follow'),
('contact', 'اتصل بنا - عالم الصناعة لتجارة الكهرباء', 'Contact Us - Alam Al-Sinaa', 'تواصل مع عالم الصناعة لتجارة الكهرباء. أرقام واتساب: 781067910 - 734009848 - 783678560', 'Contact Alam Al-Sinaa for Electricity Trading. WhatsApp: 781067910 - 734009848 - 783678560', 'website', 'summary_large_image', 'index,follow'),
('products', 'المنتجات - عالم الصناعة لتجارة الكهرباء', 'Products - Alam Al-Sinaa for Electricity Trading', 'استعرض مجموعتنا الواسعة من المنتجات الكهربائية والصناعية. مولدات، انفرترات، منظمات، كيبلات، أجهزة تحكم PLC، حساسات حرارة، مستلزمات مصانع', 'Browse our wide range of electrical and industrial products. Generators, inverters, voltage regulators, cables, PLC controllers, temperature sensors, factory supplies', 'website', 'summary_large_image', 'index,follow'),
('portfolio', 'معرض الأعمال - عالم الصناعة لتجارة الكهرباء', 'Portfolio - Alam Al-Sinaa for Electricity Trading', 'شاهد مشاريعنا المنفذة في مجال الأنظمة الكهربائية والصناعية. تركيب مولدات، أنظمة طاقة متجددة، لوحات تحكم', 'View our completed projects in electrical and industrial systems. Generator installation, renewable energy systems, control panels', 'website', 'summary_large_image', 'index,follow');

-- ============================================================
-- REDIRECTS
-- ============================================================

INSERT INTO `redirects` (`old_url`, `new_url`, `status_code`) VALUES
('/old-products', '/products', 301),
('/contact-us', '/contact', 301);

-- ============================================================
-- MEDIA FOLDERS
-- ============================================================

INSERT INTO `media_folders` (`name`, `created_at`) VALUES
('Products', NOW()),
('Portfolio', NOW()),
('Categories', NOW()),
('General', NOW());

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

-- ============================================================
-- Migration: Comprehensive Logo Management, Site Font, Custom Cursor
-- Safe to run on the LIVE database — does not touch or drop any
-- existing table.
--
-- `site_settings` is a generic key/value table, so no ALTER TABLE is
-- needed for the new logo or cursor fields: admin/settings.php simply
-- INSERT ... ON DUPLICATE KEY UPDATE's these new setting_key values
-- the first time they are saved from the admin panel:
--
--   logo_header_light, logo_header_dark, logo_mobile_menu, logo_footer
--   cursor_icon_light, cursor_icon_dark
--
-- This migration only seeds a sensible default for the new `site_font`
-- setting so existing installs render with the original Tajawal font
-- until an admin picks a different one from Settings > خط الموقع.
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('site_font', 'Tajawal', 'appearance')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;

-- ============================================================
-- Migration: About Page - Secondary Image
-- Adds the upload section for the square image block next to the
-- "About Us" text/values grid (previously a hardcoded blue
-- placeholder box with no real image). Safe to run on the LIVE
-- database — only adds one row, does not touch or drop anything.
-- ============================================================

SET NAMES utf8mb4;

INSERT IGNORE INTO `page_images`
  (`section_key`, `section_label`, `desktop_width`, `desktop_height`, `mobile_width`, `mobile_height`)
VALUES
  ('about_secondary_image', 'صورة صفحة من نحن (بجانب النص)', 800, 600, 500, 375);

-- ============================================================
-- Migration: Site Rebrand ("عالم الصناعة لتجارة الكهرباء") + SEO Keywords
-- Safe to run on the LIVE database — does not touch or drop any
-- existing table, only UPDATEs/INSERTs settings rows.
--
-- What this does:
--   1. Renames the site (site_name_ar / site_name_en) to the new
--      brand name across the whole site (header, page titles,
--      Open Graph tags, the AI assistant persona text, etc. all
--      read this value dynamically via getSetting()).
--   2. Refreshes the sitewide default SEO title/description so the
--      new name is used, while keeping the previous company name
--      mentioned once for continuity in search rankings.
--   3. Adds two brand-new settings — default_meta_keywords_ar and
--      default_meta_keywords_en — consumed by the new <meta
--      name="keywords"> tag in includes/header.php (see code change
--      shipped alongside this migration). `site_settings` is a
--      generic key/value table, so no ALTER TABLE is required.
--   4. Refreshes the per-page SEO titles/descriptions in
--      `seo_settings` for home/about/contact/products/portfolio.
--   5. Updates the company-name mention inside the "About" CMS page
--      content (cms_pages.content_ar / content_en) via REPLACE(),
--      so any other admin edits to that page are preserved.
--
-- Re-running this migration is safe (idempotent).
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 1. Site name
-- ------------------------------------------------------------
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('site_name_ar', 'عالم الصناعة لتجارة الكهرباء', 'general')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('site_name_en', 'Alam Al-Sinaa for Electricity Trading', 'general')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ------------------------------------------------------------
-- 2. Sitewide default SEO title & description
-- ------------------------------------------------------------
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('default_meta_title_ar', 'عالم الصناعة لتجارة الكهرباء - مستلزمات كهربائية وصناعية في تعز', 'seo')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('default_meta_title_en', 'Alam Al-Sinaa for Electricity Trading - Electrical & Industrial Supplies in Taiz', 'seo')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('default_meta_description_ar', 'عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية سابقاً) - مولدات كهرباء وقطع غيارها، انفرترات اونجريد اوف جريد وهيبرد، أجهزة تحكم صناعي PLC، منظمات، كيبلات، لوحات كهرباء ومفاتيح، أجهزة قياس كهربائية، مستلزمات مصانع البلاستيك والتعبئة والتغليف والأغذية، مصانع البلك والاسمنت والكسارات، مستلزمات المخابز الآلية، مستلزمات تبريد وسخانات، أدوات إنارة وكشافات طاقة، حساسات حرارة، وأنظمة الطاقة المتجددة في تعز، اليمن', 'seo')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('default_meta_description_en', 'Alam Al-Sinaa for Electricity Trading (formerly Alam Al-Sinaa for Electrical & Industrial Supplies) - generators & spare parts, on-grid/off-grid/hybrid inverters, PLC industrial control, voltage regulators, cables, electrical panels, measuring devices, plastic factory & packaging supplies, food factory equipment, block/cement/crusher equipment, bakery ovens, cooling supplies, heaters, modern lighting & floodlights, temperature sensors, renewable energy in Taiz, Yemen', 'seo')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ------------------------------------------------------------
-- 3. New: sitewide default meta keywords (consumed by the new
--    <meta name="keywords"> tag added to includes/header.php)
-- ------------------------------------------------------------
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('default_meta_keywords_ar', 'عالم الصناعة لتجارة الكهرباء، عالم الصناعة للكهرباء والمستلزمات الصناعية، قطع مستلزمات كهرباء، الطاقة المتجددة، تحكم صناعي، قطع صناعية، قطع غيار مولدات كهرباء، مولدات كهرباء، اجهزة قياس كهربائية، انفرترات اونجريد اوف جريد، انفرترات محركات، انفرترات هيبرد، مستلزمات المصانع، مستلزمات مصانع البلاستيك، مكائن الفلم والاكياس، مستلزمات مصانع و خطوط انتاج التعبئه و التغليف مصانع الأغذية و ادوات السلامه والامان، مصانع البلك و الاسمنت و الكسارات، مستلزمات المخابز و الافران الاليه، مستلزمات المحركات الكهربائيه والتبريد، منظمات، كيبلات، اجهزة تحكم PLC، مستلزمات المصانع وخطوط الانتاج والمعدات، لوحات كهرباء طبلونات، مفاتيح كهرباء، قطع الكترونيات، مستلزمات تبريد، سخانات، ادوات انارة حديثه كشافات طاقه، حساسات حرارة', 'seo')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`)
VALUES ('default_meta_keywords_en', 'Alam Al-Sinaa Electricity Trading, electrical industrial supplies Yemen, renewable energy, industrial control, generator spare parts, electric generators, electrical measuring devices, on-grid off-grid inverters, motor inverters, hybrid inverters, factory supplies, plastic factory equipment, film and bag machines, packaging line supplies, food factory equipment, safety tools, block and cement factory equipment, crushers, automatic bakery ovens, electric motors, cooling equipment, voltage regulators, cables, PLC controllers, electrical panels, electrical switches, electronic parts, refrigeration supplies, heaters, modern lighting floodlights, temperature sensors', 'seo')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ------------------------------------------------------------
-- 4. Per-page SEO titles/descriptions
-- ------------------------------------------------------------
UPDATE `seo_settings` SET
  `meta_title_ar` = 'عالم الصناعة لتجارة الكهرباء - مستلزمات كهربائية وصناعية في تعز',
  `meta_title_en` = 'Alam Al-Sinaa for Electricity Trading - Electrical & Industrial Supplies in Taiz',
  `meta_description_ar` = 'عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية سابقاً) - مولدات كهرباء، انفرترات اونجريد اوف جريد وهيبرد، أجهزة تحكم صناعي PLC، منظمات، كيبلات، مستلزمات مصانع، معدات تعبئة وتغليف في تعز',
  `meta_description_en` = 'Alam Al-Sinaa for Electricity Trading (formerly Alam Al-Sinaa for Electrical & Industrial Supplies) - Generators, on-grid/off-grid/hybrid inverters, PLC industrial controls, voltage regulators, cables, factory equipment in Taiz'
WHERE `page_key` = 'home';

UPDATE `seo_settings` SET
  `meta_title_ar` = 'من نحن - عالم الصناعة لتجارة الكهرباء',
  `meta_title_en` = 'About Us - Alam Al-Sinaa for Electricity Trading',
  `meta_description_ar` = 'تعرف على عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية سابقاً) - شريككم الموثوق في الحلول الكهربائية والصناعية المتكاملة في تعز',
  `meta_description_en` = 'Learn about Alam Al-Sinaa for Electricity Trading - Your trusted partner for integrated electrical and industrial solutions in Taiz'
WHERE `page_key` = 'about';

UPDATE `seo_settings` SET
  `meta_title_ar` = 'اتصل بنا - عالم الصناعة لتجارة الكهرباء',
  `meta_description_ar` = 'تواصل مع عالم الصناعة لتجارة الكهرباء. أرقام واتساب: 781067910 - 734009848 - 783678560',
  `meta_description_en` = 'Contact Alam Al-Sinaa for Electricity Trading. WhatsApp: 781067910 - 734009848 - 783678560'
WHERE `page_key` = 'contact';

UPDATE `seo_settings` SET
  `meta_title_ar` = 'المنتجات - عالم الصناعة لتجارة الكهرباء',
  `meta_title_en` = 'Products - Alam Al-Sinaa for Electricity Trading',
  `meta_description_ar` = 'استعرض مجموعتنا الواسعة من المنتجات الكهربائية والصناعية. مولدات، انفرترات، منظمات، كيبلات، أجهزة تحكم PLC، حساسات حرارة، مستلزمات مصانع',
  `meta_description_en` = 'Browse our wide range of electrical and industrial products. Generators, inverters, voltage regulators, cables, PLC controllers, temperature sensors, factory supplies'
WHERE `page_key` = 'products';

UPDATE `seo_settings` SET
  `meta_title_ar` = 'معرض الأعمال - عالم الصناعة لتجارة الكهرباء',
  `meta_title_en` = 'Portfolio - Alam Al-Sinaa for Electricity Trading',
  `meta_description_ar` = 'شاهد مشاريعنا المنفذة في مجال الأنظمة الكهربائية والصناعية. تركيب مولدات، أنظمة طاقة متجددة، لوحات تحكم',
  `meta_description_en` = 'View our completed projects in electrical and industrial systems. Generator installation, renewable energy systems, control panels'
WHERE `page_key` = 'portfolio';

-- ------------------------------------------------------------
-- 5. "About" CMS page body copy — swap only the old company-name
--    mention, leaving the rest of any admin-edited content intact.
-- ------------------------------------------------------------
UPDATE `cms_pages`
SET `content_ar` = REPLACE(
  `content_ar`,
  'عالم الصناعة للكهرباء والمستلزمات الصناعية شركة رائدة',
  'عالم الصناعة لتجارة الكهرباء شركة رائدة'
)
WHERE `page_key` = 'about';

UPDATE `cms_pages`
SET `content_en` = REPLACE(
  `content_en`,
  'Alam Al-Sinaa for Electrical & Industrial Supplies is a leading provider',
  'Alam Al-Sinaa for Electricity Trading is a leading provider'
)
WHERE `page_key` = 'about';

-- ------------------------------------------------------------
-- 6. AI assistant persona (only if the admin panel already saved a
--    custom system prompt into site_settings — otherwise the code
--    default in ai-assistant/config/assistant.php is used, which is
--    already updated to the new name in this same code delivery).
-- ------------------------------------------------------------
UPDATE `site_settings`
SET `setting_value` = REPLACE(
  `setting_value`,
  'عالم الصناعة للكهرباء والمستلزمات الصناعية',
  'عالم الصناعة لتجارة الكهرباء'
)
WHERE `setting_key` = 'ai_system_prompt';

-- ============================================================
-- Migration 008: Business catalogue, contact data & SEO keywords
-- عالم الصناعة لتجارة الكهرباء / عالم الصناعة للكهرباء والمستلزمات الصناعية
--
-- Safe to run on the LIVE database and safe to re-run (idempotent):
--  * Restructures the product categories around the real business
--    activity (7 main sections, 32 sub-sections). Existing categories are
--    matched by slug and UPDATED in place (ids, products and uploaded
--    images are preserved); new ones are INSERTED.
--  * Adds per-category SEO title/description.
--  * Updates address, phones, working hours, announcement bar, keywords
--    and per-page SEO.
--  * Fixes the demo PLC product that was filed under "voltage regulators".
-- Run in phpMyAdmin › SQL (or import the file).
-- ============================================================

SET NAMES utf8mb4;

-- ---------- 1. Main sections ----------
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (NULL,'الطاقة المتجددة والمنظومات الشمسية','Renewable Energy & Solar Systems','power-systems','منظومات طاقة شمسية متكاملة، ألواح شمسية، انفرترات أونجريد وأوف جريد وهيبرد، منظمات جهد وكيبلات — حلول طاقة متجددة للمنازل والمصانع والمزارع في اليمن.','Complete solar power systems, solar panels, on-grid/off-grid and hybrid inverters, voltage regulators and cables — renewable energy for homes, factories and farms in Yemen.','zap',1,1,'الطاقة المتجددة والمنظومات الشمسية في تعز - عالم الصناعة لتجارة الكهرباء','Renewable Energy & Solar Systems in Taiz, Yemen - Alam Al-Sinaa for Electricity Trading','منظومات طاقة شمسية متكاملة، ألواح شمسية، انفرترات أونجريد وأوف جريد وهيبرد، منظمات جهد وكيبلات — حلول طاقة متجددة للمنازل والمصانع والمزارع في اليمن.','Complete solar power systems, solar panels, on-grid/off-grid and hybrid inverters, voltage regulators and cables — renewable energy for homes, factories and farms in Yemen.')
ON DUPLICATE KEY UPDATE `parent_id`=NULL,`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (NULL,'المولدات الكهربائية وقطع غيارها','Generators & Spare Parts','generators-spare-parts','مولدات كهرباء بجميع القدرات وقطع غيار المولدات الكهربائية والمعدات الزراعية الأصلية: فلاتر، شمعات، منظمات AVR، كروت تحكم وغيرها.','Electric generators of all capacities plus genuine spare parts for generators and agricultural equipment: filters, AVRs, control modules and more.','power',2,1,'المولدات الكهربائية وقطع غيارها في تعز - عالم الصناعة لتجارة الكهرباء','Generators & Spare Parts in Taiz, Yemen - Alam Al-Sinaa for Electricity Trading','مولدات كهرباء بجميع القدرات وقطع غيار المولدات الكهربائية والمعدات الزراعية الأصلية: فلاتر، شمعات، منظمات AVR، كروت تحكم وغيرها.','Electric generators of all capacities plus genuine spare parts for generators and agricultural equipment: filters, AVRs, control modules and more.')
ON DUPLICATE KEY UPDATE `parent_id`=NULL,`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (NULL,'التحكم الصناعي وأجهزة PLC','Industrial Control & PLC','industrial-control','أجهزة تحكم PLC، انفرترات المحركات VFD، حساسات حرارة، أجهزة قياس كهربائية وأنظمة التحكم الصناعي لخطوط الإنتاج.','PLC controllers, motor drives (VFD), temperature sensors, electrical measuring instruments and industrial control systems for production lines.','cpu',3,1,'التحكم الصناعي وأجهزة PLC في تعز - عالم الصناعة لتجارة الكهرباء','Industrial Control & PLC in Taiz, Yemen - Alam Al-Sinaa for Electricity Trading','أجهزة تحكم PLC، انفرترات المحركات VFD، حساسات حرارة، أجهزة قياس كهربائية وأنظمة التحكم الصناعي لخطوط الإنتاج.','PLC controllers, motor drives (VFD), temperature sensors, electrical measuring instruments and industrial control systems for production lines.')
ON DUPLICATE KEY UPDATE `parent_id`=NULL,`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (NULL,'مستلزمات وقطع كهربائية','Electrical Supplies & Parts','electrical-supplies','قطع ومستلزمات الكهرباء: لوحات كهرباء (طبلونات)، مفاتيح وقواطع، كونتاكتورات، قطع إلكترونيات وسخانات ومقاومات صناعية.','Electrical parts and supplies: distribution panels, switches and breakers, contactors, electronic components, heaters and industrial heating elements.','plug',4,1,'مستلزمات وقطع كهربائية في تعز - عالم الصناعة لتجارة الكهرباء','Electrical Supplies & Parts in Taiz, Yemen - Alam Al-Sinaa for Electricity Trading','قطع ومستلزمات الكهرباء: لوحات كهرباء (طبلونات)، مفاتيح وقواطع، كونتاكتورات، قطع إلكترونيات وسخانات ومقاومات صناعية.','Electrical parts and supplies: distribution panels, switches and breakers, contactors, electronic components, heaters and industrial heating elements.')
ON DUPLICATE KEY UPDATE `parent_id`=NULL,`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (NULL,'مستلزمات المصانع وخطوط الإنتاج','Factory & Production Line Supplies','factory-supplies','مستلزمات مصانع البلاستيك ومكائن الفلم والأكياس، خطوط التعبئة والتغليف، مصانع الأغذية، مصانع البلك والأسمنت والكسارات، المخابز والأفران الآلية وأدوات السلامة.','Supplies for plastic factories, film & bag machines, packaging lines, food factories, block/cement plants & crushers, automatic bakeries and safety equipment.','factory',5,1,'مستلزمات المصانع وخطوط الإنتاج في تعز - عالم الصناعة لتجارة الكهرباء','Factory & Production Line Supplies in Taiz, Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات مصانع البلاستيك ومكائن الفلم والأكياس، خطوط التعبئة والتغليف، مصانع الأغذية، مصانع البلك والأسمنت والكسارات، المخابز والأفران الآلية وأدوات السلامة.','Supplies for plastic factories, film & bag machines, packaging lines, food factories, block/cement plants & crushers, automatic bakeries and safety equipment.')
ON DUPLICATE KEY UPDATE `parent_id`=NULL,`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (NULL,'المحركات الكهربائية والتبريد','Electric Motors & Refrigeration','motors-cooling','محركات كهربائية صناعية ومستلزماتها، ومستلزمات التبريد والتكييف الصناعي: كمبروسرات، مراوح، حساسات وثرموستات.','Industrial electric motors and accessories, plus refrigeration & cooling supplies: compressors, fans, sensors and thermostats.','rotate-cw',6,1,'المحركات الكهربائية والتبريد في تعز - عالم الصناعة لتجارة الكهرباء','Electric Motors & Refrigeration in Taiz, Yemen - Alam Al-Sinaa for Electricity Trading','محركات كهربائية صناعية ومستلزماتها، ومستلزمات التبريد والتكييف الصناعي: كمبروسرات، مراوح، حساسات وثرموستات.','Industrial electric motors and accessories, plus refrigeration & cooling supplies: compressors, fans, sensors and thermostats.')
ON DUPLICATE KEY UPDATE `parent_id`=NULL,`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (NULL,'الإنارة الحديثة وكشافات الطاقة','Modern Lighting & Solar Floodlights','lighting-solutions','أدوات إنارة حديثة LED، كشافات طاقة شمسية وكشافات صناعية للمصانع والشوارع والمستودعات.','Modern LED lighting, solar floodlights and industrial floodlights for factories, streets and warehouses.','lightbulb',7,1,'الإنارة الحديثة وكشافات الطاقة في تعز - عالم الصناعة لتجارة الكهرباء','Modern Lighting & Solar Floodlights in Taiz, Yemen - Alam Al-Sinaa for Electricity Trading','أدوات إنارة حديثة LED، كشافات طاقة شمسية وكشافات صناعية للمصانع والشوارع والمستودعات.','Modern LED lighting, solar floodlights and industrial floodlights for factories, streets and warehouses.')
ON DUPLICATE KEY UPDATE `parent_id`=NULL,`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

-- ---------- 2. Sub-sections ----------
SET @parent := (SELECT `id` FROM `categories` WHERE `slug` = 'power-systems' LIMIT 1);
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'المنظومات والألواح الشمسية','Solar Systems & Panels','solar-systems','منظومات شمسية متكاملة وألواح شمسية بقدرات مختلفة للمنازل والمزارع والمصانع مع التصميم والتركيب.','Complete solar systems and panels for homes, farms and factories, including design and installation.','sun',1,1,'المنظومات والألواح الشمسية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Solar Systems & Panels in Yemen - Alam Al-Sinaa for Electricity Trading','منظومات شمسية متكاملة وألواح شمسية بقدرات مختلفة للمنازل والمزارع والمصانع مع التصميم والتركيب.','Complete solar systems and panels for homes, farms and factories, including design and installation.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'انفرترات أونجريد وأوف جريد','On-Grid & Off-Grid Inverters','inverters','انفرترات أونجريد (On-Grid) وأوف جريد (Off-Grid) بماركات عالمية وقدرات متعددة.','On-grid and off-grid inverters from international brands in multiple ratings.','toggle-right',2,1,'انفرترات أونجريد وأوف جريد في تعز واليمن - عالم الصناعة لتجارة الكهرباء','On-Grid & Off-Grid Inverters in Yemen - Alam Al-Sinaa for Electricity Trading','انفرترات أونجريد (On-Grid) وأوف جريد (Off-Grid) بماركات عالمية وقدرات متعددة.','On-grid and off-grid inverters from international brands in multiple ratings.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'انفرترات هيبرد','Hybrid Inverters','hybrid-inverters','انفرترات هيبرد (Hybrid) تعمل مع الشبكة والبطاريات والألواح في آن واحد.','Hybrid inverters that work with the grid, batteries and panels together.','battery-charging',3,1,'انفرترات هيبرد في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Hybrid Inverters in Yemen - Alam Al-Sinaa for Electricity Trading','انفرترات هيبرد (Hybrid) تعمل مع الشبكة والبطاريات والألواح في آن واحد.','Hybrid inverters that work with the grid, batteries and panels together.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'منظمات الجهد','Voltage Regulators','voltage-regulators','منظمات جهد أوتوماتيكية (ستبلايزر) لحماية الأجهزة والمعدات بقدرات متنوعة.','Automatic voltage regulators (stabilizers) protecting appliances and equipment.','activity',4,1,'منظمات الجهد في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Voltage Regulators in Yemen - Alam Al-Sinaa for Electricity Trading','منظمات جهد أوتوماتيكية (ستبلايزر) لحماية الأجهزة والمعدات بقدرات متنوعة.','Automatic voltage regulators (stabilizers) protecting appliances and equipment.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'الكيبلات والأسلاك','Cables & Wires','cables','كيبلات وأسلاك كهربائية وصناعية وكيبلات الطاقة الشمسية بجميع المقاسات.','Electrical, industrial and solar cables and wires in all sizes.','git-branch',5,1,'الكيبلات والأسلاك في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Cables & Wires in Yemen - Alam Al-Sinaa for Electricity Trading','كيبلات وأسلاك كهربائية وصناعية وكيبلات الطاقة الشمسية بجميع المقاسات.','Electrical, industrial and solar cables and wires in all sizes.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

SET @parent := (SELECT `id` FROM `categories` WHERE `slug` = 'generators-spare-parts' LIMIT 1);
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مولدات الكهرباء','Electric Generators','electric-generators','مولدات كهربائية ديزل وبنزين بقدرات متنوعة للمنازل والمصانع والمنشآت.','Diesel and petrol generators of various capacities for homes, factories and facilities.','power',1,1,'مولدات الكهرباء في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Electric Generators in Yemen - Alam Al-Sinaa for Electricity Trading','مولدات كهربائية ديزل وبنزين بقدرات متنوعة للمنازل والمصانع والمنشآت.','Diesel and petrol generators of various capacities for homes, factories and facilities.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'قطع غيار المولدات الكهربائية','Generator Spare Parts','generator-parts','قطع غيار أصلية لجميع أنواع المولدات: فلاتر، منظمات AVR، كروت تحكم، حساسات ودينموهات.','Genuine parts for all generator types: filters, AVRs, controllers, sensors and alternators.','settings',2,1,'قطع غيار المولدات الكهربائية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Generator Spare Parts in Yemen - Alam Al-Sinaa for Electricity Trading','قطع غيار أصلية لجميع أنواع المولدات: فلاتر، منظمات AVR، كروت تحكم، حساسات ودينموهات.','Genuine parts for all generator types: filters, AVRs, controllers, sensors and alternators.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'قطع غيار المعدات الزراعية','Agricultural Equipment Parts','agricultural-equipment-parts','قطع غيار المعدات الزراعية والمضخات والغطاسات ومستلزماتها الكهربائية.','Spare parts for agricultural equipment, pumps and submersibles and their electrical accessories.','droplet',3,1,'قطع غيار المعدات الزراعية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Agricultural Equipment Parts in Yemen - Alam Al-Sinaa for Electricity Trading','قطع غيار المعدات الزراعية والمضخات والغطاسات ومستلزماتها الكهربائية.','Spare parts for agricultural equipment, pumps and submersibles and their electrical accessories.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

SET @parent := (SELECT `id` FROM `categories` WHERE `slug` = 'industrial-control' LIMIT 1);
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'أجهزة تحكم PLC','PLC Controllers','plc-controllers','أجهزة تحكم منطقي مبرمج PLC وشاشات HMI ووحدات إدخال وإخراج بماركات عالمية.','PLC controllers, HMI panels and I/O modules from international brands.','server',1,1,'أجهزة تحكم PLC في تعز واليمن - عالم الصناعة لتجارة الكهرباء','PLC Controllers in Yemen - Alam Al-Sinaa for Electricity Trading','أجهزة تحكم منطقي مبرمج PLC وشاشات HMI ووحدات إدخال وإخراج بماركات عالمية.','PLC controllers, HMI panels and I/O modules from international brands.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'انفرترات المحركات (VFD)','Motor Inverters (VFD)','inverter-motors','انفرترات تحكم بسرعة المحركات (VFD / درايف) لجميع القدرات وتطبيقات المضخات والمصانع.','Variable frequency drives for motors of all ratings, pumps and factory applications.','refresh-cw',2,1,'انفرترات المحركات (VFD) في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Motor Inverters (VFD) in Yemen - Alam Al-Sinaa for Electricity Trading','انفرترات تحكم بسرعة المحركات (VFD / درايف) لجميع القدرات وتطبيقات المضخات والمصانع.','Variable frequency drives for motors of all ratings, pumps and factory applications.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'حساسات الحرارة','Temperature Sensors','temperature-sensors','حساسات حرارة صناعية (PT100، ثيرموكبل) ومنظمات حرارة دقيقة.','Industrial temperature sensors (PT100, thermocouples) and precise temperature controllers.','thermometer',3,1,'حساسات الحرارة في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Temperature Sensors in Yemen - Alam Al-Sinaa for Electricity Trading','حساسات حرارة صناعية (PT100، ثيرموكبل) ومنظمات حرارة دقيقة.','Industrial temperature sensors (PT100, thermocouples) and precise temperature controllers.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'أجهزة القياس الكهربائية','Electrical Measuring Instruments','measuring-instruments','أجهزة قياس كهربائية: أفوميترات، أجهزة كلامب، عدادات كهرباء وأجهزة قياس العزل.','Electrical measuring instruments: multimeters, clamp meters, energy meters and insulation testers.','gauge',4,1,'أجهزة القياس الكهربائية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Electrical Measuring Instruments in Yemen - Alam Al-Sinaa for Electricity Trading','أجهزة قياس كهربائية: أفوميترات، أجهزة كلامب، عدادات كهرباء وأجهزة قياس العزل.','Electrical measuring instruments: multimeters, clamp meters, energy meters and insulation testers.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'أنظمة التحكم الصناعي','Industrial Control Systems','control-systems','أنظمة تحكم صناعي متكاملة وقطع التحكم لخطوط الإنتاج والمعدات.','Integrated industrial control systems and control parts for production lines.','sliders',5,1,'أنظمة التحكم الصناعي في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Industrial Control Systems in Yemen - Alam Al-Sinaa for Electricity Trading','أنظمة تحكم صناعي متكاملة وقطع التحكم لخطوط الإنتاج والمعدات.','Integrated industrial control systems and control parts for production lines.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

SET @parent := (SELECT `id` FROM `categories` WHERE `slug` = 'electrical-supplies' LIMIT 1);
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'لوحات الكهرباء (طبلونات)','Electrical Panels (Switchboards)','electrical-panels','لوحات كهرباء توزيع وتحكم (طبلونات) جاهزة وحسب الطلب.','Ready-made and custom distribution & control panels (switchboards).','layout',1,1,'لوحات الكهرباء (طبلونات) في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Electrical Panels (Switchboards) in Yemen - Alam Al-Sinaa for Electricity Trading','لوحات كهرباء توزيع وتحكم (طبلونات) جاهزة وحسب الطلب.','Ready-made and custom distribution & control panels (switchboards).')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مفاتيح وقواطع الكهرباء','Electrical Switches & Breakers','protection-switches','مفاتيح كهرباء، قواطع، كونتاكتورات وأجهزة حماية من التيار الزائد وهبوط الجهد.','Switches, circuit breakers, contactors and over-current/under-voltage protection.','shield',2,1,'مفاتيح وقواطع الكهرباء في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Electrical Switches & Breakers in Yemen - Alam Al-Sinaa for Electricity Trading','مفاتيح كهرباء، قواطع، كونتاكتورات وأجهزة حماية من التيار الزائد وهبوط الجهد.','Switches, circuit breakers, contactors and over-current/under-voltage protection.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'قطع ومستلزمات الكهرباء','Electrical Accessories','electrical-accessories','قطع ومستلزمات كهرباء عامة: أفياش، مرابط، أطراف كيبلات وملحقات التمديدات.','General electrical accessories: sockets, terminals, lugs and installation fittings.','plug',3,1,'قطع ومستلزمات الكهرباء في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Electrical Accessories in Yemen - Alam Al-Sinaa for Electricity Trading','قطع ومستلزمات كهرباء عامة: أفياش، مرابط، أطراف كيبلات وملحقات التمديدات.','General electrical accessories: sockets, terminals, lugs and installation fittings.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'قطع الإلكترونيات','Electronic Components','electronic-components','قطع إلكترونيات صناعية وكروت وبوردات وملحقاتها بجودة عالية.','Industrial electronic components, boards and accessories.','radio',4,1,'قطع الإلكترونيات في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Electronic Components in Yemen - Alam Al-Sinaa for Electricity Trading','قطع إلكترونيات صناعية وكروت وبوردات وملحقاتها بجودة عالية.','Industrial electronic components, boards and accessories.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'السخانات والمقاومات الحرارية','Heaters & Heating Elements','heaters','سخانات صناعية ومنزلية ومقاومات حرارية للمكائن والأفران وخطوط الإنتاج.','Industrial and domestic heaters and heating elements for machines, ovens and lines.','flame',5,1,'السخانات والمقاومات الحرارية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Heaters & Heating Elements in Yemen - Alam Al-Sinaa for Electricity Trading','سخانات صناعية ومنزلية ومقاومات حرارية للمكائن والأفران وخطوط الإنتاج.','Industrial and domestic heaters and heating elements for machines, ovens and lines.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

SET @parent := (SELECT `id` FROM `categories` WHERE `slug` = 'factory-supplies' LIMIT 1);
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مستلزمات مصانع البلاستيك','Plastic Factory Supplies','plastic-factory','مستلزمات وقطع غيار مصانع البلاستيك والسخانات والحساسات والكنترول.','Supplies and spare parts for plastic factories, heaters, sensors and controls.','box',1,1,'مستلزمات مصانع البلاستيك في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Plastic Factory Supplies in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات وقطع غيار مصانع البلاستيك والسخانات والحساسات والكنترول.','Supplies and spare parts for plastic factories, heaters, sensors and controls.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مكائن الفلم والأكياس','Film & Bag Machines','film-bag-machines','مستلزمات وقطع مكائن الفلم والأكياس البلاستيكية.','Parts and supplies for plastic film and bag machines.','film',2,1,'مكائن الفلم والأكياس في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Film & Bag Machines in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات وقطع مكائن الفلم والأكياس البلاستيكية.','Parts and supplies for plastic film and bag machines.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'خطوط إنتاج التعبئة والتغليف','Filling & Packaging Lines','packaging-lines','مستلزمات مصانع وخطوط إنتاج التعبئة والتغليف.','Supplies for filling and packaging factories and production lines.','package',3,1,'خطوط إنتاج التعبئة والتغليف في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Filling & Packaging Lines in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات مصانع وخطوط إنتاج التعبئة والتغليف.','Supplies for filling and packaging factories and production lines.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مستلزمات مصانع الأغذية','Food Factory Supplies','food-processing','مستلزمات وقطع غيار مصانع الأغذية وخطوط الإنتاج الغذائي.','Supplies and spare parts for food factories and food production lines.','coffee',4,1,'مستلزمات مصانع الأغذية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Food Factory Supplies in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات وقطع غيار مصانع الأغذية وخطوط الإنتاج الغذائي.','Supplies and spare parts for food factories and food production lines.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مستلزمات مصانع البلك','Block Factory Supplies','brick-factory','مستلزمات ومعدات مصانع البلك والطوب.','Supplies and equipment for block and brick factories.','square',5,1,'مستلزمات مصانع البلك في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Block Factory Supplies in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات ومعدات مصانع البلك والطوب.','Supplies and equipment for block and brick factories.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مستلزمات مصانع الأسمنت','Cement Factory Supplies','cement-factory','مستلزمات وقطع غيار مصانع الأسمنت.','Supplies and spare parts for cement factories.','database',6,1,'مستلزمات مصانع الأسمنت في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Cement Factory Supplies in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات وقطع غيار مصانع الأسمنت.','Supplies and spare parts for cement factories.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مستلزمات الكسارات','Crusher Supplies','crusher-equipment','مستلزمات ومعدات الكسارات والمحاجر.','Crusher and quarry supplies and equipment.','aperture',7,1,'مستلزمات الكسارات في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Crusher Supplies in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات ومعدات الكسارات والمحاجر.','Crusher and quarry supplies and equipment.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مستلزمات المخابز والأفران الآلية','Bakery & Automatic Oven Supplies','bakery-equipment','مستلزمات المخابز والأفران الآلية: سخانات، حساسات، لوحات تحكم ومحركات.','Supplies for bakeries and automatic ovens: heaters, sensors, control panels and motors.','flame',8,1,'مستلزمات المخابز والأفران الآلية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Bakery & Automatic Oven Supplies in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات المخابز والأفران الآلية: سخانات، حساسات، لوحات تحكم ومحركات.','Supplies for bakeries and automatic ovens: heaters, sensors, control panels and motors.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'قطع ومعدات صناعية','Industrial Parts & Equipment','industrial-equipment','قطع صناعية ومعدات عامة ومتخصصة للمصانع وخطوط الإنتاج.','General and specialised industrial parts and equipment for factories and lines.','tool',9,1,'قطع ومعدات صناعية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Industrial Parts & Equipment in Yemen - Alam Al-Sinaa for Electricity Trading','قطع صناعية ومعدات عامة ومتخصصة للمصانع وخطوط الإنتاج.','General and specialised industrial parts and equipment for factories and lines.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'أدوات السلامة والأمان','Safety & Security Equipment','safety-equipment','أدوات السلامة والأمان المهني للمصانع ومواقع العمل.','Occupational safety and security equipment for factories and work sites.','hard-hat',10,1,'أدوات السلامة والأمان في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Safety & Security Equipment in Yemen - Alam Al-Sinaa for Electricity Trading','أدوات السلامة والأمان المهني للمصانع ومواقع العمل.','Occupational safety and security equipment for factories and work sites.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

SET @parent := (SELECT `id` FROM `categories` WHERE `slug` = 'motors-cooling' LIMIT 1);
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'المحركات الكهربائية','Electric Motors','electric-motors','محركات كهربائية صناعية بجميع القدرات ومستلزماتها: بلي، مكثفات، مراوح.','Industrial electric motors of all ratings and accessories: bearings, capacitors, fans.','rotate-cw',1,1,'المحركات الكهربائية في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Electric Motors in Yemen - Alam Al-Sinaa for Electricity Trading','محركات كهربائية صناعية بجميع القدرات ومستلزماتها: بلي، مكثفات، مراوح.','Industrial electric motors of all ratings and accessories: bearings, capacitors, fans.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'مستلزمات التبريد','Refrigeration Supplies','refrigeration','مستلزمات التبريد والتكييف: كمبروسرات، ثرموستات، مراوح وحساسات.','Refrigeration and AC supplies: compressors, thermostats, fans and sensors.','snowflake',2,1,'مستلزمات التبريد في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Refrigeration Supplies in Yemen - Alam Al-Sinaa for Electricity Trading','مستلزمات التبريد والتكييف: كمبروسرات، ثرموستات، مراوح وحساسات.','Refrigeration and AC supplies: compressors, thermostats, fans and sensors.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

SET @parent := (SELECT `id` FROM `categories` WHERE `slug` = 'lighting-solutions' LIMIT 1);
INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'أدوات الإنارة الحديثة','Modern Lighting','modern-lighting','أدوات إنارة LED حديثة للمنازل والمحلات والمصانع.','Modern LED lighting for homes, shops and factories.','sunrise',1,1,'أدوات الإنارة الحديثة في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Modern Lighting in Yemen - Alam Al-Sinaa for Electricity Trading','أدوات إنارة LED حديثة للمنازل والمحلات والمصانع.','Modern LED lighting for homes, shops and factories.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

INSERT INTO `categories` (`parent_id`,`name_ar`,`name_en`,`slug`,`description_ar`,`description_en`,`icon`,`sort_order`,`is_active`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`)
VALUES (@parent,'كشافات الطاقة','Solar & Power Floodlights','power-floodlights','كشافات طاقة شمسية وكشافات LED عالية القدرة للإنارة الخارجية.','Solar floodlights and high-power LED floodlights for outdoor lighting.','sun',2,1,'كشافات الطاقة في تعز واليمن - عالم الصناعة لتجارة الكهرباء','Solar & Power Floodlights in Yemen - Alam Al-Sinaa for Electricity Trading','كشافات طاقة شمسية وكشافات LED عالية القدرة للإنارة الخارجية.','Solar floodlights and high-power LED floodlights for outdoor lighting.')
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`name_ar`=VALUES(`name_ar`),`name_en`=VALUES(`name_en`),`description_ar`=VALUES(`description_ar`),`description_en`=VALUES(`description_en`),`icon`=VALUES(`icon`),`sort_order`=VALUES(`sort_order`),`deleted_at`=NULL,`meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

-- ---------- 3. Data fixes ----------
UPDATE `products` SET `category_id` = (SELECT `id` FROM (SELECT `id` FROM `categories` WHERE `slug`='plc-controllers') t)
WHERE `slug` = 'plc-s7-1200';

-- ---------- 4. Company data, announcement bar & default SEO ----------
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('site_name_alt_ar','عالم الصناعة للكهرباء والمستلزمات الصناعية','general') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('site_name_alt_en','Alam Al-Sinaa for Electrical & Industrial Supplies','general') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('company_address_ar','تعز - الحوبان - مقابل البيت الحديث - أرفع من الجوبي للمفروشات','contact') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('company_address_en','Taiz - Al-Hawban - Opposite Al-Bayt Al-Hadeeth - just past Al-Jawbi Furniture','contact') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('whatsapp_primary','781067910','contact') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('whatsapp_secondary_1','734009848','contact') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('whatsapp_secondary_2','783678560','contact') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('working_hours_ar','السبت – الخميس: 8:00 صباحاً – 10:00 مساءً، الجمعة: 2:00 ظهراً – 10:00 مساءً','contact') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('working_hours_en','Saturday – Thursday: 8:00 AM – 10:00 PM, Friday: 2:00 PM – 10:00 PM','contact') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('ad_bar_items_ar','⚡ مولدات وقطع غيار المولدات والمعدات الزراعية|☀️ منظومات شمسية وانفرترات أونجريد وأوف جريد وهيبرد|🏭 مستلزمات المصانع وخطوط الإنتاج والتعبئة والتغليف|🎛️ أجهزة تحكم PLC وأجهزة قياس وحساسات حرارة|🚚 توصيل لجميع المحافظات — واتساب 781067910','general') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('ad_bar_items_en','⚡ Generators, generator & agricultural spare parts|☀️ Solar systems and on-grid / off-grid / hybrid inverters|🏭 Factory, production-line & packaging supplies|🎛️ PLC controllers, measuring instruments & temperature sensors|🚚 Delivery to all governorates — WhatsApp +967 781 067 910','general') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('default_meta_title_ar','عالم الصناعة لتجارة الكهرباء | مستلزمات كهربائية وصناعية ومولدات وطاقة شمسية في تعز','seo') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('default_meta_title_en','Alam Al-Sinaa for Electricity Trading | Electrical & Industrial Supplies, Generators & Solar in Taiz','seo') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('default_meta_description_ar','عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية) في تعز - الحوبان: بيع مستلزمات كهربائية وصناعية، مولدات وقطع غيار المولدات والمعدات الزراعية، منظومات شمسية وانفرترات أونجريد وأوف جريد وهيبرد، أجهزة تحكم PLC وأجهزة قياس، لوحات كهرباء، كيبلات، منظمات، ومستلزمات المصانع وخطوط الإنتاج. توصيل لجميع المحافظات.','seo') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('default_meta_description_en','Alam Al-Sinaa for Electricity Trading in Taiz, Yemen: electrical & industrial supplies, generators and generator/agricultural spare parts, solar systems, on-grid/off-grid/hybrid inverters, PLC controllers, measuring instruments, panels, cables, regulators and factory/production-line supplies. Delivery to all governorates.','seo') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('default_meta_keywords_ar','عالم الصناعة، عالم الصناعة لتجارة الكهرباء، عالم الصناعة للكهرباء والمستلزمات الصناعية، بيع مستلزمات كهربائية، بيع مستلزمات صناعية، بيع قطع غيار المولدات الكهربائية والمعدات الزراعية، المنظومات الشمسية، قطع مستلزمات كهرباء، الطاقة المتجددة، تحكم صناعي، قطع صناعية، قطع غيار مولدات كهرباء، مولدات كهرباء، اجهزة قياس كهربائية، انفرترات اونجريد اوف جريد، انفرترات محركات، انفرترات هيبرد، مستلزمات المصانع، مستلزمات مصانع البلاستيك، مكائن الفلم والاكياس، مستلزمات خطوط انتاج التعبئة والتغليف، مستلزمات مصانع الأغذية، ادوات السلامة والامان، مصانع البلك والاسمنت والكسارات، مستلزمات المخابز والافران الالية، مستلزمات المحركات الكهربائية والتبريد، منظمات، كيبلات، اجهزة تحكم PLC، مستلزمات المصانع وخطوط الانتاج والمعدات، لوحات كهرباء طبلونات، مفاتيح كهرباء، قطع الكترونيات، مستلزمات تبريد، سخانات، ادوات انارة حديثة، كشافات طاقة، حساسات حرارة، كهرباء تعز، الحوبان، اليمن','seo') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
INSERT INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES ('default_meta_keywords_en','Alam Al-Sinaa, Alam Al-Sinaa for Electricity Trading, electrical supplies Yemen, industrial supplies Taiz, generator spare parts, agricultural equipment spare parts, solar systems Yemen, renewable energy, industrial control, PLC controllers, on-grid inverters, off-grid inverters, hybrid inverters, motor inverters VFD, electrical measuring instruments, voltage regulators, cables, electrical panels, switchboards, electrical switches, electronic components, refrigeration supplies, heaters, LED lighting, solar floodlights, temperature sensors, plastic factory supplies, film and bag machines, packaging lines, food factory supplies, safety equipment, block cement crusher supplies, bakery oven supplies, electric motors','seo') ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);

-- ---------- 5. Per-page SEO ----------
INSERT INTO `seo_settings` (`page_key`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`,`og_type`,`twitter_card`,`robots_meta`)
VALUES ('home','عالم الصناعة لتجارة الكهرباء | مستلزمات كهربائية وصناعية في تعز','Alam Al-Sinaa | Electrical & Industrial Supplies in Taiz, Yemen','عالم الصناعة لتجارة الكهرباء (عالم الصناعة للكهرباء والمستلزمات الصناعية) في تعز - الحوبان: بيع مستلزمات كهربائية وصناعية، مولدات وقطع غيار المولدات والمعدات الزراعية، منظومات شمسية وانفرترات أونجريد وأوف جريد وهيبرد، أجهزة تحكم PLC وأجهزة قياس، لوحات كهرباء، كيبلات، منظمات، ومستلزمات المصانع وخطوط الإنتاج. توصيل لجميع المحافظات.','Alam Al-Sinaa for Electricity Trading in Taiz, Yemen: electrical & industrial supplies, generators and generator/agricultural spare parts, solar systems, on-grid/off-grid/hybrid inverters, PLC controllers, measuring instruments, panels, cables, regulators and factory/production-line supplies. Delivery to all governorates.','website','summary_large_image','index,follow')
ON DUPLICATE KEY UPDATE `meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);
INSERT INTO `seo_settings` (`page_key`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`,`og_type`,`twitter_card`,`robots_meta`)
VALUES ('about','من نحن | عالم الصناعة للكهرباء والمستلزمات الصناعية','About Us | Alam Al-Sinaa for Electricity Trading','تعرف على عالم الصناعة لتجارة الكهرباء في تعز - الحوبان: خبرة في تجارة المستلزمات الكهربائية والصناعية، الطاقة المتجددة، المولدات وقطع غيارها، ومستلزمات المصانع وخطوط الإنتاج.','About Alam Al-Sinaa in Taiz, Yemen: experienced suppliers of electrical & industrial supplies, renewable energy, generators & spare parts and factory supplies.','website','summary_large_image','index,follow')
ON DUPLICATE KEY UPDATE `meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);
INSERT INTO `seo_settings` (`page_key`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`,`og_type`,`twitter_card`,`robots_meta`)
VALUES ('products','المنتجات | مولدات، انفرترات، منظومات شمسية، PLC ومستلزمات مصانع - عالم الصناعة','Products | Generators, Inverters, Solar, PLC & Factory Supplies - Alam Al-Sinaa','تسوق مستلزمات كهربائية وصناعية: مولدات وقطع غيارها، انفرترات أونجريد وأوف جريد وهيبرد، انفرترات محركات، منظومات شمسية، منظمات، كيبلات، لوحات كهرباء، مفاتيح، أجهزة قياس، حساسات حرارة ومستلزمات المصانع.','Shop electrical & industrial supplies: generators & parts, on-grid/off-grid/hybrid inverters, VFDs, solar systems, regulators, cables, panels, switches, measuring instruments, sensors and factory supplies.','website','summary_large_image','index,follow')
ON DUPLICATE KEY UPDATE `meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);
INSERT INTO `seo_settings` (`page_key`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`,`og_type`,`twitter_card`,`robots_meta`)
VALUES ('services','خدماتنا | تركيب منظومات شمسية وصيانة مولدات ولوحات تحكم - عالم الصناعة','Services | Solar Installation, Generator Maintenance & Control Panels - Alam Al-Sinaa','خدمات فنية من عالم الصناعة: استشارات وتصميم منظومات الطاقة الشمسية، تركيب وتشغيل المولدات ولوحات التحكم وPLC، صيانة المصانع وخطوط الإنتاج وتوفير قطع الغيار الأصلية.','Technical services: solar system design & installation, generator and control panel commissioning, PLC programming, factory maintenance and genuine spare parts.','website','summary_large_image','index,follow')
ON DUPLICATE KEY UPDATE `meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);
INSERT INTO `seo_settings` (`page_key`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`,`og_type`,`twitter_card`,`robots_meta`)
VALUES ('portfolio','معرض الأعمال | مشاريع كهربائية وصناعية وطاقة شمسية - عالم الصناعة','Portfolio | Electrical, Industrial & Solar Projects - Alam Al-Sinaa','مشاريع نفذها عالم الصناعة: منظومات طاقة شمسية، تركيب مولدات، لوحات تحكم وتوريدات مصانع وخطوط إنتاج في اليمن.','Projects delivered by Alam Al-Sinaa: solar systems, generator installations, control panels and factory supplies across Yemen.','website','summary_large_image','index,follow')
ON DUPLICATE KEY UPDATE `meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);
INSERT INTO `seo_settings` (`page_key`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`,`og_type`,`twitter_card`,`robots_meta`)
VALUES ('contact','اتصل بنا | عالم الصناعة - تعز الحوبان - واتساب 781067910','Contact | Alam Al-Sinaa - Taiz Al-Hawban - WhatsApp +967 781067910','تواصل مع عالم الصناعة لتجارة الكهرباء: تعز - الحوبان - مقابل البيت الحديث - أرفع من الجوبي للمفروشات. واتساب: 781067910 - 734009848 - 783678560.','Contact Alam Al-Sinaa: Taiz - Al-Hawban - opposite Al-Bayt Al-Hadeeth. WhatsApp: +967 781067910, 734009848, 783678560.','website','summary_large_image','index,follow')
ON DUPLICATE KEY UPDATE `meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);
INSERT INTO `seo_settings` (`page_key`,`meta_title_ar`,`meta_title_en`,`meta_description_ar`,`meta_description_en`,`og_type`,`twitter_card`,`robots_meta`)
VALUES ('faq','الأسئلة الشائعة | عالم الصناعة لتجارة الكهرباء','FAQ | Alam Al-Sinaa for Electricity Trading','إجابات عن الأسئلة الشائعة حول المنتجات الكهربائية والصناعية، عروض الأسعار، التوصيل للمحافظات، الضمان والتركيب.','Answers about our electrical & industrial products, quotes, delivery, warranty and installation.','website','summary_large_image','index,follow')
ON DUPLICATE KEY UPDATE `meta_title_ar`=VALUES(`meta_title_ar`),`meta_title_en`=VALUES(`meta_title_en`),`meta_description_ar`=VALUES(`meta_description_ar`),`meta_description_en`=VALUES(`meta_description_en`);

-- ---------- 6. Robot & bubbles defaults (only if not set yet) ----------
INSERT IGNORE INTO `site_settings` (`setting_key`,`setting_value`,`setting_group`) VALUES
('ai_bubbles_enabled','1','ai'),('ai_bubbles_per_page','3','ai'),('ai_bubbles_per_session','10','ai'),
('ai_bubbles_first_delay','4','ai'),('ai_bubbles_cooldown','22','ai'),
('ai_robot_name_ar','فولت · المساعد الذكي','ai'),('ai_robot_name_en','Volt · Smart Assistant','ai');

