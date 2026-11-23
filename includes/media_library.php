<?php
/**
 * Media Library Module ("مكتبة الملفات")
 * -------------------------------------------------------------
 * Aggregates every image stored across the project's tables into a single,
 * categorized library, and manages the `brand_logos` table that drives the
 * homepage brand marquee.
 *
 * Categories (source_type):
 *   - hero       "صور الهيرو والبانرات"   -> page_images (desktop + mobile)
 *   - product    "صور المنتجات"           -> products.main_image + product_images
 *   - brand      "شعارات الماركات"        -> brand_logos
 *   - other      "صور عامة أخرى"          -> categories.image, portfolio.main_image,
 *                                            portfolio_images, cms_pages (og/meta not
 *                                            included — text only), site_settings logo/
 *                                            favicon/og assets, seo_settings.og_image
 *
 * Each table already stores its own image path relative to UPLOADS_PATH
 * (see uploadPath()/uploadUrl() in includes/functions.php), so no schema
 * change is required to classify existing images — the table/column each
 * row comes from IS the classification signal (per spec section 1, second
 * fallback option: "استخدم اسم الجدول نفسه كمعيار تصنيف").
 *
 * Every row returned by the functions below is normalized to this shape:
 *   [
 *     'source_table'  => string,   // e.g. 'product_images'
 *     'source_id'     => int,      // primary key in that table
 *     'category'      => string,   // hero|product|brand|other
 *     'category_label'=> string,   // Arabic label for the UI
 *     'image_path'    => string,   // relative path under UPLOADS_PATH
 *     'title'         => string,   // human label (product name, section label, ...)
 *     'used_in'       => string,   // where it's used, shown in the modal
 *     'extra'         => array,    // category-specific extra fields (e.g. brand_name)
 *   ]
 */

// ============================================================
// AGGREGATION — fetch + classify every image in the project
// ============================================================

/**
 * Fetch every image from every relevant table, normalized and classified.
 * Optional $search filters by title (LIKE), applied per-source in SQL so
 * large tables are not pulled into PHP before filtering.
 */
function getMediaLibraryImages(string $search = ''): array {
    $items = [];
    $items = array_merge($items, getMediaLibraryHeroImages($search));
    $items = array_merge($items, getMediaLibraryProductImages($search));
    $items = array_merge($items, getMediaLibraryBrandImages($search));
    $items = array_merge($items, getMediaLibraryOtherImages($search));
    return $items;
}

/**
 * Group already-fetched items by category, preserving a fixed display order.
 */
function groupMediaLibraryByCategory(array $items): array {
    $groups = [
        'hero'    => ['label' => 'صور الهيرو والبانرات', 'items' => []],
        'product' => ['label' => 'صور المنتجات',          'items' => []],
        'brand'   => ['label' => 'شعارات الماركات',        'items' => []],
        'other'   => ['label' => 'صور عامة أخرى',          'items' => []],
    ];
    foreach ($items as $item) {
        $cat = $item['category'];
        if (!isset($groups[$cat])) {
            $cat = 'other';
        }
        $groups[$cat]['items'][] = $item;
    }
    return $groups;
}

// ---- 1. Hero / banner images (page_images) -----------------

function getMediaLibraryHeroImages(string $search = ''): array {
    $sql = "SELECT id, section_key, section_label, desktop_image_path, mobile_image_path
            FROM page_images
            WHERE (desktop_image_path IS NOT NULL OR mobile_image_path IS NOT NULL)";
    $params = [];
    if ($search !== '') {
        $sql .= " AND section_label LIKE :s";
        $params['s'] = '%' . $search . '%';
    }
    $rows = Database::fetchAll($sql, $params);

    $items = [];
    foreach ($rows as $row) {
        if (!empty($row['desktop_image_path'])) {
            $items[] = [
                'source_table'   => 'page_images',
                'source_id'      => (int) $row['id'],
                'category'       => 'hero',
                'category_label' => 'صور الهيرو والبانرات',
                'image_path'     => $row['desktop_image_path'],
                'title'          => $row['section_label'] . ' (سطح المكتب)',
                'used_in'        => $row['section_label'] . ' — ' . $row['section_key'],
                'extra'          => ['device' => 'desktop', 'section_key' => $row['section_key']],
            ];
        }
        if (!empty($row['mobile_image_path'])) {
            $items[] = [
                'source_table'   => 'page_images',
                'source_id'      => (int) $row['id'],
                'category'       => 'hero',
                'category_label' => 'صور الهيرو والبانرات',
                'image_path'     => $row['mobile_image_path'],
                'title'          => $row['section_label'] . ' (الهاتف)',
                'used_in'        => $row['section_label'] . ' — ' . $row['section_key'],
                'extra'          => ['device' => 'mobile', 'section_key' => $row['section_key']],
            ];
        }
    }
    return $items;
}

