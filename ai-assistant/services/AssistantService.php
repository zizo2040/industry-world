<?php
/**
 * AI Assistant - Core Service
 *
 * الخدمة الرئيسية للمساعد. تشمل:
 * - إدارة الجلسة والسياق
 * - التحقق من الإدخال
 * - Rate Limiting
 * - تسجيل الأخطاء
 * - التواصل مع ApiClient لإرسال الرسائل وأستقبال الردود
 */

if (!defined('BASE_PATH')) exit;

class AssistantService
{
    // --------------------------------------------------------
    // Session Management
    // --------------------------------------------------------

    /**
     * تهيئة جلسة المحادثة إن لم تكن موجودة.
     */
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['ai_conversation'])) {
            $_SESSION['ai_conversation'] = [];
        }

        if (!isset($_SESSION['ai_session_start'])) {
            $_SESSION['ai_session_start'] = time();
        }

        // إعادة تعيين الجلسة إذا انتهت صلاحيتها
        if (time() - $_SESSION['ai_session_start'] > AI_SESSION_LIFETIME) {
            self::clearSession();
        }
    }

    /**
     * مسح جلسة المحادثة.
     */
    public static function clearSession(): void
    {
        $_SESSION['ai_conversation'] = [];
        $_SESSION['ai_session_start'] = time();
    }

    /**
     * الحصول على سياق المحادثة الحالي.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public static function getConversationHistory(): array
    {
        return $_SESSION['ai_conversation'] ?? [];
    }

    /**
     * إضافة رسالة إلى سياق المحادثة مع الحفاظ على الحد الأقصى.
     *
     * @param string $role    'user' أو 'assistant'
     * @param string $content محتوى الرسالة
     */
    public static function appendMessage(string $role, string $content): void
    {
        $_SESSION['ai_conversation'][] = [
            'role'    => $role,
            'content' => $content,
        ];

        // الحفاظ على آخر N رسالة فقط (FIFO)
        $max = AI_MAX_CONTEXT_MESSAGES;
        if (count($_SESSION['ai_conversation']) > $max) {
            $_SESSION['ai_conversation'] = array_slice(
                $_SESSION['ai_conversation'],
                -$max,
                $max,
                true
            );
        }
    }

    // --------------------------------------------------------
    // Input Validation
    // --------------------------------------------------------

    /**
     * التحقق من صحة رسالة المستخدم وتنظيفها.
     *
     * @param  mixed $input
     * @return array{valid: bool, message: string, error?: string}
     */
    public static function validateUserInput($input): array
    {
        if (!is_string($input)) {
            return ['valid' => false, 'message' => '', 'error' => 'invalid_input'];
        }

        $message = trim($input);

        if ($message === '') {
            return ['valid' => false, 'message' => '', 'error' => 'empty_message'];
        }

        if (mb_strlen($message) > AI_MAX_USER_MESSAGE_LENGTH) {
            return [
                'valid'   => false,
                'message' => '',
                'error'   => 'message_too_long',
            ];
        }

        return ['valid' => true, 'message' => $message];
    }

    // --------------------------------------------------------
    // Rate Limiting
    // --------------------------------------------------------

    /**
     * التحقق مما إذا كان المستخدم تجاوز حد الطلبات.
     *
     * @return bool true إذا كان مسموحاً بالطلب، false إذا تجاوز الحد
     */
    public static function checkRateLimit(): bool
    {
        $key    = 'ai_rate_' . self::getClientIdentifier();
        $window = AI_RATE_LIMIT_WINDOW;
        $limit  = AI_RATE_LIMIT;

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'window_start' => time()];
        }

        $data = &$_SESSION[$key];

        // إعادة تعيين النافذة إذا انتهت
        if (time() - $data['window_start'] >= $window) {
            $data['count']        = 0;
            $data['window_start'] = time();
        }

        if ($data['count'] >= $limit) {
            return false;
        }

        $data['count']++;
        return true;
    }

    /**
     * معرّف فريد للعميل (للـ Rate Limiting).
     */
    private static function getClientIdentifier(): string
    {
        return md5(
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown') .
            ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
    }

    // --------------------------------------------------------
    // Error Logging
    // --------------------------------------------------------

    /**
     * تسجيل خطأ في ملف السجل.
     *
     * @param string $message  رسالة الخطأ
     * @param array  $context  بيانات سياقية إضافية
     */
    public static function logError(string $message, array $context = []): void
    {
        if (!AI_LOG_ERRORS) {
            return;
        }

        $logDir = dirname(AI_LOG_PATH);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] [ERROR] %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            empty($context) ? '' : json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        file_put_contents(AI_LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * تسجيل معلومة في ملف السجل.
     *
     * @param string $message رسالة
     * @param array  $context بيانات سياقية
     */
    public static function logInfo(string $message, array $context = []): void
    {
        if (!AI_LOG_ERRORS) {
            return;
        }

        $logDir = dirname(AI_LOG_PATH);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] [INFO]  %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            empty($context) ? '' : json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        file_put_contents(AI_LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
    }

    // --------------------------------------------------------
    // API Communication
    // --------------------------------------------------------

    /**
     * إرسال رسائل المحادثة إلى نموذج الذكاء الاصطناعي والحصول على الرد.
     *
     * يقوم بـ:
     * 1. حقن system prompt (سيُملأ في المرحلة السادسة)
     * 2. تمرير سياق المحادثة الكامل إلى ApiClient
     * 3. حفظ الرد في الجلسة
     * 4. إعادة النص أو رسالة خطأ واضحة
     *
     * @param  string $userMessage  رسالة المستخدم المُتحقَّق منها
     * @param  string $systemPrompt System prompt (فارغ حتى المرحلة السادسة)
     * @return array{success: bool, reply: string, errorCode?: string}
     */
    public static function chat(string $userMessage, string $systemPrompt = '', string $lang = 'ar'): array
    {
        // حفظ رسالة المستخدم في السياق
        self::appendMessage('user', $userMessage);

        // جلب السياق من الـ RAG ديناميكياً بناءً على رسالة العميل الحالية
        $ragContext = self::retrieveRelevantContext($userMessage, $lang);
        $finalPrompt = $systemPrompt . $ragContext;

        // بناء السياق الكامل لإرساله
        $history = self::getConversationHistory();

        // إرسال إلى API
        $result = ApiClient::sendMessages($history, $finalPrompt);

        if (!$result->success) {
            // في حالة عدم ضبط API Key أو تعثر الاتصال: نعتمد محرك الإجابات الذكي المحلي لضمان استمرارية الخدمة
            $offlineResponse = self::getSmartOfflineResponse($userMessage, $lang);

            self::logInfo('API unavailable - Served smart offline response', [
                'code' => $result->errorCode,
                'msg'  => $result->errorMsg,
            ]);

            // حفظ رد المساعد في السياق
            self::appendMessage('assistant', $offlineResponse);

            return [
                'success' => true,
                'reply'   => $offlineResponse,
            ];
        }

        // حفظ رد المساعد في السياق
        self::appendMessage('assistant', $result->content);

        self::logInfo('Chat success', ['response_length' => mb_strlen($result->content)]);

        return [
            'success' => true,
            'reply'   => $result->content,
        ];
    }

    /**
     * إزالة آخر رسالة مستخدم من السياق (تُستخدم عند فشل API).
     */
    private static function removeLastUserMessage(): void
    {
        if (empty($_SESSION['ai_conversation'])) {
            return;
        }

        $last = end($_SESSION['ai_conversation']);
        if ($last && $last['role'] === 'user') {
            array_pop($_SESSION['ai_conversation']);
        }
    }

    /**
     * محرك الإجابات الذكي المحلي (Smart Offline Engine)
     * يعمل لتزويد العميل بإجابات دقيقة واحترافية عند عدم تفعيل مفتاح API أو حدوث مشكلة بالاتصال.
     */
    public static function getSmartOfflineResponse(string $userMessage, string $lang = 'ar'): string
    {
        $msg = mb_strtolower(trim($userMessage));
        $isAr = ($lang === 'ar');

        // 1. العناوين والفروع والفرع الرئيسي
        if (preg_match('/(عنوان|موقع|فرع|فروع|مكان|وين|فين|توجدون|الحوبان|تعز|location|address|branch|where)/u', $msg)) {
            return $isAr
                ? "📍 **عنوان مقر وفروع شركة عالم الصناعة:**\n\n" .
                  "🏢 **المقر الرئيسي:** اليمن - تعز - الحوبان - مقابل البيت الحديث.\n\n" .
                  "يسعدنا زيارتكم في معرضنا للاطلاع على أحدث المنتجات الكهربائية والمستلزمات الصناعية. يمكنك أيضاً استكشاف خريطة الموقع عبر صفحة [اتصل بنا](/contact.php)."
                : "📍 **Location & Branch Address:**\n\n" .
                  "🏢 **Headquarters:** Yemen - Taiz - Al-Hawban - Opposite Al-Bayt Al-Hadeeth.\n\n" .
                  "We look forward to your visit! You can also view our map location on the [Contact Us](/contact.php) page.";
        }

        // 2. التواصل والواتساب والمبيعات
        if (preg_match('/(تواصل|اتصل|واتس|واتساب|هاتف|رقم|أرقام|مبيعات|خدمة العملاء|contact|whatsapp|phone|call|sales)/u', $msg)) {
            return $isAr
                ? "📞 **أرقام التواصل المباشر مع قسم المبيعات والدعم الفني:**\n\n" .
                  "- 📱 **واتساب / هاتف:** 781067910\n" .
                  "- 📱 **واتساب / هاتف:** 734009848\n" .
                  "- 📱 **واتساب / هاتف:** 783678560\n\n" .
                  "يمكنك أيضاً تعبئة نموذج التواصل المباشر من خلال صفحة [اتصل بنا](/contact.php)."
                : "📞 **Direct Contact & WhatsApp Numbers:**\n\n" .
                  "- 📱 **WhatsApp / Phone:** 781067910\n" .
                  "- 📱 **WhatsApp / Phone:** 734009848\n" .
                  "- 📱 **WhatsApp / Phone:** 783678560\n\n" .
                  "You can also submit an inquiry directly via the [Contact Us](/contact.php) page.";
        }

        // 3. الأسعار وعروض الأسعار
        if (preg_match('/(سعر|أسعار|اسعار|تسعيرة|عرض سعر|شراء|طلب|كمية|ثمن|price|quote|buy|cost)/u', $msg)) {
            return $isAr
                ? "📋 **طلب عرض سعر أو شراء منتجات:**\n\n" .
                  "للحصول على أفضل عرض سعر تنافسي للكميات والمشاريع الكهربائية والصناعية:\n" .
                  "1. تصفح المنتجات واختيار الصنف المطلوب عبر [صفحة المنتجات](/products.php).\n" .
                  "2. التواصل المباشر مع مبيعاتنا عبر الواتساب على الرقم: **781067910** مع إرسال قائمة الكميات المطلوبة.\n" .
                  "3. أو مراسلتنا وتعبئة نموذج الطلب عبر صفحة [اتصل بنا](/contact.php)."
                : "📋 **Requesting a Price Quote or Purchase:**\n\n" .
                  "To get competitive quotes for electrical and industrial supplies:\n" .
                  "1. Browse our catalog on the [Products Page](/products.php).\n" .
                  "2. Send your list of required items via WhatsApp to: **781067910**.\n" .
                  "3. Or submit a formal quote request through [Contact Us](/contact.php).";
        }

        // 4. ساعات العمل والدوام
        if (preg_match('/(ساعات|مواعيد|أوقات|دوام|مفتوح|متى|hours|open|timing|time)/u', $msg)) {
            return $isAr
                ? "⏰ **مواعيد وساعات العمل الرسمية:**\n\n" .
                  "📅 **من السبت إلى الخميس:** 8:00 صباحاً - 8:00 مساءً\n" .
                  "🚫 **الجمعة:** إجازة أسبوعية\n\n" .
                  "فريق الدعم والمبيعات جاهز لاستقبال طلباتكم عبر الواتساب على مدار الساعة!"
                : "⏰ **Official Business Hours:**\n\n" .
                  "📅 **Saturday to Thursday:** 8:00 AM - 8:00 PM\n" .
                  "🚫 **Friday:** Closed\n\n" .
                  "Our sales team receives online requests via WhatsApp 24/7!";
        }

        // 5. المنتجات والتصنيفات
        if (preg_match('/(منتج|منتجات|فئة|فئات|قواطع|محولات|طاقة|شمسية|انفرتر|موتور|محرك|كابلات|اسلاك|مستلزمات|product|category|catalog)/u', $msg)) {
            return $isAr
                ? "⚡ **المنتجات والتصنيفات المتوفرة في عالم الصناعة:**\n\n" .
                  "نوفر مجموعة واسعة من المعدات الكهربائية والصناعية ذات الجودة العالية:\n" .
                  "- 🔹 **أنظمة وقواطع التحكم الكهربائي** (Circuit Breakers)\n" .
                  "- 🔹 **المحركات الكهربائية والمحولات** (Motors & Transformers)\n" .
                  "- 🔹 **أنظمة الطاقة الشمسية ومغيرات السرعة (Inverters)**\n" .
                  "- 🔹 **الكابلات والأسلاك ومستلزمات التمديدات الصناعية**\n\n" .
                  "استعرض جميع المنتجات والتصنيفات عبر قسم [المنتجات](/products.php)."
                : "⚡ **Available Products & Categories:**\n\n" .
                  "We supply comprehensive high-quality electrical & industrial products:\n" .
                  "- 🔹 Circuit Breakers & Switchgear\n" .
                  "- 🔹 Industrial Electric Motors & Transformers\n" .
                  "- 🔹 Solar Energy Systems & Frequency Inverters\n" .
                  "- 🔹 Industrial Cables & Wiring Accessories\n\n" .
                  "Explore the full catalog on our [Products Page](/products.php).";
        }

        // 6. الخدمات
        if (preg_match('/(خدمة|خدمات|تركيب|صيانة|استشارات|تدريب|شحن|توصيل|service|installation|maintenance)/u', $msg)) {
            return $isAr
                ? "🛠️ **الخدمات الفنية التي تقدمها الشركة:**\n\n" .
                  "- 💡 **الاستشارات الهندسية والفنية** لاختيار المنظومات المناسبة.\n" .
                  "- ⚙️ **الدعم الفني والتركيب والتشغيل** للأنظمة والمعدات.\n" .
                  "- 🔧 **خدمات الصيانة وتوفير قطع الغيار الأصلية**.\n\n" .
                  "تعرّف على كافة خدماتنا بالتفصيل في قسم [خدماتنا](/services.php)."
                : "🛠️ **Our Professional Technical Services:**\n\n" .
                  "- 💡 Engineering & Technical Consultation\n" .
                  "- ⚙️ Installation & Commissioning Support\n" .
                  "- 🔧 Maintenance & Original Spare Parts Supply\n\n" .
                  "Discover more details on our [Services Page](/services.php).";
        }

        // 7. من نحن / معلومات عن الشركة
        if (preg_match('/(من نحن|شركة|خبرة|عن الشركة|عالم الصناعة|about|company)/u', $msg)) {
            return $isAr
                ? "🏢 **عن شركة عالم الصناعة للكهرباء والمستلزمات الصناعية:**\n\n" .
                  "شريككم الموثوق في الحلول الكهربائية والصناعية المتكاملة في اليمن. نتميز بتوفير أحدث المنتجات والحلول الهندسية المعتمدة بأسعار تنافسية ودعم فني متميز.\n\n" .
                  "اقرأ المزيد عن رؤيتنا وقيمنا في صفحة [من نحن](/about.php)."
                : "🏢 **About Alam Al-Sinaa:**\n\n" .
                  "Your trusted partner for integrated electrical and industrial solutions in Yemen. We deliver certified products, engineering solutions, and dedicated technical support.\n\n" .
                  "Learn more on our [About Us](/about.php) page.";
        }

        // 8. الإجابة الافتراضية العامة
        return $isAr
            ? "أهلاً بك! أنا المساعد الذكي لشركة **عالم الصناعة للكهرباء والمستلزمات الصناعية** ⚡\n\n" .
              "كيف يمكنني مساعدتك اليوم؟\n" .
              "- 📦 استعراض [كتالوج المنتجات](/products.php)\n" .
              "- 🛠️ الاطلاع على [الخدمات الفنية](/services.php)\n" .
              "- 📍 التعرف على [عناوين الفروع](/contact.php)\n" .
              "- 📞 التواصل المباشر مع المبيعات عبر الواتساب: **781067910**"
            : "Welcome! I am the Smart Assistant for **Alam Al-Sinaa for Electrical & Industrial Supplies** ⚡\n\n" .
              "How can I assist you today?\n" .
              "- 📦 Browse our [Products Catalog](/products.php)\n" .
              "- 🛠️ Check our [Technical Services](/services.php)\n" .
              "- 📍 View [Branch Location](/contact.php)\n" .
              "- 📞 Direct Sales WhatsApp: **781067910**";
    }

    // ============================================================
    // RAG Search & Retrieval (Phase 9)
    // ============================================================

    /**
     * استخلاص الكلمات المفتاحية الهامة للبحث من رسالة المستخدم.
     */
    private static function extractKeywords(string $text): array
    {
        // إزالة الحركات وعلامات الترقيم والأحرف الخاصة
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $cleaned = mb_strtolower($cleaned);

        $words = preg_split('/\s+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

        // قائمة الكلمات الوظيفية (Stop Words) التي يجب تصفيتها
        $stopWords = [
            'هل', 'ما', 'من', 'في', 'على', 'إلى', 'عن', 'هو', 'هي', 'هم', 'هذا', 'هذه', 'أو', 'و', 'ثم', 
            'لا', 'نعم', 'مع', 'حيث', 'كيف', 'متى', 'أين', 'أريد', 'شراء', 'طلب', 'يا', 'حكم', 'ان', 'أن',
            'لكن', 'كان', 'كانت', 'كل', 'بعض', 'تم', 'عن', 'عند', 'بين', 'التي', 'الذي', 'الذين', 'الموقع',
            'مساعدة', 'من فضلك', 'لو سمحت', 'ممكن', 'تفضل', 'please', 'want', 'buy', 'how', 'where', 'is', 'the'
        ];

        $keywords = [];
        foreach ($words as $w) {
            if (mb_strlen($w) > 1 && !in_array($w, $stopWords, true)) {
                $keywords[] = $w;
            }
        }

        return $keywords;
    }

    /**
     * البحث داخل المنتجات في قاعدة البيانات.
     */
    private static function searchProducts(array $keywords, string $lang): string
    {
        if (empty($keywords)) return '';

        $isAr = ($lang === 'ar');
        $params = [];
        $conditions = [];

        foreach ($keywords as $idx => $kw) {
            $key = "p_kw_{$idx}";
            $conditions[] = "(p.name_{$lang} LIKE :{$key} OR p.description_{$lang} LIKE :{$key} OR p.short_description_{$lang} LIKE :{$key})";
            $params[$key] = "%{$kw}%";
        }

        $sql = "SELECT p.name_{$lang} as name, p.slug, p.price, p.currency, p.stock_status, c.name_{$lang} as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 AND p.deleted_at IS NULL AND (" . implode(' OR ', $conditions) . ")
                LIMIT 5";

        try {
            $products = Database::fetchAll($sql, $params);
            if (empty($products)) return '';

            $out = $isAr ? "\n=== منتجات ذات صلة متوفرة في المتجر ===\n" : "\n=== Related Products in Store ===\n";
            foreach ($products as $p) {
                $priceStr = $p['price'] ? " (السعر: " . number_format($p['price'], 2) . " " . ($p['currency'] ?: 'YER') . ")" : "";
                $stockStr = $p['stock_status'] === 'in_stock' 
                    ? ($isAr ? ' [متوفر]' : ' [In Stock]') 
                    : ($isAr ? ' [غير متوفر حالياً]' : ' [Out of Stock]');

                $out .= "- [{$p['name']}](/product.php?slug={$p['slug']}){$priceStr}{$stockStr} - القسم: {$p['category_name']}\n";
            }
            return $out;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * البحث داخل صفحات إدارة المحتوى (CMS Pages) في قاعدة البيانات.
     */
    private static function searchCmsPages(array $keywords, string $lang): string
    {
        if (empty($keywords)) return '';

        $isAr = ($lang === 'ar');
        $params = [];
        $conditions = [];

        foreach ($keywords as $idx => $kw) {
            $key = "cms_kw_{$idx}";
            $conditions[] = "(title_{$lang} LIKE :{$key} OR content_{$lang} LIKE :{$key} OR excerpt_{$lang} LIKE :{$key})";
            $params[$key] = "%{$kw}%";
        }

        $sql = "SELECT title_{$lang} as title, page_key, excerpt_{$lang} as excerpt
                FROM cms_pages
                WHERE is_active = 1 AND (" . implode(' OR ', $conditions) . ")
                LIMIT 2";

        try {
            $pages = Database::fetchAll($sql, $params);
            if (empty($pages)) return '';

            $out = $isAr ? "\n=== معلومات من صفحات الموقع ===\n" : "\n=== Info from CMS Pages ===\n";
            foreach ($pages as $p) {
                $url = "/{$p['page_key']}.php";
                if ($p['page_key'] === 'home') $url = '/';
                $out .= "- [{$p['title']}]({$url}): " . ($p['excerpt'] ? strip_tags($p['excerpt']) : '') . "\n";
            }
            return $out;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * البحث داخل ملف المعرفة الموحد (knowledge.md).
     */
    private static function searchKnowledgeFile(array $keywords, string $lang): string
    {
        // محاولة القراءة من قاعدة البيانات أولاً
        $content = '';
        if (function_exists('getSetting')) {
            $dbVal = getSetting('ai_knowledge_base');
            if ($dbVal !== null && trim($dbVal) !== '') {
                $content = $dbVal;
            }
        }

        // Fallback للملف الفيزيائي إذا كان الحقل فارغاً
        if (empty($content)) {
            $filePath = BASE_PATH . '/ai-assistant/data/knowledge.md';
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
            }
        }

        if (empty($content)) return '';

        // تقسيم الملف إلى فقرات أو عناوين (##)
        $sections = preg_split('/(?=\n## )/u', $content);
        $matchedSections = [];

        foreach ($sections as $section) {
            $score = 0;
            foreach ($keywords as $kw) {
                // زيادة وزن الكلمة إذا وجدت في عنوان القسم
                $firstLine = strtok($section, "\n");
                if (mb_stripos($firstLine, $kw) !== false) {
                    $score += 5;
                }
                // وزن أقل إذا كانت في متن القسم
                if (mb_stripos($section, $kw) !== false) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $matchedSections[] = [
                    'content' => trim($section),
                    'score'   => $score
                ];
            }
        }

        if (empty($matchedSections)) return '';

        // ترتيب المقاطع تنازلياً حسب درجة المطابقة
        usort($matchedSections, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // استرجاع أفضل مقطعين فقط لعدم تجاوز سقف الـ tokens
        $topSections = array_slice($matchedSections, 0, 2);

        $isAr = ($lang === 'ar');
        $out = $isAr ? "\n=== معلومات مستخلصة من وثيقة المعرفة ===\n" : "\n=== Extracted Knowledge ===\n";
        foreach ($topSections as $sec) {
            $out .= $sec['content'] . "\n\n";
        }

        return $out;
    }

    /**
     * محرك الـ RAG الرئيسي لجلب السياق الإضافي للمحادثة بناءً على سؤال المستخدم.
     */
    public static function retrieveRelevantContext(string $userMessage, string $lang = 'ar'): string
    {
        $keywords = self::extractKeywords($userMessage);
        if (empty($keywords)) return '';

        $productsContext  = self::searchProducts($keywords, $lang);
        $cmsContext       = self::searchCmsPages($keywords, $lang);
        $knowledgeContext = self::searchKnowledgeFile($keywords, $lang);

        $ragBlock = "\n\n" . "=== معلومات مسترجعة ديناميكياً ذات صلة بسؤال العميل (اعتمِد عليها للإجابة) ===" . "\n";
        $ragBlock .= $productsContext;
        $ragBlock .= $cmsContext;
        $ragBlock .= $knowledgeContext;

        // إرجاع الـ block فقط إذا كان يحتوي على معلومات فعلية مسترجعة
        if (trim($productsContext) === '' && trim($cmsContext) === '' && trim($knowledgeContext) === '') {
            return '';
        }

        return $ragBlock;
    }


    /**
     * جلب بنية الموقع والتصنيفات النشطة من قاعدة البيانات
     * وتنسيقها كـ Context ليتم حقنه في الـ system prompt.
     *
     * @param string $lang اللغة الحالية للمستخدم
     * @return string سياق بنية الموقع
     */
    public static function getSiteStructureContext(string $lang = 'ar'): string
    {
        $isAr = ($lang === 'ar');

        // 1. الصفحات الرئيسية للموقع
        $pages = [
            $isAr ? 'الرئيسية' : 'Home' => '/',
            $isAr ? 'من نحن' : 'About Us' => '/about.php',
            $isAr ? 'خدماتنا' : 'Our Services' => '/services.php',
            $isAr ? 'المنتجات' : 'Products' => '/products.php',
            $isAr ? 'معرض الأعمال' : 'Portfolio' => '/portfolio.php',
            $isAr ? 'اتصل بنا' : 'Contact Us' => '/contact.php',
            $isAr ? 'الأسئلة الشائعة' : 'FAQ' => '/faq.php',
        ];

        $context = "\n\n=== بنية الموقع وروابط الصفحات الرئيسية ===\n";
        $context .= $isAr 
            ? "عند توجيه المستخدم لأي صفحة، استخدم التنسيق [اسم الصفحة](الرابط) بدقة:\n"
            : "When directing users, use exactly [Page Name](link) format:\n";

        foreach ($pages as $name => $url) {
            $context .= "- {$name}: {$url}\n";
        }

        // 2. التصنيفات النشطة من قاعدة البيانات
        $categories = [];
        try {
            $categories = Database::fetchAll(
                "SELECT name_{$lang} as name, slug FROM categories WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order ASC"
            );
        } catch (Exception $e) {
            // تجاهل الخطأ في حال عدم وجود الجدول لتفادي كسر التطبيق
        }

        if (!empty($categories)) {
            $context .= $isAr 
                ? "\n=== تصنيفات المنتجات النشطة وروابطها ===\nوجه المستخدم للقسم المناسب باستخدام التنسيق [اسم القسم](/products.php?category=الاسم):\n"
                : "\n=== Active Product Categories & Links ===\nDirect users using [Category Name](/products.php?category=slug) format:\n";

            foreach ($categories as $cat) {
                $context .= "- {$cat['name']}: /products.php?category={$cat['slug']}\n";
            }
        }

        // 3. الخدمات المتاحة
        $services = [
            $isAr ? 'الاستشارات الفنية' : 'Technical Consultations' => '/services.php',
            $isAr ? 'التركيب والتشغيل' : 'Installation & Commissioning' => '/services.php',
            $isAr ? 'الصيانة الدورية' : 'Periodic Maintenance' => '/services.php',
            $isAr ? 'قطع الغيار' : 'Spare Parts' => '/services.php',
            $isAr ? 'دراسة الجدوى' : 'Feasibility Studies' => '/services.php',
            $isAr ? 'التدريب الفني' : 'Technical Training' => '/services.php',
        ];

        $context .= $isAr 
            ? "\n=== الخدمات المتاحة وروابطها ===\n"
            : "\n=== Available Services & Links ===\n";

        foreach ($services as $name => $url) {
            $context .= "- {$name}: {$url}\n";
        }

        $context .= "\n" . ($isAr 
            ? "تنبيه هام جداً: لا تخترع أي روابط غير موجودة أعلاه. استخدم الروابط النسبية المحددة فقط."
            : "IMPORTANT: Never invent links. Only use the relative links listed above.");

        return $context;
    }

    // --------------------------------------------------------
    // Response Helpers
    // --------------------------------------------------------

    /**
     * إرسال استجابة JSON وإنهاء التنفيذ.
     *
     * @param bool   $success نجاح الطلب أم لا
     * @param string $message رسالة للعرض
     * @param array  $data    بيانات إضافية اختيارية
     * @param int    $code    HTTP status code
     */
    public static function jsonResponse(
        bool   $success,
        string $message,
        array  $data = [],
        int    $code = 200
    ): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
