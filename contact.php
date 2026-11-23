<?php
/**
 * Contact Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'contact';

// SEO
$seo = getSeoSettings('contact');
$pageTitle = $seo['title'];
$pageDescription = $seo['description'];
$canonicalUrl = url('contact.php');

// Get CMS page content
$cmsPage = Database::fetchOne("SELECT * FROM cms_pages WHERE page_key = 'contact' AND is_active = 1");

// Map embed: prefer the link the admin saved in Settings > Contact
// Information (resolved there into a safe "output=embed" URL), otherwise
// fall back to the site's default coordinates.
$mapEmbedSrc = getSetting('google_maps_embed_url');
if ($mapEmbedSrc === '') {
    $mapEmbedSrc = 'https://www.google.com/maps?q=13.615232175302207,44.11388758250849&hl=ar&z=15&output=embed';
}

// Handle form submission
$formSuccess = false;
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $formError = $lang === 'ar' ? 'خطأ في التحقق من الأمان. يرجى المحاولة مرة أخرى.' : 'Security verification failed. Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitizeEmail($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $type = sanitize($_POST['type'] ?? 'contact');
        
        if (empty($name) || empty($message)) {
            $formError = $lang === 'ar' ? 'الرجاء ملء جميع الحقول المطلوبة.' : 'Please fill in all required fields.';
        } else {
            try {
                Database::insert('contacts', [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $message,
                    'type' => $type,
                    'ip_address' => getClientIP()
                ]);
                
                $formSuccess = true;
                
                // Send WhatsApp notification
                $whatsappMessage = generateWhatsAppMessage('contact', [
                    'name' => $name,
                    'subject' => $subject ?: 'General Inquiry'
                ]);
                
            } catch (Exception $e) {
                $formError = $lang === 'ar' ? 'حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة مرة أخرى.' : 'An error occurred while sending the message. Please try again.';
            }
        }
    }
}

// FAQ for contact page
$faqs = Database::fetchAll("SELECT * FROM cms_pages WHERE page_key = 'faq' AND is_active = 1 LIMIT 5");

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Banner (plain image, no overlay) -->
<div class="page-banner">
    <picture>
        <source media="(max-width: 768px)" srcset="<?php echo e(get_page_image('contact_banner', 'mobile')); ?>">
        <img src="<?php echo e(get_page_image('contact_banner', 'desktop')); ?>" alt="" loading="eager">
    </picture>
</div>

<!-- Page Title -->
<div class="page-header after-banner">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo __('nav_contact'); ?></span>
            </nav>
            <h1 class="page-header-title"><?php echo __('contact_title'); ?></h1>
            <p class="page-header-subtitle"><?php echo __('contact_subtitle'); ?></p>
        </div>
    </div>
</div>

<!-- Contact Section: Form + Map -->
<section class="section contact-section">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Form (right in RTL) -->
            <div class="reveal-right">
                <div class="contact-form-card">
                    <h3 class="contact-form-title"><?php echo __('contact_form_title'); ?></h3>
                    
                    <?php if ($formSuccess): ?>
                    <div class="alert alert-success">
                        <?php echo $lang === 'ar' ? 'تم إرسال رسالتك بنجاح! سنقوم بالرد عليك في أقرب وقت.' : 'Your message has been sent successfully! We will reply to you as soon as possible.'; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($formError): ?>
                    <div class="alert alert-danger">
                        <?php echo e($formError); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" data-validate>
                        <?php echo csrfField(); ?>
                        
                        <input type="hidden" name="type" value="contact">
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('name'); ?> <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?php echo e($_POST['name'] ?? ''); ?>">
                        </div>
                        
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
                            <div class="form-group">
                                <label class="form-label"><?php echo __('email'); ?></label>
                                <input type="email" name="email" class="form-control" value="<?php echo e($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo __('phone'); ?></label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo e($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('subject'); ?></label>
                            <input type="text" name="subject" class="form-control" value="<?php echo e($_POST['subject'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('message'); ?> <span class="required">*</span></label>
                            <textarea name="message" class="form-control" rows="5" required><?php echo e($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full"
                                data-ai-peek-text="<?php echo $lang === 'ar' ? 'يمكنك أيضاً التحدث معي مباشرة إن أردت رداً أسرع!' : 'You can also chat with me directly for a faster reply!'; ?>">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            <?php echo __('send'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Map (left in RTL) -->
            <div class="reveal-left">
                <div class="contact-map">
                    <iframe src="<?php echo e($mapEmbedSrc); ?>" width="100%" height="450" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            </div>
        </div>

        <!-- Contact Info Cards: reflowed from vertical to horizontal.
             Right column (RTL): Address above Working Hours.
             Left column (RTL): Phone Numbers above Email.
             Each card keeps the same visual design as before; only the
             overall layout changed from one stacked card to four cards
             arranged in a responsive 2x2 grid (collapses to 1 column
             on mobile). -->
        <div class="contact-info-grid reveal" style="margin-top:var(--space-12);">
            <div class="contact-info-card contact-info-card-address">
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4><?php echo __('address'); ?></h4>
                        <p><?php echo e(getSetting('company_address_' . $lang)); ?></p>
                    </div>
                </div>
            </div>

            <div class="contact-info-card contact-info-card-phones">
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4><?php echo __('phone'); ?></h4>
                        <?php
                        $contactPhoneNumbers = array_filter([
                            getSetting('whatsapp_primary'),
                            getSetting('whatsapp_secondary_1'),
                            getSetting('whatsapp_secondary_2'),
                        ]);
                        ?>
                        <?php foreach ($contactPhoneNumbers as $i => $contactPhone): ?>
                        <p style="margin:0;<?php echo $i > 0 ? 'margin-top:4px;' : ''; ?>">
                            <a href="<?php echo e(telLink($contactPhone)); ?>" dir="ltr"><?php echo e(formatPhoneDisplay($contactPhone)); ?></a>
                            <a href="<?php echo e(whatsappLink($contactPhone)); ?>" target="_blank" rel="noopener noreferrer" class="contact-wa-mini" aria-label="WhatsApp <?php echo e(formatPhoneDisplay($contactPhone)); ?>">WhatsApp</a>
                        </p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="contact-info-card contact-info-card-hours">
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4><?php echo __('working_hours'); ?></h4>
                        <p><?php echo __('sat_thu'); ?>: 8:00 AM - 10:00 PM<br><?php echo __('friday'); ?>: 2:00 PM - 10:00 PM</p>
                    </div>
                </div>
            </div>

            <div class="contact-info-card contact-info-card-email">
                <div class="contact-item">
                    <div class="contact-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                    <div class="contact-item-content">
                        <h4><?php echo __('email'); ?></h4>
                        <p><a href="mailto:<?php echo e(getSetting('company_email')); ?>"><?php echo e(getSetting('company_email')); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
