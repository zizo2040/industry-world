<?php
/**
 * Admin Settings - Updated with Logo/Favicon Upload
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'settings';
$pageTitle = ($lang === 'ar' ? 'الإعدادات - ' : 'Settings - ') . getSetting('site_name_' . $lang);

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        
        // ── Text/URL settings ──────────────────────────────────────
        $textSettings = [
            'site_name_ar', 'site_name_en', 'site_tagline_ar', 'site_tagline_en',
            'company_address_ar', 'company_address_en', 'company_email',
            'whatsapp_primary', 'whatsapp_secondary_1', 'whatsapp_secondary_2',
            'contact_phone',
            'facebook_url', 'twitter_url', 'instagram_url',
            'working_hours_ar', 'working_hours_en', 'ad_bar_items_ar', 'ad_bar_items_en',
            'google_analytics_id', 'site_verification_google', 'site_verification_bing',
            'youtube_url', 'tiktok_url',
        ];
        
        foreach ($textSettings as $key) {
            $value = trim((string) ($_POST[$key] ?? ''));
            if ($key === 'google_analytics_id') $value = preg_replace('/[^A-Za-z0-9\-]/', '', $value);
            if (str_starts_with($key, 'site_verification_')) {
                // accept either the bare token or the full <meta ... content="TOKEN"> tag
                if (preg_match('/content=["\']([^"\']+)["\']/', $value, $m)) $value = $m[1];
                $value = preg_replace('/[^A-Za-z0-9_\-]/', '', $value);
            }
            Database::query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) 
                 ON DUPLICATE KEY UPDATE setting_value = :value",
                ['key' => $key, 'value' => $value]
            );
        }

        // ── Site font (validated against the fixed catalog) ─────────
        $fontCatalog = getSiteFontCatalog();
        $postedFont = $_POST['site_font'] ?? '';
        if (isset($fontCatalog[$postedFont])) {
            Database::query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES ('site_font', :value) 
                 ON DUPLICATE KEY UPDATE setting_value = :value",
                ['value' => $postedFont]
            );
        }
        
        // ── File uploads ───────────────────────────────────────────
        $uploadDir = BASE_PATH . '/assets/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadFields = [
            'site_logo'       => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
            'site_favicon'    => ['ico', 'png', 'svg'],
            'og_image'        => ['jpg', 'jpeg', 'png', 'webp'],
            'apple_touch_icon'=> ['png'],
            // ── Comprehensive logo management (see get_site_logo()) ──
            'logo_header_light' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
            'logo_header_dark'  => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
            'logo_mobile_menu'  => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
            'logo_footer'       => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
        ];
        
        foreach ($uploadFields as $fieldKey => $allowedExts) {
            if (!isset($_FILES[$fieldKey]) || $_FILES[$fieldKey]['error'] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $file     = $_FILES[$fieldKey];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExts)) {
                $error .= ($lang === 'ar' 
                    ? "امتداد غير مسموح به لـ $fieldKey. " 
                    : "Invalid extension for $fieldKey. ");
                continue;
            }
            
            // Validate MIME for image files
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimes = [
                'image/jpeg','image/png','image/gif','image/svg+xml',
                'image/webp','image/x-icon','image/vnd.microsoft.icon'
            ];
            
            if (!in_array($mimeType, $allowedMimes)) {
                $error .= ($lang === 'ar' 
                    ? "نوع ملف غير مسموح به لـ $fieldKey. " 
                    : "Invalid file type for $fieldKey. ");
                continue;
            }
            
            $fileName = $fieldKey . '_' . time() . '.' . $ext;
            $destPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                // Delete old file if different
                $oldFile = getSetting($fieldKey);
                if ($oldFile && $oldFile !== $fileName) {
                    $oldPath = $uploadDir . $oldFile;
                    if (file_exists($oldPath)) @unlink($oldPath);
                }
                
                Database::query(
                    "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) 
                     ON DUPLICATE KEY UPDATE setting_value = :value",
                    ['key' => $fieldKey, 'value' => $fileName]
                );
            } else {
                $error .= ($lang === 'ar' 
                    ? "فشل رفع $fieldKey. " 
                    : "Upload failed for $fieldKey. ");
            }
        }
        
        // ── Google Maps link (resolved once here into a safe embeddable
        //    URL, so contact.php never has to hit the network) ──────────
        if (isset($_POST['google_maps_url'])) {
            $mapsUrlRaw = trim($_POST['google_maps_url']);
            Database::query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES ('google_maps_url', :value) 
                 ON DUPLICATE KEY UPDATE setting_value = :value",
                ['value' => $mapsUrlRaw]
            );

            if ($mapsUrlRaw !== '') {
                $resolvedMapEmbed = resolveGoogleMapsEmbedUrl($mapsUrlRaw);
                if ($resolvedMapEmbed) {
                    Database::query(
                        "INSERT INTO site_settings (setting_key, setting_value) VALUES ('google_maps_embed_url', :value) 
                         ON DUPLICATE KEY UPDATE setting_value = :value",
                        ['value' => $resolvedMapEmbed]
                    );
                } else {
                    $error .= ($lang === 'ar'
                        ? 'تعذّر استخراج إحداثيات موقعك من رابط خرائط جوجل، تأكد من صحة الرابط. '
                        : 'Could not resolve coordinates from the Google Maps link, please check it. ');
                }
            } else {
                Database::query("DELETE FROM site_settings WHERE setting_key = 'google_maps_embed_url'");
            }
        }

        if (empty($error)) {
            logActivity('update', 'site_settings', null, 'Settings updated');
            setFlash('success', $lang === 'ar' ? 'تم حفظ الإعدادات بنجاح' : 'Settings saved successfully');
            header('Location: ' . adminUrl('settings.php'));
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6);" class="reveal">
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'إعدادات الموقع' : 'Site Settings'; ?></h1>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--space-4);padding:var(--space-4);background:#fee2e2;border-radius:var(--radius-md);color:#b91c1c;">
    <?php echo e($error); ?>
</div>
<?php endif; ?>

<div class="admin-card reveal">
    <div class="admin-card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            
            <!-- ══════════════════════════════════════
                 SECTION: SITE IDENTITY
                 ══════════════════════════════════════ -->
            <h3 style="margin-bottom:var(--space-4);color:var(--color-primary);font-size:1.05rem;border-bottom:2px solid var(--color-border);padding-bottom:var(--space-3);">
                🏢 <?php echo $lang === 'ar' ? 'هوية الموقع' : 'Site Identity'; ?>
            </h3>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'اسم الموقع (عربي)' : 'Site Name (Arabic)'; ?></label>
                    <input type="text" name="site_name_ar" class="form-control" value="<?php echo e(getSetting('site_name_ar')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'اسم الموقع (إنجليزي)' : 'Site Name (English)'; ?></label>
                    <input type="text" name="site_name_en" class="form-control" value="<?php echo e(getSetting('site_name_en')); ?>">
                </div>
            </div>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الشعار الفرعي (عربي)' : 'Tagline (Arabic)'; ?></label>
                    <input type="text" name="site_tagline_ar" class="form-control" value="<?php echo e(getSetting('site_tagline_ar')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الشعار الفرعي (إنجليزي)' : 'Tagline (English)'; ?></label>
                    <input type="text" name="site_tagline_en" class="form-control" value="<?php echo e(getSetting('site_tagline_en')); ?>">
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 SECTION: LOGO & IMAGES
                 ══════════════════════════════════════ -->
            <h3 style="margin:var(--space-6) 0 var(--space-4);color:var(--color-primary);font-size:1.05rem;border-bottom:2px solid var(--color-border);padding-bottom:var(--space-3);">
                🖼️ <?php echo $lang === 'ar' ? 'الشعار والصور' : 'Logo & Images'; ?>
            </h3>
            
            <div class="admin-form-grid">
                <!-- Site Logo -->
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $lang === 'ar' ? 'شعار الموقع الرئيسي' : 'Main Site Logo'; ?>
                        <small style="color:var(--color-text-muted);font-weight:400;"> (PNG, JPG, SVG, WebP)</small>
                    </label>
                    <?php if (getSetting('site_logo')): ?>
                    <div style="margin-bottom:var(--space-3);padding:var(--space-3);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);">
                        <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:var(--space-2);">
                            <?php echo $lang === 'ar' ? 'الشعار الحالي:' : 'Current logo:'; ?>
                        </p>
                        <img src="<?php echo asset('images/' . getSetting('site_logo')); ?>" 
                             alt="Current Logo" 
                             style="max-height:60px;max-width:200px;object-fit:contain;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" class="form-control" accept="image/png,image/jpeg,image/gif,image/svg+xml,image/webp">
                    <small style="color:var(--color-text-muted);font-size:0.78rem;">
                        <?php echo $lang === 'ar' ? 'يُنصح باستخدام PNG شفاف، أبعاد مقترحة: 240×60 بكسل' : 'Recommended: transparent PNG, suggested size: 240×60 px'; ?>
                    </small>
                </div>
                
                <!-- Favicon -->
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $lang === 'ar' ? 'أيقونة المتصفح (Favicon)' : 'Browser Favicon'; ?>
                        <small style="color:var(--color-text-muted);font-weight:400;"> (ICO, PNG, SVG)</small>
                    </label>
                    <?php if (getSetting('site_favicon')): ?>
                    <div style="margin-bottom:var(--space-3);padding:var(--space-3);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);display:flex;align-items:center;gap:var(--space-3);">
                        <img src="<?php echo asset('images/' . getSetting('site_favicon')); ?>" 
                             alt="Current Favicon" 
                             style="width:32px;height:32px;object-fit:contain;">
                        <span style="font-size:0.8rem;color:var(--color-text-muted);">
                            <?php echo e(getSetting('site_favicon')); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="site_favicon" class="form-control" accept=".ico,image/x-icon,image/png,image/svg+xml">
                    <small style="color:var(--color-text-muted);font-size:0.78rem;">
                        <?php echo $lang === 'ar' ? 'الحجم المثالي: 32×32 أو 16×16 بكسل (ICO أو PNG)' : 'Ideal size: 32×32 or 16×16 px (ICO or PNG)'; ?>
                    </small>
                </div>
            </div>
            
            <div class="admin-form-grid">
                <!-- OG Image -->
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $lang === 'ar' ? 'صورة المشاركة (Open Graph)' : 'Social Share Image (OG)'; ?>
                        <small style="color:var(--color-text-muted);font-weight:400;"> (JPG, PNG, WebP)</small>
                    </label>
                    <?php if (getSetting('og_image')): ?>
                    <div style="margin-bottom:var(--space-3);padding:var(--space-3);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);">
                        <img src="<?php echo asset('images/' . getSetting('og_image')); ?>" 
                             alt="OG Image" 
                             style="max-height:80px;max-width:100%;object-fit:cover;border-radius:var(--radius-sm);">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="og_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small style="color:var(--color-text-muted);font-size:0.78rem;">
                        <?php echo $lang === 'ar' ? 'الأبعاد المثالية: 1200×630 بكسل (للمشاركة على وسائل التواصل)' : 'Ideal: 1200×630 px (for social media sharing)'; ?>
                    </small>
                </div>
                
                <!-- Apple Touch Icon -->
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $lang === 'ar' ? 'أيقونة تطبيق Apple (Touch Icon)' : 'Apple Touch Icon'; ?>
                        <small style="color:var(--color-text-muted);font-weight:400;"> (PNG)</small>
                    </label>
                    <?php if (getSetting('apple_touch_icon')): ?>
                    <div style="margin-bottom:var(--space-3);padding:var(--space-3);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);display:flex;align-items:center;gap:var(--space-3);">
                        <img src="<?php echo asset('images/' . getSetting('apple_touch_icon')); ?>" 
                             alt="Apple Touch Icon" 
                             style="width:48px;height:48px;object-fit:contain;border-radius:10px;">
                        <span style="font-size:0.8rem;color:var(--color-text-muted);">
                            <?php echo e(getSetting('apple_touch_icon')); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="apple_touch_icon" class="form-control" accept="image/png">
                    <small style="color:var(--color-text-muted);font-size:0.78rem;">
                        <?php echo $lang === 'ar' ? 'الحجم المثالي: 180×180 بكسل (PNG شفاف بدون خلفية)' : 'Ideal: 180×180 px (transparent PNG)'; ?>
                    </small>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 SECTION: COMPREHENSIVE LOGO MANAGEMENT
                 Covers every real place the logo appears in the code:
                 header (light/dark), off-canvas mobile menu, footer.
                 Any field left empty falls back automatically to
                 logo_header_light via get_site_logo() — a location
                 never renders without a logo.
                 ══════════════════════════════════════ -->
            <h3 style="margin:var(--space-6) 0 var(--space-4);color:var(--color-primary);font-size:1.05rem;border-bottom:2px solid var(--color-border);padding-bottom:var(--space-3);">
                🖼️ <?php echo $lang === 'ar' ? 'إدارة الشعار الشاملة' : 'Comprehensive Logo Management'; ?>
            </h3>
            <p style="font-size:0.82rem;color:var(--color-text-muted);margin:-var(--space-2) 0 var(--space-4);">
                <?php echo $lang === 'ar'
                    ? 'اترك أي حقل فارغاً ليستخدم الموقع تلقائياً شعار الهيدر (الوضع الفاتح) كبديل، دون أن يظهر أي مكان بدون شعار.'
                    : 'Leave any field empty and the site automatically falls back to the header (light) logo — no location ever renders without a logo.'; ?>
            </p>

            <?php
            $logoManagementFields = [
                'logo_header_light' => [
                    'label_ar' => 'شعار الهيدر - الوضع الفاتح (الشعار الرئيسي)',
                    'label_en' => 'Header Logo - Light Mode (primary logo)',
                    'hint_ar'  => 'يُستخدم أيضاً كشعار احتياطي لأي مكان آخر لم يُرفع له شعار خاص. أبعاد مقترحة: 76×76 بكسل (دائري)',
                    'hint_en'  => 'Also used as the fallback for any other spot without its own logo. Suggested: 76×76 px (circular)',
                ],
                'logo_header_dark' => [
                    'label_ar' => 'شعار الهيدر - الوضع الداكن',
                    'label_en' => 'Header Logo - Dark Mode',
                    'hint_ar'  => 'يظهر تلقائياً عند تفعيل الوضع الداكن. إن تُرك فارغاً يُستخدم شعار الوضع الفاتح بدلاً منه.',
                    'hint_en'  => 'Shown automatically in dark mode. Falls back to the light-mode logo if left empty.',
                ],
                'logo_mobile_menu' => [
                    'label_ar' => 'شعار قائمة الهامبرغر (الجوال)',
                    'label_en' => 'Mobile Hamburger Menu Logo',
                    'hint_ar'  => 'يظهر أعلى القائمة المنسدلة على الجوال. يتبدّل مع الوضع الفاتح/الداكن تلقائياً إذا رفعت شعار الهيدر لكلا الوضعين.',
                    'hint_en'  => 'Shown at the top of the mobile off-canvas menu. Follows light/dark mode automatically if both header logos are set.',
                ],
                'logo_footer' => [
                    'label_ar' => 'شعار الفوتر',
                    'label_en' => 'Footer Logo',
                    'hint_ar'  => 'يظهر بجانب اسم الشركة أسفل الموقع.',
                    'hint_en'  => 'Shown next to the company name at the bottom of the site.',
                ],
            ];
            ?>

            <div class="admin-form-grid">
                <?php foreach ($logoManagementFields as $fieldKey => $meta): ?>
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $lang === 'ar' ? e($meta['label_ar']) : e($meta['label_en']); ?>
                        <small style="color:var(--color-text-muted);font-weight:400;"> (PNG, JPG, SVG, WebP)</small>
                    </label>
                    <?php if (getSetting($fieldKey)): ?>
                    <div style="margin-bottom:var(--space-3);padding:var(--space-3);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);">
                        <p style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:var(--space-2);">
                            <?php echo $lang === 'ar' ? 'الشعار الحالي:' : 'Current logo:'; ?>
                        </p>
                        <img src="<?php echo asset('images/' . getSetting($fieldKey)); ?>"
                             alt="<?php echo e($fieldKey); ?>"
                             data-logo-preview-current="<?php echo e($fieldKey); ?>"
                             style="max-height:60px;max-width:200px;object-fit:contain;">
                    </div>
                    <?php else: ?>
                    <img data-logo-preview-current="<?php echo e($fieldKey); ?>" style="display:none;max-height:60px;max-width:200px;object-fit:contain;margin-bottom:var(--space-3);">
                    <?php endif; ?>
                    <input type="file" name="<?php echo e($fieldKey); ?>" class="form-control logo-upload-input"
                           data-preview-target="<?php echo e($fieldKey); ?>"
                           accept="image/png,image/jpeg,image/gif,image/svg+xml,image/webp">
                    <small style="color:var(--color-text-muted);font-size:0.78rem;">
                        <?php echo $lang === 'ar' ? e($meta['hint_ar']) : e($meta['hint_en']); ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ══════════════════════════════════════
                 SECTION: SITE FONT
                 ══════════════════════════════════════ -->
            <h3 style="margin:var(--space-6) 0 var(--space-4);color:var(--color-primary);font-size:1.05rem;border-bottom:2px solid var(--color-border);padding-bottom:var(--space-3);">
                🔤 <?php echo $lang === 'ar' ? 'خط الموقع' : 'Site Font'; ?>
            </h3>

            <?php $currentFont = getSetting('site_font', 'Tajawal'); ?>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'اختر خط الموقع' : 'Choose site font'; ?></label>
                <select name="site_font" id="site-font-select" class="form-control">
                    <?php foreach (getSiteFontCatalog() as $fontKey => $fontMeta): ?>
                    <option value="<?php echo e($fontKey); ?>" <?php echo $currentFont === $fontKey ? 'selected' : ''; ?>>
                        <?php echo e($fontMeta['label_ar']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="font-preview-box" style="margin:var(--space-3) 0 var(--space-4);padding:var(--space-5);background:var(--color-bg-alt);border-radius:var(--radius-md);border:1px solid var(--color-border);text-align:center;">
                <p id="font-preview-text" dir="rtl" style="font-size:1.5rem;margin:0;transition:font-family 0.15s ease;">
                    عالم الصناعة لتجارة الكهرباء
                </p>
                <small style="color:var(--color-text-muted);font-size:0.78rem;">
                    <?php echo $lang === 'ar' ? 'معاينة فورية — لن يُحفظ الخط إلا بالضغط على "تطبيق على الموقع" أو "حفظ الإعدادات"' : 'Live preview only — the font is saved when you click "Apply to site" or "Save"'; ?>
                </small>
            </div>

            <button type="submit" name="apply_font" value="1" class="btn btn-secondary" style="margin-bottom:var(--space-6);">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3"></path><path d="M9 20h6"></path><path d="M12 4v16"></path></svg>
                <?php echo $lang === 'ar' ? 'تطبيق على الموقع' : 'Apply to site'; ?>
            </button>

            <!-- ══════════════════════════════════════
                 SECTION: CONTACT
                 ══════════════════════════════════════ -->
            <h3 style="margin:var(--space-6) 0 var(--space-4);color:var(--color-primary);font-size:1.05rem;border-bottom:2px solid var(--color-border);padding-bottom:var(--space-3);">
                📞 <?php echo $lang === 'ar' ? 'معلومات الاتصال' : 'Contact Information'; ?>
            </h3>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'العنوان (عربي)' : 'Address (Arabic)'; ?></label>
                    <input type="text" name="company_address_ar" class="form-control" value="<?php echo e(getSetting('company_address_ar')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'العنوان (إنجليزي)' : 'Address (English)'; ?></label>
                    <input type="text" name="company_address_en" class="form-control" value="<?php echo e(getSetting('company_address_en')); ?>">
                </div>
            </div>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'ساعات العمل (عربي)' : 'Working hours (Arabic)'; ?></label>
                    <input type="text" name="working_hours_ar" class="form-control" value="<?php echo e(getSetting('working_hours_ar')); ?>" placeholder="السبت – الخميس: 8 ص – 10 م، الجمعة: 2 ظ – 10 م">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'ساعات العمل (إنجليزي)' : 'Working hours (English)'; ?></label>
                    <input type="text" name="working_hours_en" class="form-control" dir="ltr" value="<?php echo e(getSetting('working_hours_en')); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'رسائل الشريط الإعلاني (عربي) — افصل بين الرسائل بـ |' : 'Announcement bar messages (Arabic) — separate with |'; ?></label>
                <textarea name="ad_bar_items_ar" class="form-control" rows="2"><?php echo e(getSetting('ad_bar_items_ar')); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'رسائل الشريط الإعلاني (إنجليزي) — افصل بين الرسائل بـ |' : 'Announcement bar messages (English) — separate with |'; ?></label>
                <textarea name="ad_bar_items_en" class="form-control" rows="2" dir="ltr"><?php echo e(getSetting('ad_bar_items_en')); ?></textarea>
            </div>

            <h3 style="margin:var(--space-6) 0 var(--space-4);color:var(--color-primary);font-size:1.05rem;border-bottom:2px solid var(--color-border);padding-bottom:var(--space-3);">
                📈 <?php echo $lang === 'ar' ? 'التحليلات وأدوات مشرفي المواقع' : 'Analytics & Webmaster Tools'; ?>
            </h3>
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Google Analytics 4 (Measurement ID)</label>
                    <input type="text" name="google_analytics_id" class="form-control" dir="ltr" placeholder="G-XXXXXXXXXX" value="<?php echo e(getSetting('google_analytics_id')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Google Search Console <?php echo $lang === 'ar' ? '(رمز التحقق)' : '(verification)'; ?></label>
                    <input type="text" name="site_verification_google" class="form-control" dir="ltr" placeholder="google-site-verification token" value="<?php echo e(getSetting('site_verification_google')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Bing Webmaster <?php echo $lang === 'ar' ? '(رمز التحقق)' : '(verification)'; ?></label>
                    <input type="text" name="site_verification_bing" class="form-control" dir="ltr" value="<?php echo e(getSetting('site_verification_bing')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">TikTok URL</label>
                    <input type="url" name="tiktok_url" class="form-control" dir="ltr" value="<?php echo e(getSetting('tiktok_url')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">YouTube URL</label>
                    <input type="url" name="youtube_url" class="form-control" dir="ltr" value="<?php echo e(getSetting('youtube_url')); ?>">
                </div>
            </div>

            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?></label>
                    <input type="email" name="company_email" class="form-control" value="<?php echo e(getSetting('company_email')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $lang === 'ar' ? 'رقم الهاتف للاتصال' : 'Phone Number (Call)'; ?>
                        <small style="color:var(--color-text-muted);font-weight:400;"> <?php echo $lang === 'ar' ? '(زر الاتصال في الهيدر)' : '(header call button)'; ?></small>
                    </label>
                    <input type="text" name="contact_phone" class="form-control" 
                           placeholder="+967XXXXXXXXX"
                           value="<?php echo e(getSetting('contact_phone')); ?>">
                </div>
            </div>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">WhatsApp <?php echo $lang === 'ar' ? 'الرئيسي' : 'Primary'; ?></label>
                    <input type="text" name="whatsapp_primary" class="form-control" value="<?php echo e(getSetting('whatsapp_primary')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">WhatsApp 2</label>
                    <input type="text" name="whatsapp_secondary_1" class="form-control" value="<?php echo e(getSetting('whatsapp_secondary_1')); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">WhatsApp 3</label>
                <input type="text" name="whatsapp_secondary_2" class="form-control" value="<?php echo e(getSetting('whatsapp_secondary_2')); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?php echo $lang === 'ar' ? 'رابط الموقع على خرائط جوجل' : 'Google Maps Link'; ?>
                </label>
                <input type="url" name="google_maps_url" class="form-control"
                       placeholder="https://maps.app.goo.gl/xxxxxxxx"
                       value="<?php echo e(getSetting('google_maps_url')); ?>">
                <small style="color:var(--color-text-muted);font-size:0.78rem;">
                    <?php echo $lang === 'ar'
                        ? 'الصق رابط موقعك من خرائط جوجل (رابط مشاركة قصير مثل maps.app.goo.gl أو رابط كامل)، وسيظهر تلقائياً في خريطة صفحة اتصل بنا.'
                        : 'Paste your location link from Google Maps (a short maps.app.goo.gl share link or a full link) — it will appear automatically on the Contact page map.'; ?>
                </small>
            </div>
            
            <!-- ══════════════════════════════════════
                 SECTION: SOCIAL MEDIA
                 ══════════════════════════════════════ -->
            <h3 style="margin:var(--space-6) 0 var(--space-4);color:var(--color-primary);font-size:1.05rem;border-bottom:2px solid var(--color-border);padding-bottom:var(--space-3);">
                📱 <?php echo $lang === 'ar' ? 'وسائل التواصل الاجتماعي' : 'Social Media'; ?>
            </h3>
            
            <div class="admin-form-grid">
                <div class="form-group">
                    <label class="form-label">Facebook</label>
                    <input type="url" name="facebook_url" class="form-control" value="<?php echo e(getSetting('facebook_url')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Twitter / X</label>
                    <input type="url" name="twitter_url" class="form-control" value="<?php echo e(getSetting('twitter_url')); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Instagram</label>
                <input type="url" name="instagram_url" class="form-control" value="<?php echo e(getSetting('instagram_url')); ?>">
            </div>
            
            <div style="margin-top:var(--space-6);">
                <button type="submit" class="btn btn-primary">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <?php echo __('save'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';

    // ── Live preview for every logo / cursor-icon upload field ──────
    document.querySelectorAll('.logo-upload-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var file = input.files && input.files[0];
            if (!file) return;
            var target = document.querySelector('img[data-logo-preview-current="' + input.dataset.previewTarget + '"]');
            if (!target) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                target.src = e.target.result;
                target.style.display = '';
            };
            reader.readAsDataURL(file);
        });
    });

    // ── Font live preview (no save needed) ───────────────────────────
    var fontCatalog = <?php echo json_encode(array_map(function($f) { return $f['google']; }, getSiteFontCatalog())); ?>;
    var fontSelect = document.getElementById('site-font-select');
    var previewText = document.getElementById('font-preview-text');
    var loadedFonts = {};

    function loadFontFace(fontKey) {
        if (loadedFonts[fontKey] || !fontCatalog[fontKey]) return;
        loadedFonts[fontKey] = true;
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + fontCatalog[fontKey] + '&display=swap';
        document.head.appendChild(link);
    }

    function applyPreview(fontKey) {
        loadFontFace(fontKey);
        previewText.style.fontFamily = "'" + fontKey + "', sans-serif";
    }

    if (fontSelect && previewText) {
        applyPreview(fontSelect.value);
        fontSelect.addEventListener('change', function() {
            applyPreview(fontSelect.value);
        });
    }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
