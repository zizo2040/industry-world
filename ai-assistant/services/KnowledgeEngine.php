<?php
/**
 * AI Assistant — Local Knowledge Engine
 * ============================================================
 * Answers visitors WITHOUT any external AI API (used when no API key is
 * configured, or when the provider is down). It is also used to build the
 * retrieval context (RAG) that is sent to the AI provider when one IS set.
 *
 * - Arabic-aware normalisation & light stemming ("المولدات" → "مولد")
 * - Domain synonyms (انفرتر = عاكس = inverter, طبلون = لوحة كهرباء, …)
 * - Intent detection (location, hours, contact, price, delivery, warranty,
 *   payment, services, sizing help, greeting, thanks…)
 * - Live search in categories + products (with links and stock status)
 * - Search inside the editable knowledge base (Admin › AI Assistant)
 * - Page awareness: "كم سعر هذا؟" on a product page refers to that product
 * All company facts come from site_settings, so editing them in the admin
 * panel updates the assistant automatically.
 */

if (!defined('BASE_PATH')) exit;

class KnowledgeEngine
{
    /** Domain synonyms: every word in a group expands to the whole group */
    private const SYNONYMS = [
        ['انفرتر', 'انفرترات', 'عاكس', 'عواكس', 'inverter', 'inverters', 'محول تردد', 'vfd', 'درايف', 'drive'],
        ['اونجريد', 'اون جريد', 'on-grid', 'ongrid', 'on grid'],
        ['اوفجريد', 'اوف جريد', 'off-grid', 'offgrid', 'off grid'],
        ['هايبرد', 'هيبرد', 'هجين', 'hybrid'],
        ['شمسي', 'شمسية', 'شمسيه', 'الواح', 'ألواح', 'لوح', 'منظومة', 'منظومات', 'مضمومات', 'solar', 'panel', 'panels', 'pv'],
        ['مولد', 'مولدات', 'ماطور', 'مواطير', 'دينمو', 'generator', 'generators', 'genset'],
        ['منظم', 'منظمات', 'ستبلايزر', 'استبلايزر', 'stabilizer', 'regulator', 'avr'],
        ['كيبل', 'كيبلات', 'كابل', 'كابلات', 'سلك', 'اسلاك', 'أسلاك', 'cable', 'cables', 'wire'],
        ['طبلون', 'طبلونات', 'لوحة', 'لوحات', 'panel board', 'switchboard', 'distribution board'],
        ['قاطع', 'قواطع', 'مفتاح', 'مفاتيح', 'بريكر', 'breaker', 'switch', 'switches', 'mcb', 'mccb', 'كونتاكتور', 'contactor'],
        ['plc', 'بي ال سي', 'تحكم', 'controller', 'automation', 'اتمتة', 'أتمتة'],
        ['حساس', 'حساسات', 'سنسر', 'sensor', 'sensors', 'ثرموستات', 'thermostat', 'ثيرموكبل', 'thermocouple'],
        ['قياس', 'افوميتر', 'أفوميتر', 'ملتيميتر', 'multimeter', 'كلامب', 'clamp', 'meter', 'meters', 'عداد', 'عدادات'],
        ['تبريد', 'ثلاجة', 'ثلاجات', 'كمبروسر', 'كمبريسور', 'compressor', 'refrigeration', 'cooling', 'فريون'],
        ['سخان', 'سخانات', 'هيتر', 'heater', 'heaters', 'مقاومة', 'مقاومات', 'heating element'],
        ['اضاءة', 'إضاءة', 'انارة', 'إنارة', 'كشاف', 'كشافات', 'لمبة', 'لمبات', 'led', 'lighting', 'floodlight'],
        ['بلاستيك', 'نايلون', 'فلم', 'اكياس', 'أكياس', 'plastic', 'film', 'bags', 'extruder'],
        ['تعبئة', 'تعبئه', 'تغليف', 'packaging', 'filling'],
        ['مخبز', 'مخابز', 'فرن', 'افران', 'أفران', 'bakery', 'oven', 'ovens'],
        ['بلك', 'بلوك', 'block', 'blocks', 'اسمنت', 'أسمنت', 'cement', 'كسارة', 'كسارات', 'crusher'],
        ['محرك', 'محركات', 'موتور', 'مواتير', 'motor', 'motors'],
        ['سلامة', 'سلامه', 'امان', 'أمان', 'safety', 'خوذة', 'قفازات'],
        ['الكترونيات', 'إلكترونيات', 'الكترونية', 'electronics', 'electronic', 'كروت', 'كرت', 'بورد'],
        ['زراعي', 'زراعية', 'زراعيه', 'مضخة', 'مضخات', 'غطاس', 'غطاسات', 'agricultural', 'pump', 'pumps'],
        ['قطع غيار', 'قطع', 'spare', 'spares', 'parts', 'فلتر', 'فلاتر', 'filter'],
    ];

