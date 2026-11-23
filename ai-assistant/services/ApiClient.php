<?php
/**
 * AI Assistant - API Client
 *
 * المسؤول الوحيد عن التواصل مع مزود الذكاء الاصطناعي.
 * يدعم أي مزود متوافق مع معيار OpenAI Chat Completions API.
 *
 * المسؤوليات:
 * - بناء طلب HTTP وإرساله عبر cURL
 * - معالجة الاستجابة الصحيحة
 * - معالجة جميع أنواع الأخطاء بشكل منظم
 * - إدارة الـ Timeout
 */

if (!defined('BASE_PATH')) exit;

/**
 * تمثّل نتيجة طلب API.
 * نستخدم كلاساً بسيطاً بدلاً من array لتوضيح النية.
 */
class ApiResult
{
    public bool   $success;
    public string $content;   // الرد النصي من النموذج (عند النجاح)
    public string $errorCode; // رمز الخطأ (عند الفشل)
    public string $errorMsg;  // رسالة الخطأ (عند الفشل)
    public int    $httpStatus; // رمز HTTP المُستلم

    public function __construct(
        bool   $success,
        string $content    = '',
        string $errorCode  = '',
        string $errorMsg   = '',
        int    $httpStatus = 200
    ) {
        $this->success    = $success;
        $this->content    = $content;
        $this->errorCode  = $errorCode;
        $this->errorMsg   = $errorMsg;
        $this->httpStatus = $httpStatus;
    }
}

class ApiClient
{
    // --------------------------------------------------------
    // رموز الأخطاء الداخلية
    // --------------------------------------------------------
    const ERR_NO_KEY          = 'no_api_key';
    const ERR_CURL_DISABLED   = 'curl_disabled';
    const ERR_CURL_FAILED     = 'curl_failed';
    const ERR_TIMEOUT         = 'timeout';
    const ERR_HTTP            = 'http_error';
    const ERR_INVALID_JSON    = 'invalid_json';
    const ERR_EMPTY_RESPONSE  = 'empty_response';
    const ERR_API_ERROR       = 'api_error';
    const ERR_RATE_LIMITED    = 'rate_limited';
    const ERR_UNAUTHORIZED    = 'unauthorized';
    const ERR_INSUFFICIENT_QUOTA = 'insufficient_quota';

    // --------------------------------------------------------
    // الواجهة العامة الوحيدة
    // --------------------------------------------------------

    /**
     * إرسال قائمة رسائل إلى نموذج الذكاء الاصطناعي.
     *
     * @param  array  $messages  مصفوفة بصيغة [{role, content}, ...]
     * @param  string $systemPrompt  النظام prompt (اختياري هنا، يُحقن في المرحلة السادسة)
     * @return ApiResult
     */
    public static function sendMessages(array $messages, string $systemPrompt = ''): ApiResult
    {
        // 1. التحقق من توفر cURL
        if (!function_exists('curl_init')) {
            return new ApiResult(false, '', self::ERR_CURL_DISABLED, 'مكتبة cURL غير مفعّلة على الخادم.');
        }

        // 2. التحقق من وجود مفتاح API
        $apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';
        if (empty($apiKey)) {
            return new ApiResult(false, '', self::ERR_NO_KEY, 'مفتاح API غير مُضبوط.');
        }

        // 3. بناء قائمة الرسائل النهائية
        $payload = self::buildPayload($messages, $systemPrompt);

        // 4. إرسال الطلب
        $raw = self::executeRequest($payload, $apiKey);

        if ($raw['error']) {
            return $raw['result'];
        }

        // 5. تحليل الاستجابة
        return self::parseResponse($raw['body'], $raw['httpStatus']);
    }

    // --------------------------------------------------------
    // بناء الـ Payload
    // --------------------------------------------------------