// ---- 2. Product images (products.main_image + product_images) ----

function getMediaLibraryProductImages(string $search = ''): array {
    $lang = getCurrentLang();
    $items = [];

    // Main product image
    $sql = "SELECT id, name_{$lang} AS name, main_image
            FROM products
            WHERE main_image IS NOT NULL AND main_image != '' AND deleted_at IS NULL";
    $params = [];
    if ($search !== '') {
        $sql .= " AND name_{$lang} LIKE :s";
        $params['s'] = '%' . $search . '%';
    }
    $rows = Database::fetchAll($sql, $params);
    foreach ($rows as $row) {
        $items[] = [
            'source_table'   => 'products',
            'source_id'      => (int) $row['id'],
            'category'       => 'product',
            'category_label' => 'صور المنتجات',
            'image_path'     => $row['main_image'],
            'title'          => $row['name'],
            'used_in'        => 'صورة المنتج الرئيسية: ' . $row['name'],
            'extra'          => ['product_id' => (int) $row['id'], 'role' => 'main'],
        ];
    }

    // Product gallery images
    $sql = "SELECT pi.id, pi.product_id, pi.image_path, p.name_{$lang} AS name
            FROM product_images pi
            INNER JOIN products p ON p.id = pi.product_id
            WHERE p.deleted_at IS NULL";
    $params = [];
    if ($search !== '') {
        $sql .= " AND p.name_{$lang} LIKE :s";
        $params['s'] = '%' . $search . '%';
    }
    $sql .= " ORDER BY pi.sort_order ASC";
    $rows = Database::fetchAll($sql, $params);
    foreach ($rows as $row) {
        $items[] = [
            'source_table'   => 'product_images',
            'source_id'      => (int) $row['id'],
            'category'       => 'product',
            'category_label' => 'صور المنتجات',
            'image_path'     => $row['image_path'],
            'title'          => $row['name'],
            'used_in'        => 'معرض صور المنتج: ' . $row['name'],
            'extra'          => ['product_id' => (int) $row['product_id'], 'role' => 'gallery'],
        ];
    }

    return $items;
}

// ---- 3. Brand logos (brand_logos) ---------------------------

function getMediaLibraryBrandImages(string $search = ''): array {
    $rows = getAllBrandLogos($search);
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'source_table'   => 'brand_logos',
            'source_id'      => (int) $row['id'],
            'category'       => 'brand',
            'category_label' => 'شعارات الماركات',
            'image_path'     => $row['logo_path'],
            'title'          => $row['brand_name'],
            'used_in'        => 'شريط الشعارات المتحرك — الصفحة الرئيسية',
            'extra'          => [
                'brand_name'    => $row['brand_name'],
                'display_order' => (int) $row['display_order'],
                'is_active'     => (int) $row['is_active'],
            ],
        ];
    }
    return $items;
}

// ---- 4. Other images (categories, portfolio, site settings) ----

