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
