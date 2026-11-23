<?php
/**
 * Admin Portfolio Management
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'portfolio';
$pageTitle = ($lang === 'ar' ? 'معرض الأعمال - ' : 'Portfolio - ') . getSetting('site_name_' . $lang);

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Delete
if ($action === 'delete' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        Database::query("DELETE FROM portfolio WHERE id = :id", ['id' => $id]);
        logActivity('delete', 'portfolio', $id, 'Portfolio deleted');
        setFlash('success', $lang === 'ar' ? 'تم الحذف' : 'Deleted');
    }
    header('Location: ' . adminUrl('portfolio.php'));
    exit;
}

// Toggle status
if ($action === 'toggle' && $id) {
    if (validateCsrfToken($_GET[CSRF_TOKEN_NAME] ?? '')) {
        $item = Database::fetchOne("SELECT is_active FROM portfolio WHERE id = :id", ['id' => $id]);
        if ($item) {
            Database::update('portfolio', ['is_active' => $item['is_active'] ? 0 : 1], 'id = :id', ['id' => $id]);
            setFlash('success', $lang === 'ar' ? 'تم التحديث' : 'Updated');
        }
    }
    header('Location: ' . adminUrl('portfolio.php'));
    exit;
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $data = [
            'category_id' => intval($_POST['category_id'] ?? 0),
            'title_ar' => sanitize($_POST['title_ar'] ?? ''),
            'title_en' => sanitize($_POST['title_en'] ?? ''),
            'slug' => createSlug($_POST['title_en'] ?? $_POST['title_ar'] ?? ''),
            'description_ar' => $_POST['description_ar'] ?? '',
            'description_en' => $_POST['description_en'] ?? '',
            'client_name' => sanitize($_POST['client_name'] ?? ''),
            'location_ar' => sanitize($_POST['location_ar'] ?? ''),
            'location_en' => sanitize($_POST['location_en'] ?? ''),
            'completion_date' => $_POST['completion_date'] ?? null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        if (!empty($_FILES['main_image']['tmp_name'])) {
            $upload = uploadFile($_FILES['main_image'], 'portfolio');
            if ($upload['success']) $data['main_image'] = $upload['path'];
        }
        
        try {
            if ($action === 'edit' && $id) {
                Database::update('portfolio', $data, 'id = :id', ['id' => $id]);
                logActivity('update', 'portfolio', $id, 'Portfolio updated');
                setFlash('success', $lang === 'ar' ? 'تم التحديث' : 'Updated');
            } else {
                $newId = Database::insert('portfolio', $data);
                logActivity('create', 'portfolio', $newId, 'Portfolio created');
                setFlash('success', $lang === 'ar' ? 'تم الإضافة' : 'Added');
            }
            header('Location: ' . adminUrl('portfolio.php'));
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$portfolioItem = null;
if ($action === 'edit' && $id) {
    $portfolioItem = Database::fetchOne("SELECT * FROM portfolio WHERE id = :id", ['id' => $id]);
}

$categories = Database::fetchAll("SELECT id, name_{$lang} as name FROM portfolio_categories WHERE is_active = 1 ORDER BY sort_order");

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$total = Database::fetchOne("SELECT COUNT(*) as c FROM portfolio")['c'] ?? 0;
$pagination = paginate($total, $page, $perPage);

$items = Database::fetchAll(
    "SELECT p.*, c.name_{$lang} as category_name FROM portfolio p LEFT JOIN portfolio_categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset",
    ['limit' => $perPage, 'offset' => $pagination['offset']]
);

include __DIR__ . '/includes/header.php';
?>

<?php if (in_array($action, ['add', 'edit'])): ?>

<div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);" class="reveal">
    <a href="<?php echo adminUrl('portfolio.php'); ?>" class="btn btn-ghost btn-sm"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg></a>
    <h1 style="font-size:1.35rem;"><?php echo $action === 'edit' ? ($lang === 'ar' ? 'تعديل' : 'Edit') : ($lang === 'ar' ? 'إضافة' : 'Add'); ?></h1>
</div>

<div class="admin-card reveal">
    <div class="admin-card-body">
        <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
            <?php echo csrfField(); ?>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'العنوان (عربي)' : 'Title (Arabic)'; ?> *</label>
                    <input type="text" name="title_ar" class="form-control" value="<?php echo e($portfolioItem['title_ar'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'العنوان (إنجليزي)' : 'Title (English)'; ?> *</label>
                    <input type="text" name="title_en" class="form-control" value="<?php echo e($portfolioItem['title_en'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الفئة' : 'Category'; ?></label>
                <select name="category_id" class="form-control">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($portfolioItem['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-grid">
                <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'العميل' : 'Client'; ?></label><input type="text" name="client_name" class="form-control" value="<?php echo e($portfolioItem['client_name'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'تاريخ الإنجاز' : 'Completion Date'; ?></label><input type="date" name="completion_date" class="form-control" value="<?php echo e($portfolioItem['completion_date'] ?? ''); ?>"></div>
            </div>
            <div class="admin-form-grid">
                <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'الموقع (عربي)' : 'Location (Arabic)'; ?></label><input type="text" name="location_ar" class="form-control" value="<?php echo e($portfolioItem['location_ar'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'الموقع (إنجليزي)' : 'Location (English)'; ?></label><input type="text" name="location_en" class="form-control" value="<?php echo e($portfolioItem['location_en'] ?? ''); ?>"></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (عربي)' : 'Description (Arabic)'; ?></label>
                <textarea name="description_ar" class="form-control" rows="4"><?php echo e($portfolioItem['description_ar'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (إنجليزي)' : 'Description (English)'; ?></label>
                <textarea name="description_en" class="form-control" rows="4"><?php echo e($portfolioItem['description_en'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'الصورة' : 'Image'; ?></label>
                <input type="file" name="main_image" class="form-control" accept="image/*">
                <?php if (!empty($portfolioItem['main_image'])): ?><div style="margin-top:var(--space-2);"><img src="<?php echo uploadUrl($portfolioItem['main_image']); ?>" alt="" style="max-width:150px;border-radius:var(--radius-md);"></div><?php endif; ?>
            </div>
            <div style="display:flex;gap:var(--space-4);margin-top:var(--space-2);">
                <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;"><input type="checkbox" name="is_featured" <?php echo ($portfolioItem['is_featured'] ?? 0) ? 'checked' : ''; ?>> <?php echo $lang === 'ar' ? 'مميز' : 'Featured'; ?></label>
                <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;"><input type="checkbox" name="is_active" <?php echo ($portfolioItem['is_active'] ?? 1) ? 'checked' : ''; ?>> <?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></label>
            </div>
            <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                <a href="<?php echo adminUrl('portfolio.php'); ?>" class="btn btn-ghost"><?php echo __('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'معرض الأعمال' : 'Portfolio'; ?></h1>
    <a href="<?php echo adminUrl('portfolio.php?action=add'); ?>" class="btn btn-primary"><svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> <?php echo $lang === 'ar' ? 'إضافة' : 'Add'; ?></a>
</div>

<div class="admin-card reveal">
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr><th><?php echo $lang === 'ar' ? 'المشروع' : 'Project'; ?></th><th><?php echo $lang === 'ar' ? 'الفئة' : 'Category'; ?></th><th><?php echo $lang === 'ar' ? 'العميل' : 'Client'; ?></th><th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th><th style="text-align:center;"><?php echo __('actions'); ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><strong><?php echo e($item['title_ar']); ?></strong></td>
                    <td><?php echo e($item['category_name'] ?? '-'); ?></td>
                    <td><?php echo e($item['client_name'] ?? '-'); ?></td>
                    <td><a href="<?php echo adminUrl('portfolio.php?action=toggle&id=' . $item['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="status-badge <?php echo $item['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $item['is_active'] ? ($lang === 'ar' ? 'نشط' : 'Active') : ($lang === 'ar' ? 'غير نشط' : 'Inactive'); ?></a></td>
                    <td>
                        <div style="display:flex;gap:var(--space-1);justify-content:center;">
                            <a href="<?php echo adminUrl('portfolio.php?action=edit&id=' . $item['id']); ?>" class="btn btn-sm btn-ghost" title="<?php echo __('edit'); ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                            <a href="<?php echo adminUrl('portfolio.php?action=delete&id=' . $item['id'] . '&' . CSRF_TOKEN_NAME . '=' . generateCsrfToken()); ?>" class="btn btn-sm btn-ghost" style="color:var(--color-danger);" onclick="return confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد؟' : 'Are you sure?'; ?>');"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="5" style="text-align:center;padding:var(--space-8);color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'لا توجد مشاريع' : 'No projects'; ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