    /**
     * بناء جسم طلب API بالتنسيق المطلوب.
     *
     * @param  array  $messages
     * @param  string $systemPrompt
     * @return array
     */
    private static function buildPayload(array $messages, string $systemPrompt): array
    {
        $finalMessages = [];

        // حقن system prompt إذا وُجد
        if (!empty(trim($systemPrompt))) {
            $finalMessages[] = [
                'role'    => 'system',
                'content' => trim($systemPrompt),
            ];
        }

        // إضافة رسائل المحادثة
        foreach ($messages as $msg) {
            if (isset($msg['role'], $msg['content']) &&
                in_array($msg['role'], ['user', 'assistant'], true)) {
                $finalMessages[] = [
                    'role'    => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        return [
            'model'       => defined('AI_MODEL')       ? AI_MODEL       : 'gpt-4o-mini',
            'messages'    => $finalMessages,
            'max_tokens'  => defined('AI_MAX_TOKENS')  ? AI_MAX_TOKENS  : 1024,
            'temperature' => defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.4,
        ];
    }

    // --------------------------------------------------------
    // تنفيذ طلب cURL
    // --------------------------------------------------------

    /**
     * إرسال طلب HTTP POST إلى API endpoint.
     *
     * @param  array  $payload
     * @param  string $apiKey
     * @return array{error: bool, body: string, httpStatus: int, result?: ApiResult}
     */
    private static function executeRequest(array $payload, string $apiKey): array
    {
        $endpoint = defined('AI_API_ENDPOINT') ? AI_API_ENDPOINT : 'https://api.openai.com/v1/chat/completions';
        $timeout  = defined('AI_TIMEOUT')      ? AI_TIMEOUT      : 30;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            // لا نتبع redirects من APIs
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body       = curl_exec($ch);
        $curlErrNo  = curl_errno($ch);
        $curlErrMsg = curl_error($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // خطأ في cURL نفسه (شبكة، SSL، إلخ)
        if ($body === false || $curlErrNo !== 0) {
            $errCode = ($curlErrNo === CURLE_OPERATION_TIMEDOUT)
                ? self::ERR_TIMEOUT
                : self::ERR_CURL_FAILED;

            return [
                'error'  => true,
                'result' => new ApiResult(false, '', $errCode, $curlErrMsg, 0),
            ];
        }

        return [
            'error'      => false,
            'body'       => $body,
            'httpStatus' => $httpStatus,
        ];
    }

    // --------------------------------------------------------
    // تحليل الاستجابة
    // --------------------------------------------------------

    /**
     * تحليل استجابة JSON من API وإعادة ApiResult.
     *
     * @param  string $body
     * @param  int    $httpStatus
     * @return ApiResult
     */
    private static function parseResponse(string $body, int $httpStatus): ApiResult
    {
        // تحليل JSON
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new ApiResult(
                false, '', self::ERR_INVALID_JSON,
                'استجابة غير صالحة من الخادم.',
                $httpStatus
            );
        }

        // معالجة أخطاء API (error object موجود في الجسم)
        if (isset($data['error'])) {
            return self::handleApiError($data['error'], $httpStatus);
        }

        // معالجة أخطاء HTTP (4xx, 5xx بدون error object)
        if ($httpStatus >= 400) {
            return new ApiResult(
                false, '', self::ERR_HTTP,
                'خطأ HTTP: ' . $httpStatus,
                $httpStatus
            );
        }

        // استخراج محتوى الرد
        $content = $data['choices'][0]['message']['content'] ?? '';

        if (empty(trim($content))) {
            return new ApiResult(
                false, '', self::ERR_EMPTY_RESPONSE,
                'الرد فارغ.',
                $httpStatus
            );
        }

        return new ApiResult(true, trim($content), '', '', $httpStatus);
    }

    // --------------------------------------------------------
    // معالجة أخطاء API
    // --------------------------------------------------------

    /**
     * ترجمة كائن خطأ API إلى ApiResult منظّم.
     *
     * @param  array  $error  كائن الخطأ من API
     * @param  int    $httpStatus
     * @return ApiResult
     */
    private static function handleApiError(array $error, int $httpStatus): ApiResult
    {
        $type    = $error['type']    ?? '';
        $message = $error['message'] ?? 'خطأ غير معروف من API.';

        // تصنيف الخطأ
        if ($httpStatus === 401 || $type === 'invalid_request_error' && str_contains($message, 'API key')) {
            return new ApiResult(false, '', self::ERR_UNAUTHORIZED, $message, $httpStatus);
        }

        if ($httpStatus === 429 || str_contains($type, 'rate_limit')) {
            return new ApiResult(false, '', self::ERR_RATE_LIMITED, $message, $httpStatus);
        }

        if (str_contains($message, 'quota') || str_contains($message, 'billing')) {
            return new ApiResult(false, '', self::ERR_INSUFFICIENT_QUOTA, $message, $httpStatus);
        }

        return new ApiResult(false, '', self::ERR_API_ERROR, $message, $httpStatus);
    }

    // --------------------------------------------------------
    // ترجمة رمز الخطأ إلى رسالة مناسبة للمستخدم
    // --------------------------------------------------------

    /**
     * تحويل رمز خطأ داخلي إلى رسالة واضحة للمستخدم.
     *
     * @param  string $errorCode
     * @return string
     */
    public static function getUserFriendlyError(string $errorCode): string
    {
        $messages = [
            self::ERR_NO_KEY           => 'خدمة المساعد غير مُهيأة بعد. يرجى التواصل مع الإدارة.',
            self::ERR_CURL_DISABLED    => 'إعداد الخادم لا يدعم الاتصال الخارجي.',
            self::ERR_CURL_FAILED      => 'تعذّر الاتصال بخدمة الذكاء الاصطناعي. يرجى المحاولة لاحقاً.',
            self::ERR_TIMEOUT          => 'استغرق الرد وقتاً طويلاً. يرجى المحاولة مرة أخرى.',
            self::ERR_HTTP             => 'خطأ في الخادم. يرجى المحاولة لاحقاً.',
            self::ERR_INVALID_JSON     => 'استجابة غير متوقعة من الخادم. يرجى المحاولة لاحقاً.',
            self::ERR_EMPTY_RESPONSE   => 'لم يُرسل المساعد رداً. يرجى إعادة المحاولة.',
            self::ERR_API_ERROR        => 'حدث خطأ في خدمة الذكاء الاصطناعي. يرجى المحاولة لاحقاً.',
            self::ERR_RATE_LIMITED     => 'تجاوزت خدمة الذكاء الاصطناعي حدها المؤقت. يرجى الانتظار دقيقة.',
            self::ERR_UNAUTHORIZED     => 'مفتاح API غير صالح. يرجى التواصل مع الإدارة.',
            self::ERR_INSUFFICIENT_QUOTA => 'رصيد API غير كافٍ. يرجى التواصل مع الإدارة.',
        ];

        return $messages[$errorCode] ?? 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.';
    }
}
