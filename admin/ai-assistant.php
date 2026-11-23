<?php
/**
 * Admin - AI Assistant Settings (Phase 5)
 *
 * لوحة تحكم شاملة لإعدادات المساعد الذكي:
 * - مفتاح API (مع إمكانية الإخفاء)
 * - اختيار النموذج
 * - نقطة النهاية
 * - درجة الإبداعية وعدد الرموز
 * - Rate Limiting
 * - تفعيل/تعطيل المساعد
 * - اختبار الاتصال المباشر
 */

require_once __DIR__ . '/../config.php';
initApp();
requireAuth();
require_once BASE_PATH . '/ai-assistant/config/assistant.php';

$lang      = getCurrentLang();
$adminPage = 'ai_assistant';
$pageTitle = ($lang === 'ar' ? 'إعدادات المساعد الذكي - ' : 'AI Assistant Settings - ')
           . getSetting('site_name_' . $lang);

$message = '';
$error   = '';

// ============================================================
// POST: حفظ الإعدادات
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = $lang === 'ar' ? 'رمز الحماية غير صالح.' : 'Invalid CSRF token.';
    } else {
        $settingsToSave = [
            'ai_enabled'              => isset($_POST['ai_enabled']) ? '1' : '0',
            'ai_provider'             => trim($_POST['ai_provider']  ?? 'openai'),
            'ai_api_endpoint'         => trim($_POST['ai_api_endpoint'] ?? ''),
            'ai_model'                => trim($_POST['ai_model']     ?? ''),
            'ai_max_tokens'           => (string)(int)($_POST['ai_max_tokens'] ?? 1024),
            'ai_temperature'          => number_format(max(0, min(2, (float)($_POST['ai_temperature'] ?? 0.4))), 1),
            'ai_timeout'              => (string)(int)($_POST['ai_timeout'] ?? 30),
            'ai_max_context_messages' => (string)(int)($_POST['ai_max_context_messages'] ?? 10),
            'ai_max_message_length'   => (string)(int)($_POST['ai_max_message_length'] ?? 1000),
            'ai_session_lifetime'     => (string)(int)($_POST['ai_session_lifetime'] ?? 7200),
            'ai_rate_limit'           => (string)(int)($_POST['ai_rate_limit'] ?? 20),
            'ai_rate_limit_window'    => (string)(int)($_POST['ai_rate_limit_window'] ?? 60),
            'ai_system_prompt'        => trim($_POST['ai_system_prompt'] ?? ''),
            'ai_knowledge_base'       => trim($_POST['ai_knowledge_base'] ?? ''),
            // Robot & context bubbles
            'ai_bubbles_enabled'      => isset($_POST['ai_bubbles_enabled']) ? '1' : '0',
            'ai_bubbles_per_page'     => (string) max(1, min(10, (int) ($_POST['ai_bubbles_per_page'] ?? 3))),
            'ai_bubbles_per_session'  => (string) max(1, min(50, (int) ($_POST['ai_bubbles_per_session'] ?? 10))),
            'ai_bubbles_first_delay'  => (string) max(0, min(60, (int) ($_POST['ai_bubbles_first_delay'] ?? 4))),
            'ai_bubbles_cooldown'     => (string) max(5, min(300, (int) ($_POST['ai_bubbles_cooldown'] ?? 22))),
            'ai_robot_name_ar'        => trim($_POST['ai_robot_name_ar'] ?? ''),
            'ai_robot_name_en'        => trim($_POST['ai_robot_name_en'] ?? ''),
            'ai_robot_welcome_ar'     => trim($_POST['ai_robot_welcome_ar'] ?? ''),
            'ai_robot_welcome_en'     => trim($_POST['ai_robot_welcome_en'] ?? ''),
        ];

        // مفتاح API: لا تحفظ إذا كان المستخدم أرسل placeholder
        $apiKeyInput = $_POST['ai_api_key'] ?? '';
        if ($apiKeyInput !== '' && !str_starts_with($apiKeyInput, '••••')) {
            $settingsToSave['ai_api_key'] = $apiKeyInput;
        }

        foreach ($settingsToSave as $key => $value) {
            Database::query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE setting_value = :value",
                ['key' => $key, 'value' => $value]
            );
        }

        // تسجيل النشاط
        if (function_exists('logActivity')) {
            logActivity('ai_settings_updated', 'AI assistant settings updated');
        }

        $message = $lang === 'ar' ? 'تم حفظ الإعدادات بنجاح.' : 'Settings saved successfully.';
    }
}

// ============================================================
// قراءة الإعدادات الحالية
// ============================================================
function aiSetting(string $key, string $default = ''): string {
    $val = getSetting($key);
    return ($val !== null && $val !== '') ? $val : $default;
}

$rawKnowledgeDefault = '';
$filePath = BASE_PATH . '/ai-assistant/data/knowledge.md';
if (file_exists($filePath)) {
    $rawKnowledgeDefault = file_get_contents($filePath);
}

