<?php
/**
 * Site Header Template - Updated
 */
if (!defined('BASE_PATH')) exit;

$lang = getCurrentLang();
$dir = getDirection();
$theme = $_COOKIE['theme'] ?? DEFAULT_THEME;
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo $dir; ?>" data-theme="<?php echo e($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Security -->
    <meta name="referrer" content="strict-origin-when-cross-origin">
    
    <!-- SEO Meta -->
    <title><?php echo e($pageTitle ?? getSetting('site_name_' . $lang, 'Alam Al-Sinaa for Electricity Trading')); ?></title>
    <meta name="description" content="<?php echo e($pageDescription ?? getSetting('default_meta_description_' . $lang)); ?>">
    <meta name="keywords" content="<?php echo e($pageKeywords ?? getSetting('default_meta_keywords_' . $lang)); ?>">
    <meta name="robots" content="<?php echo e($robotsMeta ?? 'index,follow'); ?>">
    <link rel="canonical" href="<?php echo e($canonicalUrl ?? currentUrl()); ?>">
    
    <!-- Open Graph -->
    <meta property="og:locale" content="<?php echo $lang === 'ar' ? 'ar_YE' : 'en_US'; ?>">
    <meta property="og:type" content="<?php echo e($ogType ?? 'website'); ?>">
    <meta property="og:title" content="<?php echo e($ogTitle ?? ($pageTitle ?? getSetting('site_name_' . $lang))); ?>">
    <meta property="og:description" content="<?php echo e($ogDescription ?? ($pageDescription ?? getSetting('default_meta_description_' . $lang))); ?>">
    <meta property="og:url" content="<?php echo e($canonicalUrl ?? currentUrl()); ?>">
    <meta property="og:site_name" content="<?php echo e(getSetting('site_name_' . $lang)); ?>">
    <meta property="og:image" content="<?php echo e($ogImage ?? url('assets/images/' . getSetting('og_image', 'og-default.jpg'))); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="<?php echo e($twitterCard ?? 'summary_large_image'); ?>">
    <meta name="twitter:title" content="<?php echo e($ogTitle ?? ($pageTitle ?? getSetting('site_name_' . $lang))); ?>">
    <meta name="twitter:description" content="<?php echo e($ogDescription ?? ($pageDescription ?? getSetting('default_meta_description_' . $lang))); ?>">
    <meta name="twitter:image" content="<?php echo e($ogImage ?? url('assets/images/' . getSetting('og_image', 'og-default.jpg'))); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/' . getSetting('site_favicon', 'favicon.ico')); ?>">
    
    <!-- Fonts: driven by the "site_font" setting (admin > Settings > Site Font) -->
    <?php $siteFontKey = getSetting('site_font', 'Tajawal'); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo e(getSiteFontGoogleUrl($siteFontKey)); ?>" rel="stylesheet">
    <style>:root{--main-font:'<?php echo e(addslashes($siteFontKey)); ?>';}</style>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/header.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/assistant.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/assistant-robot.css'); ?>">
    
    <!-- Schema -->
    <?php if (!empty($schemaMarkup)): ?>
    <script type="application/ld+json">
    <?php echo $schemaMarkup; ?>
    </script>
    <?php endif; ?>
    
    <!-- Theme initialization -->
    <script>
    (function() {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>

    <!-- Logo / cursor light-dark config, consumed by main.js to swap
         instantly on theme toggle without a page reload. -->
    <script id="site-assets-config" type="application/json"><?php echo json_encode(getSiteAssetsConfig(), JSON_UNESCAPED_SLASHES); ?></script>
</head>
<body>
    <!-- Scroll Progress Bar -->
    <div class="scroll-progress" aria-hidden="true"></div>
    
    <!-- Skip Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:bg-primary focus:text-white focus:px-4 focus:py-2 focus:rounded-md">
        <?php echo __('skip_to_content'); ?>
    </a>
    
    <!-- Header: hamburger, logo, language toggle, theme toggle ONLY -->
    <header class="header" role="banner">
        <div class="container header-inner">
            <!-- Mobile Menu Toggle (Hamburger) - always visible, opens the off-canvas nav panel -->
            <button class="menu-toggle" aria-label="<?php echo __('nav_menu'); ?>" aria-expanded="false" aria-controls="nav-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Logo (light/dark aware — see get_site_logo() in includes/functions.php) -->
            <?php
            $headerLogoLight = get_site_logo_url('header', 'light');
            $headerLogoDark  = get_site_logo_url('header', 'dark');
            $headerLogoInitial = ($theme === 'dark')
                ? ($headerLogoDark ?: $headerLogoLight)
                : ($headerLogoLight ?: $headerLogoDark);
            ?>
            <a href="<?php echo url(); ?>" class="header-logo" id="site-logo-link" aria-label="<?php echo e(getSetting('site_name_' . $lang)); ?> - <?php echo e(__('nav_home')); ?>">
                <div class="logo-circle">
                    <?php if ($headerLogoInitial): ?>
                    <img src="<?php echo e($headerLogoInitial); ?>"
                         data-logo-light="<?php echo e($headerLogoLight ?? ''); ?>"
                         data-logo-dark="<?php echo e($headerLogoDark ?? ''); ?>"
                         alt="<?php echo e(getSetting('site_name_' . $lang)); ?>" class="logo-img" id="site-logo-img">
                    <?php else: ?>
                    <div class="logo-icon">
                        <!-- Electrical bolt icon (built-in fallback mark — shown only if no logo was ever uploaded) -->
                        <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" width="28" height="28">
                            <path d="M22 4L8 22H20L18 36L32 18H20L22 4Z" fill="white" stroke="white" stroke-width="1" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="logo-tooltip" id="logo-tooltip" role="tooltip">
                    <span class="logo-tooltip-title"><?php echo e(getSetting('site_name_' . $lang)); ?></span>
                    <span class="logo-tooltip-tagline"><?php echo e(getSetting('site_tagline_' . $lang)); ?></span>
                </div>
            </a>

            <!-- Desktop-only: two-line site name/tagline text next to the logo.
                 Hidden by default (mobile) and revealed only at the desktop
                 breakpoint via header.css, so mobile header stays untouched. -->
            <div class="header-desktop-text">
                <span class="header-desktop-title"><?php echo e(getSetting('site_name_' . $lang)); ?></span>
                <span class="header-desktop-subtitle"><?php echo e(getSetting('site_tagline_' . $lang)); ?></span>
            </div>

            <!-- Desktop-only: inline nav tabs (mirrors the off-canvas nav links).
                 Hidden by default (mobile) and revealed only at the desktop
                 breakpoint via header.css. -->
            <nav class="header-desktop-nav" aria-label="<?php echo __('main_nav'); ?>">
                <ul class="header-desktop-nav-list">
                    <li>
                        <a href="<?php echo url(); ?>" class="header-desktop-nav-link <?php echo $activePage === 'home' ? 'active' : ''; ?>">
                            <?php echo __('nav_home'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('about.php'); ?>" class="header-desktop-nav-link <?php echo $activePage === 'about' ? 'active' : ''; ?>">
                            <?php echo __('nav_about'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('products.php'); ?>" class="header-desktop-nav-link <?php echo $activePage === 'products' ? 'active' : ''; ?>">
                            <?php echo __('nav_products'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('services.php'); ?>" class="header-desktop-nav-link <?php echo $activePage === 'services' ? 'active' : ''; ?>">
                            <?php echo __('nav_services'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('portfolio.php'); ?>" class="header-desktop-nav-link <?php echo $activePage === 'portfolio' ? 'active' : ''; ?>">
                            <?php echo __('nav_portfolio'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('contact.php'); ?>" class="header-desktop-nav-link <?php echo $activePage === 'contact' ? 'active' : ''; ?>">
                            <?php echo __('nav_contact'); ?>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Header Actions: Language toggle + Theme (light bulb) toggle +
                 (desktop-only) flip WhatsApp/Call card. On mobile this still
                 renders exactly as before: language then theme, no flip card. -->
            <div class="header-actions">
                <!-- Language Toggle: Globe + code -->
                <button class="header-btn lang-toggle" aria-label="<?php echo $lang === 'ar' ? 'Switch to English' : 'التبديل للعربية'; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="globe-icon" width="16" height="16">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <span class="lang-code"><?php echo $lang === 'ar' ? 'EN' : 'AR'; ?></span>
                </button>

                <!-- Theme Toggle: Light Bulb -->
                <button class="header-btn theme-toggle bulb-btn" aria-label="تغيير المظهر">
                    <svg class="bulb-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="22" height="22">
                        <!-- Bulb glass -->
                        <path class="bulb-glass" d="M12 2.5c-3.59 0-6.5 2.91-6.5 6.5 0 2.29 1.19 4.3 3 5.46.34.22.5.6.5 1v.54c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-.54c0-.4.16-.78.5-1 1.81-1.16 3-3.17 3-5.46 0-3.59-2.91-6.5-6.5-6.5z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        <!-- Filament -->
                        <path class="bulb-filament" d="M10 9.5l1.2 1.6h1.6L14 9.5M10.5 12h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <!-- Base / screw thread -->
                        <line x1="9.8" y1="18" x2="14.2" y2="18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="10" y1="20" x2="14" y2="20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="10.6" y1="22" x2="13.4" y2="22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <!-- Light rays (dark mode only) -->
                        <g class="bulb-rays">
                            <line x1="12" y1="0" x2="12" y2="1.4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <line x1="3.5" y1="9" x2="2.1" y2="9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <line x1="21.9" y1="9" x2="20.5" y2="9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <line x1="5.6" y1="2.6" x2="6.5" y2="3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <line x1="18.4" y1="2.6" x2="17.5" y2="3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </g>
                    </svg>
                </button>

                <!-- Desktop-only flip card: WhatsApp <-> Call, flips every 2s, infinite loop -->
                <div class="header-contact-flip" aria-hidden="false">
                    <div class="flip-card-inner">
                        <a href="<?php echo e(whatsappLink(getSetting('whatsapp_primary'))); ?>" class="flip-face flip-face-front" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" tabindex="0">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        <a href="<?php echo e(telLink(getSetting('whatsapp_primary'))); ?>" class="flip-face flip-face-back" aria-label="<?php echo $lang === 'ar' ? 'اتصال هاتفي' : 'Phone call'; ?>" tabindex="0">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.85a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Ad Bar: fixed directly beneath the header on every page. It is the
         shared visual separator between the header and whatever follows it
         (the hero section on the home page, the page banner everywhere
         else) so the header no longer sits flush against them. -->
    <?php
    // Announcement bar items: Admin › Settings (ad_bar_items_ar / _en),
    // separated by "|". Falls back to the tagline.
    $adBarItems = array_values(array_filter(array_map('trim', explode('|', getSetting('ad_bar_items_' . $lang)))));
    if (!$adBarItems) {
        $adBarItems = [getSetting('site_tagline_' . $lang) ?: getSetting('site_name_' . $lang)];
    }
    $adBarRepeatCount = max(1, (int) ceil(6 / count($adBarItems)));
    ?>
    <div class="ad-bar" role="complementary" aria-label="<?php echo $lang === 'ar' ? 'شريط إعلاني' : 'Announcement bar'; ?>">
        <div class="ad-bar-track">
            <?php for ($adBarGroup = 0; $adBarGroup < 2; $adBarGroup++): ?>
            <div class="ad-bar-group" <?php echo $adBarGroup === 1 ? 'aria-hidden="true"' : ''; ?>>
                <?php for ($adBarRepeat = 0; $adBarRepeat < $adBarRepeatCount; $adBarRepeat++): foreach ($adBarItems as $adBarItem): ?>
                <span class="ad-bar-item"><span><?php echo e($adBarItem); ?></span></span>
                <?php endforeach; endfor; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Mobile Menu Overlay - sibling of <header>, escapes any header stacking/backdrop-filter context -->
    <div class="mobile-menu-overlay" aria-hidden="true"></div>

    <!-- Navigation (Off-canvas Slide Panel) - moved OUTSIDE <header> on purpose:
         the header uses backdrop-filter, which creates a new containing block
         for fixed-position descendants and was clipping this panel to the
         header's own height. Living directly under <body> avoids that. -->
    <nav class="nav-menu" id="nav-menu" role="navigation" aria-label="<?php echo __('main_nav'); ?>">
                <!-- Mobile Panel Header -->
                <div class="nav-mobile-header">
                    <?php
                    $mobileLogoLight = get_site_logo_url('mobile_menu', 'light');
                    $mobileLogoDark  = get_site_logo_url('mobile_menu', 'dark');
                    $mobileLogoInitial = ($theme === 'dark')
                        ? ($mobileLogoDark ?: $mobileLogoLight)
                        : ($mobileLogoLight ?: $mobileLogoDark);
                    ?>
                    <div class="nav-mobile-logo">
                        <?php if ($mobileLogoInitial): ?>
                        <div class="nav-mobile-logo-icon nav-mobile-logo-icon-img">
                            <img src="<?php echo e($mobileLogoInitial); ?>"
                                 data-logo-light="<?php echo e($mobileLogoLight ?? ''); ?>"
                                 data-logo-dark="<?php echo e($mobileLogoDark ?? ''); ?>"
                                 alt="<?php echo e(getSetting('site_name_' . $lang)); ?>" id="mobile-menu-logo-img">
                        </div>
                        <?php else: ?>
                        <div class="nav-mobile-logo-icon">
                            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" width="22" height="22">
                                <path d="M22 4L8 22H20L18 36L32 18H20L22 4Z" fill="white" stroke="white" stroke-width="1" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <?php endif; ?>
                        <span><?php echo e(getSetting('site_name_' . $lang)); ?></span>
                    </div>
                    <button class="nav-close-btn" aria-label="إغلاق القائمة">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <ul class="nav-menu-list">
                    <li>
                        <a href="<?php echo url(); ?>" class="nav-link <?php echo $activePage === 'home' ? 'active' : ''; ?>">
                            <?php echo __('nav_home'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('about.php'); ?>" class="nav-link <?php echo $activePage === 'about' ? 'active' : ''; ?>">
                            <?php echo __('nav_about'); ?>
                        </a>
                    </li>
                    <li class="nav-dropdown">
                        <a href="<?php echo url('products.php'); ?>" class="nav-link nav-dropdown-toggle <?php echo $activePage === 'products' ? 'active' : ''; ?>">
                            <?php echo __('nav_products'); ?>
                        </a>
                        <ul class="nav-dropdown-menu">
                            <?php 
                            $navCategories = Database::fetchAll(
                                "SELECT id, name_{$lang} as name, slug FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order"
                            );
                            foreach ($navCategories as $cat): 
                            ?>
                            <li>
                                <a href="<?php echo url('products.php?category=' . $cat['slug']); ?>" class="nav-dropdown-item">
                                    <?php echo e($cat['name']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li>
                        <a href="<?php echo url('services.php'); ?>" class="nav-link <?php echo $activePage === 'services' ? 'active' : ''; ?>">
                            <?php echo __('nav_services'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('portfolio.php'); ?>" class="nav-link <?php echo $activePage === 'portfolio' ? 'active' : ''; ?>">
                            <?php echo __('nav_portfolio'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('contact.php'); ?>" class="nav-link <?php echo $activePage === 'contact' ? 'active' : ''; ?>">
                            <?php echo __('nav_contact'); ?>
                        </a>
                    </li>
                </ul>
                
                <!-- Mobile Panel Footer: prominent Call + WhatsApp buttons only.
                     Language & theme are already reachable from the persistent
                     main header, so they aren't duplicated here. -->
                <div class="nav-mobile-actions">
                    <!-- WhatsApp -->
                    <a href="<?php echo e(whatsappLink(getSetting('whatsapp_primary'))); ?>" class="mobile-action-btn mobile-wa" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>WhatsApp</span>
                    </a>
                    
                    <!-- Call Button -->
                    <a href="<?php echo e(telLink(getSetting('whatsapp_primary'))); ?>" class="mobile-action-btn mobile-call">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.85a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <span><?php echo $lang === 'ar' ? 'اتصل بنا' : 'Call Us'; ?></span>
                    </a>
                </div>
    </nav>

    <!-- Search Overlay -->
    <!-- NOTE: the header no longer has a search-trigger button (only the 4
         approved elements remain in the main header bar), so this overlay
         currently has no way to be opened. Left in place untouched in case
         you want a trigger added back somewhere (e.g. inside the nav panel). -->
    <div class="search-overlay" role="dialog" aria-modal="true" aria-label="<?php echo __('search'); ?>">
        <button class="search-close" aria-label="<?php echo __('close'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div class="search-container">
            <form action="<?php echo url('search.php'); ?>" method="GET" class="search-form">
                <input type="search" name="q" class="search-input" placeholder="<?php echo __('search_placeholder'); ?>" aria-label="<?php echo __('search'); ?>">
                <button type="submit" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Main Content -->
    <main id="main-content" class="main-content">
