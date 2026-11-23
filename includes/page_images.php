<?php
/**
 * Page Images Module
 * -------------------------------------------------------------
 * Manages banner/hero images per page section ("page_images" table)
 * with automatic device fallback (mobile -> desktop -> placeholder).
 *
 * Public API used across the site templates:
 *   get_page_image(string $sectionKey, string $device = 'desktop'): string
 *
 * Public API used by the admin panel (admin/page_images.php):
 *   getAllPageImageSections(): array
 *   savePageImageUpload(array $file, int $targetWidth, int $targetHeight): array
 *
 * Notes:
 *  - Uses the existing UPLOADS_PATH / UPLOADS_URL / uploadUrl() / uploadPath()
 *    helpers already defined in includes/functions.php, so it is fully
 *    consistent with how products/portfolio images are stored.
 *  - Uses GD (already the only image library available in this project;
 *    see generateWebP()/generateThumbnail() in functions.php).
 *  - Placeholder images are drawn as a simple wordless brand icon rather
 *    than rendered Arabic text. GD's imagestring()/imagettftext() cannot
 *    correctly shape/reorder Arabic glyphs without a dedicated shaping
 *    library, so any Arabic caption baked into the image would render as
 *    broken, disconnected letters. A clean icon avoids that problem
 *    entirely while still clearly signalling "no image yet".
 */

const PAGE_IMAGE_MAX_SIZE = 5 * 1024 * 1024; // 5MB, per spec (site-wide default is 10MB)
const PAGE_IMAGE_UPLOAD_DIR = 'page_images';
const PAGE_IMAGE_PLACEHOLDER_DIR = 'placeholders';

const PAGE_IMAGE_ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// ============================================================
// READ / FALLBACK LOGIC
// ============================================================

/**
 * Fetch a single page_images row by section key (memoized per request).
 */
function getPageImageRow(string $sectionKey): ?array {
    static $cache = [];
    if (!array_key_exists($sectionKey, $cache)) {
        $cache[$sectionKey] = Database::fetchOne(
            "SELECT * FROM page_images WHERE section_key = :k",
            ['k' => $sectionKey]
        ) ?: null;
    }
    return $cache[$sectionKey];
}

/**
 * Fetch all page_images rows, ordered for admin display.
 */
function getAllPageImageSections(): array {
    return Database::fetchAll("SELECT * FROM page_images ORDER BY id ASC");
}

/**
 * The single entry point used across all site templates.
 *
 * Resolution order:
 *   1. Device-specific uploaded image (desktop_image_path / mobile_image_path).
 *   2. If mobile was requested but has no image, fall back to the desktop image.
 *   3. If nothing was ever uploaded for the section, generate (and cache) a
 *      placeholder sized exactly for the requested device.
 */
function get_page_image(string $sectionKey, string $device = 'desktop'): string {
    $device = $device === 'mobile' ? 'mobile' : 'desktop';
    $row = getPageImageRow($sectionKey);

    if (!$row) {
        // Unknown section key — safe generic fallback, never a broken <img>.
        return uploadUrl(getOrCreatePageImagePlaceholder($sectionKey, $device, 1920, 600));
    }

    // Only use paths whose file really exists on disk (a DB row can point to
    // a file that was never uploaded or was lost when moving hosts).
    $exists = static function (?string $rel): bool {
        return !empty($rel) && is_file(uploadPath($rel));
    };
    $own   = $device === 'mobile' ? ($row['mobile_image_path'] ?? '') : ($row['desktop_image_path'] ?? '');
    $other = $device === 'mobile' ? ($row['desktop_image_path'] ?? '') : ($row['mobile_image_path'] ?? '');

    // 1) Device-specific image
    if ($exists($own)) {
        return uploadUrl($own);
    }

    // 2) Missing -> reuse the other device's image automatically
    if ($exists($other)) {
        return uploadUrl($other);
    }

    // 3) Nothing uploaded at all -> generate a placeholder at the exact recommended size
    $width  = (int) ($device === 'mobile' ? $row['mobile_width'] : $row['desktop_width']);
    $height = (int) ($device === 'mobile' ? $row['mobile_height'] : $row['desktop_height']);
    return uploadUrl(getOrCreatePageImagePlaceholder($sectionKey, $device, $width, $height));
}

/**
 * Generate a branded placeholder image once and cache it on disk so it is
 * not regenerated on every page load. Returns the path relative to UPLOADS_PATH.
 */
