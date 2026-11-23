<?php
/**
 * Admin - File Library ("مكتبة الملفات")
 * Aggregated view of every image across the project (page_images,
 * products, portfolio, categories, brand logos), grouped into tabs by
 * source, with search, a details/delete/replace modal, and full CRUD +
 * drag-and-drop reordering for the homepage brand marquee (brand_logos).
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

$lang = getCurrentLang();
$adminPage = 'media_library';
$pageTitle = ($lang === 'ar' ? 'مكتبة الملفات - ' : 'File Library - ') . getSetting('site_name_' . $lang);

$errors = [];
$success = '';

// ------------------------------------------------------------
// Non-AJAX fallback: adding a brand logo also works as a normal
// POST + redirect, in case JavaScript is unavailable.
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_brand_logo_fallback') {
    if (validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $brandName = trim($_POST['brand_name'] ?? '');
        if ($brandName === '' || empty($_FILES['logo']['tmp_name'])) {
            $errors[] = $lang === 'ar' ? 'الرجاء إدخال اسم الماركة واختيار صورة الشعار' : 'Please enter a brand name and choose a logo image';
        } else {
            $upload = saveBrandLogoUpload($_FILES['logo']);
            if (!$upload['success']) {
                $errors[] = $upload['error'];
            } else {
                $id = createBrandLogo($brandName, $upload['path']);
                logActivity('create', 'brand_logos', $id, 'Added brand logo: ' . $brandName);
                setFlash('success', $lang === 'ar' ? 'تم إضافة الشعار بنجاح' : 'Logo added successfully');
                header('Location: ' . adminUrl('media_library.php') . '#brand-logos');
                exit;
            }
        }
    } else {
        $errors[] = $lang === 'ar' ? 'انتهت صلاحية الجلسة، أعد المحاولة' : 'Session expired, please retry';
    }
}

// ------------------------------------------------------------
// Data for the initial render (search box also works without JS via ?q=)
// ------------------------------------------------------------
$searchQuery = trim($_GET['q'] ?? '');
$allImages = getMediaLibraryImages($searchQuery);
$grouped = groupMediaLibraryByCategory($allImages);
$brandLogos = getAllBrandLogos();

$totalCount = count($allImages);

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-2);flex-wrap:wrap;gap:var(--space-3);" class="reveal">
    <h1 style="font-size:1.35rem;"><?php echo $lang === 'ar' ? 'مكتبة الملفات' : 'File Library'; ?></h1>
    <span class="badge badge-secondary"><?php echo $totalCount; ?> <?php echo $lang === 'ar' ? 'صورة' : 'images'; ?></span>
</div>
<p style="color:var(--color-text-muted);margin-bottom:var(--space-5);">
    <?php echo $lang === 'ar'
        ? 'جميع الصور المستخدمة في الموقع في مكان واحد: صور الهيرو والبانرات، صور المنتجات، شعارات الماركات، وصور عامة أخرى.'
        : 'Every image used across the site in one place: hero/banner images, product images, brand logos, and other images.'; ?>
</p>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--space-5);padding:var(--space-4);background:#fee2e2;border-radius:var(--radius-md);color:#b91c1c;">
    <?php foreach ($errors as $err): ?>
        <div><?php echo e($err); ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ============================================================
     Search
     ============================================================ -->
<div class="admin-card reveal" style="margin-bottom:var(--space-5);">
    <div class="admin-card-body" style="padding:var(--space-4);">
        <div style="position:relative;max-width:420px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;top:50%;transform:translateY(-50%);inset-inline-start:12px;width:18px;height:18px;color:var(--color-text-muted);pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input
                type="text"
                id="ml-search"
                class="form-control"
                style="padding-inline-start:38px;"
                placeholder="<?php echo $lang === 'ar' ? 'ابحث بالاسم...' : 'Search by name...'; ?>"
                value="<?php echo e($searchQuery); ?>"
                autocomplete="off"
            >
        </div>
    </div>
</div>

<!-- ============================================================
     Tabs
     ============================================================ -->
<div class="ml-tabs reveal" role="tablist">
    <?php
    $tabIcons = [
        'hero'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
        'product' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>',
        'brand'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a15 15 0 0 1 0 20"></path><path d="M2 12h20"></path></svg>',
        'other'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M3 9h18"></path></svg>',
    ];
    $first = true;
    foreach ($grouped as $catKey => $cat):
    ?>
    <button type="button" class="ml-tab-btn <?php echo $first ? 'active' : ''; ?>" data-tab="<?php echo e($catKey); ?>">
        <?php echo $tabIcons[$catKey] ?? ''; ?>
        <span><?php echo e($cat['label']); ?></span>
        <span class="ml-tab-count"><?php echo count($cat['items']); ?></span>
    </button>
    <?php $first = false; endforeach; ?>
</div>

<!-- ============================================================
     Tab panels: hero / product / other -> plain image grid
     ============================================================ -->
<?php foreach ($grouped as $catKey => $cat): if ($catKey === 'brand') continue; ?>
<div class="ml-panel <?php echo $catKey === array_key_first($grouped) ? '' : 'hidden'; ?>" data-panel="<?php echo e($catKey); ?>">
    <?php if (empty($cat['items'])): ?>
        <div class="ml-empty">
            <?php echo $lang === 'ar' ? 'لا توجد صور في هذا التصنيف حتى الآن.' : 'No images in this category yet.'; ?>
        </div>
    <?php else: ?>
        <div class="ml-grid">
            <?php foreach ($cat['items'] as $item):
                $mapKey = resolveMediaLibraryMapKey($item['source_table'], $item['extra']);
                $imgUrl = uploadUrl($item['image_path']);
            ?>
            <div class="ml-thumb"
                 data-map-key="<?php echo e($mapKey ?? ''); ?>"
                 data-source-id="<?php echo (int) $item['source_id']; ?>"
                 data-title="<?php echo e($item['title']); ?>"
                 data-used-in="<?php echo e($item['used_in']); ?>"
                 data-path="<?php echo e($item['image_path']); ?>"
                 data-url="<?php echo e($imgUrl); ?>"
                 tabindex="0">
                <img src="<?php echo e($imgUrl); ?>" alt="<?php echo e($item['title']); ?>" loading="lazy" decoding="async">
                <div class="ml-thumb-caption"><?php echo e($item['title']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- ============================================================
     Tab panel: brand -> logos management (add / reorder / toggle / delete)
     ============================================================ -->
<div class="ml-panel hidden" data-panel="brand" id="brand-logos">
    <div class="admin-card" style="margin-bottom:var(--space-5);">
        <div class="admin-card-header">
            <h3 style="font-size:1.05rem;"><?php echo $lang === 'ar' ? 'إضافة شعار ماركة جديد' : 'Add a new brand logo'; ?></h3>
        </div>
        <div class="admin-card-body">
            <form id="add-brand-form" method="POST" action="" enctype="multipart/form-data" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="form_action" value="add_brand_logo_fallback">
                <div class="form-group" style="flex:1;min-width:220px;margin:0;">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'اسم الماركة' : 'Brand name'; ?></label>
                    <input type="text" name="brand_name" class="form-control" required placeholder="<?php echo $lang === 'ar' ? 'مثال: شنايدر إلكتريك' : 'e.g. Schneider Electric'; ?>">
                </div>
                <div class="form-group" style="flex:1;min-width:220px;margin:0;">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'صورة الشعار' : 'Logo image'; ?></label>
                    <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <?php echo $lang === 'ar' ? 'إضافة' : 'Add'; ?>
                </button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 style="font-size:1.05rem;"><?php echo $lang === 'ar' ? 'الشعارات الحالية' : 'Current logos'; ?></h3>
            <small style="color:var(--color-text-muted);"><?php echo $lang === 'ar' ? 'اسحب لإعادة الترتيب' : 'Drag to reorder'; ?></small>
        </div>
        <div class="admin-card-body">
            <?php if (empty($brandLogos)): ?>
                <div class="ml-empty"><?php echo $lang === 'ar' ? 'لا توجد شعارات بعد — الشريط مخفي حالياً في الموقع.' : 'No logos yet — the strip is currently hidden on the site.'; ?></div>
            <?php else: ?>
                <ul id="brand-logo-list" class="ml-brand-list">
                    <?php foreach ($brandLogos as $logo): ?>
                    <li class="ml-brand-item <?php echo !$logo['is_active'] ? 'is-inactive' : ''; ?>" data-id="<?php echo (int) $logo['id']; ?>" draggable="true">
                        <span class="ml-brand-drag" title="<?php echo $lang === 'ar' ? 'اسحب لإعادة الترتيب' : 'Drag to reorder'; ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><circle cx="8" cy="6" r="1.5"/><circle cx="8" cy="12" r="1.5"/><circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="6" r="1.5"/><circle cx="16" cy="12" r="1.5"/><circle cx="16" cy="18" r="1.5"/></svg>
                        </span>
                        <img src="<?php echo e(uploadUrl($logo['logo_path'])); ?>" alt="<?php echo e($logo['brand_name']); ?>" class="ml-brand-logo-img">
                        <span class="ml-brand-name"><?php echo e($logo['brand_name']); ?></span>
                        <input type="number" class="form-control ml-brand-order" value="<?php echo (int) $logo['display_order']; ?>" min="0" style="width:70px;" data-id="<?php echo (int) $logo['id']; ?>" title="<?php echo $lang === 'ar' ? 'ترتيب الظهور' : 'Display order'; ?>">
                        <label class="ml-brand-toggle" title="<?php echo $lang === 'ar' ? 'تفعيل/تعطيل' : 'Enable/disable'; ?>">
                            <input type="checkbox" class="ml-brand-active" data-id="<?php echo (int) $logo['id']; ?>" <?php echo $logo['is_active'] ? 'checked' : ''; ?>>
                            <span class="ml-brand-toggle-slider"></span>
                        </label>
                        <button type="button" class="btn btn-sm btn-ghost ml-brand-replace" data-id="<?php echo (int) $logo['id']; ?>" title="<?php echo $lang === 'ar' ? 'استبدال الصورة' : 'Replace image'; ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost ml-brand-delete" data-id="<?php echo (int) $logo['id']; ?>" title="<?php echo $lang === 'ar' ? 'حذف' : 'Delete'; ?>" style="color:var(--color-danger);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     Image details modal
     ============================================================ -->
<div id="ml-modal-overlay" class="ml-modal-overlay hidden">
    <div class="ml-modal">
        <button type="button" class="ml-modal-close" id="ml-modal-close" aria-label="close">&times;</button>
        <div class="ml-modal-body">
            <div class="ml-modal-preview">
                <img id="ml-modal-img" src="" alt="">
            </div>
            <div class="ml-modal-info">
                <h3 id="ml-modal-title">—</h3>
                <dl class="ml-modal-meta">
                    <dt><?php echo $lang === 'ar' ? 'اسم الملف' : 'File name'; ?></dt>
                    <dd id="ml-modal-filename">—</dd>
                    <dt><?php echo $lang === 'ar' ? 'المقاس الفعلي' : 'Actual size'; ?></dt>
                    <dd id="ml-modal-dimensions">—</dd>
                    <dt><?php echo $lang === 'ar' ? 'مستخدمة في' : 'Used in'; ?></dt>
                    <dd id="ml-modal-usedin">—</dd>
                </dl>

                <div id="ml-modal-msg" class="ml-modal-msg hidden"></div>

                <div class="ml-modal-actions">
                    <label class="btn btn-outline btn-sm ml-modal-replace-btn">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                        <?php echo $lang === 'ar' ? 'استبدال الصورة' : 'Replace image'; ?>
                        <input type="file" id="ml-modal-replace-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
                    </label>
                    <button type="button" id="ml-modal-delete-btn" class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                        <?php echo $lang === 'ar' ? 'حذف' : 'Delete'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================================
   File Library - scoped styles
   ============================================================ */
