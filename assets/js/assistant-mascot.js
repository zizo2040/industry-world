/**
 * AI Assistant - Peeking Mascot (بومت)
 * ============================================================
 * روبوت صغير "يختبئ" خلف عناصر الصفحة (الشعار، الأزرار، أي عنصر
 * تفاعلي) ويظهر منها تدريجياً مع فقاعة نص عند وصول الزائر إليه،
 * ثم يختبئ من جديد بعد لحظات.
 *
 * كيف تُفعّل العنصر:
 *   أضف السمة التالية على أي زر/رابط/عنصر تريد أن يظهر الروبوت
 *   من خلفه عند دخوله الشاشة:
 *
 *     <a class="btn" data-ai-peek-text="هل تحتاج مساعدة في هذا؟">...</a>
 *
 *   اختيارياً: data-ai-peek-id="معرف" لمنع تكرار نفس الفقاعة إذا
 *   تغيّر نصها لاحقاً (افتراضياً يُستخدم النص نفسه كمعرف).
 *
 * لا حاجة لتعديل أي شيء آخر - الملف يعمل تلقائياً بعد تحميله.
 */

(function () {
    'use strict';

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        // نحترم رغبة المستخدم بتقليل الحركة: لا نعطل الميزة كلياً، لكن
        // بدون حركات القفز المتكررة - الـ CSS يتكفل بتبسيط الانتقالات.
    }

    const isAr = (document.documentElement.getAttribute('lang') || 'ar').indexOf('ar') === 0;

    const CONFIG = {
        maxPeeksPerSession: 6,     // حد أقصى لعدد مرات الظهور بكل جلسة تصفح
        autoHideMs: 5200,          // مدة ظهور الفقاعة قبل الاختباء تلقائياً
        firstDelayMs: 350,         // تأخير بسيط قبل كل ظهور (طبيعية أكثر)
        gapBetweenPeeksMs: 900,    // فاصل بين ظهور وآخر
        logoDelayMs: 1300,         // تأخير ظهور الروبوت من خلف الشعار عند دخول الموقع
        storageShownKey: 'ai_mascot_shown_v1',
        storageCountKey: 'ai_mascot_count_v1',
        storageLogoKey:  'ai_mascot_logo_shown_v1',
    };

    let shownIds = [];
    try {
        shownIds = JSON.parse(sessionStorage.getItem(CONFIG.storageShownKey) || '[]');
    } catch (e) { shownIds = []; }

    let peekCount = parseInt(sessionStorage.getItem(CONFIG.storageCountKey) || '0', 10) || 0;

    let mascotEl = null;
    let bubbleEl = null;
    let bubbleTextEl = null;
    let robotSvgEl = null;

    const queue = [];
    let isBusy = false;
    let hideTimer = null;
    let restoreTarget = null; // { el, position, zIndex }

    // ============================================================
    // بناء عنصر الروبوت مرة واحدة
    // ============================================================
    function buildMascot() {
        mascotEl = document.createElement('div');
        mascotEl.className = 'ai-mascot';
        mascotEl.setAttribute('aria-hidden', 'true');
        mascotEl.innerHTML =
            '<div class="ai-mascot-bubble">' +
                '<button type="button" class="ai-mascot-bubble-close" aria-label="' + (isAr ? 'إغلاق' : 'Close') + '">&times;</button>' +
                '<p class="ai-mascot-bubble-text"></p>' +
            '</div>' +
            '<svg class="ai-mascot-robot" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                '<path d="M32 12V4" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>' +
                '<circle cx="32" cy="4" r="3" fill="var(--ai-primary-light, #60a5fa)" stroke="currentColor" stroke-width="2"/>' +
                '<rect x="14" y="22" width="4" height="8" rx="2" fill="currentColor"/>' +
                '<rect x="46" y="22" width="4" height="8" rx="2" fill="currentColor"/>' +
                '<rect x="18" y="12" width="28" height="24" rx="6" fill="#1e293b" stroke="currentColor" stroke-width="3"/>' +
                '<circle class="ai-mascot-eye" cx="26" cy="24" r="3.5" fill="var(--ai-primary-light, #60a5fa)"/>' +
                '<circle class="ai-mascot-eye" cx="38" cy="24" r="3.5" fill="var(--ai-primary-light, #60a5fa)"/>' +
                '<path d="M27 30C27 30 29 32 32 32C35 32 37 30 37 30" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
                '<path d="M24 36H40V52H24V36Z" fill="#1e293b" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>' +
                '<path class="ai-mascot-arm" d="M40 40H47V33" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>' +
            '</svg>';

        document.body.appendChild(mascotEl);

        bubbleEl = mascotEl.querySelector('.ai-mascot-bubble');
        bubbleTextEl = mascotEl.querySelector('.ai-mascot-bubble-text');
        robotSvgEl = mascotEl.querySelector('.ai-mascot-robot');

        mascotEl.querySelector('.ai-mascot-bubble-close').addEventListener('click', function (e) {
            e.stopPropagation();
            retract(true);
        });

        bubbleEl.addEventListener('click', function () {
            document.dispatchEvent(new CustomEvent('ai-assistant:open'));
            retract(true);
        });

        // إيقاف مؤقت العدّ التنازلي عند التحويم/التركيز على الفقاعة
        bubbleEl.addEventListener('mouseenter', pauseAutoHide);
        bubbleEl.addEventListener('mouseleave', resumeAutoHide);
        bubbleEl.addEventListener('focusin', pauseAutoHide);
        bubbleEl.addEventListener('focusout', resumeAutoHide);
    }

    function pauseAutoHide() {
        if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
    }

    function resumeAutoHide() {
        if (mascotEl && mascotEl.classList.contains('is-visible')) {
            hideTimer = setTimeout(function () { retract(false); }, 1800);
        }
    }

    // ============================================================
    // هل نافذة المحادثة مفتوحة حالياً؟ (لا نزعج الزائر أثناء الدردشة)
    // ============================================================
    function isChatOpen() {
        const win = document.getElementById('ai-window');
        return !!(win && win.classList.contains('is-open'));
    }

    // ============================================================
    // إظهار الروبوت من خلف عنصر معيّن
    // ============================================================
    function peekAt(target, text) {
        if (!target || !document.body.contains(target)) return finishAndNext();

        const rect = target.getBoundingClientRect();
        if (rect.width === 0 && rect.height === 0) return finishAndNext();

        const viewportW = window.innerWidth;
        const scrollX = window.scrollX || window.pageXOffset;
        const scrollY = window.scrollY || window.pageYOffset;

        // نظهر من الجهة المتجهة نحو منتصف الشاشة كي لا نخرج عن حدود الشاشة
        const targetCenterX = rect.left + rect.width / 2;
        const sideRight = targetCenterX < viewportW / 2; // العنصر في النصف الأيسر -> نظهر من يمينه

        mascotEl.classList.toggle('is-side-left', !sideRight);

        const mascotSize = window.innerWidth <= 640 ? 38 : 46;
        const hiddenOverlap = mascotSize * 0.6;

        let left;
        if (sideRight) {
            left = rect.right + scrollX - mascotSize * 0.75;
        } else {
            left = rect.left + scrollX - mascotSize * 0.25;
        }
        const top = rect.bottom + scrollY - hiddenOverlap;

        mascotEl.style.setProperty('--ai-mascot-shift-x', '12px');
        mascotEl.style.top = top + 'px';
        mascotEl.style.left = (sideRight ? left : left - mascotSize) + 'px';

        // نص الفقاعة
        bubbleTextEl.textContent = text;

        // موضع الفقاعة: أسفل الروبوت إذا كان قريباً من أعلى الصفحة
        bubbleEl.classList.toggle('is-below', rect.top < 170);

        // رفع العنصر الهدف فوق الروبوت بصرياً (يخفي النصف العلوي المتراكب منه)
        restoreTargetStacking(target);

        // فتح الفقاعة عند الضغط على العنصر الهدف نفسه أيضاً (تجربة أوضح)
        // لا نضيف مستمع دائم، فقط نجعل الفقاعة قابلة للنقر كما هي أعلاه

        requestAnimationFrame(function () {
            mascotEl.classList.add('is-visible');
        });

        peekCount++;
        try { sessionStorage.setItem(CONFIG.storageCountKey, String(peekCount)); } catch (e) {}

        pauseAutoHide();
        hideTimer = setTimeout(function () { retract(false); }, CONFIG.autoHideMs);
    }

    function restoreTargetStacking(target) {
        const computed = window.getComputedStyle(target);
        restoreTarget = {
            el: target,
            position: target.style.position || '',
            zIndex: target.style.zIndex || '',
        };
        if (computed.position === 'static') {
            target.style.position = 'relative';
        }
        target.style.zIndex = 501;
    }

    function undoTargetStacking() {
        if (!restoreTarget) return;
        const t = restoreTarget;
        t.el.style.position = t.position;
        t.el.style.zIndex = t.zIndex;
        restoreTarget = null;
    }

    // ============================================================
    // إخفاء الروبوت (عودته للاختباء خلف العنصر)
    // ============================================================
    function retract(immediate) {
        if (!mascotEl) return;
        pauseAutoHide();
        mascotEl.classList.remove('is-visible');
        undoTargetStacking();

        const delay = immediate ? 100 : 550;
        setTimeout(finishAndNext, delay);
    }

    function finishAndNext() {
        isBusy = false;
        setTimeout(processQueue, CONFIG.gapBetweenPeeksMs);
    }

    // ============================================================
    // طابور الظهور: عنصر واحد في كل مرة، بدون إزعاج
    // ============================================================
    function enqueue(target, text, id) {
        if (peekCount >= CONFIG.maxPeeksPerSession) return;
        if (shownIds.indexOf(id) !== -1) return;
        queue.push({ target: target, text: text, id: id });
        if (!isBusy) processQueue();
    }

    function processQueue() {
        if (isBusy) return;
        if (isChatOpen()) { setTimeout(processQueue, 1500); return; }
        const item = queue.shift();
        if (!item) return;
        if (!document.body.contains(item.target)) { processQueue(); return; }

        isBusy = true;
        shownIds.push(item.id);
        try { sessionStorage.setItem(CONFIG.storageShownKey, JSON.stringify(shownIds)); } catch (e) {}

        setTimeout(function () {
            if (isChatOpen()) { retract(true); return; }
            peekAt(item.target, item.text);
        }, CONFIG.firstDelayMs);
    }

    // ============================================================
    // مراقبة العناصر التفاعلية عبر السمة data-ai-peek-text
    // ============================================================
    let observer = null;

    function watchPeekElements() {
        if (!('IntersectionObserver' in window)) return;

        observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const text = el.getAttribute('data-ai-peek-text');
                if (!text) return;
                const id = el.getAttribute('data-ai-peek-id') || text;

                observer.unobserve(el);
                enqueue(el, text, id);
            });
        }, { threshold: 0.6 });

        document.querySelectorAll('[data-ai-peek-text]').forEach(function (el) {
            const id = el.getAttribute('data-ai-peek-id') || el.getAttribute('data-ai-peek-text');
            if (shownIds.indexOf(id) === -1) {
                observer.observe(el);
            }
        });
    }

    // ============================================================
    // ظهور خاص من خلف شعار الهيدر عند دخول الموقع لأول مرة بالجلسة
    // ============================================================
    function greetFromLogo() {
        try {
            if (sessionStorage.getItem(CONFIG.storageLogoKey)) return;
        } catch (e) {}

        const logo = document.getElementById('site-logo-link');
        if (!logo) return;

        const siteNameEl = document.querySelector('.logo-tooltip-title');
        const siteName = siteNameEl ? siteNameEl.textContent.trim() : '';

        const text = isAr
            ? ('أهلاً بك' + (siteName ? ' في ' + siteName : '') + '! أنا مساعدك الذكي، اسألني عن أي شيء 👋')
            : ('Welcome' + (siteName ? ' to ' + siteName : '') + '! I\'m your AI assistant — ask me anything 👋');

        setTimeout(function () {
            try { sessionStorage.setItem(CONFIG.storageLogoKey, '1'); } catch (e) {}
            enqueue(logo, text, '__logo_greeting__');
            // أولوية للترحيب: نضعه في مقدمة الطابور
            if (queue.length > 1) {
                queue.unshift(queue.pop());
            }
            if (!isBusy) processQueue();
        }, CONFIG.logoDelayMs);
    }

    // ============================================================
    // إعادة الفحص عند إضافة عناصر جديدة ديناميكياً (اختياري وخفيف)
    // ============================================================
    function watchForNewPeekElements() {
        if (!('MutationObserver' in window) || !observer) return;
        const mo = new MutationObserver(function (mutations) {
            let found = false;
            mutations.forEach(function (m) {
                m.addedNodes && m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.hasAttribute && node.hasAttribute('data-ai-peek-text')) found = true;
                    if (node.querySelectorAll) {
                        node.querySelectorAll('[data-ai-peek-text]').forEach(function () { found = true; });
                    }
                });
            });
            if (found) {
                document.querySelectorAll('[data-ai-peek-text]').forEach(function (el) {
                    const id = el.getAttribute('data-ai-peek-id') || el.getAttribute('data-ai-peek-text');
                    if (shownIds.indexOf(id) === -1) observer.observe(el);
                });
            }
        });
        mo.observe(document.body, { childList: true, subtree: true });
    }

    // ============================================================
    // إعادة الاختباء تلقائياً إن أُغلقت نافذة المحادثة أو فُتحت
    // ============================================================
    function watchChatWindow() {
        document.addEventListener('ai-assistant:open', function () {
            if (mascotEl && mascotEl.classList.contains('is-visible')) retract(true);
        });
    }

    // ============================================================
    // Bootstrap
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {
        // الميزة تعمل فقط إذا كان المساعد الذكي مفعّلاً في الصفحة
        if (!document.getElementById('ai-toggle-btn')) return;

        buildMascot();
        watchChatWindow();
        watchPeekElements();
        watchForNewPeekElements();
        greetFromLogo();

        window.addEventListener('resize', function () {
            if (mascotEl && mascotEl.classList.contains('is-visible')) retract(true);
        });
    });
})();
