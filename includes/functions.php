<?php
/**
 * Core Helper Functions
 * Secure, reusable utility functions
 */

// ============================================================
// SECURITY FUNCTIONS
// ============================================================

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_TIME] = time();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken(string $token): bool {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_TIME])) {
        return false;
    }
    
    // Check token expiration
    if (time() - $_SESSION[CSRF_TOKEN_TIME] > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_TIME]);
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token field (HTML input)
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitizeEmail(string $email): string {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Escape output
 */
function e(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Generate random token
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Rate limiting check
 */
function checkRateLimit(string $identifier): bool {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['time'];
    
    if ($elapsed > RATE_LIMIT_WINDOW) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    if ($data['count'] >= RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Send security headers
 */
function sendSecurityHeaders(): void {
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: blob: https:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://www.googletagmanager.com; media-src 'self'; object-src 'none'; frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self' https://wa.me;");
    
    // Security headers
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    // HSTS (uncomment for HTTPS)
    // header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

/**
 * Get client IP
 */
function getClientIP(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ============================================================
// STRING & URL FUNCTIONS
// ============================================================

/**
 * Create URL slug
 */
function createSlug(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

/**
 * Create Arabic-friendly slug
 */
function createArabicSlug(string $text): string {
    // Remove special characters but keep Arabic
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', '-', $text);
    $text = preg_replace('/\s+/', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('/-+/', '-', $text);
    return mb_strtolower($text, 'UTF-8');
}

/**
 * Generate clean URL
 */
function url(string $path = ''): string {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Admin URL
 */
function adminUrl(string $path = ''): string {
    return rtrim(ADMIN_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Asset URL
 */
function asset(string $path): string {
    $path = ltrim($path, '/');
    $url  = rtrim(ASSETS_URL, '/') . '/' . $path;
    // Cache-busting for CSS/JS so visitors always get the latest design
    // after an update (the file's modification time is the version).
    if (preg_match('/\.(css|js)$/i', $path)) {
        $file = BASE_PATH . '/assets/' . $path;
        if (is_file($file)) $url .= '?v=' . filemtime($file);
    }
    return $url;
}

/**
 * Upload URL
 */
function uploadUrl(string $path): string {
    return rtrim(UPLOADS_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Upload path
 */
function uploadPath(string $path): string {
    return rtrim(UPLOADS_PATH, '/') . '/' . ltrim($path, '/');
}

/**
 * Current URL
 */
function currentUrl(): string {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
           '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// ============================================================
// PAGINATION
// ============================================================

/**
 * Generate pagination
 */
function paginate(int $total, int $page = 1, int $perPage = null): array {
    $perPage = $perPage ?: ITEMS_PER_PAGE;
    $totalPages = (int) ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages,
        'previous_page' => $page - 1,
        'next_page' => $page + 1,
        'start' => $offset + 1,
        'end' => min($offset + $perPage, $total)
    ];
}

/**
 * Render pagination HTML
 */
function renderPagination(array $pagination, string $baseUrl): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $lang = getCurrentLang();
    $html = '<nav class="pagination" aria-label="' . ($lang === 'ar' ? 'التنقل بين الصفحات' : 'Page navigation') . '">';
    $html .= '<div class="pagination-info">';
    $html .= sprintf(
        $lang === 'ar' ? 'عرض %d - %d من %d' : 'Showing %d - %d of %d',
        $pagination['start'],
        $pagination['end'],
        $pagination['total']
    );
    $html .= '</div>';
    $html .= '<div class="pagination-links">';
    
    // Previous
    if ($pagination['has_previous']) {
        $prevUrl = $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . 'page=' . $pagination['previous_page'];
        $html .= '<a href="' . e($prevUrl) . '" class="pagination-btn pagination-prev">';
        $html .= $lang === 'ar' ? 'السابق' : 'Previous';
        $html .= '</a>';
    } else {
        $html .= '<span class="pagination-btn pagination-prev disabled">';
        $html .= $lang === 'ar' ? 'السابق' : 'Previous';
        $html .= '</span>';
    }
    
    // Page numbers
    $startPage = max(1, $pagination['current_page'] - 2);
    $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($startPage > 1) {
        $html .= '<a href="' . e($baseUrl) . '" class="pagination-btn">1</a>';
        if ($startPage > 2) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i === $pagination['current_page']) {
            $html .= '<span class="pagination-btn active">' . $i . '</span>';
        } else {
            $pageUrl = $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . 'page=' . $i;
            $html .= '<a href="' . e($pageUrl) . '" class="pagination-btn">' . $i . '</a>';
        }
    }
    
    if ($endPage < $pagination['total_pages']) {
        if ($endPage < $pagination['total_pages'] - 1) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }
        $lastUrl = $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . 'page=' . $pagination['total_pages'];
        $html .= '<a href="' . e($lastUrl) . '" class="pagination-btn">' . $pagination['total_pages'] . '</a>';
    }
    
    // Next
    if ($pagination['has_next']) {
        $nextUrl = $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . 'page=' . $pagination['next_page'];
        $html .= '<a href="' . e($nextUrl) . '" class="pagination-btn pagination-next">';
        $html .= $lang === 'ar' ? 'التالي' : 'Next';
        $html .= '</a>';
    } else {
        $html .= '<span class="pagination-btn pagination-next disabled">';
        $html .= $lang === 'ar' ? 'التالي' : 'Next';
        $html .= '</span>';
    }
    
    $html .= '</div></nav>';
    return $html;
}

// ============================================================
// FILE & IMAGE FUNCTIONS
// ============================================================

/**
 * Upload file
 */
function uploadFile(array $file, string $directory = '', array $allowedTypes = []): array {
    $result = ['success' => false, 'path' => '', 'error' => '', 'filename' => ''];
    
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $result['error'] = 'No file uploaded';
        return $result;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Upload error: ' . $file['error'];
        return $result;
    }
    
    // Check size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $result['error'] = 'File too large. Max: ' . formatBytes(MAX_UPLOAD_SIZE);
        return $result;
    }
    
    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowed = $allowedTypes ?: ALLOWED_IMAGE_TYPES;
    if (!in_array($mimeType, $allowed)) {
        $result['error'] = 'Invalid file type';
        return $result;
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    
    $uploadDir = UPLOADS_PATH . '/' . trim($directory, '/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filepath = $uploadDir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $result['success'] = true;
        $result['path'] = trim($directory, '/') . '/' . $filename;
        $result['filename'] = $filename;
        
        // Generate WebP if image
        if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
            generateWebP($filepath);
            generateThumbnail($filepath, $directory);
        }
    } else {
        $result['error'] = 'Failed to move uploaded file';
    }
    
    return $result;
}

/**
 * Generate WebP version
 */
function generateWebP(string $sourcePath): bool {
    $info = getimagesize($sourcePath);
    if (!$info) return false;
    
    $webpPath = preg_replace('/\.[^.]+$/', '.webp', $sourcePath);
    
    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        default:
            return false;
    }
    
    if ($image) {
        imagewebp($image, $webpPath, 80);
        imagedestroy($image);
        return true;
    }
    
    return false;
}

/**
 * Generate thumbnail
 */
function generateThumbnail(string $sourcePath, string $directory, int $width = 300, int $height = 300): bool {
    $info = getimagesize($sourcePath);
    if (!$info) return false;
    
    $filename = basename($sourcePath);
    $thumbDir = UPLOADS_PATH . '/' . trim($directory, '/') . '/thumbs';
    
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }
    
    $thumbPath = $thumbDir . '/' . $filename;
    
    switch ($info['mime']) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        default:
            return false;
    }
    
    $origWidth = imagesx($source);
    $origHeight = imagesy($source);
    
    // Calculate aspect ratio
    $ratio = min($width / $origWidth, $height / $origHeight);
    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);
    
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency
    if ($info['mime'] === 'image/png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    
    if ($info['mime'] === 'image/png') {
        imagepng($thumb, $thumbPath, 8);
    } else {
        imagejpeg($thumb, $thumbPath, 85);
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return true;
}

/**
 * Format bytes
 */
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}

// ============================================================
// WHATSAPP FUNCTIONS
// ============================================================

/**
 * Generate WhatsApp link
 */
function whatsappLink(string $phone, string $message = '', bool $isGroup = false): string {
    $phone = $isGroup ? preg_replace('/[^0-9A-Za-z]/', '', $phone) : normalizeYemenPhone($phone);
    $base = $isGroup ? 'https://chat.whatsapp.com/' : 'https://wa.me/';
    
    if ($isGroup) {
        return $base . $phone;
    }
    
    $url = $base . $phone;
    if (!empty($message)) {
        $url .= '?text=' . urlencode($message);
    }
    return $url;
}

/**
 * Normalise a Yemeni mobile number to international format without "+".
 * "781067910" / "0781067910" / "+967 781 067 910" → "967781067910".
 * wa.me links do NOT work without the country code.
 */
function normalizeYemenPhone(string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') return '';
    if (str_starts_with($digits, '00')) $digits = substr($digits, 2);
    if (str_starts_with($digits, '967')) return $digits;
    $digits = ltrim($digits, '0');
    return strlen($digits) === 9 ? '967' . $digits : $digits;
}

/**
 * tel: link value (+967…)
 */
function telLink(string $phone): string {
    $n = normalizeYemenPhone($phone);
    return $n === '' ? '' : 'tel:+' . $n;
}

/**
 * Human-readable phone: +967 781 067 910
 */
function formatPhoneDisplay(string $phone): string {
    $n = normalizeYemenPhone($phone);
    if (strlen($n) === 12 && str_starts_with($n, '967')) {
        return '+967 ' . substr($n, 3, 3) . ' ' . substr($n, 6, 3) . ' ' . substr($n, 9, 3);
    }
    return $phone;
}

/**
 * Generate pre-filled WhatsApp message
 */
function generateWhatsAppMessage(string $type, array $data = []): string {
    $lang = getCurrentLang();
    
    switch ($type) {
        case 'contact':
            return $lang === 'ar' 
                ? "السلام عليكم، أود الاستفسار عن خدماتكم.\nالاسم: " . ($data['name'] ?? '') . "\nالموضوع: " . ($data['subject'] ?? '')
                : "Hello, I would like to inquire about your services.\nName: " . ($data['name'] ?? '') . "\nSubject: " . ($data['subject'] ?? '');
                
        case 'quote':
            return $lang === 'ar'
                ? "السلام عليكم، أود طلب عرض سعر.\nالاسم: " . ($data['name'] ?? '') . "\nالمنتج/الخدمة: " . ($data['product'] ?? '') . "\nالكمية: " . ($data['quantity'] ?? '')
                : "Hello, I would like to request a quote.\nName: " . ($data['name'] ?? '') . "\nProduct/Service: " . ($data['product'] ?? '') . "\nQuantity: " . ($data['quantity'] ?? '');
                
        case 'product_inquiry':
            return $lang === 'ar'
                ? "السلام عليكم، أود الاستفسار عن منتج.\nالمنتج: " . ($data['product'] ?? '') . "\nالاسم: " . ($data['name'] ?? '')
                : "Hello, I would like to inquire about a product.\nProduct: " . ($data['product'] ?? '') . "\nName: " . ($data['name'] ?? '');
                
        default:
            return $lang === 'ar' ? 'السلام عليكم، أود الاستفسار.' : 'Hello, I would like to inquire.';
    }
}

// ============================================================
// DATE & TIME FUNCTIONS
// ============================================================

/**
 * Format date
 */
function formatDate(?string $date, string $format = null): string {
    if (!$date) {
        return '-';
    }
    $lang = getCurrentLang();
    $format = $format ?: ($lang === 'ar' ? 'd/m/Y' : 'Y-m-d');
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime(string $datetime): string {
    $lang = getCurrentLang();
    return date($lang === 'ar' ? 'd/m/Y H:i' : 'Y-m-d H:i', strtotime($datetime));
}

/**
 * Time ago
 */
function timeAgo(string $datetime): string {
    $lang = getCurrentLang();
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $lang === 'ar' ? 'منذ لحظات' : 'Just now';
    }
    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $lang === 'ar' ? "منذ {$mins} دقيقة" : "{$mins} minutes ago";
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $lang === 'ar' ? "منذ {$hours} ساعة" : "{$hours} hours ago";
    }
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $lang === 'ar' ? "منذ {$days} يوم" : "{$days} days ago";
    }
    return formatDate($datetime);
}

// ============================================================
// SEO FUNCTIONS
// ============================================================

/**
 * Get SEO settings for page
 */
function getSeoSettings(string $pageKey): array {
    $lang = getCurrentLang();
    $seo = Database::fetchOne(
        "SELECT * FROM seo_settings WHERE page_key = :key",
        ['key' => $pageKey]
    );
    
    if (!$seo) {
        return [
            'title' => getSetting('default_meta_title_' . $lang, ''),
            'description' => getSetting('default_meta_description_' . $lang, ''),
            'og_title' => '',
            'og_description' => '',
            'og_image' => getSetting('og_image', ''),
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'canonical_url' => currentUrl(),
            'robots' => 'index,follow'
        ];
    }
    
    return [
        'title' => $lang === 'ar' ? $seo['meta_title_ar'] : $seo['meta_title_en'],
        'description' => $lang === 'ar' ? $seo['meta_description_ar'] : $seo['meta_description_en'],
        'og_title' => $lang === 'ar' ? $seo['og_title_ar'] : $seo['og_title_en'],
        'og_description' => $lang === 'ar' ? $seo['og_description_ar'] : $seo['og_description_en'],
        'og_image' => $seo['og_image'] ?: getSetting('og_image', ''),
        'og_type' => $seo['og_type'] ?: 'website',
        'twitter_card' => $seo['twitter_card'] ?: 'summary_large_image',
        'canonical_url' => $seo['canonical_url'] ?: currentUrl(),
        'robots' => $seo['robots_meta'] ?: 'index,follow',
        'schema_markup' => $seo['schema_markup'] ?? ''
    ];
}

/**
 * Generate breadcrumb schema
 */
function generateBreadcrumbSchema(array $items): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => []
    ];
    
    foreach ($items as $index => $item) {
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url'] ?? null
        ];
    }
    
    return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Generate local business schema
 */
function generateLocalBusinessSchema(): string {
    $lang = getCurrentLang();
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ElectronicsStore',
        'name' => $lang === 'ar' ? getSetting('site_name_ar') : getSetting('site_name_en'),
        'description' => $lang === 'ar' ? getSetting('site_tagline_ar') : getSetting('site_tagline_en'),
        'url' => SITE_URL,
        'telephone' => '+967' . getSetting('whatsapp_primary'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $lang === 'ar' ? getSetting('company_address_ar') : getSetting('company_address_en'),
            'addressLocality' => 'Taiz',
            'addressCountry' => 'YE'
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '13.6045',
            'longitude' => '44.0044'
        ],
        'openingHoursSpecification' => [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
                'opens' => '08:00',
                'closes' => '22:00'
            ],
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Friday'],
                'opens' => '14:00',
                'closes' => '22:00'
            ]
        ],
        'sameAs' => array_filter([
            getSetting('facebook_url'),
            getSetting('twitter_url'),
            getSetting('instagram_url'),
            getSetting('linkedin_url')
        ])
    ];
    
    return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// ============================================================