$currentSettings = [
    'ai_enabled'              => aiSetting('ai_enabled', '1'),
    'ai_provider'             => aiSetting('ai_provider', 'openai'),
    'ai_api_key'              => aiSetting('ai_api_key', ''),
    'ai_api_endpoint'         => aiSetting('ai_api_endpoint', 'https://api.openai.com/v1/chat/completions'),
    'ai_model'                => aiSetting('ai_model', 'gpt-4o-mini'),
    'ai_max_tokens'           => aiSetting('ai_max_tokens', '1024'),
    'ai_temperature'          => aiSetting('ai_temperature', '0.4'),
    'ai_timeout'              => aiSetting('ai_timeout', '30'),
    'ai_max_context_messages' => aiSetting('ai_max_context_messages', '10'),
    'ai_max_message_length'   => aiSetting('ai_max_message_length', '1000'),
    'ai_session_lifetime'     => aiSetting('ai_session_lifetime', '7200'),
    'ai_rate_limit'           => aiSetting('ai_rate_limit', '20'),
    'ai_rate_limit_window'    => aiSetting('ai_rate_limit_window', '60'),
    'ai_system_prompt'        => aiSetting('ai_system_prompt', AI_DEFAULT_SYSTEM_PROMPT),
    'ai_knowledge_base'       => aiSetting('ai_knowledge_base', $rawKnowledgeDefault),
    'ai_bubbles_enabled'      => aiSetting('ai_bubbles_enabled', '1'),
    'ai_bubbles_per_page'     => aiSetting('ai_bubbles_per_page', '3'),
    'ai_bubbles_per_session'  => aiSetting('ai_bubbles_per_session', '10'),
    'ai_bubbles_first_delay'  => aiSetting('ai_bubbles_first_delay', '4'),
    'ai_bubbles_cooldown'     => aiSetting('ai_bubbles_cooldown', '22'),
    'ai_robot_name_ar'        => aiSetting('ai_robot_name_ar', 'فولت · المساعد الذكي'),
    'ai_robot_name_en'        => aiSetting('ai_robot_name_en', 'Volt · Smart Assistant'),
    'ai_robot_welcome_ar'     => aiSetting('ai_robot_welcome_ar', ''),
    'ai_robot_welcome_en'     => aiSetting('ai_robot_welcome_en', ''),
];

// إخفاء المفتاح في الواجهة
$maskedKey = $currentSettings['ai_api_key'] !== ''
    ? '••••••••' . substr($currentSettings['ai_api_key'], -4)
    : '';

$isEnabled = $currentSettings['ai_enabled'] === '1';

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="ai-admin-page-header reveal">
    <div>
        <h1 style="font-size:1.5rem;margin-bottom:var(--space-1);display:flex;align-items:center;gap:10px;">
            <span class="ai-admin-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/>
                    <path d="M8 12h.01M12 12h.01M16 12h.01" stroke-linecap="round" stroke-width="2.5"/>
                </svg>
            </span>
            <?php echo $lang === 'ar' ? 'إعدادات المساعد الذكي' : 'AI Assistant Settings'; ?>
        </h1>
        <p style="color:var(--color-text-muted);">
            <?php echo $lang === 'ar'
                ? 'إدارة مفتاح API والنموذج وإعدادات المحادثة'
                : 'Manage API key, model, and conversation settings'; ?>
        </p>
    </div>
    <div style="display:flex;gap:var(--space-3);align-items:center;">
        <!-- حالة المساعد -->
        <div class="ai-admin-status-badge <?php echo $isEnabled ? 'active' : 'inactive'; ?>">
            <span class="ai-admin-status-dot"></span>
            <?php echo $isEnabled
                ? ($lang === 'ar' ? 'المساعد نشط' : 'Assistant Active')
                : ($lang === 'ar' ? 'المساعد معطّل' : 'Assistant Disabled'); ?>
        </div>
        <!-- زر اختبار الاتصال -->
        <button type="button" id="ai-test-btn" class="btn btn-outline btn-sm">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            <?php echo $lang === 'ar' ? 'اختبار الاتصال' : 'Test Connection'; ?>
        </button>
    </div>
</div>

