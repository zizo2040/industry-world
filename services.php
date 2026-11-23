<?php
/**
 * Services Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'services';

// Get CMS page content
$cmsPage = Database::fetchOne("SELECT * FROM cms_pages WHERE page_key = 'services' AND is_active = 1");

// SEO
$pageTitle = ($lang === 'ar' ? 'خدماتنا - ' : 'Our Services - ') . getSetting('site_name_' . $lang);
$pageDescription = $cmsPage ? e($cmsPage['excerpt_' . $lang]) : getSetting('default_meta_description_' . $lang);
$canonicalUrl = url('services.php');

$schemaMarkup = generateBreadcrumbSchema([
    ['name' => __('nav_home'), 'url' => url()],
    ['name' => __('nav_services'), 'url' => null],
]);

// Services list
$services = [
    [
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>',
        'title_ar' => 'الاستشارات الفنية',
        'title_en' => 'Technical Consultations',
        'desc_ar' => 'فريقنا الهندسي جاهز لتقديم استشارات فنية متخصصة لمشاريعكم الصناعية. نساعدكم في اختيار أفضل الحلول والمعدات.',
        'desc_en' => 'Our engineering team is ready to provide specialized technical consultations for your industrial projects. We help you choose the best solutions and equipment.'
    ],
    [
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>',
        'title_ar' => 'التركيب والتشغيل',
        'title_en' => 'Installation & Commissioning',
        'desc_ar' => 'نقدم خدمات التركيب والتشغيل لجميع الأنظمة والمعدات الكهربائية والصناعية بكفاءة عالية واحترافية.',
        'desc_en' => 'We provide installation and commissioning services for all electrical and industrial systems and equipment with high efficiency and professionalism.'
    ],
    [
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
        'title_ar' => 'الصيانة الدورية',
        'title_en' => 'Periodic Maintenance',
        'desc_ar' => 'عقود صيانة دورية لضمان استمرارية عمل منشآتكم الصناعية وتجنب الأعطال غير المتوقعة.',
        'desc_en' => 'Periodic maintenance contracts to ensure the continuity of your industrial operations and avoid unexpected breakdowns.'
    ],
    [
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
        'title_ar' => 'قطع الغيار',
        'title_en' => 'Spare Parts',
        'desc_ar' => 'نوفر قطع غيار أصلية لجميع المعدات والأنظمة التي نوردها، مع ضمان الجودة والتوافق.',
        'desc_en' => 'We provide original spare parts for all equipment and systems we supply, with guaranteed quality and compatibility.'
    ],
    [
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path></svg>',
        'title_ar' => 'دراسة الجدوى',
        'title_en' => 'Feasibility Studies',
        'desc_ar' => 'نقدم دراسات جدوى شاملة لمشاريعكم الصناعية لتقييم الاحتياجات والتكاليف والعائد المتوقع.',
        'desc_en' => 'We provide comprehensive feasibility studies for your industrial projects to assess needs, costs, and expected returns.'
    ],
    [
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>',
        'title_ar' => 'التدريب الفني',
        'title_en' => 'Technical Training',
        'desc_ar' => 'برامج تدريبية متخصصة للفرق الفنية في الشركات على تشغيل وصيانة المعدات والأنظمة.',
        'desc_en' => 'Specialized training programs for company technical teams on operating and maintaining equipment and systems.'
    ]
];

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Banner (plain image, no overlay) -->
<div class="page-banner">
    <picture>
        <source media="(max-width: 768px)" srcset="<?php echo e(get_page_image('services_banner', 'mobile')); ?>">
        <img src="<?php echo e(get_page_image('services_banner', 'desktop')); ?>" alt="" loading="eager">
    </picture>
</div>

<!-- Page Title -->
<div class="page-header after-banner">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo __('nav_services'); ?></span>
            </nav>
            <h1 class="page-header-title"><?php echo $lang === 'ar' ? 'خدماتنا' : 'Our Services'; ?></h1>
            <p class="page-header-subtitle"><?php echo $lang === 'ar' ? 'مجموعة شاملة من الخدمات للقطاع الصناعي' : 'A comprehensive range of services for the industrial sector'; ?></p>
        </div>
    </div>
</div>

<!-- Services Content -->
<section class="section">
    <div class="container">
        <?php if ($cmsPage && $cmsPage['content_' . $lang]): ?>
        <div class="content-rich reveal" style="max-width:800px;margin:0 auto var(--space-12);">
            <?php echo $cmsPage['content_' . $lang]; ?>
        </div>
        <?php endif; ?>
        
        <!-- Services Grid -->
        <div class="grid grid-2 services-grid-narrative" style="gap:var(--space-6);">
            <?php foreach ($services as $index => $service): ?>
            <div class="card reveal" style="padding:var(--space-8);transition-delay:<?php echo ($index % 2) * 0.1; ?>s">
                <div class="feature-icon" style="width:56px;height:56px;margin-bottom:var(--space-4);">
                    <?php echo $service['icon']; ?>
                </div>
                <h3 style="font-size:1.15rem;margin-bottom:var(--space-2);"><?php echo e($lang === 'ar' ? $service['title_ar'] : $service['title_en']); ?></h3>
                <p style="color:var(--color-text-secondary);font-size:0.9375rem;line-height:1.7;margin:0;">
                    <?php echo e($lang === 'ar' ? $service['desc_ar'] : $service['desc_en']); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- CTA -->
        <div style="text-align:center;margin-top:var(--space-12);" class="reveal">
            <div class="card" style="padding:var(--space-10);background:linear-gradient(135deg,rgba(var(--color-primary-rgb),0.05),rgba(var(--color-secondary-rgb),0.05));border-color:rgba(var(--color-primary-rgb),0.15);">
                <h3 style="font-size:1.5rem;margin-bottom:var(--space-3);"><?php echo $lang === 'ar' ? 'هل تحتاج إلى خدمة مخصصة؟' : 'Need a custom service?'; ?></h3>
                <p style="color:var(--color-text-secondary);margin-bottom:var(--space-6);"><?php echo $lang === 'ar' ? 'تواصل معنا لمناقشة احتياجاتكم الخاصة' : 'Contact us to discuss your specific needs'; ?></p>
                <a href="<?php echo url('contact.php'); ?>" class="btn btn-primary btn-lg">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <?php echo __('contact_us'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
