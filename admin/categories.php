<?php
/**
 * Admin Categories Management
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'categories';
$pageTitle = ($lang === 'ar' ? 'الفئات - ' : 'Categories - ') . getSetting('site_name_' . $lang);

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Delete
if ($action === 'delete' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        Database::softDelete('categories', 'id = :id', ['id' => $id]);
        logActivity('delete', 'categories', $id, 'Category deleted');
        setFlash('success', $lang === 'ar' ? 'تم حذف الفئة بنجاح' : 'Category deleted successfully');
    }
    header('Location: ' . adminUrl('categories.php'));
    exit;
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $data = [
            'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
            'name_ar' => sanitize($_POST['name_ar'] ?? ''),
            'name_en' => sanitize($_POST['name_en'] ?? ''),
            'slug' => createSlug($_POST['name_en'] ?? $_POST['name_ar'] ?? ''),
            'description_ar' => $_POST['description_ar'] ?? '',
            'description_en' => $_POST['description_en'] ?? '',
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        // Handle image upload (card image shown in the homepage "Product Categories" section)
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], 'categories', ['image/jpeg', 'image/png', 'image/webp']);
            if ($upload['success']) {
                $imagePath = uploadPath($upload['path']);
                $imageMime = (new finfo(FILEINFO_MIME_TYPE))->file($imagePath);
                // Crop/resize to the exact recommended card size (868x540, same for desktop and mobile)
                fitImageToExactDimensions($imagePath, $imageMime, 868, 540);
                $data['image'] = $upload['path'];
            } else {
                $error = $upload['error'];
            }
        }

        try {
            if ($action === 'edit' && $id) {
                Database::update('categories', $data, 'id = :id', ['id' => $id]);
                logActivity('update', 'categories', $id, 'Category updated');
                setFlash('success', $lang === 'ar' ? 'تم تحديث الفئة' : 'Category updated');
            } else {
                $newId = Database::insert('categories', $data);
                logActivity('create', 'categories', $newId, 'Category created');
                setFlash('success', $lang === 'ar' ? 'تم إضافة الفئة' : 'Category added');
            }
            header('Location: ' . adminUrl('categories.php'));
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$category = null;
if ($action === 'edit' && $id) {
    $category = Database::fetchOne("SELECT * FROM categories WHERE id = :id", ['id' => $id]);
}

$parentCategories = Database::fetchAll("SELECT id, name_{$lang} as name FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order");

// All categories
$categories = Database::fetchAll(
    "SELECT c.*, p.name_{$lang} as parent_name 
     FROM categories c 
     LEFT JOIN categories p ON c.parent_id = p.id 
     WHERE c.deleted_at IS NULL 
     ORDER BY c.parent_id IS NULL DESC, c.sort_order, c.id"
);

include __DIR__ . '/includes/header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>

<div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);" class="reveal">
    <a href="<?php echo adminUrl('categories.php'); ?>" class="btn btn-ghost btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    </a>
    <h1 style="font-size:1.35rem;"><?php echo $action === 'edit' ? ($lang === 'ar' ? 'تعديل فئة' : 'Edit Category') : ($lang === 'ar' ? 'إضافة فئة' : 'Add Category'); ?></h1>
</div>

<div class="admin-card reveal">
    <div class="admin-card-body">
        <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
            <?php echo csrfField(); ?>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (عربي)' : 'Name (Arabic)'; ?> *</label>
                    <input type="text" name="name_ar" class="form-control" value="<?php echo e($category['name_ar'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (إنجليزي)' : 'Name (English)'; ?> *</label>
                    <input type="text" name="name_en" class="form-control" value="<?php echo e($category['name_en'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">
                    <?php echo $lang === 'ar' ? 'صورة الفئة' : 'Category Image'; ?>
                    <small style="color:var(--color-text-muted);font-weight:400;">
                        (<?php echo $lang === 'ar' ? 'المقاس الموصى به' : 'Recommended size'; ?>: 868×540 px)
                    </small>
                </label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($category['image'])): ?>
                <div style="margin-top:var(--space-2);">
                    <img src="<?php echo uploadUrl($category['image']); ?>" alt="" style="width:217px;max-width:100%;aspect-ratio:868/540;object-fit:cover;border-radius:var(--radius-md);">
                </div>
                <?php endif; ?>
                <small style="color:var(--color-text-muted);font-size:0.78rem;">
                    JPG, PNG, WEBP — <?php echo $lang === 'ar' ? 'سيُعاد قصّ/تحجيم الصورة تلقائياً للمقاس الموصى به. اتركه فارغاً للإبقاء على الصورة الحالية' : 'will be auto-cropped/resized to the recommended size. Leave empty to keep the current image'; ?>
                </small>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الفئة الأب' : 'Parent Category'; ?></label>
                <select name="parent_id" class="form-control">
                    <option value=""><?php echo $lang === 'ar' ? 'فئة رئيسية' : 'Main Category'; ?></option>
                    <?php foreach ($parentCategories as $pc): ?>
                    <?php if ($pc['id'] != $id): ?>
                    <option value="<?php echo $pc['id']; ?>" <?php echo ($category['parent_id'] ?? '') == $pc['id'] ? 'selected' : ''; ?>><?php echo e($pc['name']); ?></option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الترتيب' : 'Sort Order'; ?></label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo $category['sort_order'] ?? '0'; ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (عربي)' : 'Description (Arabic)'; ?></label>
                <textarea name="description_ar" class="form-control" rows="3"><?php echo e($category['description_ar'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (إنجليزي)' : 'Description (English)'; ?></label>
                <textarea name="description_en" class="form-control" rows="3"><?php echo e($category['description_en'] ?? ''); ?></textarea>
            </div>
            <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;margin-top:var(--space-2);">
                <input type="checkbox" name="is_active" <?php echo ($category['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <span><?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></span>
            </label>
            <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                <a href="<?php echo adminUrl('categories.php'); ?>" class="btn btn-ghost"><?php echo __('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'الفئات' : 'Categories'; ?></h1>
    <a href="<?php echo adminUrl('categories.php?action=add'); ?>" class="btn btn-primary">
        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        <?php echo $lang === 'ar' ? 'إضافة فئة' : 'Add Category'; ?>
    </a>
</div>

<div class="admin-card reveal">
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'الصورة' : 'Image'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الفئة' : 'Category'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الفئة الأب' : 'Parent'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الترتيب' : 'Order'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th style="text-align:center;"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <?php if (!empty($cat['image'])): ?>
                        <img src="<?php echo uploadUrl($cat['image']); ?>" alt="" style="width:56px;height:35px;object-fit:cover;border-radius:var(--radius-md);">
                        <?php else: ?>
                        <div style="width:56px;height:35px;border-radius:var(--radius-md);background:var(--color-bg-alt);border:1px dashed var(--color-border);"></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo e($cat['name_ar']); ?></strong>
                        <div style="font-size:0.75rem;color:var(--color-text-muted);"><?php echo e($cat['name_en']); ?></div>
                    </td>
                    <td><?php echo e($cat['parent_name'] ?? ($lang === 'ar' ? 'رئيسية' : 'Main')); ?></td>
                    <td><?php echo $cat['sort_order']; ?></td>
                    <td>
                        <span class="status-badge <?php echo $cat['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $cat['is_active'] ? ($lang === 'ar' ? 'نشط' : 'Active') : ($lang === 'ar' ? 'غير نشط' : 'Inactive'); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:var(--space-1);justify-content:center;">
                            <a href="<?php echo adminUrl('categories.php?action=edit&id=' . $cat['id']); ?>" class="btn btn-sm btn-ghost" title="<?php echo __('edit'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                            <a href="<?php echo adminUrl('categories.php?action=delete&id=' . $cat['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="btn btn-sm btn-ghost" style="color:var(--color-danger);" onclick="return confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد؟' : 'Are you sure?'; ?>');" title="<?php echo __('delete'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                <tr><td colspan="6" style="text-align:center;padding:var(--space-8);color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'لا توجد فئات' : 'No categories'; ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