<!-- Flash Messages -->
<?php if ($message): ?>
<div class="alert alert-success reveal" style="margin-bottom:var(--space-4);">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
        <polyline points="20 6 9 17 4 12"/>
    </svg>
    <?php echo e($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger reveal" style="margin-bottom:var(--space-4);">
    <?php echo e($error); ?>
</div>
<?php endif; ?>

<!-- Test Result Banner -->
<div id="ai-test-result" style="display:none;margin-bottom:var(--space-4);" class="reveal"></div>

<!-- ============================================================
     Main Form
     ============================================================ -->
<form method="POST" action="" id="ai-settings-form">
    <?php echo csrfField(); ?>

    <div class="ai-admin-grid">

        <!-- ====================================================
             بطاقة 1: الحالة العامة
             ==================================================== -->
        <div class="admin-card reveal">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <?php echo $lang === 'ar' ? 'الإعدادات العامة' : 'General Settings'; ?>
                </h2>
            </div>
            <div class="admin-card-body">
                <!-- تفعيل المساعد -->
                <div class="form-group">
                    <label class="toggle-label">
                        <input type="checkbox" name="ai_enabled" id="ai_enabled" value="1"
                               <?php echo $isEnabled ? 'checked' : ''; ?>>
                        <span class="toggle-switch"></span>
                        <span class="toggle-text">
                            <?php echo $lang === 'ar'
                                ? 'تفعيل المساعد الذكي على الموقع'
                                : 'Enable AI Assistant on the website'; ?>
                        </span>
                    </label>
                    <p class="form-help">
                        <?php echo $lang === 'ar'
                            ? 'عند التعطيل، لن يظهر زر المساعد لزوار الموقع.'
                            : 'When disabled, the assistant button will be hidden from visitors.'; ?>
                    </p>
                </div>

                <!-- مزود الذكاء الاصطناعي -->
                <div class="form-group">
                    <label class="form-label" for="ai_provider">
                        <?php echo $lang === 'ar' ? 'مزود الذكاء الاصطناعي' : 'AI Provider'; ?>
                    </label>
                    <select name="ai_provider" id="ai_provider" class="form-control" onchange="updateEndpointHint(this.value)">
                        <option value="openai"    <?php echo $currentSettings['ai_provider'] === 'openai'    ? 'selected' : ''; ?>>OpenAI (GPT-4, GPT-4o)</option>
                        <option value="groq"      <?php echo $currentSettings['ai_provider'] === 'groq'      ? 'selected' : ''; ?>>Groq (LLaMA 3, Mixtral)</option>
                        <option value="together"  <?php echo $currentSettings['ai_provider'] === 'together'  ? 'selected' : ''; ?>>Together AI</option>
                        <option value="openrouter"<?php echo $currentSettings['ai_provider'] === 'openrouter'? 'selected' : ''; ?>>OpenRouter</option>
                        <option value="custom"    <?php echo $currentSettings['ai_provider'] === 'custom'    ? 'selected' : ''; ?>>
                            <?php echo $lang === 'ar' ? 'مخصص (Custom endpoint)' : 'Custom Endpoint'; ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ====================================================
             بطاقة 2: إعدادات API
             ==================================================== -->
        <div class="admin-card reveal">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <?php echo $lang === 'ar' ? 'مفتاح API والنموذج' : 'API Key & Model'; ?>
                </h2>
            </div>
            <div class="admin-card-body">

                <!-- API Key -->
                <div class="form-group">
                    <label class="form-label" for="ai_api_key">
                        <?php echo $lang === 'ar' ? 'مفتاح API' : 'API Key'; ?>
                        <?php if ($maskedKey): ?>
                        <span class="badge badge-success" style="font-weight:400;font-size:11px;margin-inline-start:8px;">
                            <?php echo $lang === 'ar' ? 'محفوظ' : 'Saved'; ?>
                        </span>
                        <?php endif; ?>
                    </label>
                    <div class="ai-key-input-wrap">
                        <input type="password" name="ai_api_key" id="ai_api_key"
                               class="form-control"
                               placeholder="<?php echo $maskedKey ?: 'sk-...'; ?>"
                               autocomplete="new-password">
                        <button type="button" class="ai-key-toggle-btn" onclick="toggleKeyVisibility()" aria-label="Toggle visibility">
                            <svg id="key-eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <p class="form-help">
                        <?php echo $lang === 'ar'
                            ? 'اترك الحقل فارغاً للإبقاء على المفتاح الحالي. المفتاح مُخزَّن بأمان.'
                            : 'Leave blank to keep current key. The key is stored securely.'; ?>
                    </p>
                </div>

                <!-- Endpoint -->
                <div class="form-group">
                    <label class="form-label" for="ai_api_endpoint">
                        <?php echo $lang === 'ar' ? 'نقطة النهاية (Endpoint)' : 'API Endpoint'; ?>
                    </label>
                    <input type="url" name="ai_api_endpoint" id="ai_api_endpoint"
                           class="form-control"
                           value="<?php echo e($currentSettings['ai_api_endpoint']); ?>"
                           dir="ltr"
                           placeholder="https://api.openai.com/v1/chat/completions">
                    <p class="form-help" id="endpoint-hint">
                        <?php echo $lang === 'ar'
                            ? 'عادةً لا تحتاج لتغيير هذا إلا إذا استخدمت مزوداً مخصصاً.'
                            : 'You usually don\'t need to change this unless using a custom provider.'; ?>
                    </p>
                </div>

                <!-- Model -->
                <div class="form-group">
                    <label class="form-label" for="ai_model">
                        <?php echo $lang === 'ar' ? 'النموذج' : 'Model'; ?>
                    </label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <select name="ai_model" id="ai_model_select" class="form-control"
                                onchange="syncModelInput(this.value)">
                            <optgroup label="OpenAI">
                                <option value="gpt-4o-mini">gpt-4o-mini (سريع واقتصادي)</option>
                                <option value="gpt-4o">gpt-4o (الأحدث)</option>
                                <option value="gpt-4-turbo">gpt-4-turbo</option>
                                <option value="gpt-3.5-turbo">gpt-3.5-turbo (اقتصادي)</option>
                            </optgroup>
                            <optgroup label="Groq (مجاني)">
                                <option value="llama3-8b-8192">LLaMA3 8B (سريع جداً)</option>
                                <option value="llama3-70b-8192">LLaMA3 70B (أقوى)</option>
                                <option value="mixtral-8x7b-32768">Mixtral 8x7B</option>
                            </optgroup>
                            <option value="__custom__"><?php echo $lang === 'ar' ? 'نموذج مخصص...' : 'Custom model...'; ?></option>
                        </select>
                    </div>
                    <input type="text" name="ai_model" id="ai_model"
                           class="form-control" style="margin-top:8px;"
                           value="<?php echo e($currentSettings['ai_model']); ?>"
                           dir="ltr"
                           placeholder="gpt-4o-mini">
                    <p class="form-help">
                        <?php echo $lang === 'ar' ? 'اختر من القائمة أو اكتب اسم النموذج مباشرة.' : 'Select from list or type the model name directly.'; ?>
                    </p>
                </div>

            </div>
        </div>

        <!-- ====================================================
             بطاقة 3: معلمات الطلب
             ==================================================== -->
        <div class="admin-card reveal">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/>
                        <line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/>
                        <line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/>
                        <line x1="17" y1="16" x2="23" y2="16"/>
                    </svg>
                    <?php echo $lang === 'ar' ? 'معلمات الطلب' : 'Request Parameters'; ?>
                </h2>
            </div>
            <div class="admin-card-body">

                <div class="ai-admin-row-2">
                    <!-- Max Tokens -->
                    <div class="form-group">
                        <label class="form-label" for="ai_max_tokens">
                            <?php echo $lang === 'ar' ? 'الحد الأقصى للرموز' : 'Max Tokens'; ?>
                        </label>
                        <input type="number" name="ai_max_tokens" id="ai_max_tokens"
                               class="form-control" min="256" max="8192" step="128"
                               value="<?php echo e($currentSettings['ai_max_tokens']); ?>">
                        <p class="form-help"><?php echo $lang === 'ar' ? 'يؤثر في طول الردود. افتراضي: 1024' : 'Affects response length. Default: 1024'; ?></p>
                    </div>

                    <!-- Timeout -->
                    <div class="form-group">
                        <label class="form-label" for="ai_timeout">
                            <?php echo $lang === 'ar' ? 'مهلة الاتصال (ثانية)' : 'Timeout (seconds)'; ?>
                        </label>
                        <input type="number" name="ai_timeout" id="ai_timeout"
                               class="form-control" min="5" max="120"
                               value="<?php echo e($currentSettings['ai_timeout']); ?>">
                        <p class="form-help"><?php echo $lang === 'ar' ? 'إلغاء الطلب بعد هذه المدة. افتراضي: 30' : 'Cancel request after this duration. Default: 30'; ?></p>
                    </div>
                </div>

                <!-- Temperature -->
                <div class="form-group">
                    <label class="form-label" for="ai_temperature">
                        <?php echo $lang === 'ar' ? 'درجة الإبداعية (Temperature)' : 'Temperature'; ?>
                        <span id="temp-display" class="ai-admin-badge"><?php echo e($currentSettings['ai_temperature']); ?></span>
                    </label>
                    <input type="range" name="ai_temperature" id="ai_temperature"
                           class="ai-range-input" min="0" max="1.5" step="0.1"
                           value="<?php echo e($currentSettings['ai_temperature']); ?>"
                           oninput="document.getElementById('temp-display').textContent = this.value">
                    <div class="ai-range-labels">
                        <span><?php echo $lang === 'ar' ? '🎯 دقيق' : '🎯 Precise'; ?></span>
                        <span><?php echo $lang === 'ar' ? '🎨 إبداعي' : '🎨 Creative'; ?></span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ====================================================
             بطاقة 4: إعدادات المحادثة
             ==================================================== -->
        <div class="admin-card reveal">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <?php echo $lang === 'ar' ? 'إعدادات المحادثة' : 'Conversation Settings'; ?>
                </h2>
            </div>
            <div class="admin-card-body">

                <div class="ai-admin-row-2">
                    <!-- Max Context Messages -->
                    <div class="form-group">
                        <label class="form-label" for="ai_max_context_messages">
                            <?php echo $lang === 'ar' ? 'عدد الرسائل في السياق' : 'Context Message Limit'; ?>
                        </label>
                        <input type="number" name="ai_max_context_messages" id="ai_max_context_messages"
                               class="form-control" min="2" max="50"
                               value="<?php echo e($currentSettings['ai_max_context_messages']); ?>">
                        <p class="form-help"><?php echo $lang === 'ar' ? 'عدد الرسائل المحفوظة في الذاكرة. افتراضي: 10' : 'Messages kept in memory. Default: 10'; ?></p>
                    </div>

                    <!-- Max Message Length -->
                    <div class="form-group">
                        <label class="form-label" for="ai_max_message_length">
                            <?php echo $lang === 'ar' ? 'الحد الأقصى للرسالة (حرف)' : 'Max Message Length'; ?>
                        </label>
                        <input type="number" name="ai_max_message_length" id="ai_max_message_length"
                               class="form-control" min="100" max="5000"
                               value="<?php echo e($currentSettings['ai_max_message_length']); ?>">
                        <p class="form-help"><?php echo $lang === 'ar' ? 'الحد الأقصى لرسالة المستخدم. افتراضي: 1000' : 'Max user message length. Default: 1000'; ?></p>
                    </div>
                </div>

                <div class="ai-admin-row-2">
                    <!-- Session Lifetime -->
                    <div class="form-group">
                        <label class="form-label" for="ai_session_lifetime">
                            <?php echo $lang === 'ar' ? 'مدة الجلسة (ثانية)' : 'Session Lifetime (seconds)'; ?>
                        </label>
                        <input type="number" name="ai_session_lifetime" id="ai_session_lifetime"
                               class="form-control" min="300" max="86400" step="300"
                               value="<?php echo e($currentSettings['ai_session_lifetime']); ?>">
                        <p class="form-help"><?php echo $lang === 'ar' ? '7200 = ساعتان. الجلسة تُحذف بعد هذه المدة.' : '7200 = 2 hours. Session expires after this.'; ?></p>
                    </div>

                    <!-- Rate Limit -->
                    <div class="form-group">
                        <label class="form-label" for="ai_rate_limit">
                            <?php echo $lang === 'ar' ? 'الحد الأقصى للطلبات/دقيقة' : 'Rate Limit (req/min)'; ?>
                        </label>
                        <input type="number" name="ai_rate_limit" id="ai_rate_limit"
                               class="form-control" min="1" max="200"
                               value="<?php echo e($currentSettings['ai_rate_limit']); ?>">
                        <p class="form-help"><?php echo $lang === 'ar' ? 'الحد الأقصى لكل مستخدم في الدقيقة. افتراضي: 20' : 'Max requests per user per minute. Default: 20'; ?></p>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /ai-admin-grid -->

    <!-- ====================================================
         بطاقة: الروبوت التفاعلي والفقاعات الذكية
         ==================================================== -->
    <div class="admin-card reveal" style="margin-bottom: var(--space-5);">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="7" width="16" height="12" rx="3"/><path d="M12 3v4M9 12h.01M15 12h.01M9 16h6"/></svg>
                <?php echo $lang === 'ar' ? 'الروبوت التفاعلي والفقاعات الذكية' : 'Interactive Robot & Smart Bubbles'; ?>
            </h2>
        </div>
        <div class="admin-card-body">
            <p class="form-help" style="margin-bottom:var(--space-4);">
                <?php echo $lang === 'ar'
                    ? 'الروبوت ثلاثي الأبعاد يتابع حركة الزائر ويُظهر فقاعات مناسبة للصفحة والقسم الذي يشاهده (الأقسام، مواصفات المنتج، نموذج التواصل…) مع أزرار سريعة للسؤال أو واتساب. هذه الإعدادات تتحكم بعدد مرات الظهور حتى لا يكون مزعجاً.'
                    : 'The 3D robot follows the visitor and shows bubbles that match the current page and section (categories, product specs, contact form…) with quick actions. These settings control how often it speaks so it never becomes annoying.'; ?>
            </p>
            <div class="form-group">
                <label class="ai-admin-toggle-label" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="ai_bubbles_enabled" value="1" <?php echo $currentSettings['ai_bubbles_enabled'] === '1' ? 'checked' : ''; ?>>
                    <strong><?php echo $lang === 'ar' ? 'تفعيل الفقاعات الذكية' : 'Enable smart bubbles'; ?></strong>
                </label>
            </div>
            <div class="ai-admin-row-2">
                <div class="form-group">
                    <label class="form-label" for="ai_bubbles_per_page"><?php echo $lang === 'ar' ? 'أقصى عدد فقاعات في الصفحة' : 'Max bubbles per page'; ?></label>
                    <input type="number" class="form-control" id="ai_bubbles_per_page" name="ai_bubbles_per_page" min="1" max="10" value="<?php echo e($currentSettings['ai_bubbles_per_page']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ai_bubbles_per_session"><?php echo $lang === 'ar' ? 'أقصى عدد فقاعات في الزيارة' : 'Max bubbles per visit'; ?></label>
                    <input type="number" class="form-control" id="ai_bubbles_per_session" name="ai_bubbles_per_session" min="1" max="50" value="<?php echo e($currentSettings['ai_bubbles_per_session']); ?>">
                </div>
            </div>
            <div class="ai-admin-row-2">
                <div class="form-group">
                    <label class="form-label" for="ai_bubbles_first_delay"><?php echo $lang === 'ar' ? 'تأخير أول فقاعة (ثانية)' : 'First bubble delay (s)'; ?></label>
                    <input type="number" class="form-control" id="ai_bubbles_first_delay" name="ai_bubbles_first_delay" min="0" max="60" value="<?php echo e($currentSettings['ai_bubbles_first_delay']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ai_bubbles_cooldown"><?php echo $lang === 'ar' ? 'أقل فاصل بين فقاعتين (ثانية)' : 'Min gap between bubbles (s)'; ?></label>
                    <input type="number" class="form-control" id="ai_bubbles_cooldown" name="ai_bubbles_cooldown" min="5" max="300" value="<?php echo e($currentSettings['ai_bubbles_cooldown']); ?>">
                </div>
            </div>
            <div class="ai-admin-row-2">
                <div class="form-group">
                    <label class="form-label" for="ai_robot_name_ar"><?php echo $lang === 'ar' ? 'اسم المساعد (عربي)' : 'Assistant name (Arabic)'; ?></label>
                    <input type="text" class="form-control" id="ai_robot_name_ar" name="ai_robot_name_ar" maxlength="60" value="<?php echo e($currentSettings['ai_robot_name_ar']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ai_robot_name_en"><?php echo $lang === 'ar' ? 'اسم المساعد (إنجليزي)' : 'Assistant name (English)'; ?></label>
                    <input type="text" class="form-control" id="ai_robot_name_en" name="ai_robot_name_en" maxlength="60" dir="ltr" value="<?php echo e($currentSettings['ai_robot_name_en']); ?>">
                </div>
            </div>
            <div class="ai-admin-row-2">
                <div class="form-group">
                    <label class="form-label" for="ai_robot_welcome_ar"><?php echo $lang === 'ar' ? 'رسالة الترحيب في الرئيسية (عربي) — اختياري' : 'Home welcome bubble (Arabic) — optional'; ?></label>
                    <textarea class="form-control" id="ai_robot_welcome_ar" name="ai_robot_welcome_ar" rows="2" maxlength="300"><?php echo e($currentSettings['ai_robot_welcome_ar']); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ai_robot_welcome_en"><?php echo $lang === 'ar' ? 'رسالة الترحيب في الرئيسية (إنجليزي) — اختياري' : 'Home welcome bubble (English) — optional'; ?></label>
                    <textarea class="form-control" id="ai_robot_welcome_en" name="ai_robot_welcome_en" rows="2" maxlength="300" dir="ltr"><?php echo e($currentSettings['ai_robot_welcome_en']); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================
         بطاقة 5: التعليمات الأساسية للمساعد (System Prompt)
         ==================================================== -->
    <div class="admin-card reveal" style="margin-bottom: var(--space-5);">
        <div class="admin-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <h2 class="admin-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9M3 20v-8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2v8"/>
                    <rect x="3" y="12" width="18" height="8" rx="2" ry="2"/>
                </svg>
                <?php echo $lang === 'ar' ? 'التعليمات الأساسية وهويّة المساعد (System Prompt)' : 'Basic Instructions & Assistant Identity (System Prompt)'; ?>
            </h2>
            <button type="button" class="btn btn-outline btn-sm" onclick="resetSystemPromptToDefault()">
                <?php echo $lang === 'ar' ? 'استعادة التوجيه الافتراضي' : 'Reset to Default'; ?>
            </button>
        </div>
        <div class="admin-card-body">
            <div class="form-group" style="margin-bottom:0;">
                <textarea name="ai_system_prompt" id="ai_system_prompt" class="form-control" rows="8" style="font-family:inherit; line-height:1.6; font-size:14px; direction:rtl; text-align:right;"><?php echo e($currentSettings['ai_system_prompt']); ?></textarea>
                <p class="form-help">
                    <?php echo $lang === 'ar'
                        ? 'هذا التوجيه (System Prompt) يحدد هوية المساعد، نطاقه، أسلوب إجابته، ويمنعه من الخروج عن تخصص الشركة.'
                        : 'This System Prompt defines the assistant\'s identity, scope, tone, and prevents it from discussing out-of-scope topics.'; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ====================================================
         بطاقة 6: قاعدة المعرفة الإضافية (Knowledge Base)
         ==================================================== -->
    <div class="admin-card reveal" style="margin-bottom: var(--space-5);">
        <div class="admin-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <h2 class="admin-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                <?php echo $lang === 'ar' ? 'قاعدة المعرفة الإضافية للشركة (Knowledge Base)' : 'Additional Company Knowledge Base (Markdown)'; ?>
            </h2>
            <button type="button" class="btn btn-outline btn-sm" onclick="resetKnowledgeToDefault()">
                <?php echo $lang === 'ar' ? 'استعادة محتوى الملف الافتراضي' : 'Reset to Default File'; ?>
            </button>
        </div>
        <div class="admin-card-body">
            <div class="form-group" style="margin-bottom:0;">
                <textarea name="ai_knowledge_base" id="ai_knowledge_base" class="form-control" rows="12" style="font-family:monospace; line-height:1.6; font-size:13px; direction:rtl; text-align:right;"><?php echo e($currentSettings['ai_knowledge_base']); ?></textarea>
                <p class="form-help">
                    <?php echo $lang === 'ar'
                        ? 'تتيح لك هذه اللوحة كتابة وتحرير معلومات الشركة، ساعات العمل، أرقام التواصل، والسياسات مباشرة بصيغة Markdown. يتم البحث فيها ديناميكياً باستخدام الـ RAG عند سؤال العميل.'
                        : 'Exposes markdown text content to edit additional FAQs, contact numbers, hours, policies, and brands. Searched dynamically via local RAG.'; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- زر الحفظ -->
    <div class="ai-admin-save-bar reveal">
        <button type="submit" class="btn btn-primary">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>
            <?php echo $lang === 'ar' ? 'حفظ الإعدادات' : 'Save Settings'; ?>
        </button>
        <span class="ai-admin-save-note">
            <?php echo $lang === 'ar'
                ? 'التغييرات تنعكس فوراً على المساعد بعد الحفظ.'
                : 'Changes take effect immediately after saving.'; ?>
        </span>
    </div>

</form>

<!-- ============================================================
     CSS مخصص للصفحة
     ============================================================ -->
<style>
/* Page Header */
.ai-admin-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-6);
    flex-wrap: wrap;
}

