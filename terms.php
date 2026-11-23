<?php
/**
 * Terms & Conditions Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();

// Get CMS page content
$cmsPage = Database::fetchOne("SELECT * FROM cms_pages WHERE page_key = 'terms' AND is_active = 1");

$pageTitle = ($lang === 'ar' ? 'الشروط والأحكام - ' : 'Terms & Conditions - ') . getSetting('site_name_' . $lang);
$pageDescription = $lang === 'ar' ? 'الشروط والأحكام لموقع عالم الصناعة' : 'Terms and Conditions for Alam Al-Sinaa website';
$canonicalUrl = url('terms.php');

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo __('terms_conditions'); ?></span>
            </nav>
            <h1 class="page-header-title"><?php echo __('terms_conditions'); ?></h1>
        </div>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width:800px;">
        <div class="reveal">
            <?php if ($cmsPage && $cmsPage['content_' . $lang]): ?>
            <div class="content-rich">
                <?php echo $cmsPage['content_' . $lang]; ?>
            </div>
            <?php else: ?>
            <div class="content-rich" style="line-height:1.8;color:var(--color-text-secondary);">
                <h2><?php echo $lang === 'ar' ? 'الشروط والأحكام' : 'Terms and Conditions'; ?></h2>
                <p><?php echo $lang === 'ar' ? 'باستخدامك لهذا الموقع، فإنك توافق على الشروط والأحكام التالية:' : 'By using this website, you agree to the following terms and conditions:'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'المنتجات والخدمات' : 'Products and Services'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'جميع المنتجات والخدمات المقدمة خاضعة للتوافر والتأكيد النهائي. نحن نبذل قصارى جهدنا لضمان دقة المعلومات المعروضة.' : 'All products and services provided are subject to availability and final confirmation. We make every effort to ensure the accuracy of displayed information.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'الأسعار' : 'Prices'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'الأسعار المعروضة قابلة للتغيير دون إشعار مسبق. يرجى التواصل معنا للحصول على عرض سعر محدث.' : 'Displayed prices are subject to change without prior notice. Please contact us for an updated quote.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'الملكية الفكرية' : 'Intellectual Property'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'جميع المحتويات على هذا الموقع محمية بموجب قوانين حقوق النشر. لا يجوز إعادة إنتاج أو توزيع أي محتوى دون إذن كتابي مسبق.' : 'All content on this website is protected by copyright laws. No content may be reproduced or distributed without prior written permission.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'تحديد المسؤولية' : 'Limitation of Liability'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'نحن غير مسؤولين عن أي أضرار مباشرة أو غير مباشرة قد تنتج عن استخدام هذا الموقع أو المنتجات والخدمات المقدمة.' : 'We are not liable for any direct or indirect damages that may result from the use of this website or the products and services provided.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'التعديلات' : 'Modifications'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'نحتفظ بالحق في تعديل هذه الشروط والأحكام في أي وقت. يُعتبر استمرار استخدامك للموقع بعد أي تعديلات موافقة منك على الشروط المحدثة.' : 'We reserve the right to modify these terms and conditions at any time. Your continued use of the website after any modifications constitutes your acceptance of the updated terms.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'القانون الواجب التطبيق' : 'Governing Law'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'تخضع هذه الشروط والأحكام لقوانين الجمهورية اليمنية.' : 'These terms and conditions are governed by the laws of the Republic of Yemen.'; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
