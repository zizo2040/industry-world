<?php
/**
 * Product Detail Page
 */
require_once __DIR__ . '/config.php';
initApp();

$lang = getCurrentLang();
$activePage = 'products';

// Get product
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . url('products.php'));
    exit;
}

$product = Database::fetchOne(
    "SELECT p.*, c.name_{$lang} as category_name, c.slug as category_slug 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.slug = :slug AND p.is_active = 1 AND p.deleted_at IS NULL",
    ['slug' => $slug]
);

if (!$product) {
    http_response_code(404);
    header('Location: ' . url('404.php'));
    exit;
}

// SEO
$pageTitle = $product['meta_title_' . $lang] ?? $product['name_' . $lang] . ' - ' . getSetting('site_name_' . $lang);
$pageDescription = $product['meta_description_' . $lang] ?? $product['short_description_' . $lang];
$canonicalUrl = url('product.php?slug=' . $slug);

// Schema
$productSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name_' . $lang],
    'description' => $product['description_' . $lang] ?? '',
    'image' => $product['main_image'] ? uploadUrl($product['main_image']) : '',
    'sku' => $product['sku'] ?? '',
    'category' => $product['category_name'] ?? '',
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => $product['currency'] ?? 'YER',
        'price' => $product['price'] ?? '',
        'availability' => $product['stock_status'] === 'in_stock' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
    ],
    'brand' => [
        '@type' => 'Brand',
        'name' => getSetting('site_name_' . $lang)
    ]
];
$schemaMarkup = json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Fetch specifications
$specs = Database::fetchAll(
    "SELECT * FROM product_specifications WHERE product_id = :id ORDER BY sort_order",
    ['id' => $product['id']]
);

// Fetch gallery images
$images = Database::fetchAll(
    "SELECT * FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, sort_order",
    ['id' => $product['id']]
);

// Fetch related products
$related = Database::fetchAll(
    "SELECT p.*, c.name_{$lang} as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.category_id = :cat_id AND p.id != :id AND p.is_active = 1 AND p.deleted_at IS NULL 
     ORDER BY p.is_featured DESC LIMIT 4",
    ['cat_id' => $product['category_id'], 'id' => $product['id']]
);

// Breadcrumb
$breadcrumbs = [
    ['name' => __('nav_home'), 'url' => url()],
    ['name' => __('nav_products'), 'url' => url('products.php')],
    ['name' => $product['category_name'], 'url' => url('products.php?category=' . $product['category_slug'])],
    ['name' => $product['name_' . $lang], 'url' => null],
];

include BASE_PATH . '/includes/header.php';
?>

