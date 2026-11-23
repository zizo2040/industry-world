<?php
/**
 * Products Listing Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'products';

// Get category filter
$categorySlug = $_GET['category'] ?? '';
$currentCategory = null;

if ($categorySlug) {
    $currentCategory = Database::fetchOne(
        "SELECT * FROM categories WHERE slug = :slug AND is_active = 1",
        ['slug' => $categorySlug]
    );
}

// SEO
$seo = getSeoSettings('products');
$pageTitle = $currentCategory ? (($currentCategory['meta_title_' . $lang] ?? '') ?: ($currentCategory['name_' . $lang] . ' - ' . getSetting('site_name_' . $lang))) : $seo['title'];
$pageDescription = $currentCategory ? (($currentCategory['meta_description_' . $lang] ?? '') ?: ($currentCategory['description_' . $lang] ?? '')) : $seo['description'];
$canonicalUrl = url('products.php' . ($currentCategory ? '?category=' . rawurlencode($currentCategory['slug']) : ''));
if ($currentCategory) {
    $pageKeywords = implode('، ', array_filter([
        $currentCategory['name_' . $lang],
        ($lang === 'ar' ? 'بيع ' : 'buy ') . $currentCategory['name_' . $lang],
        $currentCategory['name_' . $lang] . ($lang === 'ar' ? ' تعز' : ' Taiz'),
        $currentCategory['name_' . $lang] . ($lang === 'ar' ? ' اليمن' : ' Yemen'),
        getSetting('site_name_' . $lang),
    ]));
}
// Paginated pages are distinct pages for search engines
if (!empty($_GET['page']) && (int) $_GET['page'] > 1) {
    $canonicalUrl .= (str_contains($canonicalUrl, '?') ? '&' : '?') . 'page=' . (int) $_GET['page'];
}
// Unknown category slug → noindex instead of duplicating the main listing
if ($categorySlug && !$currentCategory) { $robotsMeta = 'noindex,follow'; }

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;

// Build query
$where = "p.is_active = 1 AND p.deleted_at IS NULL";
$params = [];

if ($currentCategory) {
    $where .= " AND (p.category_id = :cat_id OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat_id))";
    $params['cat_id'] = $currentCategory['id'];
}

// Count total
$countSql = "SELECT COUNT(*) as total FROM products p WHERE {$where}";
$total = Database::fetchOne($countSql, $params)['total'] ?? 0;
$pagination = paginate($total, $page, $perPage);

// Fetch products
$sql = "SELECT p.*, c.name_{$lang} as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE {$where} 
        ORDER BY p.is_featured DESC, p.created_at DESC 
        LIMIT :limit OFFSET :offset";

$params['limit'] = $perPage;
$params['offset'] = $pagination['offset'];
$products = Database::fetchAll($sql, $params);

// Fetch parent categories for sidebar
$parentCategories = Database::fetchAll(
    "SELECT id, name_{$lang} as name, slug, icon FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order"
);

// Breadcrumb
$breadcrumbs = [
    ['name' => __('nav_home'), 'url' => url()],
    ['name' => __('nav_products'), 'url' => url('products.php')],
];
if ($currentCategory) {
    $breadcrumbs[] = ['name' => $currentCategory['name_' . $lang], 'url' => null];
}

$schemaMarkup = generateBreadcrumbSchema($breadcrumbs);

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Banner (plain image, no overlay) -->
<div class="page-banner">
    <picture>
        <source media="(max-width: 768px)" srcset="<?php echo e(get_page_image('products_banner', 'mobile')); ?>">
        <img src="<?php echo e(get_page_image('products_banner', 'desktop')); ?>" alt="" loading="eager">
    </picture>
</div>

<!-- Page Title -->
<div class="page-header after-banner">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <?php if ($i > 0): ?><span class="breadcrumb-separator">/</span><?php endif; ?>
                    <?php if ($crumb['url']): ?>
                    <a href="<?php echo e($crumb['url']); ?>"><?php echo e($crumb['name']); ?></a>
                    <?php else: ?>
                    <span class="breadcrumb-current"><?php echo e($crumb['name']); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <h1 class="page-header-title"><?php echo $currentCategory ? e($currentCategory['name_' . $lang]) : __('products_title'); ?></h1>
            <p class="page-header-subtitle"><?php echo $currentCategory ? e($currentCategory['description_' . $lang]) : __('products_subtitle'); ?></p>
        </div>
    </div>
</div>

<!-- Products Section -->
<section class="section">
    <div class="container">
        <div class="products-layout">
            <!-- Sidebar -->
            <aside class="reveal-left">
                <div class="card" style="padding:var(--space-5);">
                    <h3 style="font-size:1.05rem;margin-bottom:var(--space-4);"><?php echo $lang === 'ar' ? 'الفئات' : 'Categories'; ?></h3>
                    <ul style="list-style:none;">
                        <li style="margin-bottom:var(--space-2);">
                            <a href="<?php echo url('products.php'); ?>" class="nav-dropdown-item" style="border-radius:var(--radius-md);<?php echo !$currentCategory ? 'background:rgba(var(--color-primary-rgb),0.08);color:var(--color-primary);' : ''; ?>">
                                <?php echo $lang === 'ar' ? 'جميع المنتجات' : 'All Products'; ?>
                            </a>
                        </li>
                        <?php foreach ($parentCategories as $cat): ?>
                        <li style="margin-bottom:var(--space-2);">
                            <a href="<?php echo url('products.php?category=' . $cat['slug']); ?>" class="nav-dropdown-item" style="border-radius:var(--radius-md);<?php echo $currentCategory && $currentCategory['id'] == $cat['id'] ? 'background:rgba(var(--color-primary-rgb),0.08);color:var(--color-primary);' : ''; ?>">
                                <?php echo e($cat['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
            
            <!-- Products Grid -->
            <div>
                <div class="products-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
                    <?php foreach ($products as $index => $product): ?>
                    <div class="product-card reveal" style="transition-delay:<?php echo ($index % 3) * 0.1; ?>s">
                        <div class="product-image">
                            <?php if ($product['main_image']): ?>
                            <img src="<?php echo uploadUrl($product['main_image']); ?>" alt="<?php echo e($product['name_' . $lang]); ?>" loading="lazy">
                            <?php else: ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-bg-alt),var(--color-border));display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:0.875rem;">
                                <?php echo e(getSetting('site_name_' . $lang)); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($product['is_featured']): ?>
                            <div class="product-badges">
                                <span class="badge badge-secondary"><?php echo $lang === 'ar' ? 'مميز' : 'Featured'; ?></span>
                            </div>
                            <?php endif; ?>
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
                    <?php endforeach; ?>
                    
                    <?php if (empty($products)): ?>
                    <div class="empty-state" style="grid-column:1/-1;">
                        <div class="empty-state-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                        </div>
                        <p class="empty-state-text"><?php echo __('no_products'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <?php echo renderPagination($pagination, $canonicalUrl); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
