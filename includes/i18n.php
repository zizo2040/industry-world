<?php
/**
 * Internationalization (i18n) System
 * Multi-language support: Arabic & English
 */

// ============================================================
// LANGUAGE MANAGEMENT
// ============================================================

/**
 * Get current language (Strictly validated against supported languages to prevent Cookie SQL Injection)
 */
function getCurrentLang(): string {
    $lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? DEFAULT_LANGUAGE;
    return in_array($lang, SUPPORTED_LANGUAGES, true) ? $lang : DEFAULT_LANGUAGE;
}

/**
 * Set language
 */
function setLanguage(string $lang): void {
    if (!in_array($lang, SUPPORTED_LANGUAGES, true)) {
        $lang = DEFAULT_LANGUAGE;
    }
    
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, [
        'expires' => time() + 86400 * 365,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Set locale
    $locales = [
        'ar' => 'ar_YE.utf8',
        'en' => 'en_US.utf8'
    ];
    setlocale(LC_ALL, $locales[$lang] ?? $locales['en']);
}

/**
 * Check if current language is Arabic
 */
function isArabic(): bool {
    return getCurrentLang() === 'ar';
}

/**
 * Get text direction
 */
function getDirection(): string {
    return isArabic() ? 'rtl' : 'ltr';
}

/**
 * Get HTML lang attribute
 */
function getHtmlLang(): string {
    return isArabic() ? 'ar-YE' : 'en';
}

/**
 * Initialize i18n
 */
function initI18n(): void {
    $lang = $_GET['lang'] ?? null;
    if ($lang && in_array($lang, SUPPORTED_LANGUAGES, true)) {
        setLanguage($lang);
    }
    
    // Ensure language is set
    if (!isset($_SESSION['lang'])) {
        setLanguage(DEFAULT_LANGUAGE);
    }
}

/**
 * Translation function (Optimized with In-Memory Caching)
 */
function __(string $key, array $params = []): string {
    static $translationsCache = [];
    $lang = getCurrentLang();
    
    // Bulk load language translations into memory cache on first call
    if (!isset($translationsCache[$lang])) {
        $translationsCache[$lang] = [];
        try {
            $rows = Database::fetchAll(
                "SELECT lang_key, value FROM translations WHERE lang = :lang",
                ['lang' => $lang]
            );
            foreach ($rows as $row) {
                $translationsCache[$lang][$row['lang_key']] = $row['value'];
            }
        } catch (Exception $e) {
            // DB query failed or table missing, fallback gracefully
        }
    }
    
    if (isset($translationsCache[$lang][$key])) {
        $text = $translationsCache[$lang][$key];
    } else {
        // Fallback to built-in translations
        $text = getBuiltinTranslation($key, $lang);
    }
    
    // Replace parameters
    if (!empty($params)) {
        foreach ($params as $placeholder => $value) {
            $text = str_replace(':' . $placeholder, $value, $text);
        }
    }
    
    return $text;
}

/**
 * Get built-in translation
 */
function getBuiltinTranslation(string $key, string $lang): string {
    $translations = [
        'ar' => [
            // Navigation
            'nav_home' => 'الرئيسية',
            'nav_about' => 'من نحن',
            'nav_services' => 'خدماتنا',
            'nav_products' => 'المنتجات',
            'nav_portfolio' => 'معرض الأعمال',
            'nav_contact' => 'اتصل بنا',
            'nav_faq' => 'الأسئلة الشائعة',
            'nav_search' => 'بحث',
            'nav_admin' => 'لوحة التحكم',
            
            // Common
            'read_more' => 'اقرأ المزيد',
            'learn_more' => 'اعرف المزيد',
            'view_details' => 'عرض التفاصيل',
            'contact_us' => 'تواصل معنا',
            'get_quote' => 'اطلب عرض سعر',
            'send' => 'إرسال',
            'submit' => 'إرسال',
            'cancel' => 'إلغاء',
            'close' => 'إغلاق',
            'save' => 'حفظ',
            'edit' => 'تعديل',
            'delete' => 'حذف',
            'add' => 'إضافة',
            'search' => 'بحث',
            'filter' => 'تصفية',
            'all' => 'الكل',
            'next' => 'التالي',
            'previous' => 'السابق',
            'back' => 'رجوع',
            'yes' => 'نعم',
            'no' => 'لا',
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'status' => 'الحالة',
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'message' => 'الرسالة',
            'subject' => 'الموضوع',
            'category' => 'الفئة',
            'description' => 'الوصف',
            'price' => 'السعر',
            'quantity' => 'الكمية',
            'date' => 'التاريخ',
            'actions' => 'الإجراءات',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
            
            // Hero
            'hero_title' => 'حلول كهربائية وصناعية متكاملة',
            'hero_subtitle' => 'شريككم الموثوق في توفير أحدث المنتجات والحلول الكهربائية والصناعية بجودة عالية وأسعار تنافسية',
            'hero_cta_primary' => 'استعرض منتجاتنا',
            'hero_cta_secondary' => 'تواصل عبر واتساب',
            
            // Features
            'feature_quality' => 'جودة عالية',
            'feature_quality_desc' => 'نقدم منتجات معتمدة من أفضل الماركات العالمية',
            'feature_expert' => 'خبرة فنية',
            'feature_expert_desc' => 'فريق هندسي متخصص لاستشاراتكم الفنية',
            'feature_support' => 'دعم متميز',
            'feature_support_desc' => 'خدمة عملاء على مدار الساعة لمساعدتكم',
            'feature_delivery' => 'توصيل سريع',
            'feature_delivery_desc' => 'نوصل طلباتكم في أسرع وقت ممكن',
            
            // Products
            'products_title' => 'منتجاتنا',
            'products_subtitle' => 'اكتشف مجموعتنا الواسعة من المنتجات الكهربائية والصناعية',
            'featured_products' => 'منتجات مميزة',
            'all_products' => 'جميع المنتجات',
            'no_products' => 'لا توجد منتجات في هذه الفئة',
            'request_quote' => 'اطلب عرض سعر',
            'specifications' => 'المواصفات التقنية',
            'related_products' => 'منتجات ذات صلة',
            
            // Portfolio
            'portfolio_title' => 'معرض أعمالنا',
            'portfolio_subtitle' => 'مشاريعنا المنفذة في مجال الحلول الكهربائية والصناعية',
            'view_project' => 'عرض المشروع',
            
            // Contact
            'contact_title' => 'اتصل بنا',
            'contact_subtitle' => 'نحن هنا لمساعدتك. تواصل معنا عبر أي من القنوات التالية',
            'contact_form_title' => 'نموذج الاتصال',
            'contact_info_title' => 'معلومات التواصل',
            'address' => 'العنوان',
            'whatsapp' => 'واتساب',
            'working_hours' => 'ساعات العمل',
            'sat_thu' => 'السبت - الخميس',
            'friday' => 'الجمعة',
            'closed' => 'مغلق',
            
            // Footer
            'quick_links' => 'روابط سريعة',
            'services' => 'خدماتنا',
            'categories' => 'الفئات',
            'follow_us' => 'تابعنا',
            'all_rights_reserved' => 'جميع الحقوق محفوظة',
            'privacy_policy' => 'سياسة الخصوصية',
            'terms_conditions' => 'الشروط والأحكام',
            
            // WhatsApp
            'whatsapp_contact' => 'تواصل عبر واتساب',
            'whatsapp_click' => 'اضغط للمحادثة',
            
            // Search
            'search_title' => 'البحث',
            'search_placeholder' => 'ابحث عن منتج أو خدمة...',
            'search_results' => 'نتائج البحث',
            'no_results' => 'لا توجد نتائج',
            
            // 404
            '404_title' => 'الصفحة غير موجودة',
            '404_message' => 'عذراً، الصفحة التي تبحث عنها غير موجودة',
            'back_home' => 'العودة للرئيسية',
            
            // FAQ
            'faq_title' => 'الأسئلة الشائعة',
            'faq_subtitle' => 'إجابات على الأسئلة الأكثر شيوعاً',
            
            // Admin
            'admin_dashboard' => 'لوحة التحكم',
            'admin_products' => 'المنتجات',
            'admin_categories' => 'الفئات',
            'admin_portfolio' => 'معرض الأعمال',
            'admin_cms' => 'إدارة المحتوى',
            'admin_media' => 'مكتبة الوسائط',
            'admin_contacts' => 'جهات الاتصال',
            'admin_seo' => 'SEO',
            'admin_settings' => 'الإعدادات',
            'admin_users' => 'المستخدمين',
            'admin_logout' => 'تسجيل الخروج',
        ],
        'en' => [
            // Navigation
            'nav_home' => 'Home',
            'nav_about' => 'About Us',
            'nav_services' => 'Our Services',
            'nav_products' => 'Products',
            'nav_portfolio' => 'Portfolio',
            'nav_contact' => 'Contact Us',
            'nav_faq' => 'FAQ',
            'nav_search' => 'Search',
            'nav_admin' => 'Admin Panel',
            
            // Common
            'read_more' => 'Read More',
            'learn_more' => 'Learn More',
            'view_details' => 'View Details',
            'contact_us' => 'Contact Us',
            'get_quote' => 'Get a Quote',
            'send' => 'Send',
            'submit' => 'Submit',
            'cancel' => 'Cancel',
            'close' => 'Close',
            'save' => 'Save',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'add' => 'Add',
            'search' => 'Search',
            'filter' => 'Filter',
            'all' => 'All',
            'next' => 'Next',
            'previous' => 'Previous',
            'back' => 'Back',
            'yes' => 'Yes',
            'no' => 'No',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'status' => 'Status',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'message' => 'Message',
            'subject' => 'Subject',
            'category' => 'Category',
            'description' => 'Description',
            'price' => 'Price',
            'quantity' => 'Quantity',
            'date' => 'Date',
            'actions' => 'Actions',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            
            // Hero
            'hero_title' => 'Integrated Electrical & Industrial Solutions',
            'hero_subtitle' => 'Your trusted partner for high-quality electrical and industrial products at competitive prices',
            'hero_cta_primary' => 'Browse Our Products',
            'hero_cta_secondary' => 'Contact via WhatsApp',
            
            // Features
            'feature_quality' => 'High Quality',
            'feature_quality_desc' => 'We offer certified products from the best international brands',
            'feature_expert' => 'Technical Expertise',
            'feature_expert_desc' => 'Specialized engineering team for your technical consultations',
            'feature_support' => 'Exceptional Support',
            'feature_support_desc' => 'Customer service available around the clock',
            'feature_delivery' => 'Fast Delivery',
            'feature_delivery_desc' => 'We deliver your orders in the shortest possible time',
            
            // Products
            'products_title' => 'Our Products',
            'products_subtitle' => 'Discover our wide range of electrical and industrial products',
            'featured_products' => 'Featured Products',
            'all_products' => 'All Products',
            'no_products' => 'No products in this category',
            'request_quote' => 'Request a Quote',
            'specifications' => 'Technical Specifications',
            'related_products' => 'Related Products',
            
            // Portfolio
            'portfolio_title' => 'Our Portfolio',
            'portfolio_subtitle' => 'Our completed projects in electrical and industrial solutions',
            'view_project' => 'View Project',
            
            // Contact
            'contact_title' => 'Contact Us',
            'contact_subtitle' => 'We are here to help. Reach us through any of the following channels',
            'contact_form_title' => 'Contact Form',
            'contact_info_title' => 'Contact Information',
            'address' => 'Address',
            'whatsapp' => 'WhatsApp',
            'working_hours' => 'Working Hours',
            'sat_thu' => 'Saturday - Thursday',
            'friday' => 'Friday',
            'closed' => 'Closed',
            
            // Footer
            'quick_links' => 'Quick Links',
            'services' => 'Our Services',
            'categories' => 'Categories',
            'follow_us' => 'Follow Us',
            'all_rights_reserved' => 'All Rights Reserved',
            'privacy_policy' => 'Privacy Policy',
            'terms_conditions' => 'Terms & Conditions',
            
            // WhatsApp
            'whatsapp_contact' => 'Contact via WhatsApp',
            'whatsapp_click' => 'Click to Chat',
            
            // Search
            'search_title' => 'Search',
            'search_placeholder' => 'Search for a product or service...',
            'search_results' => 'Search Results',
            'no_results' => 'No results found',
            
            // 404
            '404_title' => 'Page Not Found',
            '404_message' => 'Sorry, the page you are looking for does not exist',
            'back_home' => 'Back to Home',
            
            // FAQ
            'faq_title' => 'Frequently Asked Questions',
            'faq_subtitle' => 'Answers to the most common questions',
            
            // Admin
            'admin_dashboard' => 'Dashboard',
            'admin_products' => 'Products',
            'admin_categories' => 'Categories',
            'admin_portfolio' => 'Portfolio',
            'admin_cms' => 'Content Management',
            'admin_media' => 'Media Library',
            'admin_contacts' => 'Contacts',
            'admin_seo' => 'SEO',
            'admin_settings' => 'Settings',
            'admin_users' => 'Users',
            'admin_logout' => 'Logout',
        ]
    ];
    
    return $translations[$lang][$key] ?? $key;
}
