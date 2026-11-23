<?php
/**
 * Search Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'search';

$query = trim($_GET['q'] ?? '');
$results = [];

if ($query) {
    $searchTerm = "%{$query}%";
    
    // Search products
    $products = Database::fetchAll(
        "SELECT p.id, p.name_{$lang} as name, p.slug, p.short_description_{$lang} as description, p.main_image, 'product' as type,
         c.name_{$lang} as category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE (p.name_{$lang} LIKE :q OR p.description_{$lang} LIKE :q OR p.short_description_{$lang} LIKE :q)
         AND p.is_active = 1 AND p.deleted_at IS NULL
         LIMIT 10",
        ['q' => $searchTerm]
    );
    
    // Search categories
    $categories = Database::fetchAll(
        "SELECT id, name_{$lang} as name, slug, description_{$lang} as description, 'category' as type,
         NULL as category_name, NULL as main_image
         FROM categories
         WHERE (name_{$lang} LIKE :q OR description_{$lang} LIKE :q)
         AND is_active = 1 AND parent_id IS NULL
         LIMIT 5",
        ['q' => $searchTerm]
    );
    
    // Search portfolio
    $portfolio = Database::fetchAll(
        "SELECT p.id, p.title_{$lang} as name, p.slug, p.description_{$lang} as description, p.main_image, 'portfolio' as type,
         c.name_{$lang} as category_name
         FROM portfolio p
         LEFT JOIN portfolio_categories c ON p.category_id = c.id
         WHERE (p.title_{$lang} LIKE :q OR p.description_{$lang} LIKE :q)
         AND p.is_active = 1
         LIMIT 5",
        ['q' => $searchTerm]
    );
    
    $results = array_merge($products, $categories, $portfolio);
}

$pageTitle = ($lang === 'ar' ? 'البحث: ' : 'Search: ') . $query . ' - ' . getSetting('site_name_' . $lang);
$pageDescription = $lang === 'ar' ? 'نتائج البحث عن: ' . $query : 'Search results for: ' . $query;
$robotsMeta = 'noindex,follow'; // internal search results must not be indexed
$canonicalUrl = url('search.php?q=' . urlencode($query));

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo __('search'); ?></span>
            </nav>
            <h1 class="page-header-title"><?php echo __('search_title'); ?></h1>
        </div>
    </div>
</div>

<!-- Search Section -->
<section class="section">
    <div class="container" style="max-width:900px;">
        <!-- Search Form -->
        <div class="search-form-main reveal">
            <form action="<?php echo url('search.php'); ?>" method="GET" class="search-form" style="background:var(--color-bg-card);padding:var(--space-6);border-radius:var(--radius-xl);border:1px solid var(--color-border);">
                <input type="search" name="q" class="search-input" value="<?php echo e($query); ?>" placeholder="<?php echo __('search_placeholder'); ?>" aria-label="<?php echo __('search'); ?>" style="flex:1;background:var(--color-bg);" autofocus>
                <button type="submit" class="btn btn-primary" style="padding:var(--space-3) var(--space-5);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </form>
        </div>
        
        <?php if ($query): ?>
        <!-- Results -->
        <div class="reveal">
            <h2 style="font-size:1.15rem;margin-bottom:var(--space-4);color:var(--color-text-secondary);">
                <?php echo sprintf($lang === 'ar' ? 'نتائج البحث عن "%s" (%d نتيجة)' : 'Search results for "%s" (%d results)', e($query), count($results)); ?>
            </h2>
            
            <?php if (empty($results)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                <p class="empty-state-text"><?php echo __('no_results'); ?></p>
            </div>
            <?php else: ?>
            <div class="search-results-list">
                <?php foreach ($results as $result): ?>
                <a href="<?php 
                    switch ($result['type']) {
                        case 'product': echo url('product.php?slug=' . $result['slug']); break;
                        case 'category': echo url('products.php?category=' . $result['slug']); break;
                        case 'portfolio': echo url('portfolio.php#project-' . $result['id']); break;
                        default: echo url();
                    }
                ?>" class="search-result-item" style="text-decoration:none;color:inherit;">
                    <div class="search-result-image">
                        <?php if ($result['main_image'] ?? null): ?>
                        <img src="<?php echo uploadUrl($result['main_image']); ?>" alt="<?php echo e($result['name']); ?>" loading="lazy">
                        <?php else: ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-primary),var(--color-primary-dark));display:flex;align-items:center;justify-content:center;color:white;font-size:1.5rem;font-weight:700;">
                            <?php echo mb_substr($result['name'], 0, 1); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="search-result-content">
                        <span class="search-result-type"><?php echo $result['type'] === 'product' ? ($lang === 'ar' ? 'منتج' : 'Product') : ($result['type'] === 'category' ? ($lang === 'ar' ? 'فئة' : 'Category') : ($lang === 'ar' ? 'مشروع' : 'Project')); ?></span>
                        <h3 style="font-size:1.05rem;margin-bottom:var(--space-1);"><?php echo e($result['name']); ?></h3>
                        <p><?php echo e($result['description'] ?? ''); ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
