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
