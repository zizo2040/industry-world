<?php
/**
 * Admin CMS Pages
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'cms';
$pageTitle = ($lang === 'ar' ? 'إدارة المحتوى - ' : 'CMS - ') . getSetting('site_name_' . $lang);

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $data = [
            'title_ar' => sanitize($_POST['title_ar'] ?? ''),
            'title_en' => sanitize($_POST['title_en'] ?? ''),
            'content_ar' => $_POST['content_ar'] ?? '',
            'content_en' => $_POST['content_en'] ?? '',
            'excerpt_ar' => sanitize($_POST['excerpt_ar'] ?? ''),
            'excerpt_en' => sanitize($_POST['excerpt_en'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        Database::update('cms_pages', $data, 'id = :id', ['id' => $id]);
        logActivity('update', 'cms_pages', $id, 'CMS page updated');
        setFlash('success', $lang === 'ar' ? 'تم تحديث الصفحة' : 'Page updated');
        header('Location: ' . adminUrl('cms.php'));
        exit;
    }
}

// Get page for edit
$page = null;
if ($action === 'edit' && $id) {
    $page = Database::fetchOne("SELECT * FROM cms_pages WHERE id = :id", ['id' => $id]);
}

// All CMS pages
$pages = Database::fetchAll("SELECT * FROM cms_pages ORDER BY sort_order, id");

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'edit' && $page): ?>

<div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);" class="reveal">
    <a href="<?php echo adminUrl('cms.php'); ?>" class="btn btn-ghost btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    </a>
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'تعديل: ' : 'Edit: '; ?><?php echo e($page['title_ar']); ?></h1>
</div>

<div class="admin-card reveal">
    <div class="admin-card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'العنوان (عربي)' : 'Title (Arabic)'; ?></label>
                    <input type="text" name="title_ar" class="form-control" value="<?php echo e($page['title_ar']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'العنوان (إنجليزي)' : 'Title (English)'; ?></label>
                    <input type="text" name="title_en" class="form-control" value="<?php echo e($page['title_en']); ?>">
                </div>
            </div>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'المقتطف (عربي)' : 'Excerpt (Arabic)'; ?></label>
                    <textarea name="excerpt_ar" class="form-control" rows="2"><?php echo e($page['excerpt_ar'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'المقتطف (إنجليزي)' : 'Excerpt (English)'; ?></label>
                    <textarea name="excerpt_en" class="form-control" rows="2"><?php echo e($page['excerpt_en'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'المحتوى (عربي)' : 'Content (Arabic)'; ?></label>
                <textarea name="content_ar" class="form-control" rows="10"><?php echo e($page['content_ar'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'المحتوى (إنجليزي)' : 'Content (English)'; ?></label>
                <textarea name="content_en" class="form-control" rows="10"><?php echo e($page['content_en'] ?? ''); ?></textarea>
            </div>
            <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;">
                <input type="checkbox" name="is_active" <?php echo $page['is_active'] ? 'checked' : ''; ?>>
                <span><?php echo $lang === 'ar' ? 'نشط' : 'Active'; ?></span>
            </label>
            <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                <a href="<?php echo adminUrl('cms.php'); ?>" class="btn btn-ghost"><?php echo __('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'إدارة المحتوى' : 'Content Management'; ?></h1>
</div>

<div class="admin-card reveal">
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'الصفحة' : 'Page'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'المفتاح' : 'Key'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'آخر تحديث' : 'Last Updated'; ?></th>
                    <th style="text-align:center;"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr>
                    <td>
                        <strong><?php echo e($p['title_ar']); ?></strong>
                        <div style="font-size:0.75rem;color:var(--color-text-muted);"><?php echo e($p['title_en']); ?></div>
                    </td>
                    <td><code style="background:var(--color-bg);padding:2px 6px;border-radius:var(--radius-sm);font-size:0.8125rem;"><?php echo e($p['page_key']); ?></code></td>
                    <td>
                        <span class="status-badge <?php echo $p['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $p['is_active'] ? ($lang === 'ar' ? 'نشط' : 'Active') : ($lang === 'ar' ? 'غير نشط' : 'Inactive'); ?>
                        </span>
                    </td>
                    <td style="font-size:0.8125rem;color:var(--color-text-muted);"><?php echo timeAgo($p['updated_at']); ?></td>
                    <td>
                        <div style="display:flex;gap:var(--space-1);justify-content:center;">
                            <a href="<?php echo adminUrl('cms.php?action=edit&id=' . $p['id']); ?>" class="btn btn-sm btn-ghost" title="<?php echo __('edit'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pages)): ?>
                <tr><td colspan="5" style="text-align:center;padding:var(--space-8);color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'لا توجد صفحات' : 'No pages'; ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