.ai-admin-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: rgba(var(--color-primary-rgb), 0.1);
    border-radius: 10px;
    color: var(--color-primary);
    flex-shrink: 0;
}

/* Status Badge */
.ai-admin-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 99px;
    font-size: 0.85rem;
    font-weight: 600;
}

.ai-admin-status-badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.ai-admin-status-badge.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.ai-admin-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: ai-pulse 2s infinite;
}

.inactive .ai-admin-status-dot { animation: none; }

@keyframes ai-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* Grid Layout */
.ai-admin-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-5);
    margin-bottom: var(--space-5);
}

@media (max-width: 900px) {
    .ai-admin-grid { grid-template-columns: 1fr; }
}

.ai-admin-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
}

@media (max-width: 600px) {
    .ai-admin-row-2 { grid-template-columns: 1fr; }
}

/* Toggle Switch */
.toggle-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    user-select: none;
}

.toggle-label input { display: none; }

.toggle-switch {
    position: relative;
    width: 48px;
    height: 26px;
    background: var(--color-border-dark, #ccc);
    border-radius: 13px;
    transition: background 0.25s;
    flex-shrink: 0;
}

.toggle-switch::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    top: 3px;
    left: 3px;
    transition: transform 0.25s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
}

.toggle-label input:checked + .toggle-switch { background: var(--color-primary); }
.toggle-label input:checked + .toggle-switch::after { transform: translateX(22px); }