.ml-tabs {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
  margin-bottom: var(--space-5);
  border-bottom: 1px solid var(--color-border);
  padding-bottom: var(--space-3);
}
.ml-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
}
.ml-tab-btn svg { width: 16px; height: 16px; }
.ml-tab-btn:hover { color: var(--color-primary); border-color: var(--color-primary); }
.ml-tab-btn.active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
.ml-tab-count {
  background: rgba(0,0,0,0.12);
  border-radius: 999px;
  font-size: 0.72rem;
  padding: 1px 7px;
}
.ml-tab-btn.active .ml-tab-count { background: rgba(255,255,255,0.25); }

.ml-panel.hidden { display: none; }

.ml-empty {
  padding: var(--space-8) var(--space-4);
  text-align: center;
  color: var(--color-text-muted);
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}

.ml-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--space-4);
}
.ml-thumb {
  cursor: pointer;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-bg-alt);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}
.ml-thumb:hover, .ml-thumb:focus-visible {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md, 0 6px 16px rgba(0,0,0,0.12));
  outline: none;
}
.ml-thumb img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
  background: #f1f5f9;
}
.ml-thumb-caption {
  padding: var(--space-2) var(--space-3);
  font-size: 0.78rem;
  color: var(--color-text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Brand logos manager */
.ml-brand-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: var(--space-2); }
.ml-brand-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg);
}
.ml-brand-item.is-inactive { opacity: 0.5; }
.ml-brand-item.dragging { opacity: 0.4; }
.ml-brand-drag { cursor: grab; color: var(--color-text-muted); display: flex; }
.ml-brand-logo-img { width: 44px; height: 44px; object-fit: contain; background: var(--color-bg-alt); border-radius: var(--radius-sm); padding: 4px; }
.ml-brand-name { flex: 1; font-weight: 600; font-size: 0.9rem; }
.ml-brand-order { padding: 4px 8px; }