<div class="product-detail">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb reveal" aria-label="breadcrumb" style="margin-bottom:var(--space-6);">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php if ($i > 0): ?><span class="breadcrumb-separator">/</span><?php endif; ?>
                <?php if ($crumb['url']): ?>
                <a href="<?php echo e($crumb['url']); ?>"><?php echo e($crumb['name']); ?></a>
                <?php else: ?>
                <span class="breadcrumb-current"><?php echo e($crumb['name']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        
        <!-- Product Grid -->
        <div class="product-detail-grid">
            <!-- Gallery -->
            <div class="reveal-left">
                <div class="product-gallery">
                    <div class="product-gallery-main">
                        <?php if ($product['main_image']): ?>
                        <img src="<?php echo uploadUrl($product['main_image']); ?>" alt="<?php echo e($product['name_' . $lang]); ?>" id="main-product-image">
                        <?php else: ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-bg-alt),var(--color-border));display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:1.25rem;">
                            <?php echo e(getSetting('site_name_' . $lang)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($images) > 0): ?>
                    <div class="product-gallery-thumbs">
                        <?php if ($product['main_image']): ?>
                        <div class="product-gallery-thumb active">
                            <img src="<?php echo uploadUrl($product['main_image']); ?>" alt="<?php echo e($product['name_' . $lang]); ?>">
                        </div>
                        <?php endif; ?>
                        <?php foreach ($images as $img): ?>
                        <div class="product-gallery-thumb">
                            <img src="<?php echo uploadUrl($img['image_path']); ?>" alt="<?php echo e($img['alt_text_' . $lang] ?? $product['name_' . $lang]); ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Info -->
            <div class="reveal-right">
                <div class="product-meta" style="margin-bottom:var(--space-3);">
                    <span class="badge badge-primary"><?php echo e($product['category_name']); ?></span>
                    <?php if ($product['sku']): ?>
                    <span class="product-sku">SKU: <?php echo e($product['sku']); ?></span>
                    <?php endif; ?>
                    <?php if ($product['stock_status'] === 'in_stock'): ?>
                    <span class="badge badge-success"><?php echo $lang === 'ar' ? 'متوفر' : 'In Stock'; ?></span>
                    <?php elseif ($product['stock_status'] === 'low_stock'): ?>
                    <span class="badge badge-warning"><?php echo $lang === 'ar' ? 'كمية محدودة' : 'Low Stock'; ?></span>
                    <?php else: ?>
                    <span class="badge badge-secondary"><?php echo $lang === 'ar' ? 'عند الطلب' : 'On Demand'; ?></span>
                    <?php endif; ?>
                </div>
                
                <h1 style="font-size:1.75rem;margin-bottom:var(--space-4);"><?php echo e($product['name_' . $lang]); ?></h1>
                
                <?php if ($product['price']): ?>
                <div class="product-detail-price"><?php echo number_format($product['price'], 2); ?> <span style="font-size:1rem;font-weight:500;"><?php echo $product['currency']; ?></span></div>
                <?php else: ?>
                <div class="product-detail-price" style="font-size:1.25rem;color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'السعر عند الطلب' : 'Price on request'; ?></div>
                <?php endif; ?>
                
                <div style="margin-bottom:var(--space-6);">
                    <p style="color:var(--color-text-secondary);line-height:1.7;"><?php echo nl2br(e($product['description_' . $lang] ?? '')); ?></p>
                </div>
                
                <div class="product-actions">
                    <a href="https://wa.me/<?php echo getSetting('whatsapp_primary'); ?>?text=<?php echo urlencode(generateWhatsAppMessage('product_inquiry', ['product' => $product['name_' . $lang]])); ?>" class="btn btn-primary btn-lg" target="_blank" rel="noopener noreferrer" style="flex:1;"
                       data-ai-peek-text="<?php echo $lang === 'ar' ? 'أحتاج مساعدة في مواصفات هذا المنتج أو طريقة التركيب؟ اسألني!' : 'Need help with specs or installation? Ask me!'; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <?php echo $lang === 'ar' ? 'طلب عرض سعر' : 'Request Quote'; ?>
                    </a>
                    <a href="<?php echo url('contact.php?subject=' . urlencode($product['name_' . $lang])); ?>" class="btn btn-outline btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <?php echo $lang === 'ar' ? 'استفسار' : 'Inquiry'; ?>
                    </a>
                </div>
                
                <!-- Features & Applications -->
                <?php if ($product['features_' . $lang]): ?>
                <div style="margin-top:var(--space-6);padding-top:var(--space-6);border-top:1px solid var(--color-border);">
                    <h3 style="font-size:1.1rem;margin-bottom:var(--space-3);"><?php echo $lang === 'ar' ? 'المميزات' : 'Features'; ?></h3>
                    <div style="color:var(--color-text-secondary);line-height:1.8;">
                        <?php echo nl2br(e($product['features_' . $lang])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($product['applications_' . $lang]): ?>
                <div style="margin-top:var(--space-4);">
                    <h3 style="font-size:1.1rem;margin-bottom:var(--space-3);"><?php echo $lang === 'ar' ? 'التطبيقات' : 'Applications'; ?></h3>
                    <div style="color:var(--color-text-secondary);line-height:1.8;">
                        <?php echo nl2br(e($product['applications_' . $lang])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Specifications -->
        <?php if (!empty($specs)): ?>
        <div style="margin-top:var(--space-12);" class="reveal">
            <h2 style="font-size:1.5rem;margin-bottom:var(--space-6);"><?php echo __('specifications'); ?></h2>
            <div class="card" style="overflow:hidden;">
                <table class="specs-table">
                    <tbody>
                        <?php foreach ($specs as $spec): ?>
                        <tr>
                            <td><?php echo e($spec['spec_name_' . $lang]); ?></td>
                            <td><?php echo e($spec['spec_value_' . $lang]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Related Products -->
        <?php if (!empty($related)): ?>
        <div style="margin-top:var(--space-12);">
            <h2 style="font-size:1.5rem;margin-bottom:var(--space-6);" class="reveal"><?php echo __('related_products'); ?></h2>
            <div class="products-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
                <?php foreach ($related as $r): ?>
                <div class="product-card reveal">
                    <div class="product-image">
                        <?php if ($r['main_image']): ?>
                        <img src="<?php echo uploadUrl($r['main_image']); ?>" alt="<?php echo e($r['name_' . $lang]); ?>" loading="lazy">
                        <?php else: ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--color-bg-alt),var(--color-border));display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);">
                            <?php echo e($r['name_' . $lang]); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?php echo e($r['category_name']); ?></div>
                        <h3 class="product-title"><?php echo e($r['name_' . $lang]); ?></h3>
                        <div class="product-footer">
                            <?php if ($r['price']): ?>
                            <span class="product-price"><?php echo number_format($r['price'], 2); ?></span>
                            <?php endif; ?>
                            <a href="<?php echo url('product.php?slug=' . $r['slug']); ?>" class="btn btn-sm btn-primary"><?php echo __('view_details'); ?></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