.toggle-text {
    font-weight: 500;
    color: var(--color-text-primary);
}

/* API Key Input */
.ai-key-input-wrap {
    position: relative;
}

.ai-key-input-wrap .form-control {
    padding-inline-end: 44px;
    direction: ltr;
}

.ai-key-toggle-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    inset-inline-end: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--color-text-muted);
    padding: 4px;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.ai-key-toggle-btn:hover { color: var(--color-primary); }

/* Range Input */
.ai-range-input {
    width: 100%;
    height: 6px;
    -webkit-appearance: none;
    background: var(--color-border);
    border-radius: 3px;
    outline: none;
    cursor: pointer;
    margin: 8px 0;
}

.ai-range-input::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--color-primary);
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(var(--color-primary-rgb), 0.4);
}

.ai-range-labels {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--color-text-muted);
}

.ai-admin-badge {
    display: inline-block;
    background: rgba(var(--color-primary-rgb), 0.1);
    color: var(--color-primary);
    font-size: 12px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
    margin-inline-start: 8px;
    min-width: 36px;
    text-align: center;
}

/* Save Bar */
.ai-admin-save-bar {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4) var(--space-6);
    background: var(--color-bg-elevated);
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border);
    box-shadow: var(--shadow-sm);
}

.ai-admin-save-note {
    font-size: 0.875rem;
    color: var(--color-text-muted);
}

