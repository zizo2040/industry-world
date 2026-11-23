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