    private const STOP_WORDS = [
        'هل', 'ما', 'ماذا', 'من', 'في', 'على', 'الى', 'إلى', 'عن', 'هو', 'هي', 'هذا', 'هذه', 'ذلك', 'او', 'أو', 'و', 'ثم',
        'لا', 'نعم', 'مع', 'كيف', 'متى', 'اين', 'أين', 'وين', 'فين', 'اريد', 'أريد', 'ابغى', 'ابي', 'عايز', 'عندكم', 'لديكم', 'يوجد',
        'فيه', 'في', 'كم', 'لو', 'سمحت', 'ممكن', 'تفضل', 'لي', 'انا', 'أنا', 'انت', 'عندك', 'شي', 'شيء', 'اي', 'أي', 'كل',
        'the', 'a', 'an', 'is', 'are', 'do', 'you', 'have', 'i', 'me', 'my', 'want', 'need', 'please', 'how', 'what', 'where',
        'when', 'can', 'for', 'of', 'to', 'in', 'on', 'and', 'or', 'any', 'your', 'it', 'this',
    ];

    // ============================================================
    // Public API
    // ============================================================

    /**
     * Full local answer (no external AI).
     * @param array $ctx page context from the browser: page, section, product, category, url
     */
    public static function answer(string $message, string $lang = 'ar', array $ctx = []): string
    {
        $isAr   = $lang === 'ar';
        $norm   = self::normalize($message);
        $intent = self::detectIntent($norm);
        $terms  = self::terms($message);

        // "this product" on a product page → use the product's name
        $productCtx = trim((string) ($ctx['product'] ?? ''));
        $refersToPage = $productCtx !== '' && preg_match('/(هذا|هذه|هاذا|المنتج|القطعه|القطعة|الجهاز|this|it)\b/u', $norm);
        $productLimit = 5;
        if ($refersToPage || ($productCtx !== '' && in_array($intent, ['price', 'specs', 'stock', 'install'], true) && count($terms) <= 3)) {
            $terms = self::terms($productCtx);
            $productLimit = 1;
        }

        $parts = [];

        switch ($intent) {
            case 'greeting':
                return self::greeting($isAr, $ctx);
            case 'thanks':
                return $isAr
                    ? "العفو! 😊 سعيد بخدمتك. إذا احتجت أي قطعة كهربائية أو صناعية أنا هنا، أو تواصل مع المبيعات مباشرة: " . self::waLine($isAr)
                    : "You're welcome! 😊 I'm here whenever you need any electrical or industrial part. Sales WhatsApp: " . self::waLine($isAr);
            case 'location':
                $parts[] = self::locationBlock($isAr);
                break;
            case 'hours':
                $parts[] = self::hoursBlock($isAr);
                break;
            case 'contact':
                $parts[] = self::contactBlock($isAr);
                break;
            case 'delivery':
                $parts[] = $isAr
                    ? "🚚 **التوصيل والشحن:** نوصل الطلبات إلى **جميع المحافظات اليمنية** عبر شركات النقل المعتمدة، والاستلام متاح مباشرة من معرضنا في تعز - الحوبان. تكلفة ومدة الشحن تعتمد على المدينة وحجم الطلب — أرسل لنا اسم مدينتك والطلب لنؤكد لك التفاصيل."
                    : "🚚 **Delivery:** We ship to **all Yemeni governorates** via trusted carriers, or you can pick up from our showroom in Taiz - Al-Hawban. Cost & time depend on city and order size — send us your city and order for details.";
                break;
            case 'warranty':
                $parts[] = $isAr
                    ? "🛡️ **الضمان:** جميع منتجاتنا أصلية ومعها **ضمان الوكيل/المصنع**، وتختلف مدة الضمان حسب نوع المنتج والماركة (مثل الانفرترات والمولدات). احتفظ بفاتورة الشراء، وفريقنا الفني يتابع معك أي مشكلة بعد البيع."
                    : "🛡️ **Warranty:** All products are genuine and come with the **manufacturer/agent warranty**; the period depends on the product and brand. Keep your invoice — our technical team follows up on any after-sales issue.";
                break;
            case 'payment':
                $parts[] = $isAr
                    ? "💳 **طرق الدفع:** الدفع نقداً عند الاستلام من المعرض، أو تحويل عبر شركات الصرافة/الحوالات المحلية والبنوك للطلبات المرسلة للمحافظات. للمشاريع والمصانع يمكن الاتفاق على عرض سعر رسمي."
                    : "💳 **Payment:** Cash at our showroom, or local money-transfer/bank transfer for shipped orders. Projects and factories can request a formal quotation.";
                break;
            case 'services':
                $parts[] = self::servicesBlock($isAr);
                break;
            case 'sizing':
                $parts[] = self::sizingBlock($isAr, $norm);
                break;
            case 'about':
                $parts[] = self::aboutBlock($isAr);
                break;
            case 'catalog':
                $parts[] = self::categoriesBlock($isAr);
                break;
        }

        // Product / category retrieval for anything that looks like a product query
        if (!in_array($intent, ['location', 'hours', 'contact', 'about'], true) && !empty($terms)) {
            $cats  = $productLimit === 1 ? [] : self::findCategories($terms, $lang);
            $prods = self::findProducts($terms, $lang, $productLimit);

            if ($prods) {
                $parts[] = ($isAr ? "🔎 **منتجات مطابقة لطلبك:**\n" : "🔎 **Matching products:**\n") . self::formatProducts($prods, $isAr);
            }
            if ($cats) {
                $parts[] = ($isAr ? "📂 **أقسام ذات صلة:**\n" : "📂 **Related categories:**\n") . self::formatCategories($cats);
            }
            if (!$prods && !$cats && in_array($intent, ['price', 'stock', 'product', 'specs', 'install', null], true)) {
                $kb = self::searchKnowledge($terms, $lang, 1);
                if ($kb) {
                    $parts[] = self::cleanKnowledgeSnippet($kb[0]);
                } elseif ($intent !== null || count($terms) > 0) {
                    $parts[] = $isAr
                        ? "لم أجد هذا الصنف معروضاً في الموقع حالياً، لكن **كثير من الأصناف متوفرة في المخزن ولم تُضف بعد**. أرسل اسم القطعة أو صورتها أو رقم الموديل على واتساب وسنؤكد لك التوفر والسعر بسرعة."
                        : "I couldn't find that item listed on the site, but **many items are in stock and not listed yet**. Send the part name, photo or model number on WhatsApp and we'll confirm availability and price quickly.";
                }
            }
        }

        // Intent-specific closing lines
        if ($intent === 'price' || $intent === 'stock') {
            $parts[] = $isAr
                ? "💰 **الأسعار:** تتغير الأسعار حسب السوق والكمية، لذلك نرسل لك **أحدث سعر مباشرة** عبر واتساب: " . self::waLine($isAr) . ($productCtx ? "\nاذكر اسم المنتج: **{$productCtx}**" : '')
                : "💰 **Prices** change with the market and quantity, so we send you the **latest price directly** on WhatsApp: " . self::waLine($isAr) . ($productCtx ? "\nMention the product: **{$productCtx}**" : '');
        }
        if ($intent === 'install' || $intent === 'specs') {
            $parts[] = $isAr
                ? "⚠️ **تنبيه سلامة:** افصل التيار الكهربائي من القاطع الرئيسي قبل أي تركيب أو صيانة، واستعن بفني مختص. يمكن لفريقنا الفني إرشادك خطوة بخطوة عبر واتساب أو تنفيذ التركيب لك."
                : "⚠️ **Safety first:** switch off the main breaker before any installation or maintenance and use a qualified electrician. Our technicians can guide you step by step or do the installation.";
        }

        if (empty($parts)) {
            // Knowledge base as a last resort, then a helpful default
            $kb = self::searchKnowledge($terms, $lang, 1);
            if ($kb) {
                $parts[] = self::cleanKnowledgeSnippet($kb[0]);
            } else {
                return self::fallback($isAr, $ctx);
            }
        }

        if (!in_array($intent, ['contact', 'price', 'stock'], true)) {
            $parts[] = $isAr
                ? "📞 للطلب أو الاستفسار السريع: " . self::waLine($isAr)
                : "📞 For orders or quick questions: " . self::waLine($isAr);
        }

        return implode("\n\n", array_filter($parts));
    }