/* Test Result */
.ai-test-success {
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #065f46;
    border-radius: var(--radius-lg);
    padding: var(--space-4) var(--space-5);
    display: flex;
    align-items: center;
    gap: 12px;
}

.ai-test-error {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #991b1b;
    border-radius: var(--radius-lg);
    padding: var(--space-4) var(--space-5);
    display: flex;
    align-items: center;
    gap: 12px;
}

[data-theme="dark"] .ai-test-success { color: #6ee7b7; }
[data-theme="dark"] .ai-test-error   { color: #fca5a5; }
</style>

<!-- ============================================================
     JavaScript
     ============================================================ -->
<script>
const AI_ADMIN_LANG = '<?php echo $lang; ?>';

// ----- مزامنة القائمة مع حقل النموذج -----
(function () {
    const select = document.getElementById('ai_model_select');
    const input  = document.getElementById('ai_model');
    const current = input.value;

    // تحديد الخيار المناسب
    let found = false;
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === current) {
            select.selectedIndex = i;
            found = true;
            break;
        }
    }
    if (!found && current) select.value = '__custom__';
})();

function syncModelInput(val) {
    const input = document.getElementById('ai_model');
    if (val !== '__custom__') {
        input.value = val;
    }
}

// ----- تحديث hint الـ endpoint بناءً على المزود -----
const ENDPOINTS = {
    openai:     'https://api.openai.com/v1/chat/completions',
    groq:       'https://api.groq.com/openai/v1/chat/completions',
    together:   'https://api.together.xyz/v1/chat/completions',
    openrouter: 'https://openrouter.ai/api/v1/chat/completions',
    custom:     '',
};