// SITE SETTINGS
// ============================================================

/**
 * Get site setting
 */
function getSetting(string $key, string $default = ''): string {
    static $settings = null;
    
    if ($settings === null) {
        try {
            $rows = Database::fetchAll("SELECT setting_key, setting_value FROM site_settings");
            $settings = array_column($rows, 'setting_value', 'setting_key');
        } catch (Exception $e) {
            return $default;
        }
    }
    
    return $settings[$key] ?? $default;
}

// ============================================================
// SITE LOGO MANAGEMENT
// ============================================================

/**
 * Central logo resolver.
 *
 * Resolution order for a given $location ('header', 'mobile_menu', 'footer', ...):
 *   1. The dedicated field for this exact location (+ mode, for 'header').
 *   2. The primary/general logo (logo_header_light, or logo_header_dark when
 *      $mode === 'dark' and a dark variant exists) as a fallback so a place
 *      never renders empty just because its own field was never filled in.
 *   3. The legacy single-field `site_logo` setting, for sites upgraded from
 *      the old single-logo system.
 *
 * Returns the stored filename (relative to assets/images/) or null when
 * nothing at all has ever been uploaded — callers should render the
 * built-in inline SVG mark in that case so the logo spot is never blank.
 */
function get_site_logo(string $location, string $mode = 'light'): ?string {
    $mode = $mode === 'dark' ? 'dark' : 'light';

    // 1) Dedicated field for this location.
    $specificKey = $location === 'header'
        ? 'logo_header_' . $mode
        : 'logo_' . $location;

    $value = getSetting($specificKey);
    if ($value !== '') {
        return $value;
    }

    // 2) Primary/general logo fallback (the header logo acts as the site's
    //    main mark). Try the mode-matching header logo first...
    $primaryKey = 'logo_header_' . $mode;
    if ($specificKey !== $primaryKey) {
        $value = getSetting($primaryKey);
        if ($value !== '') {
            return $value;
        }
    }
    // ...then fall back to the light header logo if we were looking for dark
    // and no dark variant exists anywhere.
    if ($mode === 'dark') {
        $value = getSetting('logo_header_light');
        if ($value !== '') {
            return $value;
        }
    }

    // 3) Legacy single-field logo, kept for backward compatibility.
    $legacy = getSetting('site_logo');
    if ($legacy !== '' && $legacy !== 'logo.png') {
        return $legacy;
    }

    return null;
}

