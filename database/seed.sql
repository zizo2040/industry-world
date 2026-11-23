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