    /**
     * Retrieval block for the external AI provider (RAG).
     */
    public static function context(string $message, string $lang = 'ar', array $ctx = []): string
    {
        $isAr  = $lang === 'ar';
        $terms = self::terms($message);
        if (!empty($ctx['product'])) $terms = array_values(array_unique(array_merge($terms, self::terms($ctx['product']))));

        $out = '';
        $prods = $terms ? self::findProducts($terms, $lang) : [];
        if ($prods) $out .= "\n=== " . ($isAr ? 'منتجات من المتجر ذات صلة' : 'Related store products') . " ===\n" . self::formatProducts($prods, $isAr, true);
        $cats = $terms ? self::findCategories($terms, $lang) : [];
        if ($cats) $out .= "\n=== " . ($isAr ? 'أقسام ذات صلة' : 'Related categories') . " ===\n" . self::formatCategories($cats);
        foreach (self::searchKnowledge($terms, $lang, 2) as $sec) {
            $out .= "\n=== " . ($isAr ? 'من قاعدة المعرفة' : 'From the knowledge base') . " ===\n" . trim($sec) . "\n";
        }
        $out .= "\n=== " . ($isAr ? 'بيانات التواصل الرسمية' : 'Official contact data') . " ===\n" . self::contactBlock($isAr) . "\n" . self::locationBlock($isAr) . "\n" . self::hoursBlock($isAr) . "\n";

        return $out;
    }