/**
 * Same as get_site_logo() but returns a ready-to-use asset URL (or null).
 */
function get_site_logo_url(string $location, string $mode = 'light'): ?string {
    $file = get_site_logo($location, $mode);
    return $file ? asset('images/' . $file) : null;
}

/**
 * Build the small JSON config block consumed by main.js to swap logos
 * instantly when the visitor toggles light/dark mode, without a page reload.
 */
function getSiteAssetsConfig(): array {
    return [
        'logos' => [
            'header' => [
                'light' => get_site_logo_url('header', 'light'),
                'dark'  => get_site_logo_url('header', 'dark'),
            ],
            'mobile_menu' => [
                'light' => get_site_logo_url('mobile_menu', 'light'),
                'dark'  => get_site_logo_url('mobile_menu', 'dark'),
            ],
            'footer' => [
                'light' => get_site_logo_url('footer', 'light'),
                'dark'  => get_site_logo_url('footer', 'dark'),
            ],
        ],
    ];
}

/**
 * Resolve any Google Maps link the admin pastes (a short share link like
 * https://maps.app.goo.gl/xxxx, a full https://www.google.com/maps/place/...
 * link, or a plain q=lat,lng link) into a safe, embeddable iframe src.
 *
 * Short links are followed server-side (they redirect to the full URL that
 * contains the real coordinates), then the coordinates - or failing that the
 * place name - are extracted and rebuilt as a "/maps?q=...&output=embed"
 * URL. This is the embed format that Google does not block with the
 * "هذا المحتوى محظور" content-blocked message (unlike the Place-ID based
 * "/maps/embed?pb=..." format).
 *
 * Returns null if no coordinates/place could be resolved from the link, so
 * the caller can fall back to a previously known-good embed URL.
 */
