<?php
/**
 * Admin Products Management
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'products';
$pageTitle = ($lang === 'ar' ? 'المنتجات - ' : 'Products - ') . getSetting('site_name_' . $lang);

// Handle actions
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$message = '';
$error = '';

// Delete
if ($action === 'delete' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        Database::softDelete('products', 'id = :id', ['id' => $id]);
        logActivity('delete', 'products', $id, 'Product deleted');
        setFlash('success', $lang === 'ar' ? 'تم حذف المنتج بنجاح' : 'Product deleted successfully');
    }
    header('Location: ' . adminUrl('products.php'));
    exit;
}

// Toggle status
if ($action === 'toggle' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        $product = Database::fetchOne("SELECT is_active FROM products WHERE id = :id", ['id' => $id]);
        if ($product) {
            $newStatus = $product['is_active'] ? 0 : 1;
            Database::update('products', ['is_active' => $newStatus], 'id = :id', ['id' => $id]);
            logActivity('toggle_status', 'products', $id, 'Status changed to ' . $newStatus);
            setFlash('success', $lang === 'ar' ? 'تم تحديث الحالة' : 'Status updated');
        }
    }
    header('Location: ' . adminUrl('products.php'));
    exit;
}

// Save product (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $data = [
            'category_id' => intval($_POST['category_id'] ?? 0),
            'name_ar' => sanitize($_POST['name_ar'] ?? ''),
            'name_en' => sanitize($_POST['name_en'] ?? ''),
            'slug' => createSlug($_POST['name_en'] ?? $_POST['name_ar'] ?? ''),
            'description_ar' => $_POST['description_ar'] ?? '',
            'description_en' => $_POST['description_en'] ?? '',
            'short_description_ar' => sanitize($_POST['short_description_ar'] ?? ''),
            'short_description_en' => sanitize($_POST['short_description_en'] ?? ''),
            'features_ar' => $_POST['features_ar'] ?? '',
            'features_en' => $_POST['features_en'] ?? '',
            'applications_ar' => $_POST['applications_ar'] ?? '',
            'applications_en' => $_POST['applications_en'] ?? '',
            'price' => floatval($_POST['price'] ?? 0) ?: null,
            'currency' => sanitize($_POST['currency'] ?? 'YER'),
            'sku' => sanitize($_POST['sku'] ?? ''),
            'stock_status' => sanitize($_POST['stock_status'] ?? 'in_stock'),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        // Handle image upload
        if (!empty($_FILES['main_image']['tmp_name'])) {
            $upload = uploadFile($_FILES['main_image'], 'products');
            if ($upload['success']) {
                $data['main_image'] = $upload['path'];
            }
        }
        
        try {
            if ($action === 'edit' && $id) {
                Database::update('products', $data, 'id = :id', ['id' => $id]);
                logActivity('update', 'products', $id, 'Product updated');
                setFlash('success', $lang === 'ar' ? 'تم تحديث المنتج بنجاح' : 'Product updated successfully');
            } else {
                if (empty($data['slug'])) $data['slug'] = createSlug($data['name_en'] ?: $data['name_ar']);
                $newId = Database::insert('products', $data);
                logActivity('create', 'products', $newId, 'Product created');
                setFlash('success', $lang === 'ar' ? 'تم إضافة المنتج بنجاح' : 'Product added successfully');
            }
            header('Location: ' . adminUrl('products.php'));
            exit;
        } catch (Exception $e) {
            $error = $lang === 'ar' ? 'حدث خطأ: ' : 'Error: ';
        }
    }
}

// Get product for edit
$product = null;
if ($action === 'edit' && $id) {
    $product = Database::fetchOne("SELECT * FROM products WHERE id = :id", ['id' => $id]);
}

// Get categories for dropdown
$categories = Database::fetchAll("SELECT id, name_{$lang} as name FROM categories WHERE is_active = 1 ORDER BY sort_order");

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$total = Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE deleted_at IS NULL")['c'] ?? 0;
$pagination = paginate($total, $page, $perPage);

$products = Database::fetchAll(
    "SELECT p.*, c.name_{$lang} as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.deleted_at IS NULL 
     ORDER BY p.created_at DESC 
     LIMIT :limit OFFSET :offset",
    ['limit' => $perPage, 'offset' => $pagination['offset']]
);

include __DIR__ . '/includes/header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>

<!-- Add/Edit Form -->
<div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);" class="reveal">
    <a href="<?php echo adminUrl('products.php'); ?>" class="btn btn-ghost btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    </a>
    <h1 style="font-size:1.35rem;"><?php echo $action === 'edit' ? ($lang === 'ar' ? 'تعديل منتج' : 'Edit Product') : ($lang === 'ar' ? 'إضافة منتج' : 'Add Product'); ?></h1>
</div>

<div class="admin-card reveal">
    <div class="admin-card-body">
        <?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:var(--space-5);"><?php echo e($error); ?></div><?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
            <?php echo csrfField(); ?>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (عربي)' : 'Name (Arabic)'; ?> <span class="required">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?php echo e($product['name_ar'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (إنجليزي)' : 'Name (English)'; ?> <span class="required">*</span></label>
                    <input type="text" name="name_en" class="form-control" value="<?php echo e($product['name_en'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الفئة' : 'Category'; ?> <span class="required">*</span></label>
                <select name="category_id" class="form-control" required>
                    <option value=""><?php echo $lang === 'ar' ? 'اختر الفئة' : 'Select Category'; ?></option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'السعر' : 'Price'; ?></label>
                    <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $product['price'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'SKU' : 'SKU'; ?></label>
                    <input type="text" name="sku" class="form-control" value="<?php echo e($product['sku'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'حالة المخزون' : 'Stock Status'; ?></label>
                    <select name="stock_status" class="form-control">
                        <option value="in_stock" <?php echo ($product['stock_status'] ?? '') === 'in_stock' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'متوفر' : 'In Stock'; ?></option>
                        <option value="low_stock" <?php echo ($product['stock_status'] ?? '') === 'low_stock' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'كمية محدودة' : 'Low Stock'; ?></option>
                        <option value="out_of_stock" <?php echo ($product['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'غير متوفر' : 'Out of Stock'; ?></option>
                        <option value="on_demand" <?php echo ($product['stock_status'] ?? '') === 'on_demand' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'عند الطلب' : 'On Demand'; ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الصورة الرئيسية' : 'Main Image'; ?></label>
                    <input type="file" name="main_image" class="form-control" accept="image/*">
                    <?php if (!empty($product['main_image'])): ?>
                    <div style="margin-top:var(--space-2);">
                        <img src="<?php echo uploadUrl($product['main_image']); ?>" alt="" style="max-width:150px;border-radius:var(--radius-md);">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف المختصر (عربي)' : 'Short Description (Arabic)'; ?></label>
                <textarea name="short_description_ar" class="form-control" rows="2"><?php echo e($product['short_description_ar'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف المختصر (إنجليزي)' : 'Short Description (English)'; ?></label>
                <textarea name="short_description_en" class="form-control" rows="2"><?php echo e($product['short_description_en'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (عربي)' : 'Description (Arabic)'; ?></label>
                <textarea name="description_ar" class="form-control" rows="4"><?php echo e($product['description_ar'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (إنجليزي)' : 'Description (English)'; ?></label>
                <textarea name="description_en" class="form-control" rows="4"><?php echo e($product['description_en'] ?? ''); ?></textarea>
            </div>
            
            <div style="display:flex;gap:var(--space-4);align-items:center;margin-top:var(--space-4);">
                <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;">
                    <input type="checkbox" name="is_featured" <?php echo ($product['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                    <span><?php echo $lang === 'ar' ? 'منتج مميز' : 'Featured'; ?></span>
                </label>
                <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;">
                    <input type="checkbox" name="is_active" <?php echo ($product['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    <span><?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></span>
                </label>
            </div>
            
            <div style="margin-top:var(--space-6);display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn-primary">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <?php echo __('save'); ?>
                </button>
                <a href="<?php echo adminUrl('products.php'); ?>" class="btn btn-ghost"><?php echo __('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<!-- Products List -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <div>
        <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'المنتجات' : 'Products'; ?></h1>
        <p style="color:var(--color-text-muted);font-size:0.9375rem;"><?php echo $lang === 'ar' ? 'إدارة منتجاتك' : 'Manage your products'; ?></p>
    </div>
    <a href="<?php echo adminUrl('products.php?action=add'); ?>" class="btn btn-primary">
        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        <?php echo $lang === 'ar' ? 'إضافة منتج' : 'Add Product'; ?>
    </a>
</div>

<div class="admin-card reveal">
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'المنتج' : 'Product'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الفئة' : 'Category'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'السعر' : 'Price'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?></th>
                    <th style="text-align:center;"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <?php if ($p['main_image']): ?>
                            <img src="<?php echo uploadUrl($p['main_image']); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:var(--radius-md);">
                            <?php else: ?>
                            <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--color-primary),var(--color-primary-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:white;font-size:0.75rem;font-weight:700;"><?php echo mb_substr($p['name_ar'], 0, 1); ?></div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:600;"><?php echo e($p['name_ar']); ?></div>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);"><?php echo e($p['name_en']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo e($p['category_name'] ?? '-'); ?></td>
                    <td><?php echo $p['price'] ? number_format($p['price'], 2) . ' ' . $p['currency'] : ($lang === 'ar' ? 'عند الطلب' : 'On request'); ?></td>
                    <td>
                        <a href="<?php echo adminUrl('products.php?action=toggle&id=' . $p['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="status-badge <?php echo $p['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $p['is_active'] ? ($lang === 'ar' ? 'نشط' : 'Active') : ($lang === 'ar' ? 'غير نشط' : 'Inactive'); ?>
                        </a>
                    </td>
                    <td style="white-space:nowrap;font-size:0.8125rem;color:var(--color-text-muted);"><?php echo formatDate($p['created_at']); ?></td>
                    <td>
                        <div style="display:flex;gap:var(--space-1);justify-content:center;">
                            <a href="<?php echo adminUrl('products.php?action=edit&id=' . $p['id']); ?>" class="btn btn-sm btn-ghost" title="<?php echo __('edit'); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                            <a href="<?php echo adminUrl('products.php?action=delete&id=' . $p['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="btn btn-sm btn-ghost" style="color:var(--color-danger);" title="<?php echo __('delete'); ?>" onclick="return confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد من الحذف؟' : 'Are you sure?'; ?>');">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr><td colspan="6" style="text-align:center;padding:var(--space-8);color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'لا توجد منتجات' : 'No products found'; ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div style="padding:var(--space-4);border-top:1px solid var(--color-border-light);">
        <?php echo renderPagination($pagination, adminUrl('products.php')); ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
