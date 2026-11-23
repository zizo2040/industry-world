<?php
/**
 * Admin SEO Settings
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'seo';
$pageTitle = 'SEO - ' . getSetting('site_name_' . $lang);

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $data = [
            'meta_title_ar' => sanitize($_POST['meta_title_ar'] ?? ''),
            'meta_title_en' => sanitize($_POST['meta_title_en'] ?? ''),
            'meta_description_ar' => sanitize($_POST['meta_description_ar'] ?? ''),
            'meta_description_en' => sanitize($_POST['meta_description_en'] ?? ''),
            'og_title_ar' => sanitize($_POST['og_title_ar'] ?? ''),
            'og_title_en' => sanitize($_POST['og_title_en'] ?? ''),
            'og_description_ar' => sanitize($_POST['og_description_ar'] ?? ''),
            'og_description_en' => sanitize($_POST['og_description_en'] ?? ''),
            'canonical_url' => sanitize($_POST['canonical_url'] ?? ''),
            'robots_meta' => sanitize($_POST['robots_meta'] ?? 'index,follow'),
        ];
        
        Database::update('seo_settings', $data, 'id = :id', ['id' => $id]);
        logActivity('update', 'seo_settings', $id, 'SEO settings updated');
        setFlash('success', $lang === 'ar' ? 'تم تحديث SEO' : 'SEO updated');
        header('Location: ' . adminUrl('seo.php'));
        exit;
    }
}

$seoPage = null;
if ($action === 'edit' && $id) {
    $seoPage = Database::fetchOne("SELECT * FROM seo_settings WHERE id = :id", ['id' => $id]);
}

// Initialize default SEO settings
$defaultPages = ['home', 'about', 'contact', 'products', 'portfolio'];
foreach ($defaultPages as $p) {
    Database::query(
        "INSERT IGNORE INTO seo_settings (page_key, robots_meta) VALUES (:page, 'index,follow')",
        ['page' => $p]
    );
}

$seoSettings = Database::fetchAll("SELECT * FROM seo_settings ORDER BY page_key");

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'edit' && $seoPage): ?>

<div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);" class="reveal">
    <a href="<?php echo adminUrl('seo.php'); ?>" class="btn btn-ghost btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    </a>
    <h1 style="font-size:1.35rem;">SEO: <?php echo e($seoPage['page_key']); ?></h1>
</div>

<div class="admin-card reveal">
    <div class="admin-card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Meta Title (AR)</label>
                    <input type="text" name="meta_title_ar" class="form-control" value="<?php echo e($seoPage['meta_title_ar'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Meta Title (EN)</label>
                    <input type="text" name="meta_title_en" class="form-control" value="<?php echo e($seoPage['meta_title_en'] ?? ''); ?>">
                </div>
            </div>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Meta Description (AR)</label>
                    <textarea name="meta_description_ar" class="form-control" rows="3"><?php echo e($seoPage['meta_description_ar'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Meta Description (EN)</label>
                    <textarea name="meta_description_en" class="form-control" rows="3"><?php echo e($seoPage['meta_description_en'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">OG Title (AR)</label>
                    <input type="text" name="og_title_ar" class="form-control" value="<?php echo e($seoPage['og_title_ar'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">OG Title (EN)</label>
                    <input type="text" name="og_title_en" class="form-control" value="<?php echo e($seoPage['og_title_en'] ?? ''); ?>">
                </div>
            </div>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">OG Description (AR)</label>
                    <textarea name="og_description_ar" class="form-control" rows="2"><?php echo e($seoPage['og_description_ar'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">OG Description (EN)</label>
                    <textarea name="og_description_en" class="form-control" rows="2"><?php echo e($seoPage['og_description_en'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Canonical URL</label>
                    <input type="url" name="canonical_url" class="form-control" value="<?php echo e($seoPage['canonical_url'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Robots Meta</label>
                    <input type="text" name="robots_meta" class="form-control" value="<?php echo e($seoPage['robots_meta'] ?? 'index,follow'); ?>">
                </div>
            </div>
            <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
                <a href="<?php echo adminUrl('seo.php'); ?>" class="btn btn-ghost"><?php echo __('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);" class="reveal">
    <h1 style="font-size:1.35rem;">SEO</h1>
</div>

<div class="admin-card reveal">
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Meta Title</th>
                    <th>Robots</th>
                    <th style="text-align:center;"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($seoSettings as $s): ?>
                <tr>
                    <td><code style="background:var(--color-bg);padding:2px 8px;border-radius:var(--radius-sm);"><?php echo e($s['page_key']); ?></code></td>
                    <td><?php echo e($s['meta_title_ar'] ?? ($s['meta_title_en'] ?? '-')); ?></td>
                    <td><span class="badge badge-secondary"><?php echo e($s['robots_meta'] ?? 'index,follow'); ?></span></td>
                    <td>
                        <div style="display:flex;gap:var(--space-1);justify-content:center;">
                            <a href="<?php echo adminUrl('seo.php?action=edit&id=' . $s['id']); ?>" class="btn btn-sm btn-ghost" title="<?php echo __('edit'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
