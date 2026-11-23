<?php
/**
 * Site Footer Template
 */
if (!defined('BASE_PATH')) exit;

$lang = getCurrentLang();
$theme = $_COOKIE['theme'] ?? DEFAULT_THEME;
?>
    </main>
    
    <!-- WhatsApp Floating Button -->
    <div class="whatsapp-float">
        <div class="whatsapp-float-menu">
            <?php if (getSetting('whatsapp_primary')): ?>
            <a href="https://wa.me/<?php echo getSetting('whatsapp_primary'); ?>" class="whatsapp-float-item" target="_blank" rel="noopener noreferrer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                <?php echo getSetting('whatsapp_primary'); ?>
            </a>
            <?php endif; ?>
            <?php if (getSetting('whatsapp_secondary_1')): ?>
            <a href="https://wa.me/<?php echo getSetting('whatsapp_secondary_1'); ?>" class="whatsapp-float-item" target="_blank" rel="noopener noreferrer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                <?php echo getSetting('whatsapp_secondary_1'); ?>
            </a>
            <?php endif; ?>
            <?php if (getSetting('whatsapp_secondary_2')): ?>
            <a href="https://wa.me/<?php echo getSetting('whatsapp_secondary_2'); ?>" class="whatsapp-float-item" target="_blank" rel="noopener noreferrer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                <?php echo getSetting('whatsapp_secondary_2'); ?>
            </a>
            <?php endif; ?>
        </div>
        <a href="https://wa.me/<?php echo getSetting('whatsapp_primary'); ?>" class="whatsapp-float-btn" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"
           data-ai-peek-text="<?php echo $lang === 'ar' ? 'تفضّل تواصل معنا مباشرة على واتساب!' : 'Feel free to reach us directly on WhatsApp!'; ?>">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <span><?php echo __('whatsapp_click'); ?></span>
        </a>
    </div>
    
    <!-- Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <?php
                    $footerLogoLight = get_site_logo_url('footer', 'light');
                    $footerLogoDark  = get_site_logo_url('footer', 'dark');
                    $footerLogoInitial = ($theme === 'dark')
                        ? ($footerLogoDark ?: $footerLogoLight)
                        : ($footerLogoLight ?: $footerLogoDark);
                    ?>
                    <a href="<?php echo url(); ?>" class="footer-logo">
                        <?php if ($footerLogoInitial): ?>
                        <div class="footer-logo-icon footer-logo-icon-img">
                            <img src="<?php echo e($footerLogoInitial); ?>"
                                 data-logo-light="<?php echo e($footerLogoLight ?? ''); ?>"
                                 data-logo-dark="<?php echo e($footerLogoDark ?? ''); ?>"
                                 alt="<?php echo e(getSetting('site_name_' . $lang)); ?>" id="footer-logo-img">
                        </div>
                        <?php else: ?>
                        <div class="footer-logo-icon">A</div>
                        <?php endif; ?>
                        <div class="footer-logo-text">
                            <?php echo e(getSetting('site_name_' . $lang)); ?>
                            <span><?php echo e(getSetting('site_tagline_' . $lang)); ?></span>
                        </div>
                    </a>
                    <p class="footer-description">
                        <?php echo $lang === 'ar' 
                            ? 'شركة رائدة في مجال توفير الحلول الكهربائية والصناعية المتكاملة في اليمن. نحن نخدم القطاع الصناعي بتفانٍ واحترافية.' 
                            : 'A leading company in providing integrated electrical and industrial solutions in Yemen. We serve the industrial sector with dedication and professionalism.'; ?>
                    </p>
                    <div class="footer-social">
                        <?php if (getSetting('facebook_url')): ?>
                        <a href="<?php echo e(getSetting('facebook_url')); ?>" class="footer-social-link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (getSetting('twitter_url')): ?>
                        <a href="<?php echo e(getSetting('twitter_url')); ?>" class="footer-social-link" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if (getSetting('instagram_url')): ?>
                        <a href="<?php echo e(getSetting('instagram_url')); ?>" class="footer-social-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-column">
                    <h3 class="footer-title"><?php echo __('quick_links'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a></li>
                        <li><a href="<?php echo url('about.php'); ?>"><?php echo __('nav_about'); ?></a></li>
                        <li><a href="<?php echo url('services.php'); ?>"><?php echo __('nav_services'); ?></a></li>
                        <li><a href="<?php echo url('products.php'); ?>"><?php echo __('nav_products'); ?></a></li>
                        <li><a href="<?php echo url('portfolio.php'); ?>"><?php echo __('nav_portfolio'); ?></a></li>
                        <li><a href="<?php echo url('faq.php'); ?>"><?php echo __('nav_faq'); ?></a></li>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div class="footer-column">
                    <h3 class="footer-title"><?php echo __('categories'); ?></h3>
                    <ul class="footer-links">
                        <?php 
                        $footerCategories = Database::fetchAll(
                            "SELECT name_{$lang} as name, slug FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order LIMIT 6"
                        );
                        foreach ($footerCategories as $cat):
                        ?>
                        <li><a href="<?php echo url('products.php?category=' . $cat['slug']); ?>"><?php echo e($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="footer-column">
                    <h3 class="footer-title"><?php echo __('contact_us'); ?></h3>
                    <ul class="footer-contact-list">
                        <li class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <div class="footer-contact-text">
                                <strong><?php echo __('address'); ?></strong>
                                <?php echo e(getSetting('company_address_' . $lang)); ?>
                            </div>
                        </li>
                        <?php
                        $footerPhoneNumbers = array_filter([
                            getSetting('whatsapp_primary'),
                            getSetting('whatsapp_secondary_1'),
                            getSetting('whatsapp_secondary_2'),
                        ]);
                        ?>
                        <?php if (!empty($footerPhoneNumbers)): ?>
                        <li class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </div>
                            <div class="footer-contact-text">
                                <strong><?php echo __('phone'); ?></strong>
                                <ul class="footer-phone-list">
                                    <?php foreach ($footerPhoneNumbers as $footerPhone): ?>
                                    <li>
                                        <a href="tel:+967<?php echo e($footerPhone); ?>" class="footer-phone-link">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                            +967 <?php echo e(substr($footerPhone, 0, 3) . ' ' . substr($footerPhone, 3, 3) . ' ' . substr($footerPhone, 6)); ?>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </li>
                        <?php endif; ?>
                        <li class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div class="footer-contact-text">
                                <strong><?php echo __('working_hours'); ?></strong>
                                <?php echo __('sat_thu'); ?>: 8:00 AM - 10:00 PM<br>
                                <?php echo __('friday'); ?>: 2:00 PM - 10:00 PM
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p class="footer-copyright">
                    &copy; <?php echo date('Y'); ?> <?php echo e(getSetting('site_name_' . $lang)); ?>. <?php echo __('all_rights_reserved'); ?>
                </p>
                <ul class="footer-legal">
                    <li><a href="<?php echo url('privacy.php'); ?>"><?php echo __('privacy_policy'); ?></a></li>
                    <li><a href="<?php echo url('terms.php'); ?>"><?php echo __('terms_conditions'); ?></a></li>
                </ul>
            </div>
        </div>
    </footer>
    
    <!-- ============================================================
         AI Assistant Widget
         ============================================================ -->
    <?php
    if (file_exists(BASE_PATH . '/ai-assistant/config/assistant.php')) {
        require_once BASE_PATH . '/ai-assistant/config/assistant.php';
    }
    if (defined('AI_ENABLED') && AI_ENABLED):
    ?>
    <!-- Toggle Button -->
    <button
        id="ai-toggle-btn"
        class="ai-toggle-btn"
        aria-label="<?php echo $lang === 'ar' ? 'المساعد الذكي' : 'AI Assistant'; ?>"
        aria-expanded="false"
        aria-controls="ai-window">
        <!-- Chat icon -->
        <svg class="ai-icon-chat ai-robot-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Antenna -->
            <path d="M32 12V4" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            <circle cx="32" cy="4" r="3" fill="var(--ai-primary-light, #60a5fa)" stroke="currentColor" stroke-width="2"/>
            
            <!-- Ears -->
            <rect x="14" y="22" width="4" height="8" rx="2" fill="currentColor"/>
            <rect x="46" y="22" width="4" height="8" rx="2" fill="currentColor"/>
            
            <!-- Head -->
            <rect x="18" y="12" width="28" height="24" rx="6" fill="#1e293b" stroke="currentColor" stroke-width="3"/>
            
            <!-- Eyes -->
            <circle class="ai-robot-eye" cx="26" cy="24" r="3.5" fill="var(--ai-primary-light, #60a5fa)"/>
            <circle class="ai-robot-eye" cx="38" cy="24" r="3.5" fill="var(--ai-primary-light, #60a5fa)"/>
            
            <!-- Mouth -->
            <path d="M27 30C27 30 29 32 32 32C35 32 37 30 37 30" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            
            <!-- Body -->
            <path d="M24 36H40V48H24V36Z" fill="#1e293b" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
            
            <!-- Left Arm (Static) -->
            <path d="M24 40H18V45" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            
            <!-- Right Arm (Waving!) -->
            <path class="ai-robot-arm-wave" d="M40 40H46V33" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <!-- Close icon -->
        <svg class="ai-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
        <!-- Unread badge -->
        <span id="ai-badge" class="ai-badge" data-count="0" aria-hidden="true"></span>
    </button>

    <!-- Chat Window -->
    <div id="ai-window" class="ai-window" role="dialog"
         aria-label="<?php echo $lang === 'ar' ? 'نافذة المساعد الذكي' : 'AI Assistant Chat'; ?>"
         aria-modal="false">

        <!-- Header -->
        <div class="ai-header">
            <div class="ai-header-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
            </div>
            <div class="ai-header-info">
                <div class="ai-header-name"><?php echo $lang === 'ar' ? 'المساعد الذكي' : 'AI Assistant'; ?></div>
                <div class="ai-header-status">
                    <span class="ai-status-dot"></span>
                    <?php echo $lang === 'ar' ? 'متاح الآن' : 'Online'; ?>
                </div>
            </div>
            <div class="ai-header-actions">
                <button id="ai-clear-btn" class="ai-header-btn"
                        title="<?php echo $lang === 'ar' ? 'مسح المحادثة' : 'Clear chat'; ?>"
                        aria-label="<?php echo $lang === 'ar' ? 'مسح المحادثة' : 'Clear chat'; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                        <path d="M9 6V4h6v2"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div id="ai-messages" class="ai-messages" role="log" aria-live="polite" aria-label="<?php echo $lang === 'ar' ? 'رسائل المحادثة' : 'Chat messages'; ?>">
            <!-- Populated by assistant.js -->
        </div>

        <!-- Input -->
        <div class="ai-input-area">
            <div class="ai-input-row">
                <textarea
                    id="ai-input"
                    class="ai-input"
                    rows="1"
                    placeholder="<?php echo $lang === 'ar' ? 'اكتب رسالتك...' : 'Type your message...'; ?>"
                    aria-label="<?php echo $lang === 'ar' ? 'حقل الرسالة' : 'Message input'; ?>"
                    maxlength="1000"></textarea>
                <button id="ai-send-btn" class="ai-send-btn"
                        aria-label="<?php echo $lang === 'ar' ? 'إرسال' : 'Send'; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
            <div id="ai-error-toast" class="ai-error-toast" role="alert"></div>
        </div>
    </div>
    <!-- /AI Assistant Widget -->

    <!-- AI Assistant: Page Context (للاقتراحات الذكية) -->
    <script>
    window.AI_PAGE_CONTEXT = {
        page:  '<?php echo e($activePage ?? 'home'); ?>',
        title: '<?php echo e(addslashes($pageTitle ?? getSetting('site_name_' . $lang, ''))); ?>',
        url:   '<?php echo e(currentUrl()); ?>',
        lang:  '<?php echo e($lang); ?>',
    };
    </script>
    <script src="<?php echo asset('js/assistant.js'); ?>" defer></script>
    <script src="<?php echo asset('js/assistant-mascot.js'); ?>" defer></script>
    <?php endif; ?>

    <!-- Back to Top -->
    <button class="back-to-top" aria-label="<?php echo $lang === 'ar' ? 'العودة للأعلى' : 'Back to top'; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
    </button>
    
    <!-- Scripts -->
    <script src="<?php echo asset('js/main.js'); ?>"></script>
    
    <!-- Flash Messages -->
    <?php echo renderFlashMessages(); ?>
    
</body>
</html>
