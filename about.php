<?php
/**
 * About Us Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'about';

// Get CMS page content
$cmsPage = Database::fetchOne("SELECT * FROM cms_pages WHERE page_key = 'about' AND is_active = 1");

// SEO
$seo = getSeoSettings('about');
$pageTitle = $seo['title'];
$pageDescription = $seo['description'];
$canonicalUrl = url('about.php');

// Stats
$totalProducts = Database::fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND deleted_at IS NULL")['count'] ?? 0;
$totalProjects = Database::fetchOne("SELECT COUNT(*) as count FROM portfolio WHERE is_active = 1")['count'] ?? 0;

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Banner (plain image, no overlay) -->
<div class="page-banner">
    <picture>
        <source media="(max-width: 768px)" srcset="<?php echo e(get_page_image('about_banner', 'mobile')); ?>">
        <img src="<?php echo e(get_page_image('about_banner', 'desktop')); ?>" alt="" loading="eager">
    </picture>
</div>

<!-- Page Title -->
<div class="about-hero">
    <div class="container">
        <div class="section-header reveal" style="margin-bottom:0;">
            <span class="section-label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <?php echo $lang === 'ar' ? 'تعرف علينا' : 'About Us'; ?>
            </span>
            <h1 class="section-title"><?php echo $lang === 'ar' ? 'عالم الصناعة لتجارة الكهرباء' : 'Alam Al-Sinaa for Electricity Trading'; ?></h1>
            <p class="section-subtitle"><?php echo $cmsPage ? e($cmsPage['excerpt_' . $lang]) : ($lang === 'ar' ? 'شريككم الموثوق في الحلول الكهربائية والصناعية المتكاملة' : 'Your Trusted Partner for Integrated Electrical & Industrial Solutions'); ?></p>
        </div>
    </div>
</div>

<!-- About Content -->
<section class="section">
    <div class="container">
        <div class="about-grid">
            <div class="reveal-left">
                <?php if ($cmsPage && $cmsPage['content_' . $lang]): ?>
                <div class="content-rich">
                    <?php echo $cmsPage['content_' . $lang]; ?>
                </div>
                <?php else: ?>
                <h2><?php echo $lang === 'ar' ? 'من نحن' : 'Who We Are'; ?></h2>
                <p><?php echo $lang === 'ar' ? 'عالم الصناعة لتجارة الكهرباء شركة رائدة في مجال توفير الحلول الكهربائية والصناعية المتكاملة. نحن نخدم القطاع الصناعي منذ سنوات بتفانٍ واحترافية.' : 'Alam Al-Sinaa for Electricity Trading is a leading provider of integrated electrical and industrial solutions. We have been serving the industrial sector for years with dedication and professionalism.'; ?></p>
                <p><?php echo $lang === 'ar' ? 'نحن نقدم مجموعة واسعة من المنتجات والخدمات تشمل المولدات الكهربائية، أنظمة الطاقة الشمسية، أجهزة التحكم الصناعي، مستلزمات المصانع، والمزيد.' : 'We offer a wide range of products and services including electric generators, solar power systems, industrial control devices, factory supplies, and more.'; ?></p>
                <?php endif; ?>
                
                <!-- Values -->
                <div class="values-grid">
                    <div class="value-card reveal">
                        <div class="value-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <h4 style="margin-bottom:var(--space-2);"><?php echo $lang === 'ar' ? 'الجودة' : 'Quality'; ?></h4>
                        <p style="font-size:0.9375rem;color:var(--color-text-secondary);"><?php echo $lang === 'ar' ? 'نقدم فقط المنتجات المعتمدة والمختبرة من أفضل الماركات العالمية' : 'We only offer certified and tested products from the best international brands'; ?></p>
                    </div>
                    <div class="value-card reveal">
                        <div class="value-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <h4 style="margin-bottom:var(--space-2);"><?php echo $lang === 'ar' ? 'الموثوقية' : 'Reliability'; ?></h4>
                        <p style="font-size:0.9375rem;color:var(--color-text-secondary);"><?php echo $lang === 'ar' ? 'نفي بالتزاماتنا بدقة وفي الوقت المحدد دائماً' : 'We fulfill our commitments accurately and on time, always'; ?></p>
                    </div>
                    <div class="value-card reveal">
                        <div class="value-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        </div>
                        <h4 style="margin-bottom:var(--space-2);"><?php echo $lang === 'ar' ? 'الابتكار' : 'Innovation'; ?></h4>
                        <p style="font-size:0.9375rem;color:var(--color-text-secondary);"><?php echo $lang === 'ar' ? 'نبحث دائماً عن أحدث التقنيات والحلول لعملائنا' : 'We always seek the latest technologies and solutions for our clients'; ?></p>
                    </div>
                    <div class="value-card reveal">
                        <div class="value-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <h4 style="margin-bottom:var(--space-2);"><?php echo $lang === 'ar' ? 'الشراكة' : 'Partnership'; ?></h4>
                        <p style="font-size:0.9375rem;color:var(--color-text-secondary);"><?php echo $lang === 'ar' ? 'نرى عملاءنا كشركاء نجاح نسعى لتحقيق أهدافهم معاً' : 'We see our clients as partners in success, working together to achieve their goals'; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="reveal-right">
                <div class="about-image">
                    <picture>
                        <source media="(max-width: 768px)" srcset="<?php echo e(get_page_image('about_secondary_image', 'mobile')); ?>">
                        <img src="<?php echo e(get_page_image('about_secondary_image', 'desktop')); ?>" alt="<?php echo e(getSetting('site_name_' . $lang)); ?>" loading="lazy">
                    </picture>
                </div>
                
                <!-- Stats -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-top:var(--space-6);">
                    <div class="card" style="padding:var(--space-6);text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:var(--color-primary);" data-count="<?php echo $totalProducts; ?>">0</div>
                        <div style="font-size:0.875rem;color:var(--color-text-muted);margin-top:var(--space-1);"><?php echo $lang === 'ar' ? 'منتج' : 'Products'; ?></div>
                    </div>
                    <div class="card" style="padding:var(--space-6);text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:var(--color-primary);" data-count="<?php echo $totalProjects; ?>">0</div>
                        <div style="font-size:0.875rem;color:var(--color-text-muted);margin-top:var(--space-1);"><?php echo $lang === 'ar' ? 'مشروع' : 'Projects'; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
