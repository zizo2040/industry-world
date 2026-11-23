<?php
/**
 * Admin - File Library AJAX API
 * Handles delete / replace / reorder / toggle-active operations issued
 * from the media library grid and modal via fetch(). Every action
 * requires a valid session (requireAuth) and a valid CSRF token.
 * Always responds with JSON: { success: bool, ...payload } or { success: false, error }
 */
require_once __DIR__ . '/../config.php';
initApp();
requireAuth();

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrfToken($token)) {
    jsonResponse(['success' => false, 'error' => 'انتهت صلاحية الجلسة، أعد تحميل الصفحة'], 419);
}

$action = $_POST['action'] ?? '';
$lang = getCurrentLang();

switch ($action) {

    // ---- Delete any classified image (media library modal) ----
    case 'delete_image': {
        $mapKey = $_POST['map_key'] ?? '';
        $sourceId = (int) ($_POST['source_id'] ?? 0);
        if ($mapKey === '' || $sourceId <= 0) {
            jsonResponse(['success' => false, 'error' => 'بيانات غير صالحة']);
        }
        $result = deleteMediaLibraryImage($mapKey, $sourceId);
        if ($result['success']) {
            logActivity('delete', 'media_library', $sourceId, 'Deleted image via file library: ' . $mapKey);
        }
        jsonResponse($result);
        break;
    }

    // ---- Replace an existing image file in place ----
    case 'replace_image': {
        $mapKey = $_POST['map_key'] ?? '';
        $sourceId = (int) ($_POST['source_id'] ?? 0);
        if ($mapKey === '' || $sourceId <= 0 || empty($_FILES['file'])) {
            jsonResponse(['success' => false, 'error' => 'بيانات غير صالحة']);
        }
        $result = replaceMediaLibraryImage($mapKey, $sourceId, $_FILES['file']);
        if ($result['success']) {
            logActivity('update', 'media_library', $sourceId, 'Replaced image via file library: ' . $mapKey);
        }
        jsonResponse($result);
        break;
    }

    // ---- Brand logos: add ----
    case 'add_brand_logo': {
        $brandName = trim($_POST['brand_name'] ?? '');
        if ($brandName === '' || empty($_FILES['logo'])) {
            jsonResponse(['success' => false, 'error' => 'الرجاء إدخال اسم الماركة واختيار صورة الشعار']);
        }
        $upload = saveBrandLogoUpload($_FILES['logo']);
        if (!$upload['success']) {
            jsonResponse(['success' => false, 'error' => $upload['error']]);
        }
        $id = createBrandLogo($brandName, $upload['path']);
        logActivity('create', 'brand_logos', $id, 'Added brand logo: ' . $brandName);
        $row = Database::fetchOne("SELECT * FROM brand_logos WHERE id = :id", ['id' => $id]);
        jsonResponse(['success' => true, 'logo' => [
            'id'            => (int) $row['id'],
            'brand_name'    => $row['brand_name'],
            'logo_url'      => uploadUrl($row['logo_path']),
            'display_order' => (int) $row['display_order'],
            'is_active'     => (int) $row['is_active'],
        ]]);
        break;
    }

    // ---- Brand logos: delete ----
    case 'delete_brand_logo': {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'بيانات غير صالحة']);
        }
        $ok = deleteBrandLogo($id);
        if ($ok) {
            logActivity('delete', 'brand_logos', $id, 'Deleted brand logo');
        }
        jsonResponse(['success' => $ok, 'error' => $ok ? '' : 'الشعار غير موجود']);
        break;
    }

    // ---- Brand logos: toggle active ----
    case 'toggle_brand_logo': {
        $id = (int) ($_POST['id'] ?? 0);
        $active = ($_POST['active'] ?? '1') === '1';
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'بيانات غير صالحة']);
        }
        setBrandLogoActive($id, $active);
        logActivity('update', 'brand_logos', $id, $active ? 'Activated brand logo' : 'Deactivated brand logo');
        jsonResponse(['success' => true]);
        break;
    }

    // ---- Brand logos: update a single display_order (numeric input fallback) ----
    case 'set_brand_logo_order': {
        $id = (int) ($_POST['id'] ?? 0);
        $order = (int) ($_POST['order'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'بيانات غير صالحة']);
        }
        setBrandLogoOrder($id, $order);
        logActivity('update', 'brand_logos', $id, 'Changed display order to ' . $order);
        jsonResponse(['success' => true]);
        break;
    }

    // ---- Brand logos: full drag-and-drop reorder ----
    case 'reorder_brand_logos': {
        $ids = json_decode($_POST['ordered_ids'] ?? '[]', true);
        if (!is_array($ids) || empty($ids)) {
            jsonResponse(['success' => false, 'error' => 'بيانات غير صالحة']);
        }
        reorderBrandLogos($ids);
        logActivity('update', 'brand_logos', null, 'Reordered brand logos');
        jsonResponse(['success' => true]);
        break;
    }

    default:
        jsonResponse(['success' => false, 'error' => 'إجراء غير معروف'], 400);
}