function updateEndpointHint(provider) {
    const ep    = document.getElementById('ai_api_endpoint');
    const hint  = document.getElementById('endpoint-hint');
    const known = ENDPOINTS[provider];
    if (known) {
        ep.value = known;
        hint.textContent = AI_ADMIN_LANG === 'ar'
            ? 'تم ضبط نقطة النهاية تلقائياً لـ ' + provider + '.'
            : 'Endpoint auto-set for ' + provider + '.';
    } else {
        hint.textContent = AI_ADMIN_LANG === 'ar'
            ? 'أدخل عنوان نقطة النهاية الخاصة بك.'
            : 'Enter your custom endpoint URL.';
    }
}

// ----- إظهار/إخفاء مفتاح API -----
function toggleKeyVisibility() {
    const input = document.getElementById('ai_api_key');
    const icon  = document.getElementById('key-eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        input.type = 'password';
        icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    }
}

// ----- اختبار الاتصال -----
document.getElementById('ai-test-btn').addEventListener('click', function () {
    const btn    = this;
    const result = document.getElementById('ai-test-result');

    btn.disabled = true;
    btn.innerHTML = `
        <svg class="btn-icon ai-spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/>
            <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/>
            <line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/>
            <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>
        </svg>
        ${AI_ADMIN_LANG === 'ar' ? 'جاري الاختبار...' : 'Testing...'}`;

    result.style.display = 'none';

    fetch('/ai-assistant/handlers/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ action: 'ping' }),
    })
    .then(r => r.json())
    .then(data => {
        const isKeySet = data.data && data.data.api_key_set;
        const model    = <?php echo json_encode($currentSettings['ai_model']); ?>;

        if (isKeySet) {
            result.innerHTML = `
                <div class="ai-test-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <div>
                        <strong>${AI_ADMIN_LANG === 'ar' ? 'الاتصال يعمل ✓' : 'Connection OK ✓'}</strong><br>
                        <small style="opacity:0.8;">${AI_ADMIN_LANG === 'ar' ? 'النموذج:' : 'Model:'} ${model}</small>
                    </div>
                </div>`;
        } else {
            result.innerHTML = `
                <div class="ai-test-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>
                        <strong>${AI_ADMIN_LANG === 'ar' ? 'مفتاح API غير محدد' : 'API Key not set'}</strong><br>
                        <small>${AI_ADMIN_LANG === 'ar' ? 'أدخل مفتاح API وأعد الاختبار.' : 'Please enter an API key and try again.'}</small>
                    </div>
                </div>`;
        }
        result.style.display = 'block';
        result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(() => {
        result.innerHTML = `
            <div class="ai-test-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
                    <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/>
                    <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                    <line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
                <strong>${AI_ADMIN_LANG === 'ar' ? 'تعذّر الاتصال بالخادم' : 'Could not reach server'}</strong>
            </div>`;
        result.style.display = 'block';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = `
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            ${AI_ADMIN_LANG === 'ar' ? 'اختبار الاتصال' : 'Test Connection'}`;
    });
});

const AI_DEFAULT_PROMPT = <?php echo json_encode(AI_DEFAULT_SYSTEM_PROMPT, JSON_UNESCAPED_UNICODE); ?>;

function resetSystemPromptToDefault() {
    const confirmMsg = AI_ADMIN_LANG === 'ar'
        ? 'هل أنت متأكد من رغبتك في استعادة التوجيه الافتراضي للمساعد؟ سيؤدي ذلك لمسح أي تعديلات قمت بها.'
        : 'Are you sure you want to reset the assistant prompt to default? This will overwrite any changes.';

    if (confirm(confirmMsg)) {
        document.getElementById('ai_system_prompt').value = AI_DEFAULT_PROMPT;
    }
}

const AI_DEFAULT_KNOWLEDGE = <?php echo json_encode($rawKnowledgeDefault, JSON_UNESCAPED_UNICODE); ?>;

function resetKnowledgeToDefault() {
    const confirmMsg = AI_ADMIN_LANG === 'ar'
        ? 'هل أنت متأكد من رغبتك في استعادة محتوى قاعدة المعرفة الافتراضي؟ سيؤدي ذلك لمسح كافة التعديلات المدخلة.'
        : 'Are you sure you want to reset the knowledge base content to default? This will overwrite all custom inputs.';

    if (confirm(confirmMsg)) {
        document.getElementById('ai_knowledge_base').value = AI_DEFAULT_KNOWLEDGE;
    }
}
</script>

<style>
.ai-spin-icon { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
