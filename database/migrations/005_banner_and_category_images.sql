-- ============================================================
-- Migration: Assign the real banner/hero/category images that were
-- delivered by the client to their matching page_images / categories
-- rows. Safe to run on the LIVE database — only UPDATEs existing rows
-- by their stable section_key / slug, does not touch or drop any
-- table, and is safe to re-run (idempotent).
--
-- Expects the image files to already be uploaded at:
--   assets/uploads/page_images/home_hero_desktop.webp
--   assets/uploads/page_images/home_hero_mobile.webp
--   assets/uploads/page_images/about_banner_desktop.webp
--   assets/uploads/page_images/about_banner_mobile.webp
--   assets/uploads/page_images/products_banner_desktop.webp
--   assets/uploads/page_images/products_banner_mobile.webp
--   assets/uploads/page_images/services_banner_desktop.webp
--   assets/uploads/page_images/services_banner_mobile.webp
--   assets/uploads/page_images/portfolio_banner_desktop.webp
--   assets/uploads/page_images/portfolio_banner_mobile.webp
--   assets/uploads/page_images/contact_banner_desktop.webp
--   assets/uploads/page_images/contact_banner_mobile.webp
--   assets/uploads/categories/power-systems.webp
--   assets/uploads/categories/industrial-control.webp
--   assets/uploads/categories/factory-supplies.webp
--   assets/uploads/categories/lighting-solutions.webp
-- ============================================================

SET NAMES utf8mb4;

-- Page banners / hero (home_hero is 1200x900 / 800x600 — 4:3, matches the
-- foreground hero image; the rest are 1920x400 / 800x300 — 4.8:1 banners).
UPDATE `page_images` SET desktop_image_path = 'page_images/home_hero_desktop.webp',      mobile_image_path = 'page_images/home_hero_mobile.webp'      WHERE section_key = 'home_hero';
UPDATE `page_images` SET desktop_image_path = 'page_images/about_banner_desktop.webp',     mobile_image_path = 'page_images/about_banner_mobile.webp'     WHERE section_key = 'about_banner';
UPDATE `page_images` SET desktop_image_path = 'page_images/products_banner_desktop.webp',  mobile_image_path = 'page_images/products_banner_mobile.webp'  WHERE section_key = 'products_banner';
UPDATE `page_images` SET desktop_image_path = 'page_images/services_banner_desktop.webp',  mobile_image_path = 'page_images/services_banner_mobile.webp'  WHERE section_key = 'services_banner';
UPDATE `page_images` SET desktop_image_path = 'page_images/portfolio_banner_desktop.webp', mobile_image_path = 'page_images/portfolio_banner_mobile.webp' WHERE section_key = 'portfolio_banner';
UPDATE `page_images` SET desktop_image_path = 'page_images/contact_banner_desktop.webp',   mobile_image_path = 'page_images/contact_banner_mobile.webp'   WHERE section_key = 'contact_banner';

-- Homepage "Product Categories" cards (868x540, one unified image for both
-- desktop and mobile, matching the .category-card-image aspect-ratio).
UPDATE `categories` SET image = 'categories/power-systems.webp'      WHERE slug = 'power-systems'      AND parent_id IS NULL;
UPDATE `categories` SET image = 'categories/industrial-control.webp' WHERE slug = 'industrial-control' AND parent_id IS NULL;
UPDATE `categories` SET image = 'categories/factory-supplies.webp'   WHERE slug = 'factory-supplies'   AND parent_id IS NULL;
UPDATE `categories` SET image = 'categories/lighting-solutions.webp' WHERE slug = 'lighting-solutions' AND parent_id IS NULL;