function getMediaLibraryOtherImages(string $search = ''): array {
    $lang = getCurrentLang();
    $items = [];

    // Category thumbnails
    $sql = "SELECT id, name_{$lang} AS name, image FROM categories
            WHERE image IS NOT NULL AND image != ''";
    $params = [];
    if ($search !== '') {
        $sql .= " AND name_{$lang} LIKE :s";
        $params['s'] = '%' . $search . '%';
    }
    foreach (Database::fetchAll($sql, $params) as $row) {
        $items[] = [
            'source_table'   => 'categories',
            'source_id'      => (int) $row['id'],
            'category'       => 'other',
            'category_label' => 'صور عامة أخرى',
            'image_path'     => $row['image'],
            'title'          => $row['name'],
            'used_in'        => 'صورة الفئة: ' . $row['name'],
            'extra'          => [],
        ];
    }

    // Portfolio main images
    $sql = "SELECT id, title_{$lang} AS title, main_image FROM portfolio
            WHERE main_image IS NOT NULL AND main_image != ''";
    $params = [];
    if ($search !== '') {
        $sql .= " AND title_{$lang} LIKE :s";
        $params['s'] = '%' . $search . '%';
    }
    foreach (Database::fetchAll($sql, $params) as $row) {
        $items[] = [
            'source_table'   => 'portfolio',
            'source_id'      => (int) $row['id'],
            'category'       => 'other',
            'category_label' => 'صور عامة أخرى',
            'image_path'     => $row['main_image'],
            'title'          => $row['title'],
            'used_in'        => 'صورة مشروع معرض الأعمال: ' . $row['title'],
            'extra'          => [],
        ];
    }

    // Portfolio gallery images
    $sql = "SELECT pi.id, pi.image_path, po.title_{$lang} AS title
            FROM portfolio_images pi
            INNER JOIN portfolio po ON po.id = pi.portfolio_id";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE po.title_{$lang} LIKE :s";
        $params['s'] = '%' . $search . '%';
    }
    foreach (Database::fetchAll($sql, $params) as $row) {
        $items[] = [
            'source_table'   => 'portfolio_images',
            'source_id'      => (int) $row['id'],
            'category'       => 'other',
            'category_label' => 'صور عامة أخرى',
            'image_path'     => $row['image_path'],
            'title'          => $row['title'],
            'used_in'        => 'معرض صور المشروع: ' . $row['title'],
            'extra'          => [],
        ];
    }

    return $items;
}

// ============================================================
// PHYSICAL FILE HELPERS
// ============================================================

/**
 * Returns actual pixel dimensions ("WxH") for an image relative to
 * UPLOADS_PATH, using getimagesize(). Returns null if unreadable.
 */
function getMediaImageDimensions(string $relativePath): ?array {
    $full = uploadPath($relativePath);
    if (!file_exists($full)) {
        return null;
    }
    $info = @getimagesize($full);
    if (!$info) {
        return null;
    }
    return ['width' => $info[0], 'height' => $info[1]];
}

/**
 * Returns the on-disk file size in bytes, or null if the file is missing.
 */
function getMediaFileSize(string $relativePath): ?int {
    $full = uploadPath($relativePath);
    return file_exists($full) ? filesize($full) : null;
}

// ============================================================
// BRAND LOGOS — CRUD for the homepage marquee
// ============================================================

const BRAND_LOGO_UPLOAD_DIR = 'brand_logos';
const BRAND_LOGO_MAX_SIZE = 5 * 1024 * 1024; // 5MB

const BRAND_LOGO_ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
];

/**
 * All brand logos ordered for admin display (by display_order), optionally
 * filtered by brand_name LIKE search.
 */
function getAllBrandLogos(string $search = ''): array {
    $sql = "SELECT * FROM brand_logos";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE brand_name LIKE :s";
        $params['s'] = '%' . $search . '%';
    }
    $sql .= " ORDER BY display_order ASC, id ASC";
    return Database::fetchAll($sql, $params);
}

/**
 * Active brand logos only, ordered for the public marquee.
 * This is the single entry point the frontend template should use.
 */
function getActiveBrandLogos(): array {
    return Database::fetchAll(
        "SELECT id, brand_name, logo_path FROM brand_logos
         WHERE is_active = 1
         ORDER BY display_order ASC, id ASC"
    );
}

/**
 * Validate + store an uploaded brand logo file. Returns
 * ['success' => bool, 'path' => string, 'error' => string]
 */
function saveBrandLogoUpload(array $file): array {
    $result = ['success' => false, 'path' => '', 'error' => ''];

    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'حدث خطأ أثناء رفع الملف';
        return $result;
    }

    if ($file['size'] > BRAND_LOGO_MAX_SIZE) {
        $result['error'] = 'حجم الملف يتجاوز الحد الأقصى المسموح به (5 ميجابايت)';
        return $result;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset(BRAND_LOGO_ALLOWED_MIMES[$mime])) {
        $result['error'] = 'نوع الملف غير مسموح به (المسموح: JPG, PNG, WEBP, SVG)';
        return $result;
    }

    $ext = BRAND_LOGO_ALLOWED_MIMES[$mime];
    $filename = 'brand_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;

    $uploadDir = uploadPath(BRAND_LOGO_UPLOAD_DIR);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $result['error'] = 'فشل حفظ الملف على الخادم';
        return $result;
    }

    $result['success'] = true;
    $result['path'] = BRAND_LOGO_UPLOAD_DIR . '/' . $filename;
    return $result;
}