function resolveGoogleMapsEmbedUrl(string $url): ?string {
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $finalUrl = $url;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 6,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SiteMapsResolver/1.0)',
        ]);
        curl_exec($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlError     = curl_errno($ch);
        curl_close($ch);
        if (!$curlError && $effectiveUrl) {
            $finalUrl = $effectiveUrl;
        }
    }

    // 1) "/@lat,lng,zoom" — the coordinates Google Maps embeds in the URL
    //    of every place/share link once resolved.
    if (preg_match('/@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/', $finalUrl, $m)) {
        return 'https://www.google.com/maps?q=' . $m[1] . ',' . $m[2] . '&hl=ar&z=15&output=embed';
    }

    // 2) "?q=lat,lng" or "&q=lat,lng"
    if (preg_match('/[?&]q=(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/', $finalUrl, $m)) {
        return 'https://www.google.com/maps?q=' . $m[1] . ',' . $m[2] . '&hl=ar&z=15&output=embed';
    }

    // 3) "/place/<name>/" — fall back to the place name as a text query.
    if (preg_match('#/place/([^/@]+)#', $finalUrl, $m)) {
        $place = str_replace('+', ' ', urldecode($m[1]));
        if ($place !== '') {
            return 'https://www.google.com/maps?q=' . rawurlencode($place) . '&hl=ar&z=15&output=embed';
        }
    }

    return null;
}

