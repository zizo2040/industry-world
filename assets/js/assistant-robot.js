/**
 * AI Assistant — Interactive 3D Robot + Context-aware bubbles
 * ============================================================
 * - Renders the 3D robot inside the floating launcher (#ai-toggle-btn).
 * - The head/eyes follow the cursor (desktop) or the last touch point
 *   (phones), blinks, waves, gets sleepy when idle, "thinks" while the
 *   assistant is waiting for a reply and "talks" while it is answering.
 * - Shows short, helpful speech bubbles that match the CURRENT PAGE and
 *   the SECTION the visitor is looking at (hero, categories, a product's
 *   specs, the contact form, FAQ…), plus behaviour triggers (idle on a
 *   product, deep scroll, about to leave). Every bubble has quick actions
 *   (ask the assistant, WhatsApp with a pre-filled message, go to page).
 * - Strictly rate-limited so it helps without nagging; fully disabled
 *   from Admin › AI Assistant › Robot & Bubbles.
 *
 * Public events:
 *   document → 'ai-assistant:open'                 (opens the chat)
 *   document → 'ai-assistant:ask'   {detail:{text}} (opens + sends)
 *   document ← 'ai-assistant:state' {detail:{state}} thinking|talking|idle|open|closed
 *
 * Legacy support: any element with data-ai-peek-text="…" still works —
 * when it scrolls into view it shows that text as a bubble.
 */
