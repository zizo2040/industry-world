<?php
/**
 * Portfolio Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'portfolio';

// SEO
$seo = getSeoSettings('portfolio');
$pageTitle = $seo['title'];
$pageDescription = $seo['description'];
$canonicalUrl = url('portfolio.php');

// Get category filter
$categoryFilter = $_GET['category'] ?? '';
$currentFilter = null;

if ($categoryFilter) {
    $currentFilter = Database::fetchOne(
        "SELECT * FROM portfolio_categories WHERE slug = :slug AND is_active = 1",
        ['slug' => $categoryFilter]
    );
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;

// Build query
$where = "p.is_active = 1";
$params = [];

if ($currentFilter) {
    $where .= " AND p.category_id = :cat_id";
    $params['cat_id'] = $currentFilter['id'];
}

$countSql = "SELECT COUNT(*) as total FROM portfolio p WHERE {$where}";
$total = Database::fetchOne($countSql, $params)['total'] ?? 0;
$pagination = paginate($total, $page, $perPage);

// Fetch portfolio
$sql = "SELECT p.*, c.name_{$lang} as category_name 
        FROM portfolio p 
        LEFT JOIN portfolio_categories c ON p.category_id = c.id 
        WHERE {$where} 
        ORDER BY p.is_featured DESC, p.created_at DESC 
        LIMIT :limit OFFSET :offset";
$params['limit'] = $perPage;
$params['offset'] = $pagination['offset'];
$projects = Database::fetchAll($sql, $params);

// Fetch categories for filter
$portfolioCategories = Database::fetchAll(
    "SELECT id, name_{$lang} as name, slug FROM portfolio_categories WHERE is_active = 1 ORDER BY sort_order"
);

include BASE_PATH . '/includes/header.php';
?>

<!-- Page Banner (plain image, no overlay) -->
<div class="page-banner">
    <picture>
        <source media="(max-width: 768px)" srcset="<?php echo e(get_page_image('portfolio_banner', 'mobile')); ?>">
        <img src="<?php echo e(get_page_image('portfolio_banner', 'desktop')); ?>" alt="" loading="eager">
    </picture>
</div>

<!-- Page Title -->
<div class="page-header after-banner">
    <div class="container">
        <div class="page-header-content reveal">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current"><?php echo __('nav_portfolio'); ?></span>
            </nav>
            <h1 class="page-header-title"><?php echo __('portfolio_title'); ?></h1>
            <p class="page-header-subtitle"><?php echo __('portfolio_subtitle'); ?></p>
        </div>
    </div>
</div>

<!-- Portfolio Section -->
<section class="section">
    <div class="container">
        <!-- Filter -->
        <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;justify-content:center;margin-bottom:var(--space-10);" class="reveal">
            <a href="<?php echo url('portfolio.php'); ?>" class="badge <?php echo !$currentFilter ? 'badge-primary' : 'badge-secondary'; ?>" style="padding:var(--space-2) var(--space-4);font-size:0.875rem;cursor:pointer;">
                <?php echo __('all'); ?>
            </a>
            <?php foreach ($portfolioCategories as $cat): ?>
            <a href="<?php echo url('portfolio.php?category=' . $cat['slug']); ?>" class="badge <?php echo $currentFilter && $currentFilter['id'] == $cat['id'] ? 'badge-primary' : 'badge-secondary'; ?>" style="padding:var(--space-2) var(--space-4);font-size:0.875rem;cursor:pointer;">
                <?php echo e($cat['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Grid -->
        <div class="portfolio-grid">
            <?php foreach ($projects as $index => $project): ?>
            <div class="portfolio-card reveal" style="transition-delay:<?php echo ($index % 3) * 0.1; ?>s">
                <?php if ($project['main_image']): ?>
                <img src="<?php echo uploadUrl($project['main_image']); ?>" alt="<?php echo e($project['title_' . $lang]); ?>" class="portfolio-card-image" loading="lazy">
                <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-primary),var(--color-primary-dark));display:flex;align-items:center;justify-content:center;color:white;text-align:center;padding:var(--space-6);">
                    <div>
                        <div style="font-size:2rem;font-weight:700;margin-bottom:var(--space-2);"><?php echo e($project['title_' . $lang]); ?></div>
                        <span class="badge" style="background:rgba(255,255,255,0.2);color:white;"><?php echo e($project['category_name']); ?></span>
                    </div>
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
                    <p style="font-size:0.8125rem;color:rgba(255,255,255,0.7);margin-top:var(--space-1);">
                        <?php if ($project['completion_date']): ?>
                        <?php echo formatDate($project['completion_date'], $lang === 'ar' ? 'M Y' : 'F Y'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($projects)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:var(--space-12);">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" style="margin:0 auto var(--space-4);opacity:0.5;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                <p style="color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'لا توجد مشاريع حالياً' : 'No projects available yet'; ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <?php echo renderPagination($pagination, $canonicalUrl . ($categoryFilter ? '?category=' . $categoryFilter : '')); ?>
        <?php endif; ?>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