.ml-brand-toggle { position: relative; display: inline-block; width: 38px; height: 20px; flex-shrink: 0; }
.ml-brand-toggle input { opacity: 0; width: 0; height: 0; }
.ml-brand-toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 999px; cursor: pointer; transition: background var(--transition-fast); }
.ml-brand-toggle-slider::before { content: ''; position: absolute; width: 16px; height: 16px; left: 2px; top: 2px; background: #fff; border-radius: 50%; transition: transform var(--transition-fast); }
.ml-brand-toggle input:checked + .ml-brand-toggle-slider { background: var(--color-primary); }
.ml-brand-toggle input:checked + .ml-brand-toggle-slider::before { transform: translateX(18px); }
[dir="rtl"] .ml-brand-toggle input:checked + .ml-brand-toggle-slider::before { transform: translateX(-18px); }

/* Modal */
.ml-modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: var(--space-4);
}
.ml-modal-overlay.hidden { display: none; }
.ml-modal {
  background: var(--color-bg); border-radius: var(--radius-lg);
  max-width: 720px; width: 100%; max-height: 90vh; overflow-y: auto;
  position: relative; padding: var(--space-6);
}
.ml-modal-close {
  position: absolute; top: var(--space-3); inset-inline-end: var(--space-3);
  background: none; border: none; font-size: 1.5rem; line-height: 1;
  cursor: pointer; color: var(--color-text-muted); width: 32px; height: 32px;
}
.ml-modal-body { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5); }
.ml-modal-preview {
  background: var(--color-bg-alt); border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center; min-height: 220px; overflow: hidden;
}
.ml-modal-preview img { max-width: 100%; max-height: 320px; object-fit: contain; }
.ml-modal-meta { display: grid; grid-template-columns: auto 1fr; gap: var(--space-2) var(--space-3); font-size: 0.85rem; margin: var(--space-4) 0; }
.ml-modal-meta dt { color: var(--color-text-muted); font-weight: 600; }
.ml-modal-meta dd { margin: 0; word-break: break-word; }
.ml-modal-actions { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.ml-modal-replace-btn { cursor: pointer; }
.ml-modal-msg { padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: var(--space-3); }
.ml-modal-msg.hidden { display: none; }
.ml-modal-msg.error { background: #fee2e2; color: #b91c1c; }
.ml-modal-msg.success { background: #dcfce7; color: #166534; }

@media (max-width: 640px) {
  .ml-modal-body { grid-template-columns: 1fr; }
}
</style>

<script>
(function () {
  'use strict';

  var CSRF_TOKEN = '<?php echo generateCsrfToken(); ?>';
  var API_URL = '<?php echo adminUrl('media_library_api.php'); ?>';
  var LANG = '<?php echo $lang; ?>';
  var T = {
    confirmDelete: LANG === 'ar' ? 'هل أنت متأكد من حذف هذه الصورة نهائياً؟ لا يمكن التراجع عن هذا الإجراء.' : 'Delete this image permanently? This cannot be undone.',
    confirmDeleteLogo: LANG === 'ar' ? 'هل أنت متأكد من حذف هذا الشعار؟' : 'Delete this brand logo?',
    unreadable: LANG === 'ar' ? 'تعذّرت قراءة أبعاد الصورة' : 'Could not read image dimensions',
    deleted: LANG === 'ar' ? 'تم الحذف بنجاح' : 'Deleted successfully',
    replaced: LANG === 'ar' ? 'تم استبدال الصورة بنجاح' : 'Image replaced successfully',
    genericError: LANG === 'ar' ? 'حدث خطأ، حاول مرة أخرى' : 'Something went wrong, please try again'
  };

  // ---------------- Tabs ----------------
  var tabBtns = document.querySelectorAll('.ml-tab-btn');
  var panels = document.querySelectorAll('.ml-panel');
  tabBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      tabBtns.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      var target = btn.getAttribute('data-tab');
      panels.forEach(function (p) {
        p.classList.toggle('hidden', p.getAttribute('data-panel') !== target);
      });
    });
  });

  // ---------------- Search (client-side filter, no reload) ----------------
  var searchInput = document.getElementById('ml-search');
  var allThumbs = document.querySelectorAll('.ml-thumb');
  var allBrandItems = document.querySelectorAll('.ml-brand-item');
  var searchTimer = null;
  searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    var q = searchInput.value.trim().toLowerCase();
    searchTimer = setTimeout(function () {
      allThumbs.forEach(function (t) {
        var title = (t.getAttribute('data-title') || '').toLowerCase();
        t.style.display = (q === '' || title.indexOf(q) !== -1) ? '' : 'none';
      });
      allBrandItems.forEach(function (li) {
        var name = (li.querySelector('.ml-brand-name').textContent || '').toLowerCase();
        li.style.display = (q === '' || name.indexOf(q) !== -1) ? '' : 'none';
      });
    }, 150);
  });

  // ---------------- Modal ----------------
  var overlay = document.getElementById('ml-modal-overlay');
  var modalImg = document.getElementById('ml-modal-img');
  var modalTitle = document.getElementById('ml-modal-title');
  var modalFilename = document.getElementById('ml-modal-filename');
  var modalDimensions = document.getElementById('ml-modal-dimensions');
  var modalUsedin = document.getElementById('ml-modal-usedin');
  var modalMsg = document.getElementById('ml-modal-msg');
  var deleteBtn = document.getElementById('ml-modal-delete-btn');
  var replaceInput = document.getElementById('ml-modal-replace-input');

  var currentMapKey = null;
  var currentSourceId = null;
  var currentThumbEl = null;

  function showModalMsg(text, type) {
    modalMsg.textContent = text;
    modalMsg.className = 'ml-modal-msg ' + type;
  }
  function clearModalMsg() {
    modalMsg.className = 'ml-modal-msg hidden';
    modalMsg.textContent = '';
  }

  function openModalFor(thumbEl) {
    currentThumbEl = thumbEl;
    currentMapKey = thumbEl.getAttribute('data-map-key');
    currentSourceId = thumbEl.getAttribute('data-source-id');

    var url = thumbEl.getAttribute('data-url');
    var path = thumbEl.getAttribute('data-path');
    modalImg.src = url;
    modalTitle.textContent = thumbEl.getAttribute('data-title') || '';
    modalFilename.textContent = path.split('/').pop();
    modalUsedin.textContent = thumbEl.getAttribute('data-used-in') || '';
    modalDimensions.textContent = LANG === 'ar' ? 'جارٍ القياس...' : 'Measuring...';
    clearModalMsg();

    var probe = new Image();
    probe.onload = function () {
      modalDimensions.textContent = probe.naturalWidth + ' × ' + probe.naturalHeight + ' px';
    };
    probe.onerror = function () {
      modalDimensions.textContent = T.unreadable;
    };
    probe.src = url;

    overlay.classList.remove('hidden');
  }

  function closeModal() {
    overlay.classList.add('hidden');
    replaceInput.value = '';
  }

  allThumbs.forEach(function (thumb) {
    thumb.addEventListener('click', function () { openModalFor(thumb); });
    thumb.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModalFor(thumb); }
    });
  });

  document.getElementById('ml-modal-close').addEventListener('click', closeModal);
  overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

  deleteBtn.addEventListener('click', function () {
    if (!currentMapKey || !currentSourceId) return;
    if (!confirm(T.confirmDelete)) return;

    var fd = new FormData();
    fd.append('action', 'delete_image');
    fd.append('map_key', currentMapKey);
    fd.append('source_id', currentSourceId);
    fd.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);

    fetch(API_URL, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          if (currentThumbEl) currentThumbEl.remove();
          closeModal();
        } else {
          showModalMsg(data.error || T.genericError, 'error');
        }
      })
      .catch(function () { showModalMsg(T.genericError, 'error'); });
  });

  replaceInput.addEventListener('change', function () {
    if (!replaceInput.files || !replaceInput.files[0] || !currentMapKey || !currentSourceId) return;

    var fd = new FormData();
    fd.append('action', 'replace_image');
    fd.append('map_key', currentMapKey);
    fd.append('source_id', currentSourceId);
    fd.append('file', replaceInput.files[0]);
    fd.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);

    fetch(API_URL, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          showModalMsg(T.replaced, 'success');
          // Refresh preview + thumbnail with a cache-busted URL
          var bustUrl = modalImg.src.split('?')[0] + '?t=' + Date.now();
          modalImg.src = bustUrl;
          if (currentThumbEl) {
            currentThumbEl.querySelector('img').src = bustUrl;
            currentThumbEl.setAttribute('data-url', bustUrl);
          }
        } else {
          showModalMsg(data.error || T.genericError, 'error');
        }
      })
      .catch(function () { showModalMsg(T.genericError, 'error'); })
      .finally(function () { replaceInput.value = ''; });
  });

  // ---------------- Brand logos: toggle active ----------------
  document.querySelectorAll('.ml-brand-active').forEach(function (chk) {
    chk.addEventListener('change', function () {
      var id = chk.getAttribute('data-id');
      var fd = new FormData();
      fd.append('action', 'toggle_brand_logo');
      fd.append('id', id);
      fd.append('active', chk.checked ? '1' : '0');
      fd.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);
      fetch(API_URL, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var li = chk.closest('.ml-brand-item');
          if (data.success) {
            li.classList.toggle('is-inactive', !chk.checked);
          } else {
            chk.checked = !chk.checked;
            alert(data.error || T.genericError);
          }
        });
    });
  });

  // ---------------- Brand logos: numeric order fallback ----------------
  document.querySelectorAll('.ml-brand-order').forEach(function (input) {
    input.addEventListener('change', function () {
      var fd = new FormData();
      fd.append('action', 'set_brand_logo_order');
      fd.append('id', input.getAttribute('data-id'));
      fd.append('order', input.value);
      fd.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);
      fetch(API_URL, { method: 'POST', body: fd });
    });
  });

  // ---------------- Brand logos: delete ----------------
  document.querySelectorAll('.ml-brand-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm(T.confirmDeleteLogo)) return;
      var id = btn.getAttribute('data-id');
      var fd = new FormData();
      fd.append('action', 'delete_brand_logo');
      fd.append('id', id);
      fd.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);
      fetch(API_URL, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            btn.closest('.ml-brand-item').remove();
          } else {
            alert(data.error || T.genericError);
          }
        });
    });
  });

  // ---------------- Brand logos: replace image ----------------
  document.querySelectorAll('.ml-brand-replace').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-id');
      var fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.accept = 'image/jpeg,image/png,image/webp,image/svg+xml';
      fileInput.addEventListener('change', function () {
        if (!fileInput.files || !fileInput.files[0]) return;
        var fd = new FormData();
        fd.append('action', 'replace_image');
        fd.append('map_key', 'brand_logos');
        fd.append('source_id', id);
        fd.append('file', fileInput.files[0]);
        fd.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);
        fetch(API_URL, { method: 'POST', body: fd })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.success) {
              var img = btn.closest('.ml-brand-item').querySelector('.ml-brand-logo-img');
              img.src = img.src.split('?')[0] + '?t=' + Date.now();
            } else {
              alert(data.error || T.genericError);
            }
          });
      });
      fileInput.click();
    });
  });

  // ---------------- Brand logos: drag-and-drop reorder ----------------
  var list = document.getElementById('brand-logo-list');
  if (list) {
    var draggedEl = null;

    list.querySelectorAll('.ml-brand-item').forEach(function (item) {
      item.addEventListener('dragstart', function () {
        draggedEl = item;
        item.classList.add('dragging');
      });
      item.addEventListener('dragend', function () {
        item.classList.remove('dragging');
        draggedEl = null;
        persistOrder();
      });
    });

    list.addEventListener('dragover', function (e) {
      e.preventDefault();
      var afterEl = getDragAfterElement(list, e.clientY);
      if (!draggedEl) return;
      if (afterEl == null) {
        list.appendChild(draggedEl);
      } else {
        list.insertBefore(draggedEl, afterEl);
      }
    });

    function getDragAfterElement(container, y) {
      var items = Array.prototype.slice.call(container.querySelectorAll('.ml-brand-item:not(.dragging)'));
      return items.reduce(function (closest, child) {
        var box = child.getBoundingClientRect();
        var offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          return { offset: offset, element: child };
        }
        return closest;
      }, { offset: -Infinity, element: null }).element;
    }

    function persistOrder() {
      var ids = Array.prototype.map.call(list.querySelectorAll('.ml-brand-item'), function (li) {
        return li.getAttribute('data-id');
      });
      // Reflect new order in the numeric inputs immediately (visual feedback)
      list.querySelectorAll('.ml-brand-item').forEach(function (li, idx) {
        var input = li.querySelector('.ml-brand-order');
        if (input) input.value = idx + 1;
      });

      var fd = new FormData();
      fd.append('action', 'reorder_brand_logos');
      fd.append('ordered_ids', JSON.stringify(ids));
      fd.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);
      fetch(API_URL, { method: 'POST', body: fd });
    }
  }

  // ---------------- Add brand logo form (AJAX, falls back to normal submit) ----------------
  var addForm = document.getElementById('add-brand-form');
  if (addForm && window.fetch) {
    addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(addForm);
      fd.set('action', 'add_brand_logo');
      var submitBtn = addForm.querySelector('button[type="submit"]');
      submitBtn.disabled = true;

      fetch(API_URL, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          submitBtn.disabled = false;
          if (data.success) {
            addForm.reset();
            location.reload();
          } else {
            alert(data.error || T.genericError);
          }
        })
        .catch(function () {
          submitBtn.disabled = false;
          alert(T.genericError);
        });
    });
  }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
