/**
 * AI Assistant - Frontend Controller (Phase 4)
 *
 * المرحلة الرابعة تضيف:
 * - اقتراحات ذكية مخصصة لكل صفحة (منتجات، خدمات، تواصل، إلخ)
 * - وعي بالسياق: الصفحة الحالية، العنوان، اللغة
 * - دعم كامل للهواتف (تجربة full-screen على الموبايل)
 * - Focus Trap داخل النافذة لسهولة الوصول
 * - تنسيق روابط الموقع داخل ردود المساعد
 */

(function () {
    'use strict';

    // ============================================================
    // Page Context (محقون من PHP عبر window.AI_PAGE_CONTEXT)
    // ============================================================
    const PAGE = window.AI_PAGE_CONTEXT || {
        page:  'home',
        title: '',
        url:   window.location.href,
        lang:  document.documentElement.lang || 'ar',
    };

    // ============================================================
    // Configuration
    // ============================================================
    const CONFIG = {
        endpoint:  '/ai-assistant/handlers/chat.php',
        maxLength: 1000,

        // اقتراحات افتراضية (عند عدم وجود سياق محدد)
        defaultSuggestions: [
            '📍 أين تقع فروعكم ومقر الشركة؟',
            '📞 كيف أتواصل مع المبيعات والواتساب؟',
            '⚡ ما هي أبرز المنتجات والتصنيفات؟',
            '📋 كيف أطلب عرض سعر لمنتج؟',
        ],

        // اقتراحات خاصة بكل صفحة
        pageSuggestions: {
            home: [
                '⚡ ما هي أبرز منتجاتكم المتاحة؟',
                '📍 أين يقع مقر الشركة الرئيسي؟',
                '📞 كيف أتواصل مع فريق المبيعات؟',
                '🛠️ ما هي الخدمات الهندسية المتوفرة؟',
            ],
            products: [
                '⚡ كيف أصل إلى فئة المنتج المناسب؟',
                '📋 كيف أحصل على عرض سعر للكميات؟',
                '📞 أريد التواصل مع مهندس المبيعات',
                '🛠️ هل تتوفر ضمانات للمنتجات؟',
            ],
            product: [
                '📋 كيف أطلب تسعيرة لهذا المنتج؟',
                '⚙️ ما هي المواصفات الفنية وطريقة التركيب؟',
                '📞 تواصل مع المبيعات عبر الواتساب',
                '🛠️ هل هذا المنتج متوفر بالمخزون؟',
            ],
            services: [
                '🛠️ كيف أطلب خدمة تركيب أو صيانة؟',
                '💡 كيف أحصل على استشارة هندسية؟',
                '📞 التواصل المباشر مع فريق الدعم الفني',
            ],
            contact: [
                '📍 ما هي أوقات وساعات العمل الرسمية؟',
                '📞 أرقام التواصل والواتساب المباشرة',
                '🏢 ما هو عنوان الفرع الرئيسي بالظبط؟',
            ],
            about: [
                '🏢 ما هي قصة وخبرة عالم الصناعة؟',
                '⚡ ما هي مجالات وتخصصات الشركة؟',
                '📞 كيف أتواصل مع الإدارة أو المبيعات؟',
            ],
            faq: [
                '📋 كيف أطلب عرض سعر للمشاريع؟',
                '📦 ما هي سياسة التوصيل والاستلام؟',
                '📞 تواصل مباشر مع الدعم الفني',
            ],
            search: [
                '⚡ البحث عن قواطع ومحولات كهربائية',
                '⚡ البحث عن أنظمة طاقة شمسية وانفرتر',
                '📞 التواصل مع قسم التجهيزات الصناعية',
            ],
        },
    };

    // ============================================================
    // State
    // ============================================================
    const STATE = {
        isOpen:        false,
        isSending:     false,
        unreadCount:   0,
        historyLoaded: false,
        isMobile:      false,
    };

    // ============================================================
    // DOM References
    // ============================================================
    let DOM = {};

    // ============================================================
    // Bootstrap
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {
        DOM = {
            toggleBtn:  document.getElementById('ai-toggle-btn'),
            window:     document.getElementById('ai-window'),
            messages:   document.getElementById('ai-messages'),
            input:      document.getElementById('ai-input'),
            sendBtn:    document.getElementById('ai-send-btn'),
            badge:      document.getElementById('ai-badge'),
            errorToast: document.getElementById('ai-error-toast'),
            clearBtn:   document.getElementById('ai-clear-btn'),
        };

        if (!DOM.toggleBtn || !DOM.window) return;

        detectMobile();
        bindEvents();
        loadHistory();
        initInteractiveFeatures();

        // يسمح لعناصر أخرى (مثل الروبوت التفاعلي وفقاعاته) بفتح نافذة المحادثة
        document.addEventListener('ai-assistant:open', function () { if (!STATE.isOpen) openWindow(); });

        // فتح المحادثة وإرسال سؤال جاهز (من أزرار فقاعات الروبوت)
        document.addEventListener('ai-assistant:ask', function (e) {
            const text = e.detail && e.detail.text ? String(e.detail.text) : '';
            if (!STATE.isOpen) openWindow();
            if (!text) return;
            const trySend = function () {
                if (STATE.isSending) { setTimeout(trySend, 400); return; }
                DOM.input.value = text;
                autoResizeInput();
                updateCharCount();
                handleSend();
            };
            setTimeout(trySend, 320);
        });
    });

    // ============================================================
    // Mobile Detection
    // ============================================================
    function detectMobile() {
        STATE.isMobile = window.innerWidth <= 640;

        window.addEventListener('resize', function () {
            const wasMobile = STATE.isMobile;
            STATE.isMobile = window.innerWidth <= 640;

            // إذا تحول من موبايل لـ desktop وكانت النافذة مفتوحة، إزالة body lock
            if (wasMobile && !STATE.isMobile && STATE.isOpen) {
                document.body.classList.remove('ai-body-lock');
            }
        });
    }

    // ============================================================
    // Event Binding
    // ============================================================
    function bindEvents() {
        DOM.toggleBtn.addEventListener('click', toggleWindow);
        DOM.sendBtn.addEventListener('click', handleSend);

        DOM.input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });

        DOM.input.addEventListener('input', function () {
            autoResizeInput();
            updateCharCount();
        });

        if (DOM.clearBtn) {
            DOM.clearBtn.addEventListener('click', handleClear);
        }

        const closeBtn = document.getElementById('ai-close-btn');
        if (closeBtn) closeBtn.addEventListener('click', closeWindow);

        // Escape يغلق النافذة
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && STATE.isOpen) closeWindow();
        });

        // Overlay click يغلق النافذة على الموبايل
        document.addEventListener('click', function (e) {
            if (STATE.isOpen &&
                !DOM.window.contains(e.target) &&
                !DOM.toggleBtn.contains(e.target) &&
                !(e.target.closest && e.target.closest('.ai-bubble'))) {
                closeWindow();
            }
        });

        // Focus Trap داخل النافذة
        DOM.window.addEventListener('keydown', trapFocus);
    }

    // ============================================================
    // Focus Trap
    // ============================================================
    function trapFocus(e) {
        if (e.key !== 'Tab' || !STATE.isOpen) return;

        const focusable = DOM.window.querySelectorAll(
            'button:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const first = focusable[0];
        const last  = focusable[focusable.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === first) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    // ============================================================
    // History Restoration
    // ============================================================
    function loadHistory() {
        sendRequest('get_history', {})
            .then(function (data) {
                STATE.historyLoaded = true;

                if (data.success && data.data.history && data.data.history.length > 0) {
                    renderHistoryMessages(data.data.history);

                    // تنبيه بسيط أن هناك محادثة سابقة
                    STATE.unreadCount = 1;
                    updateBadge();
                } else {
                    renderWelcome();
                }
            })
            .catch(function () {
                STATE.historyLoaded = true;
                renderWelcome();
            });
    }

    function renderHistoryMessages(history) {
        DOM.messages.innerHTML = '';
        history.forEach(function (msg) {
            if (msg.role === 'user' || msg.role === 'assistant') {
                appendMessage(msg.role, msg.content, false, true);
            }
        });
        scrollToBottom(false);
    }

    // ============================================================
    // Welcome Screen (مخصصة حسب الصفحة)
    // ============================================================
    function renderWelcome() {
        DOM.messages.innerHTML = '';

        const suggestions = getPageSuggestions();
        const isAr        = PAGE.lang === 'ar';

        const pageGreeting = getPageGreeting(PAGE.page, isAr);

        const welcomeHtml = `
            <div class="ai-welcome" role="region" aria-label="${isAr ? 'رسالة ترحيب' : 'Welcome message'}">
                <div class="ai-welcome-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/>
                        <path d="M8 12h.01M12 12h.01M16 12h.01" stroke-linecap="round" stroke-width="2.5"/>
                    </svg>
                </div>
                <p class="ai-welcome-title">${pageGreeting.title}</p>
                <p class="ai-welcome-subtitle">${pageGreeting.subtitle}</p>
                <div class="ai-suggestions" role="list" aria-label="${isAr ? 'اقتراحات' : 'Suggestions'}">
                    ${suggestions.map(function (s) {
                        return `<button class="ai-suggestion-btn" type="button" role="listitem">${escapeHtml(s)}</button>`;
                    }).join('')}
                </div>
            </div>`;

        DOM.messages.insertAdjacentHTML('afterbegin', welcomeHtml);

        DOM.messages.querySelectorAll('.ai-suggestion-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                DOM.input.value = btn.textContent;
                autoResizeInput();
                updateCharCount();
                handleSend();
            });
        });
    }

    /**
     * تحديد الاقتراحات المناسبة بناءً على الصفحة الحالية.
     */
    function getPageSuggestions() {
        const page = PAGE.page || 'home';
        return CONFIG.pageSuggestions[page] || CONFIG.defaultSuggestions;
    }

    /**
     * تحديد رسالة الترحيب المناسبة لكل صفحة.
     */
    function getPageGreeting(page, isAr) {
        const greetings = {
            home: {
                title:    isAr ? 'مرحباً! كيف يمكنني مساعدتك؟' : 'Hello! How can I help you?',
                subtitle: isAr
                    ? 'أنا المساعد الذكي لعالم الصناعة. يمكنني مساعدتك في الوصول إلى منتجاتنا وخدماتنا.'
                    : 'I\'m the AI assistant for Alam Al-Sinaa. I can help you find products and services.',
            },
            products: {
                title:    isAr ? 'تصفح منتجاتنا بمساعدتي!' : 'Browse our products with my help!',
                subtitle: isAr
                    ? 'يمكنني مساعدتك في اختيار المنتج المناسب، مقارنة المواصفات، أو إيجاد البدائل.'
                    : 'I can help you choose the right product, compare specs, or find alternatives.',
            },
            product: {
                title:    isAr ? 'اسألني عن هذا المنتج!' : 'Ask me about this product!',
                subtitle: isAr
                    ? 'يمكنني شرح المواصفات، طريقة التركيب، والتوافق مع منتجات أخرى.'
                    : 'I can explain specs, installation, and compatibility with other products.',
            },
            services: {
                title:    isAr ? 'اسألني عن خدماتنا!' : 'Ask me about our services!',
                subtitle: isAr
                    ? 'يمكنني شرح الخدمات المتاحة وكيفية الاستفادة منها.'
                    : 'I can explain available services and how to use them.',
            },
            contact: {
                title:    isAr ? 'هل تحتاج للتواصل معنا؟' : 'Need to contact us?',
                subtitle: isAr
                    ? 'يمكنني إرشادك لأفضل طريقة للتواصل مع فريقنا.'
                    : 'I can guide you to the best way to reach our team.',
            },
            about: {
                title:    isAr ? 'اعرف أكثر عن عالم الصناعة!' : 'Learn more about Alam Al-Sinaa!',
                subtitle: isAr
                    ? 'يمكنني الإجابة على أسئلتك عن الشركة وتاريخها.'
                    : 'I can answer your questions about the company and its history.',
            },
        };

        return greetings[page] || greetings.home;
    }

    // ============================================================
    // Window Toggle
    // ============================================================
    function toggleWindow() {
        STATE.isOpen ? closeWindow() : openWindow();
    }

    function openWindow() {
        STATE.isOpen = true;
        DOM.window.classList.add('is-open');
        DOM.toggleBtn.classList.add('is-open');
        DOM.toggleBtn.setAttribute('aria-expanded', 'true');

        // على الموبايل: منع تمرير الصفحة
        if (STATE.isMobile) {
            document.body.classList.add('ai-body-lock');
        }

        resetBadge();
        hideWelcomeBubble();
        scrollToBottom(true);
        emitState('open');

        setTimeout(function () {
            DOM.input.focus();
        }, 280);
    }

    function closeWindow() {
        STATE.isOpen = false;
        DOM.window.classList.remove('is-open');
        DOM.toggleBtn.classList.remove('is-open');
        DOM.toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('ai-body-lock');
        DOM.toggleBtn.focus({ preventScroll: true }); // إعادة التركيز للزر بعد الإغلاق
        emitState('closed');
    }

    function emitState(state) {
        document.dispatchEvent(new CustomEvent('ai-assistant:state', { detail: { state: state } }));
    }

    // ============================================================
    // Interactive Robot Scenarios (فقاعات الترحيب التفاعلية والنبض)
    // ============================================================
    let welcomeBubbleTimeout = null;

    function initInteractiveFeatures() {
        // الفقاعات والتفاعل أصبحت من مسؤولية الروبوت ثلاثي الأبعاد
        // (assets/js/assistant-robot.js) — لا شيء هنا لتجنّب تكرار الفقاعات.
    }

    function showWelcomeBubble() {
        const page = PAGE.page || 'home';
        const isAr = PAGE.lang === 'ar';
        
        const bubbleTexts = {
            home: {
                header: isAr ? 'عالم الصناعة' : 'Alam Al-Sinaa',
                body: isAr ? 'أهلاً بك! هل تبحث عن مولدات كهربائية أو أنظمة طاقة شمسية؟ اسألني!' : 'Welcome! Looking for generators or solar systems? Ask me!'
            },
            products: {
                header: isAr ? 'المنتجات' : 'Products',
                body: isAr ? 'مرحباً! هل تبحث عن منتج كهربائي أو مستلزم صناعي محدد؟' : 'Hello! Looking for a specific electrical product or supply?'
            },
            product: {
                header: isAr ? 'مستشار المنتجات' : 'Product Advisor',
                body: isAr ? 'أهلاً بك! هل تريد معرفة طريقة تركيب هذا المنتج أو التوافق فني؟' : 'Welcome! Need installation help or technical compatibility?'
            },
            services: {
                header: isAr ? 'الخدمات فنية' : 'Technical Services',
                body: isAr ? 'هل تود الاستفسار حول خدمات التركيب والصيانة الدورية للمصانع؟' : 'Would you like to ask about installation or maintenance services?'
            },
            contact: {
                header: isAr ? 'المبيعات والدعم' : 'Sales & Support',
                body: isAr ? 'أهلاً بك! يمكنني توجيهك لأرقام المبيعات مباشرة أو نموذج الطلب.' : 'Welcome! I can guide you to sales numbers or request form.'
            },
            about: {
                header: isAr ? 'عن الشركة' : 'About Us',
                body: isAr ? 'مرحباً! هل تود معرفة ساعات العمل الرسمية وفروعنا وتغطيتنا؟' : 'Hello! Want to know our working hours, branches, or coverage?'
            }
        };

        const currentText = bubbleTexts[page] || bubbleTexts.home;

        // إنشاء الفقاعة
        const bubble = document.createElement('div');
        bubble.className = 'ai-welcome-bubble';
        bubble.id = 'ai-welcome-bubble';
        bubble.innerHTML = `
            <div class="ai-welcome-bubble-header">
                <span>${currentText.header}</span>
                <button type="button" class="ai-welcome-bubble-close" aria-label="Close">&times;</button>
            </div>
            <div class="ai-welcome-bubble-body">
                ${currentText.body}
            </div>
        `;

        document.body.appendChild(bubble);

        // النقر على محتوى الفقاعة يفتح المحادثة
        bubble.querySelector('.ai-welcome-bubble-body').addEventListener('click', function() {
            openWindow();
        });

        // النقر على زر الإغلاق يغلقها نهائياً للجلسة الحالية
        bubble.querySelector('.ai-welcome-bubble-close').addEventListener('click', function(e) {
            e.stopPropagation();
            hideWelcomeBubble();
            sessionStorage.setItem('ai_bubble_closed', '1');
        });

        // تلاشي ناعم للدخول
        setTimeout(function() {
            bubble.classList.add('is-visible');
        }, 100);
    }

    function hideWelcomeBubble() {
        if (welcomeBubbleTimeout) {
            clearTimeout(welcomeBubbleTimeout);
        }
        
        DOM.toggleBtn.classList.remove('ai-pulse-active');
        
        const bubble = document.getElementById('ai-welcome-bubble');
        if (bubble) {
            bubble.classList.remove('is-visible');
            setTimeout(function() {
                if (bubble.parentNode) bubble.remove();
            }, 300);
        }
    }

    // ============================================================
    // Send Message
    // ============================================================
    function handleSend() {
        if (STATE.isSending) return;

        const text = DOM.input.value.trim();
        if (!text) return;

        if (text.length > CONFIG.maxLength) {
            showError('الرسالة طويلة جداً. الحد الأقصى ' + CONFIG.maxLength + ' حرف.');
            return;
        }

        hideError();
        DOM.input.value = '';
        autoResizeInput();
        updateCharCount();

        // إزالة شاشة الترحيب بانتقال ناعم
        const welcome = DOM.messages.querySelector('.ai-welcome');
        if (welcome) {
            welcome.style.opacity = '0';
            welcome.style.transform = 'scale(0.96)';
            setTimeout(function () {
                if (welcome.parentNode) welcome.remove();
            }, 200);
        }

        appendMessage('user', text);

        const typingId = showTyping();
        emitState('thinking');

        STATE.isSending = true;
        setSendState(true);

        const ctx = window.AI_PAGE_CONTEXT || {};
        sendRequest('send_message', {
            message: text,
            context: {
                page:     ctx.page || '',
                section:  ctx.section || '',
                product:  ctx.product || '',
                category: ctx.category || '',
                title:    document.title || '',
                url:      location.pathname + location.search,
            },
        })
            .then(function (data) {
                removeTyping(typingId);

                if (data.success) {
                    appendMessage('assistant', data.data.reply || data.message);
                } else {
                    appendMessage('assistant', data.message || 'عذراً، حدث خطأ. يرجى المحاولة مرة أخرى.', true);
                }
            })
            .catch(function () {
                removeTyping(typingId);
                appendMessage('assistant', PAGE.lang === 'ar' ? 'عذراً، تعذّر الاتصال بالخادم. يرجى التحقق من اتصالك والمحاولة مرة أخرى.' : 'Sorry, could not reach the server. Please check your connection and try again.', true);
                emitState('idle');
            })
            .finally(function () {
                STATE.isSending = false;
                setSendState(false);
            });
    }

    // ============================================================
    // Clear Conversation
    // ============================================================
    function handleClear() {
        if (!confirm(PAGE.lang === 'ar' ? 'هل تريد مسح المحادثة الحالية؟' : 'Clear the current conversation?')) return;

        sendRequest('clear_session', {}).catch(function () {});

        DOM.messages.style.opacity = '0';
        setTimeout(function () {
            renderWelcome();
            DOM.messages.style.opacity = '1';
            resetBadge();
        }, 200);
    }

    // ============================================================
    // Message Rendering
    // ============================================================
    function appendMessage(role, text, isError, isRestored) {
        const time = formatTime(new Date());

        const div = document.createElement('div');
        div.className = 'ai-message ' + role +
                        (isError    ? ' is-error'    : '') +
                        (isRestored ? ' is-restored' : '');

        const content = role === 'assistant'
            ? renderMarkdown(text)
            : escapeHtml(text);

        // هيدر الفقاعة
        div.innerHTML = `
            <div class="ai-message-bubble"></div>
            <span class="ai-message-time">${time}</span>`;

        DOM.messages.appendChild(div);
        const bubble = div.querySelector('.ai-message-bubble');

        if (role === 'assistant' && !isRestored && !isError) {
            STATE.isTypingEffect = true;
            emitState('talking');
            typewrite(bubble, content, function () {
                STATE.isTypingEffect = false;
                scrollToBottom(true);
                emitState('idle');
            });
        } else {
            bubble.innerHTML = content;
            scrollToBottom(!isRestored);
            if (role === 'assistant' && !isRestored) emitState('idle');
        }

        if (!STATE.isOpen && role === 'assistant' && !isRestored) {
            STATE.unreadCount++;
            updateBadge();
        }
    }

    // تأثير الآلة الكاتبة الآمن لـ HTML
    function typewrite(element, htmlContent, onComplete) {
        const tokens = [];
        let i = 0;
        while (i < htmlContent.length) {
            if (htmlContent[i] === '<') {
                let end = htmlContent.indexOf('>', i);
                if (end !== -1) {
                    tokens.push(htmlContent.substring(i, end + 1));
                    i = end + 1;
                    continue;
                }
            }
            let nextTag = htmlContent.indexOf('<', i);
            let textSegment = nextTag !== -1 ? htmlContent.substring(i, nextTag) : htmlContent.substring(i);
            
            for (let char of textSegment) {
                tokens.push(char);
            }
            i += textSegment.length;
        }

        element.innerHTML = '';
        let tokenIndex = 0;
        
        function draw() {
            if (tokenIndex >= tokens.length) {
                if (onComplete) onComplete();
                return;
            }
            
            let token = tokens[tokenIndex++];
            element.innerHTML += token;
            
            // سكرول ناعم مستمر أثناء الكتابة
            if (tokenIndex % 5 === 0 || tokenIndex === tokens.length) {
                scrollToBottom(true);
            }
            
            // سرعة متكيفة: أسرع للردود الطويلة جداً
            let delay = tokens.length > 600 ? 3 : 8;
            setTimeout(draw, delay);
        }
        
        draw();
    }

    // ============================================================
    // Markdown Renderer
    // ============================================================
    function renderMarkdown(text) {
        let html = escapeHtmlBasic(String(text || '').replace(/\r\n?/g, '\n')).trim();

        // كتل الكود (تُستخرج أولاً حتى لا تتأثر ببقية القواعد)
        const codeBlocks = [];
        html = html.replace(/```[a-z]*\n?([\s\S]*?)```/gi, function (_, code) {
            codeBlocks.push('<pre class="ai-code-block"><code>' + code.trim() + '</code></pre>');
            return '\u0000' + (codeBlocks.length - 1) + '\u0000';
        });

        // روابط ([text](url)) — نسمح فقط بروابط http(s) أو روابط داخلية أو wa.me/tel
        html = html.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (_, linkText, url) {
            const safe = /^(https?:\/\/|\/|tel:|mailto:)/i.test(url) || /^[a-z0-9_-]+\.php/i.test(url);
            if (!safe) return linkText;
            const isInternal = !/^https?:\/\//i.test(url);
            const target = isInternal ? '' : ' target="_blank" rel="noopener noreferrer"';
            return '<a href="' + url + '" class="ai-link' + (isInternal ? ' is-internal' : '') + '"' + target + '>' + linkText + '</a>';
        });

        html = html.replace(/`([^`\n]+)`/g, '<code class="ai-code-inline">$1</code>');
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/(^|[\s(])\*([^*\n]+)\*(?=[\s).,!?،]|$)/g, '$1<em>$2</em>');

        // كتل الأسطر: عناوين / قوائم نقطية / قوائم مرقمة / فقرات
        const lines = html.split('\n');
        const out = [];
        let list = null; // {type:'ul'|'ol', items:[]}
        const flush = function () {
            if (!list) return;
            out.push('<' + list.type + ' class="ai-list">' + list.items.map(function (i) { return '<li>' + i + '</li>'; }).join('') + '</' + list.type + '>');
            list = null;
        };
        lines.forEach(function (raw) {
            const line = raw.trim();
            let m;
            if ((m = line.match(/^[-*•]\s+(.*)$/))) {
                if (!list || list.type !== 'ul') { flush(); list = { type: 'ul', items: [] }; }
                list.items.push(m[1]);
            } else if ((m = line.match(/^(\d+)[.)]\s+(.*)$/))) {
                if (!list || list.type !== 'ol') { flush(); list = { type: 'ol', items: [] }; }
                list.items.push(m[2]);
            } else if ((m = line.match(/^#{1,4}\s+(.*)$/))) {
                flush();
                out.push('<p class="ai-md-heading"><strong>' + m[1] + '</strong></p>');
            } else if (line === '') {
                flush();
                out.push('');
            } else {
                flush();
                out.push(line);
            }
        });
        flush();

        // دمج الأسطر: فقرات مفصولة بسطر فارغ، وأسطر متتالية بـ <br>
        html = out.join('\n')
            .replace(/\n{2,}/g, '<span class="ai-md-gap"></span>')
            .replace(/\n(?=<(ul|ol|p)\b)/g, '')
            .replace(/(<\/(ul|ol|p)>)\n/g, '$1')
            .replace(/\n/g, '<br>');

        html = html.replace(/\u0000(\d+)\u0000/g, function (_, i) { return codeBlocks[+i]; });
        return html;
    }

    function escapeHtmlBasic(str) {
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    function escapeHtml(str) {
        return escapeHtmlBasic(str).replace(/\n/g, '<br>');
    }

    // ============================================================
    // Typing Indicator
    // ============================================================
    function showTyping() {
        const id  = 'ai-typing-' + Date.now();
        const div = document.createElement('div');
        div.className = 'ai-typing';
        div.id = id;
        div.setAttribute('aria-label', PAGE.lang === 'ar' ? 'المساعد يكتب...' : 'Assistant is typing...');
        div.innerHTML = `
            <div class="ai-typing-bubble">
                <span class="ai-typing-dot"></span>
                <span class="ai-typing-dot"></span>
                <span class="ai-typing-dot"></span>
            </div>`;

        DOM.messages.appendChild(div);
        scrollToBottom(true);
        return id;
    }

    function removeTyping(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    // ============================================================
    // HTTP Request
    // ============================================================
    function sendRequest(action, payload) {
        return fetch(CONFIG.endpoint, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(Object.assign({ action: action }, payload)),
        }).then(function (res) {
            return res.json();
        });
    }

    // ============================================================
    // UI Helpers
    // ============================================================
    function setSendState(disabled) {
        DOM.sendBtn.disabled = disabled;
        DOM.input.disabled   = disabled;
        DOM.sendBtn.classList.toggle('is-loading', disabled);
    }

    function scrollToBottom(animate) {
        requestAnimationFrame(function () {
            if (!animate) {
                DOM.messages.scrollTop = DOM.messages.scrollHeight;
            } else {
                DOM.messages.scrollTo({ top: DOM.messages.scrollHeight, behavior: 'smooth' });
            }
        });
    }

    function autoResizeInput() {
        DOM.input.style.height = 'auto';
        DOM.input.style.height = Math.min(DOM.input.scrollHeight, 120) + 'px';
    }

    function updateCharCount() {
        const len = DOM.input.value.length;
        const max = CONFIG.maxLength;
        let counter = document.getElementById('ai-char-count');

        if (!counter) {
            counter = document.createElement('span');
            counter.id = 'ai-char-count';
            counter.className = 'ai-char-count';
            counter.setAttribute('aria-live', 'polite');
            const inputArea = DOM.input.closest('.ai-input-area');
            if (inputArea) inputArea.appendChild(counter);
        }

        counter.textContent = len > 0 ? len + '/' + max : '';
        counter.classList.toggle('is-warning', len > max * 0.85);
        counter.classList.toggle('is-danger',  len >= max);
    }

    function showError(msg) {
        if (!DOM.errorToast) return;
        DOM.errorToast.textContent = msg;
        DOM.errorToast.classList.add('is-visible');
        setTimeout(hideError, 5000);
    }

    function hideError() {
        if (!DOM.errorToast) return;
        DOM.errorToast.classList.remove('is-visible');
    }

    function updateBadge() {
        if (!DOM.badge) return;
        const c = STATE.unreadCount;
        DOM.badge.textContent = c > 9 ? '9+' : (c > 0 ? c : '');
        DOM.badge.setAttribute('data-count', c);
    }

    function resetBadge() {
        STATE.unreadCount = 0;
        updateBadge();
    }

    function formatTime(date) {
        return date.toLocaleTimeString(PAGE.lang === 'ar' ? 'ar-YE' : 'en-US', {
            hour:   '2-digit',
            minute: '2-digit',
        });
    }

})();