/**
 * Create a new brand logo row. New logos are appended to the end of the
 * display order by default.
 */
function createBrandLogo(string $brandName, string $logoPath): int {
    $maxOrder = Database::fetchOne("SELECT MAX(display_order) AS m FROM brand_logos")['m'] ?? 0;
    return Database::insert('brand_logos', [
        'brand_name'    => $brandName,
        'logo_path'     => $logoPath,
        'display_order' => (int) $maxOrder + 1,
        'is_active'     => 1,
    ]);
}

/**
 * Delete a brand logo row and its physical file.
 */
function deleteBrandLogo(int $id): bool {
    $row = Database::fetchOne("SELECT * FROM brand_logos WHERE id = :id", ['id' => $id]);
    if (!$row) {
        return false;
    }
    $full = uploadPath($row['logo_path']);
    if (file_exists($full)) {
        @unlink($full);
    }
    Database::query("DELETE FROM brand_logos WHERE id = :id", ['id' => $id]);
    return true;
}

/**
 * Toggle (or explicitly set) a brand logo's active state.
 */
function setBrandLogoActive(int $id, bool $active): int {
    return Database::update('brand_logos', ['is_active' => $active ? 1 : 0], 'id = :id', ['id' => $id]);
}

/**
 * Update a single brand logo's display_order.
 */
function setBrandLogoOrder(int $id, int $order): int {
    return Database::update('brand_logos', ['display_order' => $order], 'id = :id', ['id' => $id]);
}

/**
 * Persist a full reordering in one go: $orderedIds is an array of brand_logos.id
 * in the desired display order (used by the drag-and-drop UI).
 */
function reorderBrandLogos(array $orderedIds): void {
    $order = 1;
    foreach ($orderedIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            Database::update('brand_logos', ['display_order' => $order], 'id = :id', ['id' => $id]);
            $order++;
        }
    }
}

/**
 * Replace an existing brand logo's image file in place (keeps the same
 * row/order, only swaps logo_path and deletes the old file).
 */
function replaceBrandLogoFile(int $id, array $file): array {
    $result = ['success' => false, 'error' => ''];
    $row = Database::fetchOne("SELECT * FROM brand_logos WHERE id = :id", ['id' => $id]);
    if (!$row) {
        $result['error'] = 'الشعار غير موجود';
        return $result;
    }

    $upload = saveBrandLogoUpload($file);
    if (!$upload['success']) {
        $result['error'] = $upload['error'];
        return $result;
    }

    $oldFull = uploadPath($row['logo_path']);
    if (file_exists($oldFull)) {
        @unlink($oldFull);
    }

    Database::update('brand_logos', ['logo_path' => $upload['path']], 'id = :id', ['id' => $id]);
    $result['success'] = true;
    return $result;
}

// ============================================================
// GENERIC IMAGE DELETE / REPLACE (used by the media library modal)
// ============================================================

/**
 * Whitelist of tables the media library modal is allowed to touch,
 * mapped to their image-path column and (optionally) whether the row
 * itself should be deleted or just the path cleared.
 *
 *  'delete_row' => true  : deleting the image deletes the whole row
 *                          (e.g. a product_images gallery entry — the row
 *                          only exists to hold that one image).
 *  'delete_row' => false : deleting the image just clears the path column
 *                          on the parent row (e.g. products.main_image —
 *                          the product itself must not be deleted).
 */