(function () {
    'use strict';

    var launcher = document.getElementById('ai-toggle-btn');
    if (!launcher) return;

    var PAGE = window.AI_PAGE_CONTEXT || { page: 'home', lang: document.documentElement.lang || 'ar' };
    var CFG = Object.assign({
        bubbles: true,
        maxPerPage: 3,
        maxPerSession: 10,
        firstDelay: 4500,
        cooldown: 22000,
        name: '',
        whatsapp: '',
        welcomeAr: '',
        welcomeEn: '',
    }, window.AI_ROBOT_CONFIG || {});

    var isAr = (PAGE.lang || 'ar').indexOf('ar') === 0;
    var T = function (ar, en) { return isAr ? ar : en; };
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var canHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    // ============================================================
    // 1) Build robot
    // ============================================================
    var robot = document.createElement('span');
    robot.className = 'ai-robot';
    robot.setAttribute('aria-hidden', 'true');
    robot.innerHTML =
        '<span class="ai-robot__platform"></span>' +
        '<span class="ai-robot__float">' +
            '<span class="ai-robot__arm ai-robot__arm--l"></span>' +
            '<span class="ai-robot__arm ai-robot__arm--r"></span>' +
            '<span class="ai-robot__neck"></span>' +
            '<span class="ai-robot__body"><span class="ai-robot__torso"></span><span class="ai-robot__core"></span></span>' +
            '<span class="ai-robot__head">' +
                '<span class="ai-robot__head-back"></span>' +
                '<span class="ai-robot__ear ai-robot__ear--l"></span>' +
                '<span class="ai-robot__ear ai-robot__ear--r"></span>' +
                '<span class="ai-robot__antenna"></span>' +
                '<span class="ai-robot__head-shell"></span>' +
                '<span class="ai-robot__visor">' +
                    '<span class="ai-robot__eyes"><span class="ai-robot__eye ai-robot__eye--l"></span><span class="ai-robot__eye ai-robot__eye--r"></span></span>' +
                    '<span class="ai-robot__mouth"></span>' +
                    '<span class="ai-robot__cheek ai-robot__cheek--l"></span><span class="ai-robot__cheek ai-robot__cheek--r"></span>' +
                '</span>' +
            '</span>' +
        '</span>';

    // Remove the legacy flat SVG icon, keep the close icon + badge.
    var legacyIcon = launcher.querySelector('.ai-icon-chat');
    if (legacyIcon) legacyIcon.remove();
    launcher.classList.add('ai-toggle-robot');
    launcher.insertBefore(robot, launcher.firstChild);

    var head = robot.querySelector('.ai-robot__head');

    // ============================================================
    // 2) Expressions
    // ============================================================
    var exprTimer = null;
    function express(state, ms) {
        robot.classList.remove('is-happy', 'is-surprised', 'is-wave');
        if (!state) return;
        robot.classList.add(state);
        clearTimeout(exprTimer);
        if (ms) exprTimer = setTimeout(function () { robot.classList.remove(state); }, ms);
    }

    function setMode(mode) { // thinking | talking | null
        robot.classList.toggle('is-thinking', mode === 'thinking');
        robot.classList.toggle('is-talking', mode === 'talking');
    }

    // Natural blinking
    (function blinkLoop() {
        var next = 2200 + Math.random() * 3800;
        setTimeout(function () {
            if (!robot.classList.contains('is-sleepy')) {
                robot.classList.add('is-blink');
                setTimeout(function () { robot.classList.remove('is-blink'); }, 140);
                if (Math.random() < 0.25) { // double blink sometimes
                    setTimeout(function () {
                        robot.classList.add('is-blink');
                        setTimeout(function () { robot.classList.remove('is-blink'); }, 120);
                    }, 260);
                }
            }
            blinkLoop();
        }, next);
    })();

    // ============================================================
    // 3) Look-at (3D head + eye tracking)
    // ============================================================
    var lookRaf = null, target = { x: 0, y: 0 };
    function lookAt(clientX, clientY) {
        target.x = clientX; target.y = clientY;
        if (lookRaf) return;
        lookRaf = requestAnimationFrame(function () {
            lookRaf = null;
            var r = launcher.getBoundingClientRect();
            var cx = r.left + r.width / 2, cy = r.top + r.height * 0.3;
            var dx = target.x - cx, dy = target.y - cy;
            var nx = Math.max(-1, Math.min(1, dx / (window.innerWidth * 0.5)));
            var ny = Math.max(-1, Math.min(1, dy / (window.innerHeight * 0.5)));
            robot.style.setProperty('--ry', (nx * 32).toFixed(1) + 'deg');
            robot.style.setProperty('--rx', (-ny * 20).toFixed(1) + 'deg');
            robot.style.setProperty('--bry', (nx * 12).toFixed(1) + 'deg');
            robot.style.setProperty('--brx', (-ny * 6).toFixed(1) + 'deg');
            robot.style.setProperty('--ex', (nx * 4).toFixed(1) + 'px');
            robot.style.setProperty('--ey', (ny * 3).toFixed(1) + 'px');
        });
        wake();
    }

    function lookCenter() {
        ['--ry', '--rx', '--bry', '--brx'].forEach(function (p) { robot.style.setProperty(p, '0deg'); });
        robot.style.setProperty('--ex', '0px');
        robot.style.setProperty('--ey', '0px');
    }

    if (!reduceMotion) {
        if (canHover) {
            document.addEventListener('mousemove', function (e) { lookAt(e.clientX, e.clientY); }, { passive: true });
            document.addEventListener('mouseleave', lookCenter);
        }
        document.addEventListener('touchstart', function (e) {
            var t = e.touches && e.touches[0];
            if (t) lookAt(t.clientX, t.clientY);
        }, { passive: true });

        // On phones, glance in the scroll direction
        var lastY = window.scrollY, glanceTimer = null;
        window.addEventListener('scroll', function () {
            if (canHover) return;
            var dir = window.scrollY > lastY ? 1 : -1;
            lastY = window.scrollY;
            robot.style.setProperty('--rx', (dir * -12) + 'deg');
            robot.style.setProperty('--ey', (dir * 2) + 'px');
            clearTimeout(glanceTimer);
            glanceTimer = setTimeout(lookCenter, 500);
            wake();
        }, { passive: true });

        // Idle "look around" when nothing happens
        setInterval(function () {
            if (Date.now() - lastActivity < 6000 || robot.classList.contains('is-thinking')) return;
            var rx = (Math.random() * 2 - 1);
            robot.style.setProperty('--ry', (rx * 28).toFixed(1) + 'deg');
            robot.style.setProperty('--ex', (rx * 3).toFixed(1) + 'px');
            setTimeout(lookCenter, 1400);
        }, 7000);
    }

    // Sleepy after long inactivity, wakes up on any interaction
    var lastActivity = Date.now();
    function wake() {
        lastActivity = Date.now();
        if (robot.classList.contains('is-sleepy')) {
            robot.classList.remove('is-sleepy');
            express('is-surprised', 700);
        }
    }
    setInterval(function () {
        if (Date.now() - lastActivity > 45000 && !STATE.chatOpen) robot.classList.add('is-sleepy');
    }, 5000);
    ['keydown', 'click', 'wheel'].forEach(function (ev) { document.addEventListener(ev, wake, { passive: true }); });

    // Hover/press reactions
    launcher.addEventListener('mouseenter', function () { express('is-happy', 1500); });
    launcher.addEventListener('pointerdown', function () { express('is-surprised', 350); });

    // ============================================================
    // 4) Chat state integration
    // ============================================================
    var STATE = { chatOpen: false, shownThisPage: 0, lastShownAt: 0, current: null };

    document.addEventListener('ai-assistant:state', function (e) {
        var s = e.detail && e.detail.state;
        if (s === 'open')     { STATE.chatOpen = true; hideBubble(true); express('is-happy', 1200); }
        if (s === 'closed')   { STATE.chatOpen = false; setMode(null); }
        if (s === 'thinking') { setMode('thinking'); }
        if (s === 'talking')  { setMode('talking'); }
        if (s === 'idle')     { setMode(null); express('is-happy', 1200); }
    });

    // ============================================================
    // 5) Context: what page / section / item is the visitor on?
    // ============================================================
    var CTX = {
        page: PAGE.page || 'home',
        section: null,
        product: textOf('.product-detail .product-title, .product-detail h1'),
        category: '',
        query: '',
    };

    if (CTX.page === 'products') {
        var crumb = textOf('.breadcrumb-current');
        var title = textOf('.page-header-title');
        CTX.category = /category=/.test(location.search) ? (crumb || title) : '';
    }
    if (CTX.page === 'search') {
        var qInput = document.querySelector('.search-form-main input[name="q"], input[name="q"]');
        CTX.query = qInput ? (qInput.value || '').trim() : '';
    }
    window.AI_PAGE_CONTEXT = Object.assign(PAGE, { product: CTX.product, category: CTX.category });

    function textOf(sel) {
        var el = document.querySelector(sel);
        return el ? el.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    // Section selectors → section key (first match wins per element)
    var SECTION_MAP = [
        ['.hero', 'hero'],
        ['.brand-marquee-section', 'brands'],
        ['.features-section', 'features'],
        ['.categories-section', 'categories'],
        ['.products-section', 'featured'],
        ['.portfolio-section', 'portfolio-teaser'],
        ['.cta-section', 'cta'],
        ['.about-grid', 'story'],
        ['.values-grid', 'values'],
        ['.services-grid-narrative, .services-grid', 'services-list'],
        ['.products-grid', 'listing'],
        ['.empty-state', 'empty'],
        ['.product-actions', 'product-actions'],
        ['.specs-table', 'specs'],
        ['.portfolio-grid', 'gallery'],
        ['.contact-info-grid', 'contact-info'],
        ['.contact-form-card, form.contact-form', 'contact-form'],
        ['.contact-map', 'map'],
        ['.faq-list', 'faq'],
        ['.search-results-list', 'results'],
        ['footer.footer', 'footer'],
    ];

    // Related products block on the product page
    var related = document.querySelectorAll('.product-detail ~ .section .products-grid, .related-products .products-grid');
    related.forEach(function (el) { el.setAttribute('data-ai-section', 'related'); });

    // ============================================================
    // 6) Message library (page × section × trigger)
    // ============================================================
    var WA_NUMBER = (CFG.whatsapp || '').replace(/\D/g, '');
    if (WA_NUMBER && WA_NUMBER.indexOf('967') !== 0) WA_NUMBER = '967' + WA_NUMBER;
    var siteName = CFG.name || textOf('.logo-tooltip-title') || T('عالم الصناعة', 'Alam Al-Sinaa');

    function ask(label, text) { return { label: label, ask: text || label }; }
    function wa(label, msg)   { return { label: label, wa: msg, kind: 'wa' }; }
    function go(label, href)  { return { label: label, href: href }; }

    function productWaMsg() {
        return T('السلام عليكم، أريد الاستفسار عن سعر وتوفر: ' + CTX.product + '\n' + location.href,
                 'Hello, I would like the price & availability of: ' + CTX.product + '\n' + location.href);
    }

    // Each rule: id, pages ('*' = all), section (optional), trigger, text(), actions()
    var RULES = [
        // ---------- Welcome (first visit in the session) ----------
        { id: 'welcome', pages: ['home'], trigger: 'load', once: 'session',
          text: function () {
              var custom = isAr ? CFG.welcomeAr : CFG.welcomeEn;
              return custom || T('أهلاً بك في <strong>' + esc(siteName) + '</strong> 👋 أنا «فولت» مساعدك الذكي. أبحث معك عن أي قطعة كهربائية أو صناعية في ثوانٍ!',
                                 'Welcome to <strong>' + esc(siteName) + '</strong> 👋 I\'m Volt, your smart assistant. I can find any electrical or industrial part for you in seconds!');
          },
          actions: function () { return [ask(T('🔎 أبحث عن منتج', '🔎 Find a product'), T('أبحث عن منتج، ما الأقسام المتوفرة لديكم؟', 'What product categories do you have?')), wa(T('واتساب', 'WhatsApp'), T('السلام عليكم، لدي استفسار', 'Hello, I have a question'))]; } },

        { id: 'welcome-inner', pages: ['about', 'products', 'services', 'portfolio', 'contact', 'faq', 'search', 'product'], trigger: 'load', once: 'session-first',
          text: function () { return T('مرحباً 👋 أنا «فولت» مساعد ' + esc(siteName) + '. إن احتجت أي شيء أثناء التصفح فقط اضغط عليّ.', 'Hi 👋 I\'m Volt from ' + esc(siteName) + '. Tap me any time you need help.'); },
          actions: function () { return [{ label: T('💬 ابدأ محادثة', '💬 Start chat'), open: true }]; } },

        // ---------- Home ----------
        { id: 'home-categories', pages: ['home'], section: 'categories', trigger: 'section',
          text: function () { return T('لدينا أقسام لكل احتياج: <strong>مولدات، انفرترات، طاقة شمسية، PLC، لوحات كهرباء، ومستلزمات المصانع</strong>. ما الذي تبحث عنه بالضبط؟', 'We cover <strong>generators, inverters, solar, PLC, panels and factory supplies</strong>. What exactly are you looking for?'); },
          actions: function () { return [ask(T('⚡ ساعدني أختار', '⚡ Help me choose'), T('ساعدني في اختيار القسم المناسب لاحتياجي', 'Help me pick the right category')), go(T('كل المنتجات', 'All products'), '/products.php')]; } },
        { id: 'home-features', pages: ['home'], section: 'features', trigger: 'section',
          text: function () { return T('نوفر <strong>قطع أصلية بضمان</strong> وتوصيل لجميع المحافظات، مع دعم فني قبل وبعد الشراء 🛠️', 'We supply <strong>genuine parts with warranty</strong>, nationwide delivery and technical support 🛠️'); },
          actions: function () { return [ask(T('ما هي شروط الضمان؟', 'Warranty terms?')), ask(T('هل توصلون لمدينتي؟', 'Do you deliver to my city?'))]; } },
        { id: 'home-featured', pages: ['home'], section: 'featured', trigger: 'section',
          text: function () { return T('أعجبك منتج؟ أرسل لي اسمه وأخبرك بالمواصفات والبدائل المناسبة، أو اطلب سعره مباشرة عبر واتساب.', 'Like a product? Tell me its name and I\'ll explain specs & alternatives, or get the price on WhatsApp.'); },
          actions: function () { return [ask(T('قارن لي بين منتجين', 'Compare two products'), T('أريد المقارنة بين منتجين', 'I want to compare two products')), wa(T('طلب سعر', 'Get a quote'), T('السلام عليكم، أريد عرض سعر', 'Hello, I need a quote'))]; } },
        { id: 'home-portfolio', pages: ['home'], section: 'portfolio-teaser', trigger: 'section',
          text: function () { return T('هذه نماذج من مشاريع نفذناها. لديك مشروع مصنع أو منظومة طاقة؟ أساعدك في تقدير الاحتياج 📐', 'Some of our delivered projects. Planning a factory or power system? I can help estimate your needs 📐'); },
          actions: function () { return [ask(T('احسب احتياجي من الطاقة', 'Estimate my power needs'), T('ساعدني في حساب احتياجي من منظومة الطاقة الشمسية أو المولد', 'Help me size a solar system or generator'))]; } },
        { id: 'home-cta', pages: ['home'], section: 'cta', trigger: 'section',
          text: function () { return T('جاهز للطلب؟ فريق المبيعات يرد عليك خلال دقائق في أوقات الدوام ✅', 'Ready to order? Our sales team replies within minutes during working hours ✅'); },
          actions: function () { return [wa(T('تواصل الآن', 'Contact now'), T('السلام عليكم، أريد الطلب', 'Hello, I want to order')), go(T('صفحة التواصل', 'Contact page'), '/contact.php')]; } },

        // ---------- Products listing ----------
        { id: 'products-cat', pages: ['products'], section: 'listing', trigger: 'section', when: function () { return !!CTX.category; },
          text: function () { return T('تتصفح قسم <strong>' + esc(CTX.category) + '</strong>. أخبرني بالقدرة أو الموديل المطلوب وأقترح عليك الأنسب 👌', 'You\'re browsing <strong>' + esc(CTX.category) + '</strong>. Tell me the rating/model you need and I\'ll suggest the best fit 👌'); },
          actions: function () { return [ask(T('ما الأنسب لي؟', 'What fits me best?'), T('ما الأنسب لي في قسم ' + CTX.category + '؟', 'What is best for me in ' + CTX.category + '?')), wa(T('اسأل عن التوفر', 'Ask availability'), T('السلام عليكم، أريد الاستفسار عن منتجات قسم: ' + CTX.category, 'Hello, I am asking about: ' + CTX.category))]; } },
        { id: 'products-all', pages: ['products'], section: 'listing', trigger: 'section', when: function () { return !CTX.category; },
          text: function () { return T('لم تجد ما تبحث عنه؟ كثير من الأصناف متوفرة بالمخزن ولم تُعرض بعد — اسألني عن أي قطعة بالاسم أو الموديل.', 'Can\'t find it? Many items are in stock but not listed yet — ask me for any part by name or model.'); },
          actions: function () { return [ask(T('أبحث عن قطعة محددة', 'Looking for a specific part'), T('أبحث عن قطعة محددة، هل هي متوفرة؟', 'Is a specific part available?'))]; } },
        { id: 'products-empty', pages: ['products', 'search'], section: 'empty', trigger: 'section',
          text: function () { return T('لا توجد نتائج معروضة هنا، لكن غالباً القطعة متوفرة لدينا 😉 أرسل اسمها أو صورتها على واتساب.', 'Nothing listed here, but we probably have it 😉 Send its name or photo on WhatsApp.'); },
          actions: function () { return [wa(T('أرسل صورة القطعة', 'Send a photo'), T('السلام عليكم، أبحث عن هذه القطعة: ', 'Hello, I am looking for this part: ') + (CTX.query || CTX.category || ''))]; } },

        // ---------- Product detail ----------
        { id: 'product-intro', pages: ['product'], trigger: 'load', delay: 6000,
          text: function () { return T('هل تريد معرفة سعر <strong>' + esc(CTX.product) + '</strong> أو توفره بالمخزون؟', 'Want the price or stock status of <strong>' + esc(CTX.product) + '</strong>?'); },
          actions: function () { return [wa(T('💬 السعر على واتساب', '💬 Price on WhatsApp'), productWaMsg()), ask(T('اشرح لي المواصفات', 'Explain the specs'), T('اشرح لي مواصفات ' + CTX.product + ' واستخداماته', 'Explain the specs and uses of ' + CTX.product))]; } },
        { id: 'product-specs', pages: ['product'], section: 'specs', trigger: 'section',
          text: function () { return T('تقرأ المواصفات الفنية 🔍 هل تريد التأكد من <strong>توافقه</strong> مع جهازك أو الحمل الكهربائي لديك؟', 'Reading the specs 🔍 Want to check <strong>compatibility</strong> with your equipment or load?'); },
          actions: function () { return [ask(T('تحقق من التوافق', 'Check compatibility'), T('هل ' + CTX.product + ' مناسب لحمل كهربائي لدي؟ سأعطيك التفاصيل', 'Is ' + CTX.product + ' suitable for my load? I will give details')), ask(T('طريقة التركيب', 'Installation'), T('ما طريقة تركيب ' + CTX.product + '؟', 'How do I install ' + CTX.product + '?'))]; } },
        { id: 'product-related', pages: ['product'], section: 'related', trigger: 'section',
          text: function () { return T('هذه بدائل مشابهة. محتار بينها؟ أقارن لك بينها في ثوانٍ ⚖️', 'These are similar alternatives. Unsure? I\'ll compare them for you ⚖️'); },
          actions: function () { return [ask(T('قارن البدائل', 'Compare alternatives'), T('قارن لي بين ' + CTX.product + ' والبدائل المشابهة', 'Compare ' + CTX.product + ' with similar alternatives'))]; } },
        { id: 'product-idle', pages: ['product'], trigger: 'idle', idle: 25000,
          text: function () { return T('ما زلت تفكر؟ 🤔 يمكنني حجز طلبك أو إرسال عرض سعر لـ <strong>' + esc(CTX.product) + '</strong> فوراً.', 'Still deciding? 🤔 I can get you a quote for <strong>' + esc(CTX.product) + '</strong> right away.'); },
          actions: function () { return [wa(T('أرسل لي عرض سعر', 'Send me a quote'), productWaMsg())]; } },

        // ---------- Services / About / Portfolio ----------
        { id: 'services-list', pages: ['services'], section: 'services-list', trigger: 'section',
          text: function () { return T('نقدم التركيب والتشغيل والصيانة الدورية وتوريد قطع الغيار للمصانع والمولدات 🔧 أي خدمة تحتاج؟', 'We install, commission, maintain and supply spare parts for factories & generators 🔧 Which service do you need?'); },
          actions: function () { return [ask(T('أطلب صيانة', 'Request maintenance'), T('أريد طلب خدمة صيانة، ما الخطوات؟', 'I want to request maintenance, what are the steps?')), ask(T('استشارة فنية', 'Technical advice'), T('أحتاج استشارة فنية لمشروعي', 'I need technical advice for my project'))]; } },
        { id: 'about-story', pages: ['about'], section: 'story', trigger: 'section',
          text: function () { return T('مقرنا في <strong>تعز - الحوبان</strong> ونخدم جميع المحافظات. تريد زيارتنا؟ أرسل لك الموقع.', 'We\'re based in <strong>Taiz - Al-Hawban</strong>, serving all governorates. Want our location?'); },
          actions: function () { return [ask(T('أين موقعكم بالضبط؟', 'Exact location?')), ask(T('ما ساعات العمل؟', 'Working hours?'))]; } },
        { id: 'portfolio-gallery', pages: ['portfolio'], section: 'gallery', trigger: 'section',
          text: function () { return T('أعجبك مشروع؟ نستطيع تنفيذ مشروع مشابه لك — أخبرني عن فكرتك.', 'Like a project? We can deliver a similar one for you — tell me your idea.'); },
          actions: function () { return [ask(T('أريد مشروعاً مشابهاً', 'I want a similar project'), T('أريد تنفيذ مشروع مشابه، ما المطلوب مني؟', 'I want a similar project, what do you need from me?'))]; } },

        // ---------- Contact ----------
        { id: 'contact-info', pages: ['contact'], section: 'contact-info', trigger: 'section',
          text: function () { return T('أسرع طريقة للرد: <strong>واتساب</strong> 📱 وإن أردت أجيبك أنا الآن مباشرة.', 'Fastest reply: <strong>WhatsApp</strong> 📱 or ask me right now.'); },
          actions: function () { return [wa(T('افتح واتساب', 'Open WhatsApp'), T('السلام عليكم، لدي استفسار', 'Hello, I have a question')), { label: T('اسألني الآن', 'Ask me now'), open: true }]; } },
        { id: 'contact-form', pages: ['contact'], section: 'contact-form', trigger: 'section',
          text: function () { return T('نصيحة: اذكر <strong>اسم القطعة والكمية والمدينة</strong> في رسالتك ليصلك عرض السعر أسرع ✍️', 'Tip: include <strong>part name, quantity and city</strong> to get your quote faster ✍️'); },
          actions: function () { return []; } },
        { id: 'contact-map', pages: ['contact'], section: 'map', trigger: 'section',
          text: function () { return T('📍 تعز - الحوبان - مقابل البيت الحديث - أرفع من الجوبي للمفروشات. نحن بانتظارك!', '📍 Taiz - Al-Hawban - opposite Al-Bayt Al-Hadeeth, near Al-Jawbi Furniture. See you soon!'); },
          actions: function () { return []; } },

        // ---------- FAQ / Search ----------
        { id: 'faq', pages: ['faq'], section: 'faq', trigger: 'section',
          text: function () { return T('لم تجد سؤالك هنا؟ اكتبه لي وسأجيبك فوراً 🙂', 'Didn\'t find your question? Ask me and I\'ll answer right away 🙂'); },
          actions: function () { return [{ label: T('اسأل سؤالي', 'Ask my question'), open: true }]; } },
        { id: 'search-results', pages: ['search'], section: 'results', trigger: 'section', when: function () { return !!CTX.query; },
          text: function () { return T('نتائج بحثك عن «<strong>' + esc(CTX.query) + '</strong>». أساعدك في تضييق الاختيار؟', 'Results for “<strong>' + esc(CTX.query) + '</strong>”. Want help narrowing it down?'); },
          actions: function () { return [ask(T('ساعدني أختار', 'Help me choose'), T('ساعدني في الاختيار من نتائج البحث عن ' + CTX.query, 'Help me choose from results for ' + CTX.query))]; } },

        // ---------- Everywhere ----------
        { id: 'deep-scroll', pages: '*', trigger: 'scroll', depth: 0.75,
          text: function () { return T('وصلت لنهاية الصفحة 👀 هل وجدت ما تبحث عنه؟', 'You reached the end 👀 Did you find what you need?'); },
          actions: function () { return [{ label: T('لا، ساعدني', 'No, help me'), open: true }, wa(T('واتساب', 'WhatsApp'), T('السلام عليكم، أبحث عن: ', 'Hello, I am looking for: '))]; } },
        { id: 'exit', pages: '*', trigger: 'exit',
          text: function () { return CTX.product
              ? T('قبل أن تغادر 👋 احفظ رقمنا واحصل على سعر <strong>' + esc(CTX.product) + '</strong> عبر واتساب.', 'Before you go 👋 get the price of <strong>' + esc(CTX.product) + '</strong> on WhatsApp.')
              : T('قبل أن تغادر 👋 هل أساعدك في الوصول لما تبحث عنه بسرعة؟', 'Before you go 👋 can I help you find it quickly?'); },
          actions: function () { return [CTX.product ? wa(T('أرسل السعر', 'Send price'), productWaMsg()) : { label: T('نعم ساعدني', 'Yes, help me'), open: true }]; } },
    ];

    // ============================================================
    // 7) Bubble UI
    // ============================================================
    var bubble = document.createElement('div');
    bubble.className = 'ai-bubble';
    bubble.setAttribute('role', 'status');
    bubble.setAttribute('aria-live', 'polite');
    bubble.innerHTML =
        '<button type="button" class="ai-bubble__close" aria-label="' + T('إغلاق', 'Close') + '">&times;</button>' +
        '<div class="ai-bubble__head"><span class="ai-bubble__tag"></span></div>' +
        '<div class="ai-bubble__body"></div>' +
        '<div class="ai-bubble__actions"></div>' +
        '<span class="ai-bubble__progress"><i></i></span>';
    document.body.appendChild(bubble);

    var bTag = bubble.querySelector('.ai-bubble__tag');
    var bBody = bubble.querySelector('.ai-bubble__body');
    var bActions = bubble.querySelector('.ai-bubble__actions');
    var hideTimer = null, remaining = 0, startedAt = 0;

    bubble.querySelector('.ai-bubble__close').addEventListener('click', function (e) {
        e.stopPropagation();
        hideBubble();
        STATE.dismissed = (STATE.dismissed || 0) + 1;
        if (STATE.dismissed >= 2) store('ai_rb_muted', '1'); // visitor clearly doesn't want bubbles
    });
    bBody.addEventListener('click', function () { openChat(); hideBubble(true); });
    bubble.addEventListener('mouseenter', pauseTimer);
    bubble.addEventListener('mouseleave', resumeTimer);
    bubble.addEventListener('focusin', pauseTimer);
    bubble.addEventListener('focusout', resumeTimer);

    var SECTION_LABELS = {
        hero: T('مرحباً بك', 'Welcome'), categories: T('الأقسام', 'Categories'), features: T('لماذا نحن', 'Why us'),
        featured: T('منتجات مميزة', 'Featured'), 'portfolio-teaser': T('أعمالنا', 'Our work'), cta: T('اطلب الآن', 'Order now'),
        listing: T('المنتجات', 'Products'), specs: T('المواصفات', 'Specifications'), related: T('بدائل مشابهة', 'Alternatives'),
        'services-list': T('خدماتنا', 'Services'), story: T('من نحن', 'About'), gallery: T('معرض الأعمال', 'Portfolio'),
        'contact-info': T('تواصل سريع', 'Quick contact'), 'contact-form': T('نصيحة', 'Tip'), map: T('موقعنا', 'Location'),
        faq: T('الأسئلة الشائعة', 'FAQ'), results: T('نتائج البحث', 'Search'), empty: T('لم تجد؟', 'Not found?'),
    };

    function showBubble(rule, overrideText) {
        if (STATE.chatOpen || document.body.classList.contains('menu-open')) return false;
        var html = overrideText ? esc(overrideText) : rule.text();
        var actions = overrideText ? [{ label: T('💬 تحدث معي', '💬 Chat with me'), open: true }] : (rule.actions ? rule.actions() : []);

        STATE.current = rule;
        bTag.textContent = SECTION_LABELS[rule.section] || (CTX.product ? T('مستشار المنتج', 'Product advisor') : T('المساعد الذكي', 'Smart assistant'));
        bActions.innerHTML = '';
        actions.filter(Boolean).slice(0, 3).forEach(function (a) { bActions.appendChild(chip(a)); });

        // "typing…" first, then the message — feels alive
        bBody.innerHTML = '<span class="ai-bubble__typing" aria-hidden="true"><i></i><i></i><i></i></span>';
        bActions.style.display = 'none';
        bubble.classList.remove('is-counting', 'is-paused');
        bubble.classList.add('is-visible');
        express('is-wave', 2000);
        setMode('talking');

        setTimeout(function () {
            bBody.innerHTML = '<p class="ai-bubble__text">' + html + '</p>';
            bActions.style.display = actions.length ? '' : 'none';
            setTimeout(function () { setMode(null); }, 900);
            var plain = bBody.textContent.length;
            remaining = Math.min(14000, Math.max(7000, plain * 75));
            bubble.style.setProperty('--rb-dur', remaining + 'ms');
            // restart the progress animation
            void bubble.offsetWidth;
            bubble.classList.add('is-counting');
            startedAt = Date.now();
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () { hideBubble(); }, remaining);
        }, reduceMotion ? 0 : 750);

        STATE.shownThisPage++;
        STATE.lastShownAt = Date.now();
        incSession();
        markShown(rule);
        return true;
    }

    function hideBubble(immediate) {
        clearTimeout(hideTimer);
        bubble.classList.remove('is-visible', 'is-counting', 'is-paused');
        STATE.current = null;
        if (!immediate) setMode(null);
    }

    function pauseTimer() {
        if (!bubble.classList.contains('is-counting')) return;
        clearTimeout(hideTimer);
        remaining = Math.max(1500, remaining - (Date.now() - startedAt));
        bubble.classList.add('is-paused');
    }
    function resumeTimer() {
        if (!bubble.classList.contains('is-paused')) return;
        bubble.classList.remove('is-paused');
        startedAt = Date.now();
        hideTimer = setTimeout(function () { hideBubble(); }, remaining);
    }

    function chip(a) {
        var el;
        if (a.wa) {
            el = document.createElement('a');
            el.href = 'https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(a.wa);
            el.target = '_blank';
            el.rel = 'noopener noreferrer';
            el.className = 'ai-bubble__chip ai-bubble__chip--wa';
            if (!WA_NUMBER) el.href = '/contact.php';
        } else if (a.href) {
            el = document.createElement('a');
            el.href = a.href;
            el.className = 'ai-bubble__chip';
        } else {
            el = document.createElement('button');
            el.type = 'button';
            el.className = 'ai-bubble__chip';
            el.addEventListener('click', function () {
                hideBubble(true);
                if (a.ask) askAssistant(a.ask); else openChat();
            });
        }
        el.textContent = a.label;
        if (a.wa || a.href) el.addEventListener('click', function () { hideBubble(true); });
        return el;
    }

    function openChat() { document.dispatchEvent(new CustomEvent('ai-assistant:open')); }
    function askAssistant(text) { document.dispatchEvent(new CustomEvent('ai-assistant:ask', { detail: { text: text } })); }

    // ============================================================
    // 8) Scheduler (anti-nag rules)
    // ============================================================
    function store(k, v) { try { if (v === undefined) return sessionStorage.getItem(k); sessionStorage.setItem(k, v); } catch (e) { return null; } }
    function sessionCount() { return parseInt(store('ai_rb_count') || '0', 10) || 0; }
    function incSession() { store('ai_rb_count', String(sessionCount() + 1)); }
    function shownList() { try { return JSON.parse(store('ai_rb_shown') || '[]'); } catch (e) { return []; } }
    function ruleKey(rule) {
        // Per-page rules may repeat on another product/category; session rules never repeat.
        if (rule.once === 'session' || rule.once === 'session-first') return rule.id;
        return rule.id + '|' + location.pathname + location.search;
    }
    function markShown(rule) {
        var l = shownList(); l.push(ruleKey(rule));
        store('ai_rb_shown', JSON.stringify(l.slice(-60)));
        if (rule.once === 'session-first') store('ai_rb_greeted', '1');
    }
    function wasShown(rule) {
        if (rule.once === 'session-first' && store('ai_rb_greeted')) return true;
        return shownList().indexOf(ruleKey(rule)) !== -1;
    }

    var pageReadyAt = Date.now();
    function canShow(rule) {
        if (!CFG.bubbles || store('ai_rb_muted')) return false;
        if (STATE.chatOpen || bubble.classList.contains('is-visible')) return false;
        if (STATE.shownThisPage >= CFG.maxPerPage) return false;
        if (sessionCount() >= CFG.maxPerSession && rule.trigger !== 'exit') return false;
        if (Date.now() - pageReadyAt < CFG.firstDelay && rule.trigger !== 'load') return false;
        if (STATE.lastShownAt && Date.now() - STATE.lastShownAt < CFG.cooldown) return false;
        if (rule.when && !rule.when()) return false;
        if (wasShown(rule)) return false;
        return true;
    }

    function rulesFor(trigger, section) {
        return RULES.filter(function (r) {
            if (r.trigger !== trigger) return false;
            if (r.pages !== '*' && r.pages.indexOf(CTX.page) === -1) return false;
            if (section && r.section !== section) return false;
            if (r.pages !== '*' && r.pages.length === 1 && r.pages[0] === 'product' && !CTX.product) return false;
            return true;
        });
    }

    function tryTrigger(trigger, section) {
        var list = rulesFor(trigger, section);
        for (var i = 0; i < list.length; i++) {
            if (canShow(list[i])) { list[i].section = list[i].section || section; return showBubble(list[i]); }
        }
        return false;
    }

    // Pending section (if blocked by cooldown, retry while still visible)
    var visibleSections = {};
    function watchSections() {
        if (!('IntersectionObserver' in window)) return;
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                var key = en.target.getAttribute('data-ai-section');
                if (en.isIntersecting) {
                    visibleSections[key] = Date.now();
                    CTX.section = key;
                    window.AI_PAGE_CONTEXT.section = key;
                } else {
                    delete visibleSections[key];
                }
            });
        }, { threshold: [0.35] });

        SECTION_MAP.forEach(function (pair) {
            document.querySelectorAll(pair[0]).forEach(function (el) {
                if (!el.hasAttribute('data-ai-section')) el.setAttribute('data-ai-section', pair[1]);
            });
        });
        document.querySelectorAll('[data-ai-section]').forEach(function (el) { io.observe(el); });

        // Dwell: show the section bubble after the visitor stays ~1.6s on it
        setInterval(function () {
            Object.keys(visibleSections).forEach(function (key) {
                if (Date.now() - visibleSections[key] > 1600) tryTrigger('section', key);
            });
        }, 1200);

        // Legacy data-ai-peek-text elements
        var peekIO = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                var text = en.target.getAttribute('data-ai-peek-text');
                var rule = { id: 'peek:' + (en.target.getAttribute('data-ai-peek-id') || text), trigger: 'peek', section: en.target.getAttribute('data-ai-section') || null };
                setTimeout(function () {
                    if (canShow(rule)) { peekIO.unobserve(en.target); showBubble(rule, text); }
                }, 1800);
            });
        }, { threshold: 0.8 });
        document.querySelectorAll('[data-ai-peek-text]').forEach(function (el) {
            if (el.closest('.whatsapp-float')) return; // the floating WA button is always on screen
            peekIO.observe(el);
        });
    }

    function watchBehaviour() {
        // Load-time rules
        setTimeout(function () {
            if (!tryTrigger('load')) { /* nothing */ }
        }, CTX.page === 'home' ? 1800 : 3500);
        rulesFor('load').forEach(function (r) {
            if (r.delay) setTimeout(function () { tryTrigger('load'); }, r.delay);
        });

        // Idle on page
        var idleTimer = null;
        function resetIdle() {
            clearTimeout(idleTimer);
            rulesFor('idle').forEach(function (r) {
                idleTimer = setTimeout(function () { tryTrigger('idle'); }, r.idle || 25000);
            });
        }
        ['scroll', 'mousemove', 'touchstart', 'keydown'].forEach(function (ev) {
            window.addEventListener(ev, resetIdle, { passive: true });
        });
        resetIdle();

        // Scroll depth
        window.addEventListener('scroll', function () {
            var h = document.documentElement;
            var depth = (window.scrollY + window.innerHeight) / h.scrollHeight;
            if (depth > 0.75) tryTrigger('scroll');
        }, { passive: true });

        // Exit intent (desktop only, after 20s on page)
        if (canHover) {
            document.addEventListener('mouseout', function (e) {
                if (e.relatedTarget || e.clientY > 8) return;
                if (Date.now() - pageReadyAt < 20000) return;
                tryTrigger('exit');
            });
        }

        // Hide the bubble when the visitor scrolls far away from its section
        window.addEventListener('scroll', function () {
            var r = STATE.current;
            if (r && r.section && !visibleSections[r.section] && Date.now() - STATE.lastShownAt > 3000) hideBubble();
        }, { passive: true });
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // ============================================================
    // 9) Boot
    // ============================================================
    function boot() {
        watchSections();
        watchBehaviour();
        // A friendly wave the first time the robot appears in a session
        if (!store('ai_rb_waved')) { setTimeout(function () { express('is-wave', 2200); }, 900); store('ai_rb_waved', '1'); }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();

    // Debug helper for admins: AIRobot.show('rule-id')
    window.AIRobot = {
        show: function (id) { var r = RULES.filter(function (x) { return x.id === id; })[0]; if (r) showBubble(r); },
        hide: hideBubble,
        express: express,
        context: CTX,
        reset: function () { ['ai_rb_count', 'ai_rb_shown', 'ai_rb_muted', 'ai_rb_greeted', 'ai_rb_waved'].forEach(function (k) { try { sessionStorage.removeItem(k); } catch (e) {} }); },
    };
})();
