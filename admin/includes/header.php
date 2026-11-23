<?php
/**
 * Admin Header
 */
if (!defined('BASE_PATH')) define('BASE_PATH', __DIR__ . '/../..');

$lang = getCurrentLang();
$dir = getDirection();
$theme = $_COOKIE['theme'] ?? DEFAULT_THEME;
$user = getCurrentUser();
$adminPage = $adminPage ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo $dir; ?>" data-theme="<?php echo e($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? ($lang === 'ar' ? 'لوحة التحكم' : 'Dashboard')); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/' . getSetting('site_favicon', 'favicon.ico')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo adminUrl('assets/css/admin.css'); ?>?v=<?php echo @filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <script>
    (function() {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-sidebar-header">
                <a href="<?php echo adminUrl('index.php'); ?>" class="admin-sidebar-logo">
                    <div class="admin-sidebar-logo-icon">
                        <svg viewBox="0 0 40 40" width="20" height="20" fill="none"><path d="M22 4L8 22H20L18 36L32 18H20L22 4Z" fill="white"/></svg>
                    </div>
                    <span><?php echo e(getSetting('site_name_' . $lang)); ?></span>
                </a>
                <button type="button" class="admin-sidebar-close" id="admin-sidebar-close" aria-label="<?php echo $lang === 'ar' ? 'إغلاق القائمة' : 'Close menu'; ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <nav>
                <ul class="admin-sidebar-nav">
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('index.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'dashboard' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            <?php echo $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('products.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'products' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                            <?php echo $lang === 'ar' ? 'المنتجات' : 'Products'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('categories.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'categories' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                            <?php echo $lang === 'ar' ? 'الفئات' : 'Categories'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('portfolio.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'portfolio' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                            <?php echo $lang === 'ar' ? 'معرض الأعمال' : 'Portfolio'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('contacts.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'contacts' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <?php echo $lang === 'ar' ? 'الرسائل' : 'Messages'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('cms.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'cms' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <?php echo $lang === 'ar' ? 'إدارة المحتوى' : 'CMS'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('page_images.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'page_images' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <?php echo $lang === 'ar' ? 'إدارة صور الصفحات' : 'Page Images'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('media_library.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'media_library' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"></path><path d="M4 3v18"></path><circle cx="9" cy="9" r="1.5"></circle><path d="M4 15l4-4 3 3 5-5 4 4"></path></svg>
                            <?php echo $lang === 'ar' ? 'مكتبة الملفات' : 'File Library'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('seo.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'seo' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            SEO
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('ai-assistant.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'ai_assistant' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/>
                                <path d="M8 12h.01M12 12h.01M16 12h.01" stroke-linecap="round" stroke-width="2.5"/>
                            </svg>
                            <?php echo $lang === 'ar' ? 'المساعد الذكي' : 'AI Assistant'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('settings.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'settings' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            <?php echo $lang === 'ar' ? 'الإعدادات' : 'Settings'; ?>
                        </a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="<?php echo adminUrl('users.php'); ?>" class="admin-sidebar-link <?php echo $adminPage === 'users' ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <?php echo $lang === 'ar' ? 'المستخدمين' : 'Users'; ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="admin-sidebar-footer">
                <a href="<?php echo adminUrl('logout.php'); ?>" class="admin-sidebar-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <?php echo $lang === 'ar' ? 'تسجيل الخروج' : 'Logout'; ?>
                </a>
            </div>
        </aside>
        
        <div class="admin-sidebar-overlay" id="admin-sidebar-overlay" aria-hidden="true"></div>

        <!-- Main -->
        <div class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="admin-header-start">
                    <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="<?php echo $lang === 'ar' ? 'القائمة' : 'Menu'; ?>" aria-controls="admin-sidebar" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <span class="admin-header-title"><?php echo e(preg_replace('/\s+-\s+.*$/u', '', $pageTitle ?? ($lang === 'ar' ? 'لوحة التحكم' : 'Dashboard'))); ?></span>
                </div>
                
                <div class="admin-header-actions">
                    <button class="admin-header-btn theme-toggle" aria-label="Toggle theme">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    </button>
                    <a href="<?php echo url(); ?>" target="_blank" class="admin-header-btn" aria-label="View site">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </a>
                    <div class="admin-user" title="<?php echo e($user['full_name'] ?? ''); ?>">
                        <div class="admin-user-avatar"><?php echo mb_substr($user['full_name'] ?? 'A', 0, 1); ?></div>
                        <span class="admin-user-name"><?php echo e($user['full_name'] ?? ''); ?></span>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <main class="admin-content">
                <?php echo renderFlashMessages(); ?>
