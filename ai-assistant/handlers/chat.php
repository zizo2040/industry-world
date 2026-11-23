<?php
/**
 * AI Assistant - Chat Handler (API Endpoint)
 *
 * يستقبل طلبات AJAX من الواجهة الأمامية ويعيد ردوداً JSON.
 * يتصل بمزود الذكاء الاصطناعي عبر AssistantService::chat().
 *
 * المسار:  /ai-assistant/handlers/chat.php
 * الطريقة: POST فقط
 * الإجراءات: send_message | clear_session | get_history | ping
 */

// ----- Bootstrap -----
define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/ai-assistant/config/assistant.php';
require_once BASE_PATH . '/ai-assistant/services/ApiClient.php';
require_once BASE_PATH . '/ai-assistant/services/AssistantService.php';


// ----- تقبل POST فقط -----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    AssistantService::jsonResponse(false, 'Method Not Allowed', [], 405);
}

// ----- منع وصول المتصفح المباشر -----
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = str_contains($contentType, 'application/json');

if (!$isAjax && !$isJson) {
    AssistantService::jsonResponse(false, 'Bad Request', [], 400);
}

// ----- قراءة الطلب -----
$rawBody = file_get_contents('php://input');
$body    = json_decode($rawBody, true);

if (!is_array($body)) {
    AssistantService::jsonResponse(false, 'طلب غير صالح.', [], 400);
}

$action = $body['action'] ?? '';

// ----- التحقق من تفعيل المساعد (يُستثنى اختبار الاتصال ping) -----
if ($action !== 'ping' && defined('AI_ENABLED') && !AI_ENABLED) {
    AssistantService::jsonResponse(false, 'المساعد الذكي غير مفعّل حالياً.', [], 403);
}

// ----- تهيئة الجلسة -----
AssistantService::initSession();

// ============================================================
// ACTION: send_message
// ============================================================
if ($action === 'send_message') {

    // Rate Limiting
    if (!AssistantService::checkRateLimit()) {
        AssistantService::jsonResponse(
            false,
            'لقد تجاوزت الحد المسموح به من الرسائل. يرجى الانتظار دقيقة.',
            [],
            429
        );
    }

    // التحقق من الإدخال
    $validation = AssistantService::validateUserInput($body['message'] ?? '');

    if (!$validation['valid']) {
        $errorMessages = [
            'empty_message'    => 'الرجاء كتابة رسالة قبل الإرسال.',
            'message_too_long' => 'الرسالة طويلة جداً. الحد الأقصى ' . AI_MAX_USER_MESSAGE_LENGTH . ' حرف.',
            'invalid_input'    => 'إدخال غير صالح.',
        ];

        AssistantService::jsonResponse(
            false,
            $errorMessages[$validation['error']] ?? 'خطأ في الإدخال.',
            [],
            400
        );
    }

    $userMessage = $validation['message'];

    $lang = function_exists('getCurrentLang') ? getCurrentLang() : 'ar';
    $siteContext = AssistantService::getSiteStructureContext($lang);
    $finalPrompt = (defined('AI_SYSTEM_PROMPT') ? AI_SYSTEM_PROMPT : '') . $siteContext;

    // ---- الاتصال الحقيقي بـ API ----
    $chatResult = AssistantService::chat($userMessage, $finalPrompt, $lang);

    if (!$chatResult['success']) {
        // أرسل رسالة الخطأ للعرض كـ "assistant message" في الواجهة
        AssistantService::jsonResponse(
            false,
            $chatResult['reply'],
            ['errorCode' => $chatResult['errorCode'] ?? 'unknown'],
            200 // نرسل 200 لأن الواجهة تتعامل مع success=false
        );
    }

    AssistantService::jsonResponse(true, $chatResult['reply'], [
        'reply'         => $chatResult['reply'],
        'history_count' => count(AssistantService::getConversationHistory()),
    ]);
}

// ============================================================
// ACTION: clear_session
// ============================================================
if ($action === 'clear_session') {
    AssistantService::clearSession();
    AssistantService::jsonResponse(true, 'تم مسح المحادثة بنجاح.');
}

// ============================================================
// ACTION: ping — للتحقق من الاتصال بالـ API
// ============================================================
if ($action === 'ping') {
    $apiKeySet = defined('AI_API_KEY') && !empty(AI_API_KEY);

    AssistantService::jsonResponse(true, 'pong', [
        'status'      => 'ready',
        'api_key_set' => $apiKeySet,
        'model'       => defined('AI_MODEL') ? AI_MODEL : 'not_set',
        'endpoint'    => defined('AI_API_ENDPOINT') ? AI_API_ENDPOINT : 'not_set',
        'timestamp'   => time(),
    ]);
}

// ============================================================
// ACTION: get_history — استرجاع سياق المحادثة للواجهة
// ============================================================
if ($action === 'get_history') {
    $history = AssistantService::getConversationHistory();

    AssistantService::jsonResponse(true, 'ok', [
        'history' => $history,
        'count'   => count($history),
    ]);
}

// ============================================================
// الإجراء غير معروف
// ============================================================
AssistantService::jsonResponse(false, 'إجراء غير معروف.', [], 400);