function getOrCreatePageImagePlaceholder(string $sectionKey, string $device, int $width, int $height): string {
    $width  = max(1, $width);
    $height = max(1, $height);

    $safeKey  = preg_replace('/[^a-z0-9_\-]/i', '', $sectionKey) ?: 'section';
    $filename = "{$safeKey}_{$device}_{$width}x{$height}.png";
    $relPath  = PAGE_IMAGE_PLACEHOLDER_DIR . '/' . $filename;
    $fullPath = uploadPath($relPath);

    if (file_exists($fullPath)) {
        return $relPath;
    }

    $dir = uploadPath(PAGE_IMAGE_PLACEHOLDER_DIR);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $img = imagecreatetruecolor($width, $height);

    // Brand colors (matches --color-primary / dark industrial blue used site-wide)
    $bgDark   = imagecolorallocate($img, 12, 34, 54);
    $bgLight  = imagecolorallocate($img, 20, 55, 84);
    $accent   = imagecolorallocate($img, 2, 129, 222);   // --color-primary
    $accentLo = imagecolorallocate($img, 2, 129, 222);
    imagealphablending($img, true);

    // Vertical gradient background
    for ($y = 0; $y < $height; $y++) {
        $ratio = $height > 1 ? $y / ($height - 1) : 0;
        $r = (int) (12 + (20 - 12) * $ratio);
        $g = (int) (34 + (55 - 34) * $ratio);
        $b = (int) (54 + (84 - 54) * $ratio);
        $rowColor = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $rowColor);
    }

    // Subtle diagonal accent stripe (echoes the circuit-line brand pattern)
    $accentSoft = imagecolorallocatealpha($img, 2, 129, 222, 100);
    imagefilledpolygon($img, [
        0, $height,
        (int) ($width * 0.4), $height,
        (int) ($width * 0.6), 0,
        0, 0,
    ], $accentSoft);

    // Centered wordless "image" icon: rounded frame + mountain + sun
    $iconSize = (int) min($width, $height) * 0.32;
    $iconSize = max(40, min($iconSize, 160));
    $cx = (int) ($width / 2);
    $cy = (int) ($height / 2);
    $left   = (int) ($cx - $iconSize / 2);
    $top    = (int) ($cy - $iconSize / 2.6);
    $right  = (int) ($cx + $iconSize / 2);
    $bottom = (int) ($cy + $iconSize / 2.6);

    $white = imagecolorallocatealpha($img, 255, 255, 255, 60);
    imagesetthickness($img, max(2, (int) ($iconSize / 20)));
    imagerectangle($img, $left, $top, $right, $bottom, $white);

    // sun
    $sunR = (int) ($iconSize * 0.11);
    imagefilledellipse($img, $left + (int) ($iconSize * 0.25), $top + (int) ($iconSize * 0.28), $sunR, $sunR, $white);

    // mountain
    imagefilledpolygon($img, [
        $left + 4, $bottom - 4,
        $left + (int) ($iconSize * 0.45), $top + (int) ($iconSize * 0.35),
        $left + (int) ($iconSize * 0.68), $bottom - (int) ($iconSize * 0.25),
        $left + (int) ($iconSize * 0.85), $top + (int) ($iconSize * 0.55),
        $right - 4, $bottom - 4,
    ], $white);

    imagepng($img, $fullPath, 6);
    imagedestroy($img);

    return $relPath;
}

// ============================================================
// ADMIN UPLOAD HANDLING
// ============================================================

/**
 * Validate + save an uploaded page image, then crop/resize it to exactly
 * match the recommended dimensions for its section, and return the path
 * relative to UPLOADS_PATH (ready to store in desktop_image_path /
 * mobile_image_path).
 *
 * Returns ['success' => bool, 'path' => string, 'error' => string]
 */
function savePageImageUpload(array $file, int $targetWidth, int $targetHeight): array {
    $result = ['success' => false, 'path' => '', 'error' => ''];

    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'حدث خطأ أثناء رفع الملف';
        return $result;
    }

    if ($file['size'] > PAGE_IMAGE_MAX_SIZE) {
        $result['error'] = 'حجم الملف يتجاوز الحد الأقصى المسموح به (5 ميجابايت)';
        return $result;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset(PAGE_IMAGE_ALLOWED_MIMES[$mime])) {
        $result['error'] = 'نوع الملف غير مسموح به (المسموح: JPG, PNG, WEBP فقط)';
        return $result;
    }

    $ext = PAGE_IMAGE_ALLOWED_MIMES[$mime];

    // Fully random, sanitized filename — original filename is discarded entirely.
    $filename = 'pg_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;

    $uploadDir = uploadPath(PAGE_IMAGE_UPLOAD_DIR);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $result['error'] = 'فشل حفظ الملف على الخادم';
        return $result;
    }

    // $targetWidth/$targetHeight are only an upper bound now (the section's
    // recommended size) — the image is never cropped or stretched to match
    // them. It keeps its own original aspect ratio; it is only downscaled if
    // it's larger than the recommended box, so the frontend container
    // (width:100%, height:auto) always fits it exactly as uploaded.
    if (!constrainImageMaxDimensions($destPath, $mime, $targetWidth, $targetHeight)) {
        @unlink($destPath);
        $result['error'] = 'تعذرت معالجة الصورة المرفوعة';
        return $result;
    }

    $result['success'] = true;
    $result['path'] = PAGE_IMAGE_UPLOAD_DIR . '/' . $filename;
    return $result;
}

