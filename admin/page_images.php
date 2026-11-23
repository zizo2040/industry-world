<?php
/**
 * Admin - Page Images Management
 * Lets the site owner upload/replace the desktop & mobile banner/hero
 * image for each page section defined in the `page_images` table.
 * Each card is saved independently (separate POST per section) so an
 * error on one card never loses edits made to another.
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'page_images';
$pageTitle = ($lang === 'ar' ? 'إدارة صور الصفحات - ' : 'Page Images - ') . getSetting('site_name_' . $lang);

$errors = [];

// Save (one section per submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $sectionKey = $_POST['section_key'] ?? '';
        $row = Database::fetchOne("SELECT * FROM page_images WHERE section_key = :k", ['k' => $sectionKey]);

        if (!$row) {
            $errors[] = $lang === 'ar' ? 'قسم غير معروف' : 'Unknown section';
        } else {
            $updates = [];

            foreach (['desktop', 'mobile'] as $device) {
                $fieldName = $device . '_image';
                if (!empty($_FILES[$fieldName]['tmp_name']) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
                    $targetWidth  = (int) $row[$device . '_width'];
                    $targetHeight = (int) $row[$device . '_height'];

                    $upload = savePageImageUpload($_FILES[$fieldName], $targetWidth, $targetHeight);

                    if ($upload['success']) {
                        $pathColumn = $device . '_image_path';
                        // Delete previously stored file for this device, if any
                        if (!empty($row[$pathColumn])) {
                            $oldFull = uploadPath($row[$pathColumn]);
                            if (file_exists($oldFull)) {
                                @unlink($oldFull);
                            }
                        }
                        $updates[$pathColumn] = $upload['path'];
                    } else {
                        $errors[] = e($row['section_label']) . ': ' . $upload['error'];
                    }
                }
            }

            if (!empty($updates)) {
                Database::update('page_images', $updates, 'section_key = :k', ['k' => $sectionKey]);
                logActivity('update', 'page_images', $row['id'], 'Updated images for section: ' . $sectionKey);
            }

            if (empty($errors)) {
                setFlash('success', $lang === 'ar' ? 'تم حفظ الصورة بنجاح' : 'Image saved successfully');
                header('Location: ' . adminUrl('page_images.php'));
                exit;
            }
        }
    } else {
        $errors[] = $lang === 'ar' ? 'انتهت صلاحية الجلسة، أعد المحاولة' : 'Session expired, please retry';
    }
}

$sections = getAllPageImageSections();

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-2);" class="reveal">
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'إدارة صور الصفحات' : 'Page Images'; ?></h1>
</div>
<p style="color:var(--color-text-muted);margin-bottom:var(--space-6);">
    <?php echo $lang === 'ar'
        ? 'ارفع صورة سطح المكتب وصورة الهاتف لكل قسم. أي قسم بلا صورة سيعرض صورة بديلة تلقائية إلى حين رفع صورة حقيقية.'
        : 'Upload a desktop and mobile image for each section. Sections without an image show an automatic placeholder until a real image is uploaded.'; ?>
</p>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--space-5);padding:var(--space-4);background:#fee2e2;border-radius:var(--radius-md);color:#b91c1c;">
    <?php foreach ($errors as $err): ?>
        <div><?php echo e($err); ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr;gap:var(--space-6);">
    <?php foreach ($sections as $section): ?>
    <?php
        $desktopUrl = $section['desktop_image_path']
            ? uploadUrl($section['desktop_image_path'])
            : get_page_image($section['section_key'], 'desktop');
        $mobileUrl = $section['mobile_image_path']
            ? uploadUrl($section['mobile_image_path'])
            : ($section['desktop_image_path'] ? $desktopUrl : get_page_image($section['section_key'], 'mobile'));
    ?>
    <div class="admin-card reveal">
        <div class="admin-card-header">
            <h3 style="font-size:1.05rem;"><?php echo e($section['section_label']); ?></h3>
            <code style="background:var(--color-bg);padding:2px 8px;border-radius:var(--radius-sm);font-size:0.75rem;color:var(--color-text-muted);">
                <?php echo e($section['section_key']); ?>
            </code>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="section_key" value="<?php echo e($section['section_key']); ?>">

                <div class="admin-form-grid">
                    <!-- Desktop -->
                    <div class="form-group">
                        <label class="form-label">
                            <?php echo $lang === 'ar' ? 'صورة سطح المكتب' : 'Desktop Image'; ?>
                            <small style="color:var(--color-text-muted);font-weight:400;">
                                (<?php echo $lang === 'ar' ? 'المقاس الموصى به' : 'Recommended size'; ?>:
                                <?php echo (int) $section['desktop_width']; ?>×<?php echo (int) $section['desktop_height']; ?> px)
                            </small>
                        </label>
                        <div style="margin-bottom:var(--space-3);padding:var(--space-3);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);">
                            <img src="<?php echo e($desktopUrl); ?>" alt=""
                                 style="width:100%;max-height:160px;object-fit:contain;background:var(--color-bg);border-radius:var(--radius-sm);display:block;">
                            <?php if (!$section['desktop_image_path']): ?>
                            <small style="color:var(--color-warning);display:block;margin-top:var(--space-2);">
                                <?php echo $lang === 'ar' ? 'صورة بديلة تلقائية — لم تُرفع صورة حقيقية بعد' : 'Automatic placeholder — no real image uploaded yet'; ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="desktop_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small style="color:var(--color-text-muted);font-size:0.78rem;">JPG, PNG, WEBP — <?php echo $lang === 'ar' ? 'حتى 5 ميجابايت. تُعرض الصورة كاملة دون أي قص؛ يتم فقط تصغيرها إذا تجاوزت المقاس الموصى به' : 'up to 5MB. The full image is shown with no cropping; it is only downscaled if larger than the recommended size'; ?></small>
                    </div>

                    <!-- Mobile -->
                    <div class="form-group">
                        <label class="form-label">
                            <?php echo $lang === 'ar' ? 'صورة الهاتف' : 'Mobile Image'; ?>
                            <small style="color:var(--color-text-muted);font-weight:400;">
                                (<?php echo $lang === 'ar' ? 'المقاس الموصى به' : 'Recommended size'; ?>:
                                <?php echo (int) $section['mobile_width']; ?>×<?php echo (int) $section['mobile_height']; ?> px)
                            </small>
                        </label>
                        <div style="margin-bottom:var(--space-3);padding:var(--space-3);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);">
                            <img src="<?php echo e($mobileUrl); ?>" alt=""
                                 style="width:100%;max-height:160px;object-fit:contain;background:var(--color-bg);border-radius:var(--radius-sm);display:block;">
                            <?php if (!$section['mobile_image_path']): ?>
                            <small style="color:var(--color-text-muted);display:block;margin-top:var(--space-2);">
                                <?php echo $lang === 'ar' ? 'لا توجد صورة هاتف مخصّصة — تُستخدم صورة سطح المكتب تلقائياً' : 'No dedicated mobile image — the desktop image is used automatically'; ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="mobile_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small style="color:var(--color-text-muted);font-size:0.78rem;"><?php echo $lang === 'ar' ? 'اترك هذا الحقل فارغاً لاستخدام نفس صورة سطح المكتب تلقائياً على الهاتف' : 'Leave empty to automatically reuse the desktop image on mobile'; ?></small>
                    </div>
                </div>

                <div style="margin-top:var(--space-4);">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <?php echo __('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