    /**
     * Describes where the visitor is, so the AI can answer "this product" etc.
     */
    public static function pageContextPrompt(array $ctx, string $lang = 'ar'): string
    {
        $isAr = $lang === 'ar';
        $pages = [
            'home' => $isAr ? 'الصفحة الرئيسية' : 'Home page', 'about' => $isAr ? 'من نحن' : 'About',
            'products' => $isAr ? 'صفحة المنتجات' : 'Products listing', 'product' => $isAr ? 'صفحة تفاصيل منتج' : 'Product detail page',
            'services' => $isAr ? 'الخدمات' : 'Services', 'portfolio' => $isAr ? 'معرض الأعمال' : 'Portfolio',
            'contact' => $isAr ? 'اتصل بنا' : 'Contact', 'faq' => $isAr ? 'الأسئلة الشائعة' : 'FAQ', 'search' => $isAr ? 'البحث' : 'Search',
        ];
        $lines = [];
        $page = (string) ($ctx['page'] ?? '');
        if ($page !== '')               $lines[] = ($isAr ? 'الصفحة الحالية: ' : 'Current page: ') . ($pages[$page] ?? $page);
        if (!empty($ctx['section']))    $lines[] = ($isAr ? 'القسم الذي يشاهده الآن: ' : 'Section being viewed: ') . $ctx['section'];
        if (!empty($ctx['product']))    $lines[] = ($isAr ? 'المنتج المعروض: ' : 'Product on screen: ') . $ctx['product'] . ($isAr ? ' (إذا قال الزائر "هذا المنتج" فهو يقصده)' : ' (when the visitor says "this product" they mean it)');
        if (!empty($ctx['category']))   $lines[] = ($isAr ? 'القسم المفتوح: ' : 'Open category: ') . $ctx['category'];
        if (!$lines) return '';
        return "\n\n=== " . ($isAr ? 'سياق تصفح الزائر الآن' : 'Visitor browsing context') . " ===\n- " . implode("\n- ", $lines) . "\n";
    }

    // ============================================================
    // Text processing
    // ============================================================