const MEDIA_LIBRARY_TABLE_MAP = [
    'page_images_desktop' => ['table' => 'page_images', 'column' => 'desktop_image_path', 'delete_row' => false],
    'page_images_mobile'  => ['table' => 'page_images', 'column' => 'mobile_image_path',  'delete_row' => false],
    'products'            => ['table' => 'products',       'column' => 'main_image',      'delete_row' => false],
    'product_images'      => ['table' => 'product_images',  'column' => 'image_path',      'delete_row' => true],
    'brand_logos'         => ['table' => 'brand_logos',      'column' => 'logo_path',       'delete_row' => true],
    'categories'          => ['table' => 'categories',       'column' => 'image',           'delete_row' => false],
    'portfolio'           => ['table' => 'portfolio',        'column' => 'main_image',      'delete_row' => false],
    'portfolio_images'    => ['table' => 'portfolio_images', 'column' => 'image_path',      'delete_row' => true],
];

/**
 * Resolve the map key for a (source_table, extra) pair coming from the
 * media library grid — page_images needs the device to disambiguate the
 * desktop/mobile columns, everything else maps 1:1 to its table name.
 */
function resolveMediaLibraryMapKey(string $sourceTable, array $extra = []): ?string {
    if ($sourceTable === 'page_images') {
        $device = $extra['device'] ?? 'desktop';
        return $device === 'mobile' ? 'page_images_mobile' : 'page_images_desktop';
    }
    return isset(MEDIA_LIBRARY_TABLE_MAP[$sourceTable]) ? $sourceTable : null;
}

/**
 * Delete an image both from disk and from the database, using the
 * whitelist above. Returns ['success' => bool, 'error' => string]
 */
function deleteMediaLibraryImage(string $mapKey, int $sourceId): array {
    $result = ['success' => false, 'error' => ''];

    if (!isset(MEDIA_LIBRARY_TABLE_MAP[$mapKey])) {
        $result['error'] = 'مصدر غير معروف';
        return $result;
    }
    $conf = MEDIA_LIBRARY_TABLE_MAP[$mapKey];
    $table = $conf['table'];
    $column = $conf['column'];

    $row = Database::fetchOne("SELECT * FROM `{$table}` WHERE id = :id", ['id' => $sourceId]);
    if (!$row || empty($row[$column])) {
        $result['error'] = 'الصورة غير موجودة';
        return $result;
    }

    $full = uploadPath($row[$column]);
    if (file_exists($full)) {
        @unlink($full);
    }

    if ($conf['delete_row']) {
        Database::query("DELETE FROM `{$table}` WHERE id = :id", ['id' => $sourceId]);
    } else {
        Database::update($table, [$column => null], 'id = :id', ['id' => $sourceId]);
    }

    $result['success'] = true;
    return $result;
}

/**
 * Replace an existing image file in place (same row, new file on disk),
 * using the whitelist above. Returns ['success' => bool, 'error' => string]
 */
function replaceMediaLibraryImage(string $mapKey, int $sourceId, array $file): array {
    $result = ['success' => false, 'error' => ''];

    if (!isset(MEDIA_LIBRARY_TABLE_MAP[$mapKey])) {
        $result['error'] = 'مصدر غير معروف';
        return $result;
    }
    $conf = MEDIA_LIBRARY_TABLE_MAP[$mapKey];
    $table = $conf['table'];
    $column = $conf['column'];

    $row = Database::fetchOne("SELECT * FROM `{$table}` WHERE id = :id", ['id' => $sourceId]);
    if (!$row) {
        $result['error'] = 'الصورة غير موجودة';
        return $result;
    }

    // Brand logos have their own dedicated uploader (keeps display_order/is_active untouched)
    if ($table === 'brand_logos') {
        return replaceBrandLogoFile($sourceId, $file);
    }

    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'حدث خطأ أثناء رفع الملف';
        return $result;
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $result['error'] = 'حجم الملف يتجاوز الحد الأقصى المسموح به';
        return $result;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        $result['error'] = 'نوع الملف غير مسموح به';
        return $result;
    }

    $dirGuess = $row[$column] ? trim(dirname($row[$column]), '.') : 'media';
    $upload = uploadFile($file, trim($dirGuess, '/') ?: 'media');
    if (!$upload['success']) {
        $result['error'] = $upload['error'];
        return $result;
    }

    if (!empty($row[$column])) {
        $oldFull = uploadPath($row[$column]);
        if (file_exists($oldFull)) {
            @unlink($oldFull);
        }
    }

    Database::update($table, [$column => $upload['path']], 'id = :id', ['id' => $sourceId]);
    $result['success'] = true;
    return $result;
}
