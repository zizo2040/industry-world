<?php
/**
 * Privacy Policy Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();

// Get CMS page content
$cmsPage = Database::fetchOne("SELECT * FROM cms_pages WHERE page_key = 'privacy' AND is_active = 1");

$pageTitle = ($lang === 'ar' ? 'سياسة الخصوصية - ' : 'Privacy Policy - ') . getSetting('site_name_' . $lang);
$pageDescription = $lang === 'ar' ? 'سياسة الخصوصية لموقع عالم الصناعة' : 'Privacy Policy for Alam Al-Sinaa website';
$canonicalUrl = url('privacy.php');

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo __('privacy_policy'); ?></span>
            </nav>
            <h1 class="page-header-title"><?php echo __('privacy_policy'); ?></h1>
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
                <h2><?php echo $lang === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy'; ?></h2>
                <p><?php echo $lang === 'ar' ? 'نحن نحرص على حماية خصوصيتك. لا نقوم بجمع أو مشاركة أي معلومات شخصية دون إذنك الصريح.' : 'We are committed to protecting your privacy. We do not collect or share any personal information without your explicit consent.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'المعلومات التي نجمعها' : 'Information We Collect'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'نقوم بجمع المعلومات التي تقدمها لنا طواعية عند ملء نماذج الاتصال أو طلب عروض الأسعار.' : 'We collect information that you voluntarily provide when filling out contact forms or requesting quotes.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'كيف نستخدم المعلومات' : 'How We Use Information'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'نستخدم المعلومات فقط للرد على استفساراتك وتقديم الخدمات المطلوبة.' : 'We use the information solely to respond to your inquiries and provide the requested services.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'حماية البيانات' : 'Data Protection'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'نستخدم إجراءات أمنية مناسبة لحماية بياناتك من الوصول غير المصرح به أو التعديل أو الإفصاح.' : 'We use appropriate security measures to protect your data from unauthorized access, modification, or disclosure.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'ملفات تعريف الارتباط' : 'Cookies'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'نستخدم ملفات تعريف الارتباط لتحسين تجربة المستخدم وتذكر تفضيلاتك مثل اللغة والسمة.' : 'We use cookies to improve user experience and remember your preferences such as language and theme.'; ?></p>
                
                <h3><?php echo $lang === 'ar' ? 'اتصل بنا' : 'Contact Us'; ?></h3>
                <p><?php echo $lang === 'ar' ? 'إذا كان لديك أي أسئلة حول سياسة الخصوصية، يرجى التواصل معنا.' : 'If you have any questions about our privacy policy, please contact us.'; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
