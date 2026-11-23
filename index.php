<?php
/**
 * Home Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'home';

// SEO
$seo = getSeoSettings('home');
$pageTitle = $seo['title'];
$pageDescription = $seo['description'];
$ogTitle = $seo['og_title'];
$ogDescription = $seo['og_description'];
$ogType = $seo['og_type'];
$twitterCard = $seo['twitter_card'];
$canonicalUrl = url();
$schemaMarkup = ''; // Organization/LocalBusiness + WebSite are printed site-wide in includes/header.php

// Fetch featured products
$featuredProducts = Database::fetchAll(
    "SELECT p.*, c.name_{$lang} as category_name, c.slug as category_slug 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.is_featured = 1 AND p.is_active = 1 AND p.deleted_at IS NULL 
     ORDER BY p.created_at DESC 
     LIMIT 6"
);

// Fetch parent categories
$categories = Database::fetchAll(
    "SELECT id, name_{$lang} as name, slug, description_{$lang} as description, icon, image 
     FROM categories 
     WHERE parent_id IS NULL AND is_active = 1 
     ORDER BY sort_order"
);

// Fetch featured portfolio
$portfolio = Database::fetchAll(
    "SELECT p.*, c.name_{$lang} as category_name 
     FROM portfolio p 
     LEFT JOIN portfolio_categories c ON p.category_id = c.id 
     WHERE p.is_featured = 1 AND p.is_active = 1 
     ORDER BY p.created_at DESC 
     LIMIT 6"
);

// Stats
$totalProducts = Database::fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND deleted_at IS NULL")['count'] ?? 0;
$totalCategories = Database::fetchOne("SELECT COUNT(*) as count FROM categories WHERE is_active = 1 AND parent_id IS NULL")['count'] ?? 0;
$totalProjects = Database::fetchOne("SELECT COUNT(*) as count FROM portfolio WHERE is_active = 1")['count'] ?? 0;

// Brand strip above the "Why Choose Us" section: pulled dynamically from
// the `brand_logos` table (managed in Admin > File Library > Brand Logos)
// instead of a hardcoded list. Only active logos are fetched, already
// ordered by display_order. If the table has no active logos at all, the
// section below is skipped entirely so no empty strip is ever rendered.
$partnerBrands = getActiveBrandLogos();

include BASE_PATH . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg-pattern" data-parallax="0.15"></div>
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    <?php echo $lang === 'ar' ? 'نحن هنا لخدمتكم' : 'We are here to serve you'; ?>
                </div>
                <h1 class="hero-title">
                    <?php echo $lang === 'ar' ? 'حلول كهربائية وصناعية <span class="highlight">متكاملة</span>' : 'Integrated <span class="highlight">Electrical & Industrial</span> Solutions'; ?>
                </h1>
                <p class="hero-subtitle">
                    <?php echo getSetting('site_tagline_' . $lang); ?>
                </p>
                <div class="hero-actions">
                    <a href="<?php echo url('products.php'); ?>" class="btn btn-primary btn-lg btn-magnetic"
                       data-ai-peek-text="<?php echo $lang === 'ar' ? 'هل تريد أن أساعدك في اختيار المنتج المناسب؟' : 'Want help picking the right product?'; ?>">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                        <?php echo __('hero_cta_primary'); ?>
                    </a>
                    <a href="<?php echo e(whatsappLink(getSetting('whatsapp_primary'))); ?>" class="btn btn-outline btn-lg" target="_blank" rel="noopener noreferrer">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <?php echo __('hero_cta_secondary'); ?>
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        </div>
                        <div class="hero-stat-number" data-count="<?php echo $totalProducts; ?>">0</div>
                        <div class="hero-stat-label"><?php echo $lang === 'ar' ? 'منتج' : 'Products'; ?></div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        </div>
                        <div class="hero-stat-number" data-count="<?php echo $totalCategories; ?>">0</div>
                        <div class="hero-stat-label"><?php echo $lang === 'ar' ? 'فئة' : 'Categories'; ?></div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </div>
                        <div class="hero-stat-number" data-count="<?php echo $totalProjects; ?>">0</div>
                        <div class="hero-stat-label"><?php echo $lang === 'ar' ? 'مشروع' : 'Projects'; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="hero-visual">
                <div class="hero-image-wrapper">
                    <picture>
                        <source media="(max-width: 768px)" srcset="<?php echo e(get_page_image('home_hero', 'mobile')); ?>">
                        <img src="<?php echo e(get_page_image('home_hero', 'desktop')); ?>" alt="<?php echo e(getSetting('site_name_' . $lang)); ?>" class="hero-image" loading="eager">
                    </picture>
                </div>
                <div class="hero-floating-card card-1">
                    <div class="floating-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div class="floating-card-text"><?php echo $lang === 'ar' ? 'جودة مضمونة' : 'Quality Assured'; ?></div>
                </div>
                <div class="hero-floating-card card-2">
                    <div class="floating-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div class="floating-card-text"><?php echo $lang === 'ar' ? 'توصيل سريع' : 'Fast Delivery'; ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($partnerBrands)): ?>
<!-- Brand Marquee -->
<section class="brand-marquee-section" aria-label="<?php echo $lang === 'ar' ? 'أبرز العلامات التجارية التي نوفرها' : 'Leading brands we carry'; ?>">
    <div class="brand-marquee">
        <div class="brand-marquee-track">
            <?php foreach (array_merge($partnerBrands, $partnerBrands) as $brand): ?>
            <div class="brand-marquee-item">
                <img
                    src="<?php echo e(uploadUrl($brand['logo_path'])); ?>"
                    alt="<?php echo e($brand['brand_name']); ?>"
                    class="brand-marquee-logo"
                    loading="lazy"
                    decoding="async"
                    onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('span'),{className:'brand-marquee-name',textContent:'<?php echo e($brand['brand_name']); ?>'}));"
                >
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="section features-section">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                <?php echo $lang === 'ar' ? 'لماذا نحن' : 'Why Choose Us'; ?>
            </span>
            <h2 class="section-title"><?php echo $lang === 'ar' ? 'نقدم لكم الأفضل دائماً' : 'We Always Deliver the Best'; ?></h2>
            <p class="section-subtitle"><?php echo $lang === 'ar' ? 'نحن نلتزم بتقديم أعلى معايير الجودة في كل ما نقوم به' : 'We are committed to delivering the highest quality standards in everything we do'; ?></p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card reveal">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <h3 class="feature-title"><?php echo __('feature_quality'); ?></h3>
                <p class="feature-desc"><?php echo __('feature_quality_desc'); ?></p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <h3 class="feature-title"><?php echo __('feature_expert'); ?></h3>
                <p class="feature-desc"><?php echo __('feature_expert_desc'); ?></p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"></path><path d="M12 20V4"></path><path d="M6 20v-6"></path></svg>
                </div>
                <h3 class="feature-title"><?php echo __('feature_support'); ?></h3>
                <p class="feature-desc"><?php echo __('feature_support_desc'); ?></p>
            </div>
            <div class="feature-card reveal">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                </div>
                <h3 class="feature-title"><?php echo __('feature_delivery'); ?></h3>
                <p class="feature-desc"><?php echo __('feature_delivery_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section categories-section">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                <?php echo $lang === 'ar' ? 'فئات المنتجات' : 'Product Categories'; ?>
            </span>
            <h2 class="section-title"><?php echo $lang === 'ar' ? 'استكشف مجموعاتنا' : 'Explore Our Categories'; ?></h2>
            <p class="section-subtitle"><?php echo $lang === 'ar' ? 'مجموعة واسعة من المنتجات الكهربائية والصناعية عالية الجودة' : 'A wide range of high-quality electrical and industrial products'; ?></p>
        </div>
        
        <div class="categories-grid">
            <?php foreach ($categories as $index => $cat): ?>
            <div class="category-card reveal" style="transition-delay: <?php echo $index * 0.1; ?>s">
                <div class="category-card-image">
                    <?php if ($cat['image']): ?>
                    <img src="<?php echo uploadUrl($cat['image']); ?>" alt="<?php echo e($cat['name']); ?>" loading="lazy">
                    <?php else: ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-primary),var(--color-primary-dark));display:flex;align-items:center;justify-content:center;color:white;font-size:3rem;font-weight:800;">
                        <?php echo mb_substr($cat['name'], 0, 1); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="category-card-body">
                    <div class="category-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    </div>
                    <h3 class="category-title"><?php echo e($cat['name']); ?></h3>
                    <p class="category-count">
                        <?php 
                        $count = Database::fetchOne(
                            "SELECT COUNT(*) as count FROM products WHERE category_id IN (SELECT id FROM categories WHERE id = ? OR parent_id = ?) AND is_active = 1",
                            [$cat['id'], $cat['id']]
                        )['count'] ?? 0;
                        echo $count . ' ' . ($lang === 'ar' ? 'منتج' : 'Products');
                        ?>
                    </p>
                    <a href="<?php echo url('products.php?category=' . $cat['slug']); ?>" class="btn btn-sm btn-outline" style="margin-top:1rem">
                        <?php echo __('view_details'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section products-section">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                <?php echo $lang === 'ar' ? 'منتجات مميزة' : 'Featured Products'; ?>
            </span>
            <h2 class="section-title"><?php echo __('featured_products'); ?></h2>
            <p class="section-subtitle"><?php echo $lang === 'ar' ? 'أفضل منتجاتنا المختارة بعناية' : 'Our best hand-picked products'; ?></p>
        </div>
        
        <?php if (!empty($featuredProducts)): ?>
        <div class="products-slider" id="products-slider">
            <div class="products-slider-viewport">
                <div class="products-track">
                    <?php foreach ($featuredProducts as $index => $product): ?>
                    <div class="product-slide">
                        <div class="product-card reveal" style="transition-delay: <?php echo min($index, 3) * 0.1; ?>s">
                            <div class="product-image">
                                <?php if ($product['main_image']): ?>
                                <img src="<?php echo uploadUrl($product['main_image']); ?>" alt="<?php echo e($product['name_' . $lang]); ?>" loading="lazy">
                                <?php else: ?>
                                <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-bg-alt),var(--color-border));display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:0.875rem;">
                                    <?php echo e(getSetting('site_name_' . $lang)); ?>
                                </div>
                                <?php endif; ?>
                                <div class="product-badges">
                                    <span class="badge badge-secondary"><?php echo $lang === 'ar' ? 'مميز' : 'Featured'; ?></span>
                                </div>
                            </div>
                            <div class="product-body">
                                <div class="product-category"><?php echo e($product['category_name']); ?></div>
                                <h3 class="product-title"><?php echo e($product['name_' . $lang]); ?></h3>
                                <p class="product-desc"><?php echo e($product['short_description_' . $lang] ?? ''); ?></p>
                                <div class="product-footer">
                                    <?php if ($product['price']): ?>
                                    <span class="product-price"><?php echo number_format($product['price'], 2); ?> <?php echo $product['currency']; ?></span>
                                    <?php else: ?>
                                    <span class="product-price" style="font-size:0.875rem;color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'السعر عند الطلب' : 'Price on request'; ?></span>
                                    <?php endif; ?>
                                    <a href="<?php echo url('product.php?slug=' . $product['slug']); ?>" class="btn btn-sm btn-primary">
                                        <?php echo __('view_details'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="button" class="slider-arrow slider-arrow-prev" aria-label="<?php echo $lang === 'ar' ? 'السابق' : 'Previous'; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button type="button" class="slider-arrow slider-arrow-next" aria-label="<?php echo $lang === 'ar' ? 'التالي' : 'Next'; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <div class="slider-dots"></div>
        </div>
        <?php else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:var(--space-12);">
            <p style="color:var(--color-text-muted);"><?php echo __('no_products'); ?></p>
        </div>
        <?php endif; ?>
        
        <div style="text-align:center;margin-top:var(--space-10);">
            <a href="<?php echo url('products.php'); ?>" class="btn btn-primary btn-lg">
                <?php echo $lang === 'ar' ? 'عرض جميع المنتجات' : 'View All Products'; ?>
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</section>

<!-- Portfolio Section -->
<section class="section portfolio-section">
    <div class="container">
        <div class="section-header reveal">
            <span class="section-label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                <?php echo $lang === 'ar' ? 'أعمالنا' : 'Our Work'; ?>
            </span>
            <h2 class="section-title"><?php echo __('portfolio_title'); ?></h2>
            <p class="section-subtitle"><?php echo __('portfolio_subtitle'); ?></p>
        </div>
        
        <div class="portfolio-grid">
            <?php foreach ($portfolio as $index => $project): ?>
            <div class="portfolio-card reveal" style="transition-delay: <?php echo $index * 0.1; ?>s">
                <?php if ($project['main_image']): ?>
                <img src="<?php echo uploadUrl($project['main_image']); ?>" alt="<?php echo e($project['title_' . $lang]); ?>" class="portfolio-card-image" loading="lazy">
                <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-primary),var(--color-primary-dark));display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;font-weight:700;">
                    <?php echo e($project['title_' . $lang]); ?>
                </div>
                <?php endif; ?>
                <div class="portfolio-card-overlay">
                    <span class="badge badge-primary" style="margin-bottom:var(--space-2);align-self:flex-start;"><?php echo e($project['category_name']); ?></span>
                    <h3 class="portfolio-card-title"><?php echo e($project['title_' . $lang]); ?></h3>
                    <p class="portfolio-card-meta">
                        <?php if ($project['client_name']): ?>
                        <?php echo e($project['client_name']); ?> | 
                        <?php endif; ?>
                        <?php if ($project['location_' . $lang]): ?>
                        <?php echo e($project['location_' . $lang]); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align:center;margin-top:var(--space-10);">
            <a href="<?php echo url('portfolio.php'); ?>" class="btn btn-outline btn-lg">
                <?php echo $lang === 'ar' ? 'عرض جميع المشاريع' : 'View All Projects'; ?>
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-bg-pattern"></div>
    <div class="container">
        <div class="cta-content reveal">
            <h2 class="cta-title"><?php echo $lang === 'ar' ? 'هل تحتاج إلى استشارة فنية؟' : 'Need Technical Consultation?'; ?></h2>
            <p class="cta-text"><?php echo $lang === 'ar' ? 'فريقنا الهندسي جاهز لمساعدتك في اختيار أفضل الحلول لمنشأتك الصناعية' : 'Our engineering team is ready to help you choose the best solutions for your industrial facility'; ?></p>
            <div class="cta-actions">
                <a href="<?php echo e(whatsappLink(getSetting('whatsapp_primary'))); ?>" class="cta-btn cta-btn-primary" target="_blank" rel="noopener noreferrer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    <?php echo $lang === 'ar' ? 'تواصل عبر واتساب' : 'Contact via WhatsApp'; ?>
                </a>
                <a href="<?php echo url('contact.php'); ?>" class="cta-btn cta-btn-secondary">
                    <?php echo $lang === 'ar' ? 'نموذج الاتصال' : 'Contact Form'; ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