// ============================================================
// SITE FONT MANAGEMENT
// ============================================================

/**
 * The 6 selectable Arabic Google Fonts. All verified as free, currently
 * published families on Google Fonts (no substitutions were needed).
 */
function getSiteFontCatalog(): array {
    return [
        'Cairo' => [
            'label_ar' => 'Cairo (القاهرة)',
            'google'   => 'Cairo:wght@400;500;600;700;800;900',
        ],
        'Tajawal' => [
            'label_ar' => 'Tajawal (تجوال)',
            'google'   => 'Tajawal:wght@400;500;700;800;900',
        ],
        'IBM Plex Sans Arabic' => [
            'label_ar' => 'IBM Plex Sans Arabic',
            'google'   => 'IBM+Plex+Sans+Arabic:wght@400;500;600;700',
        ],
        'Almarai' => [
            'label_ar' => 'Almarai (المراعي)',
            'google'   => 'Almarai:wght@400;700;800',
        ],
        'Noto Kufi Arabic' => [
            'label_ar' => 'Noto Kufi Arabic (نوتو كوفي)',
            'google'   => 'Noto+Kufi+Arabic:wght@400;500;600;700;800',
        ],
        'El Messiri' => [
            'label_ar' => 'El Messiri (المسيري)',
            'google'   => 'El+Messiri:wght@400;500;600;700',
        ],
    ];
}