    public static function normalize(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $t); // tashkeel + tatweel
        $t = strtr($t, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي']);
        $t = strtr($t, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $t = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $t);
        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    /** Light Arabic stemmer: removes the definite article / common prefixes & plural suffixes */
    public static function stem(string $w): string
    {
        if (preg_match('/^[a-z0-9\-]+$/', $w)) {
            return strlen($w) > 4 ? preg_replace('/(es|s)$/', '', $w) : $w;
        }
        foreach (['وال', 'بال', 'كال', 'فال', 'لل', 'ال'] as $p) {
            if (mb_strpos($w, $p) === 0 && mb_strlen($w) - mb_strlen($p) >= 3) { $w = mb_substr($w, mb_strlen($p)); break; }
        }
        foreach (['ات', 'ين', 'ون', 'ية', 'يه', 'ه', 'ي'] as $s) {
            if (mb_strlen($w) - mb_strlen($s) >= 3 && mb_substr($w, -mb_strlen($s)) === $s) { $w = mb_substr($w, 0, -mb_strlen($s)); break; }
        }
        return $w;
    }

    /** Search terms: stems + synonyms (raw spelling kept for LIKE matching) */
    public static function terms(string $text): array
    {
        $norm  = self::normalize($text);
        $words = preg_split('/\s+/u', $norm, -1, PREG_SPLIT_NO_EMPTY);
        $stop  = array_map([self::class, 'normalize'], self::STOP_WORDS);
        $out = [];
        foreach ($words as $w) {
            if (mb_strlen($w) < 2 || in_array($w, $stop, true)) continue;
            if (preg_match('/^\d+$/', $w)) { if (strlen($w) >= 2) $out[] = $w; continue; }
            $out[] = self::stem($w);
        }
        // synonyms (match on normalised forms)
        foreach (self::SYNONYMS as $group) {
            $gNorm = array_map([self::class, 'normalize'], $group);
            foreach ($gNorm as $g) {
                if ($g !== '' && (mb_strpos(' ' . $norm . ' ', ' ' . $g . ' ') !== false || in_array(self::stem($g), $out, true))) {
                    foreach ($gNorm as $syn) { $out[] = self::stem($syn); }
                    break;
                }
            }
        }
        $out = array_values(array_unique(array_filter($out, fn($w) => mb_strlen($w) >= 2)));
        return array_slice($out, 0, 14);
    }

    public static function detectIntent(string $norm): ?string
    {
        $map = [
            'greeting' => '/^(السلام|سلام|مرحبا|اهلا|هلا|صباح|مساء|hi|hello|hey|good (morning|evening))\b/u',
            'thanks'   => '/^(شكرا|مشكور|يعطيك|تسلم|جزاك|thanks|thank you|thx)\b/u',
            'location' => '/(عنوان|موقعكم|مكانكم|فين|وين|اين تقع|اين موقع|فرع|فروع|خريطه|location|address|branch|map|where are you)/u',
            'hours'    => '/(ساعات|مواعيد|اوقات|دوام|مفتوح|تفتحون|تغلقون|متي تفتح|الجمعه|hours|open|opening|closing)/u',
            'contact'  => '/(تواصل|اتصل|رقم|ارقام|هاتف|تلفون|جوال|واتس|whatsapp|phone|contact|call|email|ايميل)/u',
            'delivery' => '/(توصيل|شحن|توصلون|ترسلون|(?<!\p{L})(صنعاء|عدن|الحديده|اب|ذمار|حضرموت|المكلا|مارب|حجه|لحج|البيضاء)(?!\p{L})|deliver|shipping|ship)/u',
            'warranty' => '/(ضمان|كفاله|استبدال|ارجاع|warranty|guarantee|return)/u',
            'payment'  => '/(دفع|الدفع|تحويل|حواله|كاش|اقساط|تقسيط|payment|pay|transfer|installment)/u',
            'sizing'   => '/(احسب|حساب|كم احتاج|كم لوح|كم بطاريه|قدره|حجم المنظومه|يكفي|يشغل|تشغيل .* (ثلاجه|مكيف|مضخه|غطاس)|size|sizing|calculate|how many panels)/u',
            'install'  => '/(تركيب|اركب|توصيل الاسلاك|طريقه التركيب|install|wiring|connect)/u',
            'services' => '/(خدمه|خدمات|صيانه|استشاره|تدريب|دراسه جدوي|service|maintenance|repair|consult)/u',
            'price'    => '/(سعر|اسعار|بكم|كم سعر|كم حق|تكلفه|عرض سعر|تسعيره|ثمن|price|cost|quote|how much)/u',
            'stock'    => '/(متوفر|متاح|موجود|عندكم|لديكم|available|in stock|stock)/u',
            'specs'    => '/(مواصفات|مواصفه|قدره|جهد|امبير|كيلو|واط|فولت|specs|specification|voltage|ampere|kw|kva|hp)/u',
            'about'    => '/(من انتم|من نحن|عن الشركه|عن المحل|خبرتكم|about you|who are you|company)/u',
            'catalog'  => '/(اقسام|الاقسام|فئات|تصنيفات|ماذا تبيعون|ايش تبيعون|وش تبيعون|منتجاتكم|categories|catalog|what do you sell)/u',
        ];
        foreach ($map as $intent => $re) {
            if (preg_match($re, $norm)) return $intent;
        }
        return null;
    }

    // ============================================================
    // Data retrieval
    // ============================================================

    public static function findProducts(array $terms, string $lang, int $limit = 5): array
    {
        $lang = $lang === 'en' ? 'en' : 'ar';
        $terms = array_values(array_filter($terms, fn($t) => mb_strlen($t) >= 2));
        if (!$terms) return [];
        $terms = array_slice($terms, 0, 10);
        $score = [];
        $params = [];
        foreach ($terms as $i => $t) {
            $k = 'k' . $i;
            $params[$k] = '%' . $t . '%';
            $score[] = "(CASE WHEN p.name_ar LIKE :{$k} OR p.name_en LIKE :{$k} OR p.sku LIKE :{$k} THEN 3 ELSE 0 END)"
                     . " + (CASE WHEN c.name_ar LIKE :{$k} OR c.name_en LIKE :{$k} THEN 2 ELSE 0 END)"
                     . " + (CASE WHEN p.short_description_{$lang} LIKE :{$k} OR p.description_{$lang} LIKE :{$k} OR p.features_{$lang} LIKE :{$k} OR p.applications_{$lang} LIKE :{$k} THEN 1 ELSE 0 END)";
        }
        $sql = "SELECT p.name_{$lang} AS name, p.slug, p.price, p.currency, p.stock_status, c.name_{$lang} AS category, ("
             . implode(' + ', $score) . ") AS score
                FROM products p LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.is_active = 1 AND p.deleted_at IS NULL
                HAVING score > 0 ORDER BY score DESC, p.is_featured DESC LIMIT " . (int) $limit;
        try {
            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function findCategories(array $terms, string $lang, int $limit = 4): array
    {
        $lang = $lang === 'en' ? 'en' : 'ar';
        $terms = array_values(array_filter($terms, fn($t) => mb_strlen($t) >= 3));
        if (!$terms) return [];
        $score = [];
        $params = [];
        foreach (array_slice($terms, 0, 10) as $i => $t) {
            $k = 'c' . $i;
            $params[$k] = '%' . $t . '%';
            $score[] = "(CASE WHEN name_ar LIKE :{$k} OR name_en LIKE :{$k} THEN 3 ELSE 0 END) + (CASE WHEN description_ar LIKE :{$k} OR description_en LIKE :{$k} THEN 1 ELSE 0 END)";
        }
        $sql = "SELECT name_{$lang} AS name, slug, parent_id, (" . implode(' + ', $score) . ") AS score
                FROM categories WHERE is_active = 1 AND deleted_at IS NULL
                HAVING score > 0 ORDER BY score DESC, parent_id IS NULL, sort_order LIMIT " . (int) $limit;
        try {
            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function searchKnowledge(array $terms, string $lang, int $max = 2): array
    {
        if (!$terms) return [];
        $content = function_exists('getSetting') ? trim((string) getSetting('ai_knowledge_base')) : '';
        if ($content === '' && is_file(BASE_PATH . '/ai-assistant/data/knowledge.md')) {
            $content = (string) file_get_contents(BASE_PATH . '/ai-assistant/data/knowledge.md');
        }
        if ($content === '') return [];

        // Split into "## / ###" sections and "* **س:" FAQ items for finer matches
        $chunks = preg_split('/\n(?=#{2,3} )|\n(?=\* \*\*س)/u', $content);
        $scored = [];
        foreach ($chunks as $chunk) {
            $n = self::normalize($chunk);
            $first = self::normalize(strtok($chunk, "\n") ?: '');
            $s = 0;
            foreach ($terms as $t) {
                if ($t === '') continue;
                if (mb_strpos($first, $t) !== false) $s += 4;
                $s += min(3, substr_count($n, $t));
            }
            if ($s >= 2) $scored[] = ['s' => $s, 'c' => trim($chunk)];
        }
        usort($scored, fn($a, $b) => $b['s'] <=> $a['s']);
        return array_map(fn($x) => mb_substr($x['c'], 0, 1400), array_slice($scored, 0, $max));
    }

    // ============================================================
    // Formatting & company facts (all from settings)
    // ============================================================

    private static function formatProducts(array $rows, bool $isAr, bool $forAi = false): string
    {
        $out = '';
        foreach ($rows as $p) {
            $stock = [
                'in_stock'     => $isAr ? '✅ متوفر' : '✅ In stock',
                'out_of_stock' => $isAr ? '⛔ غير متوفر حالياً' : '⛔ Out of stock',
                'low_stock'    => $isAr ? '⚠️ كمية محدودة' : '⚠️ Low stock',
                'on_demand'    => $isAr ? '📞 حسب الطلب' : '📞 On demand',
            ][$p['stock_status'] ?? ''] ?? '';
            $price = (!empty($p['price']) && (float) $p['price'] > 0)
                ? ' — ' . number_format((float) $p['price']) . ' ' . ($p['currency'] ?: 'YER')
                : '';
            $out .= "- [{$p['name']}](/product.php?slug=" . rawurlencode($p['slug']) . "){$price}" . ($stock ? " · {$stock}" : '')
                 . (!empty($p['category']) ? ($isAr ? " · قسم {$p['category']}" : " · {$p['category']}") : '') . "\n";
        }
        return rtrim($out);
    }

    private static function formatCategories(array $rows): string
    {
        $out = '';
        foreach ($rows as $c) {
            $out .= "- [{$c['name']}](/products.php?category=" . rawurlencode($c['slug']) . ")\n";
        }
        return rtrim($out);
    }

    private static function cleanKnowledgeSnippet(string $s): string
    {
        $s = preg_replace('/^#{2,4}\s*\d*\.?\s*/m', '**', $s, 1);
        $s = preg_replace('/^\*\*(.+)$/m', '**$1**', $s, 1);
        $s = str_replace('****', '**', $s);
        return trim(mb_substr($s, 0, 900));
    }

    private static function phones(): array
    {
        return array_values(array_filter([
            getSetting('whatsapp_primary'), getSetting('whatsapp_secondary_1'), getSetting('whatsapp_secondary_2'),
        ]));
    }

    private static function waLine(bool $isAr): string
    {
        $p = self::phones();
        if (!$p) return $isAr ? '[اتصل بنا](/contact.php)' : '[Contact us](/contact.php)';
        $n = function_exists('normalizeYemenPhone') ? normalizeYemenPhone($p[0]) : $p[0];
        return "[{$p[0]}](https://wa.me/{$n})";
    }

    private static function contactBlock(bool $isAr): string
    {
        $lines = [];
        foreach (self::phones() as $p) {
            $n = function_exists('normalizeYemenPhone') ? normalizeYemenPhone($p) : $p;
            $lines[] = "- 📱 [{$p}](https://wa.me/{$n})" . ($isAr ? ' (واتساب واتصال)' : ' (WhatsApp & calls)');
        }
        $email = getSetting('company_email');
        if ($email) $lines[] = "- ✉️ {$email}";
        return ($isAr ? "📞 **أرقام التواصل المعتمدة:**\n" : "📞 **Official contact numbers:**\n") . implode("\n", $lines)
            . ($isAr ? "\n\nأو أرسل طلبك عبر [صفحة اتصل بنا](/contact.php)." : "\n\nOr send your request via the [Contact page](/contact.php).");
    }

    private static function locationBlock(bool $isAr): string
    {
        $addr = getSetting('company_address_' . ($isAr ? 'ar' : 'en'))
            ?: ($isAr ? 'تعز - الحوبان - مقابل البيت الحديث - أرفع من الجوبي للمفروشات' : 'Taiz - Al-Hawban - Opposite Al-Bayt Al-Hadeeth - near Al-Jawbi Furniture');
        return $isAr
            ? "📍 **عنواننا:** {$addr}\nنخدم جميع المحافظات اليمنية، وتجد الخريطة في [صفحة اتصل بنا](/contact.php)."
            : "📍 **Our address:** {$addr}\nWe serve all Yemeni governorates — see the map on the [Contact page](/contact.php).";
    }

    private static function hoursBlock(bool $isAr): string
    {
        $h = getSetting('working_hours_' . ($isAr ? 'ar' : 'en'));
        if ($h) return ($isAr ? "⏰ **ساعات العمل:** " : "⏰ **Working hours:** ") . $h;
        return $isAr
            ? "⏰ **ساعات العمل:**\n- السبت – الخميس: 8:00 صباحاً – 10:00 مساءً\n- الجمعة: 2:00 ظهراً – 10:00 مساءً\nواتساب متاح لاستقبال طلباتك في أي وقت."
            : "⏰ **Working hours:**\n- Saturday – Thursday: 8:00 AM – 10:00 PM\n- Friday: 2:00 PM – 10:00 PM\nWhatsApp is open for requests any time.";
    }

    private static function servicesBlock(bool $isAr): string
    {
        return $isAr
            ? "🛠️ **خدماتنا الفنية:**\n- استشارات فنية واختيار المعدات المناسبة\n- تركيب وتشغيل منظومات الطاقة الشمسية والمولدات ولوحات التحكم\n- صيانة دورية وطارئة للمصانع وخطوط الإنتاج\n- توفير قطع الغيار الأصلية للمولدات والمعدات الصناعية والزراعية\n- برمجة وتشغيل أجهزة PLC والانفرترات\nالتفاصيل في [صفحة الخدمات](/services.php)."
            : "🛠️ **Our technical services:**\n- Technical consultation & equipment selection\n- Installing & commissioning solar systems, generators and control panels\n- Preventive & emergency maintenance for factories and production lines\n- Genuine spare parts for generators, industrial & agricultural equipment\n- PLC & inverter programming and commissioning\nDetails on the [Services page](/services.php).";
    }

    private static function aboutBlock(bool $isAr): string
    {
        $name = getSetting('site_name_' . ($isAr ? 'ar' : 'en'));
        return $isAr
            ? "🏢 **{$name}** (عالم الصناعة للكهرباء والمستلزمات الصناعية): متخصصون في تجارة المستلزمات الكهربائية والصناعية، الطاقة المتجددة، التحكم الصناعي، المولدات وقطع غيارها، ومستلزمات المصانع وخطوط الإنتاج — من تعز إلى كل اليمن. اقرأ المزيد في [من نحن](/about.php)."
            : "🏢 **{$name}**: specialists in electrical & industrial supplies, renewable energy, industrial control, generators & spare parts, and factory/production-line supplies — from Taiz to all of Yemen. More on the [About page](/about.php).";
    }

    private static function categoriesBlock(bool $isAr): string
    {
        try {
            $lang = $isAr ? 'ar' : 'en';
            $rows = Database::fetchAll("SELECT name_{$lang} AS name, slug FROM categories WHERE parent_id IS NULL AND is_active = 1 AND deleted_at IS NULL ORDER BY sort_order");
        } catch (Throwable $e) {
            $rows = [];
        }
        if (!$rows) return $isAr ? "تصفح جميع الأقسام في [صفحة المنتجات](/products.php)." : "Browse all categories on the [Products page](/products.php).";
        return ($isAr ? "📦 **أقسامنا الرئيسية:**\n" : "📦 **Main categories:**\n") . self::formatCategories($rows)
            . ($isAr ? "\n\nأخبرني ما الذي تحتاجه بالضبط وسأوجهك للقسم الصحيح." : "\n\nTell me exactly what you need and I'll point you to the right section.");
    }

    private static function sizingBlock(bool $isAr, string $norm): string
    {
        return $isAr
            ? "📐 **لنحسب احتياجك بدقة، أرسل لي:**\n1. الأجهزة التي تريد تشغيلها وقدرة كل جهاز بالواط (مثال: ثلاجة 300W، مضخة 1.5HP).\n2. عدد ساعات التشغيل يومياً (نهاراً وليلاً).\n3. هل تحتاج تشغيل ليلي على البطاريات؟\n\n**قاعدة تقريبية للطاقة الشمسية:** إجمالي الاستهلاك اليومي (واط·ساعة) ÷ 5 ساعات شمس ≈ قدرة الألواح المطلوبة، ويُختار الانفرتر بقدرة أعلى بـ 25–30% من أعلى حمل متزامن.\nللمولدات: اجمع القدرات وأضف 25% احتياطياً، ولأحمال المحركات احسب تيار البدء (3–6 أضعاف)."
            : "📐 **To size your system accurately, send me:**\n1. The appliances and the wattage of each (e.g. fridge 300W, pump 1.5HP).\n2. Daily running hours (day/night).\n3. Do you need night-time battery backup?\n\n**Rule of thumb (solar):** daily consumption (Wh) ÷ 5 sun hours ≈ required panel capacity; pick an inverter 25–30% above the peak simultaneous load.\nFor generators: add up the loads plus 25% margin, and account for motor starting current (3–6×).";
    }

    private static function greeting(bool $isAr, array $ctx): string
    {
        $name = getSetting('site_name_' . ($isAr ? 'ar' : 'en'));
        $p = trim((string) ($ctx['product'] ?? ''));
        if ($isAr) {
            return "وعليكم السلام ورحمة الله 👋 أهلاً بك في **{$name}**، أنا «فولت» مساعدك الذكي."
                . ($p ? "\nأرى أنك تتصفح **{$p}** — هل تريد سعره أو مواصفاته؟" : "\nكيف أخدمك؟ يمكنني:")
                . ($p ? '' : "\n- البحث عن أي قطعة كهربائية أو صناعية\n- مساعدتك في حساب منظومة طاقة أو مولد\n- إعطاؤك أرقام التواصل والعنوان وساعات العمل");
        }
        return "Hello 👋 Welcome to **{$name}**, I'm Volt, your smart assistant."
            . ($p ? "\nI see you're viewing **{$p}** — want its price or specs?" : "\nHow can I help? I can:\n- Find any electrical or industrial part\n- Help you size a solar system or generator\n- Share our contacts, address and hours");
    }

    private static function fallback(bool $isAr, array $ctx): string
    {
        return $isAr
            ? "لم أفهم طلبك تماماً 🤔 جرّب كتابة **اسم القطعة أو الموديل أو القدرة** (مثال: انفرتر هيبرد 5 كيلو، كيبل 16 ملم، حساس حرارة PT100)، أو اختر:\n- 📦 [تصفح الأقسام](/products.php)\n- 🛠️ [الخدمات](/services.php)\n- 📍 [العنوان والتواصل](/contact.php)\n- 💬 واتساب المبيعات: " . self::waLine(true)
            : "I didn't fully get that 🤔 Try typing the **part name, model or rating** (e.g. hybrid inverter 5kW, 16mm cable, PT100 sensor), or choose:\n- 📦 [Browse categories](/products.php)\n- 🛠️ [Services](/services.php)\n- 📍 [Address & contact](/contact.php)\n- 💬 Sales WhatsApp: " . self::waLine(false);
    }
}
