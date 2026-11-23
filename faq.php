<?php
/**
 * FAQ Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'faq';

$pageTitle = ($lang === 'ar' ? 'الأسئلة الشائعة - ' : 'FAQ - ') . getSetting('site_name_' . $lang);
$pageDescription = $lang === 'ar' ? 'إجابات على الأسئلة الأكثر شيوعاً حول منتجاتنا وخدماتنا' : 'Answers to the most common questions about our products and services';
$canonicalUrl = url('faq.php');

// FAQ data
$faqs = [
    [
        'q_ar' => 'ما هي المنتجات التي تقدمونها؟',
        'q_en' => 'What products do you offer?',
        'a_ar' => 'نقدم مجموعة واسعة من المنتجات الكهربائية والصناعية تشمل: مولدات الكهرباء، أنظمة الطاقة الشمسية، أجهزة تحكم PLC، حساسات صناعية، لوحات كهربائية، مستلزمات مصانع، معدات تعبئة وتغليف، وأدوات سلامة مهنية.',
        'a_en' => 'We offer a wide range of electrical and industrial products including: electric generators, solar power systems, PLC controllers, industrial sensors, electrical panels, factory supplies, packaging equipment, and occupational safety tools.'
    ],
    [
        'q_ar' => 'هل تقدمون خدمات التركيب والصيانة؟',
        'q_en' => 'Do you provide installation and maintenance services?',
        'a_ar' => 'نعم، نقدم خدمات التركيب والتشغيل لجميع الأنظمة والمعدات. كما نوفر عقود صيانة دورية لضمان استمرارية عمل منشآتكم الصناعية.',
        'a_en' => 'Yes, we provide installation and commissioning services for all systems and equipment. We also offer periodic maintenance contracts to ensure the continuity of your industrial operations.'
    ],
    [
        'q_ar' => 'كيف يمكنني طلب عرض سعر؟',
        'q_en' => 'How can I request a quote?',
        'a_ar' => 'يمكنك طلب عرض سعر عبر نموذج الاتصال في صفحة "اتصل بنا"، أو عبر الواتساب على الأرقام المتوفرة في الموقع. يرجى تزويدنا بتفاصيل المنتج أو الخدمة المطلوبة والكمية.',
        'a_en' => 'You can request a quote through the contact form on our "Contact Us" page, or via WhatsApp on the numbers available on the website. Please provide details of the product or service required and the quantity.'
    ],
    [
        'q_ar' => 'ما هي مناطق التغطية لديكم؟',
        'q_en' => 'What areas do you cover?',
        'a_ar' => 'مقرنا الرئيسي في تعز - الحوبان، ونقدم خدماتنا لجميع المحافظات اليمنية. نعمل على توصيل المنتجات لجميع المناطق بأسرع وقت ممكن.',
        'a_en' => 'Our main office is in Taiz - Al-Hawban, and we provide our services to all Yemeni governorates. We work to deliver products to all areas in the shortest possible time.'
    ],
    [
        'q_ar' => 'هل المنتجات تأتي بضمان؟',
        'q_en' => 'Do products come with a warranty?',
        'a_ar' => 'نعم، جميع منتجاتنا تأتي بضمان المصنع. تختلف مدة الضمان حسب نوع المنتج والشركة المصنعة. يرجى الاستفسار عن تفاصيل الضمان عند الشراء.',
        'a_en' => 'Yes, all our products come with a manufacturer warranty. The warranty period varies depending on the product type and manufacturer. Please inquire about warranty details at the time of purchase.'
    ],
    [
        'q_ar' => 'كيف يمكنني التواصل معكم؟',
        'q_en' => 'How can I contact you?',
        'a_ar' => 'يمكنك التواصل معنا عبر: واتساب على الأرقام 781067910 أو 734009848 أو 783678560، أو عبر نموذج الاتصال في الموقع، أو زيارة مقرنا في تعز - الحوبان.',
        'a_en' => 'You can contact us via: WhatsApp on numbers 781067910 or 734009848 or 783678560, or through the contact form on the website, or by visiting our office in Taiz - Al-Hawban.'
    ],
    [
        'q_ar' => 'هل تقدمون خدمات الاستشارات الفنية؟',
        'q_en' => 'Do you offer technical consultation services?',
        'a_ar' => 'نعم، لدينا فريق هندسي متخصص جاهز لتقديم استشارات فنية مجانية لمساعدتك في اختيار أفضل الحلول لمنشأتك الصناعية.',
        'a_en' => 'Yes, we have a specialized engineering team ready to provide free technical consultations to help you choose the best solutions for your industrial facility.'
    ],
    [
        'q_ar' => 'ما هي طرق الدفع المتاحة؟',
        'q_en' => 'What payment methods are available?',
        'a_ar' => 'نقبل عدة طرق للدفع تشمل: الدفع النقدي، التحويل البنكي، والدفع عبر المحافظ الإلكترونية. يرجى التواصل معنا للتعرف على التفاصيل.',
        'a_en' => 'We accept several payment methods including: cash payment, bank transfer, and payment through electronic wallets. Please contact us for details.'
    ]
];

// Schema
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => []
];
foreach ($faqs as $faq) {
    $faqSchema['mainEntity'][] = [
        '@type' => 'Question',
        'name' => $lang === 'ar' ? $faq['q_ar'] : $faq['q_en'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $lang === 'ar' ? $faq['a_ar'] : $faq['a_en']
        ]
    ];
}
$schemaMarkup = json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo __('nav_faq'); ?></span>
            </nav>
            <h1 class="page-header-title"><?php echo __('faq_title'); ?></h1>
            <p class="page-header-subtitle"><?php echo __('faq_subtitle'); ?></p>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<section class="section">
    <div class="container">
        <div class="faq-list">
            <?php foreach ($faqs as $index => $faq): ?>
            <div class="faq-item reveal <?php echo $index === 0 ? 'active' : ''; ?>" style="transition-delay:<?php echo $index * 0.05; ?>s">
                <button class="faq-question" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                    <span><?php echo e($lang === 'ar' ? $faq['q_ar'] : $faq['q_en']); ?></span>
                    <svg class="faq-question-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="faq-answer" <?php echo $index !== 0 ? 'style="max-height:0;"' : ''; ?>>
                    <p><?php echo e($lang === 'ar' ? $faq['a_ar'] : $faq['a_en']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- CTA -->
        <div style="text-align:center;margin-top:var(--space-12);" class="reveal">
            <p style="margin-bottom:var(--space-4);"><?php echo $lang === 'ar' ? 'لم تجد إجابة لسؤالك؟' : 'Didn\'t find an answer to your question?'; ?></p>
            <a href="<?php echo url('contact.php'); ?>" class="btn btn-primary btn-lg">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <?php echo __('contact_us'); ?>
            </a>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