/**
 * Build the Google Fonts CSS2 URL for the currently active site font.
 * Falls back to Tajawal (the site's original default) for unknown keys.
 * Always includes display=swap to avoid invisible-text flashes while the
 * webfont downloads, and keeps Inter loaded for Latin/numeral text.
 */
function getSiteFontGoogleUrl(?string $fontKey = null): string {
    $catalog = getSiteFontCatalog();
    $fontKey = $fontKey ?? getSetting('site_font', 'Tajawal');

    if (!isset($catalog[$fontKey])) {
        $fontKey = 'Tajawal';
    }

    return 'https://fonts.googleapis.com/css2?family=' . $catalog[$fontKey]['google']
        . '&family=Inter:wght@400;500;600;700;800&display=swap';
}

// ============================================================
// NOTIFICATION / ALERT
// ============================================================

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get flash messages
 */
function getFlash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages
 */
function renderFlashMessages(): string {
    $messages = getFlash();
    if (empty($messages)) return '';
    
    $html = '<div class="flash-messages">';
    foreach ($messages as $msg) {
        $html .= '<div class="flash-message flash-' . e($msg['type']) . '" role="alert">';
        $html .= '<span class="flash-text">' . e($msg['message']) . '</span>';
        $html .= '<button class="flash-close" aria-label="Close">&times;</button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

// ============================================================
// INITIALIZATION
// ============================================================

/**
 * Initialize session securely
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        ini_set('session.use_strict_mode', '1');
        
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Initialize application
 */
function initApp(): void {
    sendSecurityHeaders();
    initSession();
    
    // Initialize i18n
    initI18n();
}