/**
 * Crop (center, "cover"-style) and resize an image file in place so it
 * exactly matches $targetWidth x $targetHeight, regardless of the
 * dimensions/aspect-ratio it was uploaded with.
 *
 * NOTE: kept for other callers (e.g. admin/categories.php category
 * thumbnails) that intentionally want a fixed-size crop. Page banner
 * uploads use constrainImageMaxDimensions() below instead, which never
 * crops.
 */
function fitImageToExactDimensions(string $path, string $mime, int $targetWidth, int $targetHeight): bool {
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($path);
            break;
        case 'image/webp':
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            break;
        default:
            $src = false;
    }

    if (!$src) {
        return false;
    }

    $origWidth  = imagesx($src);
    $origHeight = imagesy($src);

    if ($origWidth === $targetWidth && $origHeight === $targetHeight) {
        imagedestroy($src);
        return true; // already the exact recommended size
    }

    $targetRatio = $targetWidth / $targetHeight;
    $srcRatio    = $origWidth / $origHeight;

    if ($srcRatio > $targetRatio) {
        // source is relatively wider than target -> crop left/right
        $cropHeight = $origHeight;
        $cropWidth  = (int) round($origHeight * $targetRatio);
        $srcX = (int) (($origWidth - $cropWidth) / 2);
        $srcY = 0;
    } else {
        // source is relatively taller than target -> crop top/bottom
        $cropWidth  = $origWidth;
        $cropHeight = (int) round($origWidth / $targetRatio);
        $srcX = 0;
        $srcY = (int) (($origHeight - $cropHeight) / 2);
    }

    $dst = imagecreatetruecolor($targetWidth, $targetHeight);

    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    imagecopyresampled(
        $dst, $src,
        0, 0, $srcX, $srcY,
        $targetWidth, $targetHeight,
        $cropWidth, $cropHeight
    );

    $saved = false;
    switch ($mime) {
        case 'image/jpeg':
            $saved = imagejpeg($dst, $path, 88);
            break;
        case 'image/png':
            $saved = imagepng($dst, $path, 8);
            break;
        case 'image/webp':
            $saved = function_exists('imagewebp') ? imagewebp($dst, $path, 85) : false;
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $saved;
}

/**
 * Downscale an image file in place ONLY if it exceeds the given max
 * width/height, preserving its original aspect ratio exactly — never
 * crops and never stretches/distorts it. Images smaller than or equal
 * to the max box are left completely untouched. This guarantees the
 * banner container on the frontend (which sizes itself to the image
 * via width:100%, height:auto) always matches the uploaded image
 * pixel-for-ratio, no matter what size or shape it was uploaded in.
 */
function constrainImageMaxDimensions(string $path, string $mime, int $maxWidth, int $maxHeight): bool {
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($path);
            break;
        case 'image/webp':
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            break;
        default:
            $src = false;
    }

    if (!$src) {
        return false;
    }

    $origWidth  = imagesx($src);
    $origHeight = imagesy($src);

    // Already within bounds — keep the original file exactly as uploaded.
    if ($origWidth <= $maxWidth && $origHeight <= $maxHeight) {
        imagedestroy($src);
        return true;
    }

    // Scale down to fit inside the max box, keeping the original ratio.
    $scale     = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    $newWidth  = max(1, (int) round($origWidth * $scale));
    $newHeight = max(1, (int) round($origHeight * $scale));

    $dst = imagecreatetruecolor($newWidth, $newHeight);

    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled(
        $dst, $src,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $origWidth, $origHeight
    );

    $saved = false;
    switch ($mime) {
        case 'image/jpeg':
            $saved = imagejpeg($dst, $path, 88);
            break;
        case 'image/png':
            $saved = imagepng($dst, $path, 8);
            break;
        case 'image/webp':
            $saved = function_exists('imagewebp') ? imagewebp($dst, $path, 85) : false;
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $saved;
}
