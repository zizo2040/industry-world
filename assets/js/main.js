/**
 * Alam Al-Sinaa - Main JavaScript (Updated)
 * - Circuit Breaker theme toggle
 * - Logo text animation (auto-show 3s on load, toggle on click)
 * - Improved mobile side-panel menu
 * - Globe+code language button
 * - Icon-only WhatsApp & Call
 */

(function() {
    'use strict';

    // ============================================================
    // DOM READY
    // ============================================================
    
    function domReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    // ============================================================
    // THEME MANAGEMENT (Circuit Breaker)
    // ============================================================
    
    const ThemeManager = {
        init() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            this.apply(savedTheme);
            
            // Support multiple theme toggle buttons (desktop header + mobile panel)
            document.querySelectorAll('.theme-toggle').forEach(toggle => {
                toggle.addEventListener('click', () => this.toggle());
            });
        },
        
        apply(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            // Mirror to a cookie so the next server-rendered page picks the
            // same theme immediately (server reads $_COOKIE['theme']).
            document.cookie = 'theme=' + theme + ';path=/;max-age=31536000;samesite=lax';
            // Lever animation handled purely via CSS data-theme attribute
            SiteAssets.applyTheme(theme);
        },
        
        toggle() {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'light' ? 'dark' : 'light';
            this.apply(next);
        }
    };

    // ============================================================
    // LANGUAGE MANAGEMENT
    // ============================================================
    
    const LangManager = {
        init() {
            // Support multiple lang toggle buttons
            document.querySelectorAll('.lang-toggle').forEach(toggle => {
                toggle.addEventListener('click', () => this.toggle());
            });
        },
        
        toggle() {
            const currentLang = document.documentElement.lang || 'ar';
            const nextLang = currentLang.startsWith('ar') ? 'en' : 'ar';
            
            const url = new URL(window.location.href);
            url.searchParams.set('lang', nextLang);
            window.location.href = url.toString();
        }
    };

    // ============================================================
    // LOGO TOOLTIP (name + tagline bubble)
    // ============================================================
    // Desktop/mouse users see the tooltip on :hover via CSS alone.
    // Touch devices have no hover, so the first tap reveals the
    // tooltip instead of navigating; a second tap (or tapping
    // elsewhere) proceeds to navigate / dismisses it.

    const LogoTooltip = {
        init() {
            const logoLink = document.getElementById('site-logo-link');
            if (!logoLink) return;

            const isTouchDevice = window.matchMedia('(hover: none)').matches;
            if (!isTouchDevice) return;

            logoLink.addEventListener('click', (e) => {
                const isActive = logoLink.classList.contains('tooltip-active');
                if (!isActive) {
                    e.preventDefault();
                    logoLink.classList.add('tooltip-active');
                }
                // If already active, allow the click through as normal navigation.
            });

            document.addEventListener('click', (e) => {
                if (!logoLink.contains(e.target)) {
                    logoLink.classList.remove('tooltip-active');
                }
            });
        }
    };

    // ============================================================
    // MOBILE MENU (Side Panel)
    // ============================================================
    
    const MobileMenu = {
        init() {
            const toggle = document.querySelector('.menu-toggle');
            const menu = document.querySelector('.nav-menu');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const closeBtn = document.querySelector('.nav-close-btn');
            
            if (!toggle || !menu) return;
            
            toggle.addEventListener('click', () => {
                this.open();
            });
            
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }
            
            if (overlay) {
                overlay.addEventListener('click', () => this.close());
            }
            
            // Close on nav-link click (but not dropdown toggles).
            // The off-canvas panel is used at every breakpoint now, so this
            // always applies (no width check needed).
            menu.querySelectorAll('.nav-link:not(.nav-dropdown-toggle)').forEach(link => {
                link.addEventListener('click', () => {
                    setTimeout(() => this.close(), 150);
                });
            });
            
            // Dropdown toggles inside the panel - always expand/collapse in place
            menu.querySelectorAll('.nav-dropdown-toggle').forEach(dropToggle => {
                dropToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const dropdown = dropToggle.closest('.nav-dropdown');
                    if (dropdown) dropdown.classList.toggle('open');
                });
            });
            
            // Close on Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && menu.classList.contains('active')) this.close();
            });

            // Pages restored from the back/forward cache keep their DOM state;
            // make sure the panel is never left open (or the body left locked)
            // when the visitor comes back to a page.
            window.addEventListener('pageshow', (e) => {
                if (e.persisted || menu.classList.contains('active')) this.close(true);
            });

            // Close the drawer when growing to the desktop layout.
            window.matchMedia('(min-width: 992px)').addEventListener?.('change', (mq) => {
                if (mq.matches && menu.classList.contains('active')) this.close();
            });
        },
        
        open() {
            const menu = document.querySelector('.nav-menu');
            const toggle = document.querySelector('.menu-toggle');
            const overlay = document.querySelector('.mobile-menu-overlay');
            
            if (menu) menu.classList.add('active');
            if (toggle) {
                toggle.classList.add('active');
                toggle.setAttribute('aria-expanded', 'true');
            }
            if (overlay) overlay.classList.add('active');
            // position:fixed + full inset (via CSS class) locks background
            // scroll reliably on both Android and iOS Safari; overflow:hidden
            // alone is not enough on iOS.
            this._scrollY = window.scrollY;
            document.body.classList.add('menu-open');
            document.body.style.top = `-${this._scrollY}px`;
        },
        
        close(skipScrollRestore) {
            const menu = document.querySelector('.nav-menu');
            const wasOpen = menu && menu.classList.contains('active');
            const toggle = document.querySelector('.menu-toggle');
            const overlay = document.querySelector('.mobile-menu-overlay');
            
            if (menu) menu.classList.remove('active');
            if (toggle) {
                toggle.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
            }
            if (overlay) overlay.classList.remove('active');
            document.body.classList.remove('menu-open');
            document.body.style.top = '';
            if (wasOpen && !skipScrollRestore && typeof this._scrollY === 'number') {
                window.scrollTo({ top: this._scrollY, behavior: 'instant' });
            }
            this._scrollY = null;
        }
    };

    // ============================================================
    // SEARCH OVERLAY
    // ============================================================
    
    const SearchOverlay = {
        init() {
            const trigger = document.querySelector('.search-trigger');
            const overlay = document.querySelector('.search-overlay');
            const close = document.querySelector('.search-close');
            
            if (!trigger || !overlay) return;
            
            trigger.addEventListener('click', () => this.open());
            close?.addEventListener('click', () => this.close());
            
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.close();
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.close();
            });
        },
        
        open() {
            const overlay = document.querySelector('.search-overlay');
            if (overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    overlay.querySelector('.search-input')?.focus();
                }, 100);
            }
        },
        
        close() {
            const overlay = document.querySelector('.search-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    };

    // ============================================================
    // HEADER SCROLL EFFECT
    // ============================================================
    
    const HeaderScroll = {
        init() {
            const header = document.querySelector('.header');
            if (!header) return;
            
            let ticking = false;
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(() => {
                        header.classList.toggle('scrolled', window.scrollY > 50);
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        }
    };

    // ============================================================
    // SCROLL REVEAL
    // ============================================================
    
    const ScrollReveal = {
        init() {
            const elements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
            if (elements.length === 0) return;
            
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                elements.forEach(el => el.classList.add('visible'));
                return;
            }
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Stagger based on position among reveal siblings only,
                        // capped so large grids don't produce long waits.
                        if (!entry.target.style.transitionDelay) {
                            const siblings = Array.from(entry.target.parentElement?.children || [])
                                .filter(el => el.classList.contains('reveal') || el.classList.contains('reveal-left') || el.classList.contains('reveal-right'));
                            const index = siblings.indexOf(entry.target);
                            const delay = Math.min(index, 6) * 0.08;
                            entry.target.style.transitionDelay = `${delay}s`;
                        }
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                // threshold 0: very tall blocks (long tables/lists on phones)
                // can never reach 10% visibility, which left them invisible.
                threshold: 0,
                rootMargin: '0px 0px -40px 0px'
            });
            
            elements.forEach(el => observer.observe(el));
        }
    };

    // ============================================================
    // SCROLL PROGRESS BAR
    // ============================================================
    
    const ScrollProgressBar = {
        init() {
            const bar = document.querySelector('.scroll-progress');
            if (!bar) return;
            
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                bar.style.transition = 'none';
            }
            
            let ticking = false;
            
            const update = () => {
                const scrollTop = window.scrollY;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
                bar.style.width = progress + '%';
                ticking = false;
            };
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(update);
                    ticking = true;
                }
            }, { passive: true });
            
            update();
        }
    };

    // ============================================================
    // ANIMATED COUNTERS
    // ============================================================
    
    const AnimatedCounters = {
        init() {
            const counters = document.querySelectorAll('[data-count]');
            if (counters.length === 0) return;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animate(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            counters.forEach(counter => observer.observe(counter));
        },
        
        animate(element) {
            const target = parseInt(element.dataset.count, 10);
            const suffix = element.dataset.suffix || '';
            const prefix = element.dataset.prefix || '';
            const duration = parseInt(element.dataset.duration, 10) || 2000;
            
            const startTime = performance.now();
            
            const update = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(2, -10 * progress);
                const current = Math.floor(eased * target);
                
                element.textContent = prefix + current.toLocaleString() + suffix;
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    element.textContent = prefix + target.toLocaleString() + suffix;
                }
            };
            
            requestAnimationFrame(update);
        }
    };

    // ============================================================
    // FAQ ACCORDION
    // ============================================================
    
    const FaqAccordion = {
        init() {
            const items = document.querySelectorAll('.faq-item');
            
            items.forEach(item => {
                const question = item.querySelector('.faq-question');
                if (!question) return;
                
                question.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');
                    items.forEach(i => i.classList.remove('active'));
                    if (!isActive) item.classList.add('active');
                });
            });
        }
    };

    // ============================================================
    // BACK TO TOP
    // ============================================================
    
    const BackToTop = {
        init() {
            const button = document.querySelector('.back-to-top');
            if (!button) return;
            
            let ticking = false;
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(() => {
                        button.classList.toggle('visible', window.scrollY > 500);
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
            
            button.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    };

    // ============================================================
    // FLASH MESSAGES
    // ============================================================
    
    const FlashMessages = {
        init() {
            document.querySelectorAll('.flash-close').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const message = e.target.closest('.flash-message');
                    if (message) {
                        message.style.opacity = '0';
                        message.style.transform = 'translate(-50%, -20px)';
                        setTimeout(() => message.remove(), 300);
                    }
                });
            });
            
            document.querySelectorAll('.flash-message').forEach(msg => {
                setTimeout(() => {
                    msg.style.opacity = '0';
                    msg.style.transform = 'translate(-50%, -20px)';
                    setTimeout(() => msg.remove(), 300);
                }, 5000);
            });
        }
    };

    // ============================================================
    // PRODUCT GALLERY
    // ============================================================
    
    const ProductGallery = {
        init() {
            const mainImage = document.querySelector('.product-gallery-main img');
            const thumbs = document.querySelectorAll('.product-gallery-thumb');
            
            if (!mainImage || thumbs.length === 0) return;
            
            thumbs.forEach(thumb => {
                thumb.addEventListener('click', () => {
                    const img = thumb.querySelector('img');
                    if (img) {
                        mainImage.src = img.src;
                        mainImage.alt = img.alt;
                        thumbs.forEach(t => t.classList.remove('active'));
                        thumb.classList.add('active');
                    }
                });
            });
        }
    };

    // ============================================================
    // FORM VALIDATION
    // ============================================================
    
    const FormValidation = {
        init() {
            document.querySelectorAll('form[data-validate]').forEach(form => {
                form.addEventListener('submit', (e) => this.validate(e));
            });
        },
        
        validate(e) {
            const form = e.target;
            let isValid = true;
            
            form.querySelectorAll('.form-error').forEach(el => el.remove());
            form.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));
            
            form.querySelectorAll('[required]').forEach(field => {
                const value = field.value.trim();
                
                if (!value) {
                    isValid = false;
                    this.addError(field, isArabic() ? 'هذا الحقل مطلوب' : 'This field is required');
                }
                
                if (field.type === 'email' && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        this.addError(field, isArabic() ? 'بريد إلكتروني غير صالح' : 'Invalid email address');
                    }
                }
                
                if (field.type === 'tel' && value) {
                    const phoneRegex = /^[\d+\-\s()]{7,20}$/;
                    if (!phoneRegex.test(value)) {
                        isValid = false;
                        this.addError(field, isArabic() ? 'رقم هاتف غير صالح' : 'Invalid phone number');
                    }
                }
            });
            
            if (!isValid) e.preventDefault();
        },
        
        addError(field, message) {
            field.classList.add('error');
            const error = document.createElement('span');
            error.className = 'form-error';
            error.textContent = message;
            field.parentNode.appendChild(error);
        }
    };

    // ============================================================
    // SMOOTH SCROLL
    // ============================================================
    
    const SmoothScroll = {
        init() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    const target = document.querySelector(anchor.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        }
    };

    // ============================================================
    // LAZY LOADING
    // ============================================================
    
    const LazyLoader = {
        init() {
            const images = document.querySelectorAll('img[data-src]');
            if (images.length === 0) return;
            
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    });
                }, { rootMargin: '200px' });
                
                images.forEach(img => observer.observe(img));
            } else {
                images.forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            }
        }
    };

    // ============================================================
    // PARALLAX EFFECT
    // ============================================================
    
    const ParallaxEffect = {
        init() {
            const elements = document.querySelectorAll('[data-parallax]');
            if (elements.length === 0) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            
            let ticking = false;
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(() => {
                        const scrollY = window.scrollY;
                        elements.forEach(el => {
                            const speed = parseFloat(el.dataset.parallax) || 0.5;
                            const rect = el.getBoundingClientRect();
                            const offset = (scrollY - rect.top) * speed;
                            el.style.transform = `translateY(${offset}px)`;
                        });
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        }
    };

    // ============================================================
    // MAGNETIC BUTTON EFFECT
    // ============================================================
    
    const MagneticButtons = {
        init() {
            if (window.matchMedia('(pointer: coarse)').matches) return;
            
            document.querySelectorAll('.btn-magnetic').forEach(btn => {
                btn.addEventListener('mousemove', (e) => {
                    const rect = btn.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    btn.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
                });
                
                btn.addEventListener('mouseleave', () => {
                    btn.style.transform = '';
                });
            });
        }
    };

    // ============================================================
    // WHATSAPP PRE-FILL
    // ============================================================
    
    const WhatsAppIntegration = {
        init() {
            document.querySelectorAll('[data-whatsapp]').forEach(link => {
                link.addEventListener('click', (e) => {
                    const phone = link.dataset.whatsapp;
                    const message = link.dataset.message || '';
                    if (phone) {
                        const url = `https://wa.me/${phone}${message ? '?text=' + encodeURIComponent(message) : ''}`;
                        window.open(url, '_blank');
                    }
                });
            });
        }
    };

    // ============================================================
    // HERO ANIMATION
    // ============================================================
    
    const HeroAnimation = {
        init() {
            const hero = document.querySelector('.hero');
            if (!hero) return;
            
            const cards = hero.querySelectorAll('.hero-floating-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * -2}s`;
            });
        }
    };

    // ============================================================
    // TYPING EFFECT
    // ============================================================
    
    const TypingEffect = {
        init() {
            const elements = document.querySelectorAll('[data-typing]');
            
            elements.forEach(el => {
                const text = el.dataset.typing;
                const speed = parseInt(el.dataset.typingSpeed, 10) || 100;
                
                el.textContent = '';
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.type(el, text, speed);
                            observer.unobserve(el);
                        }
                    });
                });
                
                observer.observe(el);
            });
        },
        
        type(element, text, speed) {
            let index = 0;
            
            const tick = () => {
                if (index < text.length) {
                    element.textContent += text.charAt(index);
                    index++;
                    setTimeout(tick, speed);
                }
            };
            
            tick();
        }
    };

    // ============================================================
    // CURSOR GLOW EFFECT
    // ============================================================
    
    const CursorGlow = {
        init() {
            if (window.matchMedia('(pointer: coarse)').matches) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            
            const glow = document.createElement('div');
            glow.className = 'cursor-glow';
            glow.style.cssText = `
                position: fixed;
                width: 400px;
                height: 400px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(2,129,222,0.08) 0%, transparent 70%);
                pointer-events: none;
                z-index: 9999;
                transform: translate(-50%, -50%);
                transition: opacity 0.3s;
                opacity: 0;
            `;
            document.body.appendChild(glow);
            
            let mouseX = 0, mouseY = 0;
            let glowX = 0, glowY = 0;
            
            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
                glow.style.opacity = '1';
            });
            
            document.addEventListener('mouseleave', () => {
                glow.style.opacity = '0';
            });
            
            const animate = () => {
                glowX += (mouseX - glowX) * 0.1;
                glowY += (mouseY - glowY) * 0.1;
                glow.style.left = glowX + 'px';
                glow.style.top = glowY + 'px';
                requestAnimationFrame(animate);
            };
            
            animate();
        }
    };

    // ============================================================
    // SITE ASSETS (logo + cursor light/dark swap, no reload)
    // Reads the JSON block rendered by includes/header.php
    // (getSiteAssetsConfig() in includes/functions.php) and keeps every
    // logo spot + the custom cursor in sync with the active theme.
    // ============================================================

    const SiteAssets = {
        config: { logos: {}, cursor: {} },

        init() {
            const el = document.getElementById('site-assets-config');
            if (el) {
                try {
                    this.config = JSON.parse(el.textContent || '{}');
                } catch (e) {
                    this.config = { logos: {}, cursor: {} };
                }
            }
            this.applyTheme(document.documentElement.getAttribute('data-theme') || 'light');
        },

        applyTheme(theme) {
            this.swapImage('site-logo-img', theme);
            this.swapImage('mobile-menu-logo-img', theme);
            this.swapImage('footer-logo-img', theme);
        },

        swapImage(elId, theme) {
            const el = document.getElementById(elId);
            if (!el) return;
            const light = el.dataset.logoLight;
            const dark = el.dataset.logoDark;
            const src = theme === 'dark' ? (dark || light) : (light || dark);
            if (src && el.getAttribute('src') !== src) {
                el.setAttribute('src', src);
            }
        }
    };

    // ============================================================
    // CUSTOM CURSOR (dot + halo, brand-themed default — bolt icon)
    // Desktop-only, opt-out via <body data-custom-cursor="off">
    // ============================================================

    const CustomCursor = {
        init() {
            if (document.body.dataset.customCursor === 'off') return;
            if (window.matchMedia('(pointer: coarse)').matches) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const dot = document.createElement('div');
            dot.className = 'custom-cursor-dot';
            const ring = document.createElement('div');
            ring.className = 'custom-cursor-ring';
            ring.innerHTML = '<span class="custom-cursor-bolt">&#9889;</span>';

            document.body.appendChild(ring);
            document.body.appendChild(dot);
            document.body.classList.add('custom-cursor-active');

            let mouseX = -100, mouseY = -100;
            let ringX = -100, ringY = -100;

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
                dot.style.opacity = '1';
                ring.style.opacity = '1';
                dot.style.transform = `translate(${mouseX}px, ${mouseY}px) translate(-50%, -50%)`;
            });

            document.addEventListener('mouseleave', () => {
                dot.style.opacity = '0';
                ring.style.opacity = '0';
            });

            const animate = () => {
                ringX += (mouseX - ringX) * 0.18;
                ringY += (mouseY - ringY) * 0.18;
                ring.style.transform = `translate(${ringX}px, ${ringY}px) translate(-50%, -50%)`;
                requestAnimationFrame(animate);
            };

            animate();
        }
    };

    const RippleEffect = {
        init() {
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn');
                if (!btn) return;

                const rect = btn.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const ripple = document.createElement('span');

                ripple.className = 'btn-ripple';
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';

                btn.appendChild(ripple);
                ripple.addEventListener('animationend', () => ripple.remove());
            });
        }
    };

    // ============================================================
    // FEATURED PRODUCTS SLIDER
    // ============================================================

    const ProductsSlider = {
        root: null,
        viewport: null,
        track: null,
        slides: [],
        dotsWrap: null,
        prevBtn: null,
        nextBtn: null,
        slidesPerView: 3,
        pageIndex: 0,
        totalPages: 1,
        autoplayTimer: null,
        autoplayDelay: 4500,
        touchStartX: 0,
        touchDeltaX: 0,
        isDragging: false,
        resizeTimer: null,

        init() {
            this.root = document.getElementById('products-slider');
            if (!this.root) return;

            this.viewport = this.root.querySelector('.products-slider-viewport');
            this.track = this.root.querySelector('.products-track');
            this.slides = Array.from(this.root.querySelectorAll('.product-slide'));
            this.dotsWrap = this.root.querySelector('.slider-dots');
            this.prevBtn = this.root.querySelector('.slider-arrow-prev');
            this.nextBtn = this.root.querySelector('.slider-arrow-next');

            if (!this.viewport || !this.track || this.slides.length === 0) return;

            this.calcSlidesPerView();
            this.buildDots();
            this.goToPage(0, false);

            this.prevBtn?.addEventListener('click', () => { this.prev(); this.restartAutoplay(); });
            this.nextBtn?.addEventListener('click', () => { this.next(); this.restartAutoplay(); });

            this.root.addEventListener('mouseenter', () => this.stopAutoplay());
            this.root.addEventListener('mouseleave', () => this.startAutoplay());
            this.root.addEventListener('focusin', () => this.stopAutoplay());
            this.root.addEventListener('focusout', () => this.startAutoplay());

            this.track.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: true });
            this.track.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: true });
            this.track.addEventListener('touchend', () => this.onTouchEnd());

            window.addEventListener('resize', () => {
                clearTimeout(this.resizeTimer);
                this.resizeTimer = setTimeout(() => {
                    this.calcSlidesPerView();
                    this.buildDots();
                    this.goToPage(Math.min(this.pageIndex, this.totalPages - 1), false);
                }, 200);
            });

            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.startAutoplay();
            }
        },

        calcSlidesPerView() {
            const width = window.innerWidth;
            if (width < 768) this.slidesPerView = 1;
            else if (width < 1024) this.slidesPerView = 2;
            else this.slidesPerView = 3;

            this.totalPages = Math.max(1, Math.ceil(this.slides.length / this.slidesPerView));
        },

        buildDots() {
            if (!this.dotsWrap) return;
            this.dotsWrap.innerHTML = '';
            for (let i = 0; i < this.totalPages; i++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'slider-dot';
                dot.setAttribute('aria-label', (isArabic() ? 'الشريحة' : 'Slide') + ' ' + (i + 1));
                dot.addEventListener('click', () => { this.goToPage(i); this.restartAutoplay(); });
                this.dotsWrap.appendChild(dot);
            }
            this.updateDots();
        },

        updateDots() {
            if (!this.dotsWrap) return;
            Array.from(this.dotsWrap.children).forEach((dot, i) => {
                dot.classList.toggle('active', i === this.pageIndex);
            });
        },

        goToPage(index, animate = true) {
            this.pageIndex = ((index % this.totalPages) + this.totalPages) % this.totalPages;

            // One "page" always spans exactly one viewport width, regardless
            // of how many slides are visible per page. The track's internal
            // layout is forced physically left-to-right (see CSS), so the
            // shift direction is always negative to advance, in both LTR
            // and RTL documents.
            const viewportWidth = this.viewport.getBoundingClientRect().width;
            const shiftPx = this.pageIndex * viewportWidth;

            this.track.style.transition = animate ? 'transform 0.5s cubic-bezier(0.4,0,0.2,1)' : 'none';
            this.track.style.transform = `translateX(-${shiftPx}px)`;
            this.updateDots();
        },

        next() {
            this.goToPage(this.pageIndex + 1);
        },

        prev() {
            this.goToPage(this.pageIndex - 1);
        },

        startAutoplay() {
            this.stopAutoplay();
            if (this.totalPages <= 1) return;
            this.autoplayTimer = setInterval(() => this.next(), this.autoplayDelay);
        },

        stopAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        },

        restartAutoplay() {
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.startAutoplay();
            }
        },

        onTouchStart(e) {
            this.isDragging = true;
            this.touchStartX = e.touches[0].clientX;
            this.touchDeltaX = 0;
            this.stopAutoplay();
        },

        onTouchMove(e) {
            if (!this.isDragging) return;
            this.touchDeltaX = e.touches[0].clientX - this.touchStartX;
        },

        onTouchEnd() {
            if (!this.isDragging) return;
            this.isDragging = false;

            const threshold = 45;

            if (this.touchDeltaX > threshold) {
                this.prev();
            } else if (this.touchDeltaX < -threshold) {
                this.next();
            }
            this.restartAutoplay();
        }
    };

    // ============================================================
    // UTILITY FUNCTIONS
    // ============================================================
    
    function isArabic() {
        return document.documentElement.lang?.startsWith('ar') || document.dir === 'rtl';
    }

    // ============================================================
    // INITIALIZE ALL
    // ============================================================
    
    domReady(() => {
        ThemeManager.init();
        LangManager.init();
        LogoTooltip.init();
        MobileMenu.init();
        SearchOverlay.init();
        HeaderScroll.init();
        // Branded placeholder for missing/broken images (no ugly alt text)
        (function () {
            const PH = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"><rect width="400" height="300" fill="#eef4fb"/><path d="M212 110l-40 52h30l-6 38 40-52h-30z" fill="#0281de" opacity=".35"/></svg>');
            const fix = (img) => {
                if (img.dataset.fallbackApplied) return;
                img.dataset.fallbackApplied = '1';
                const banner = img.closest('.page-banner');
                if (banner) { banner.style.display = 'none'; return; } // no banner uploaded → just hide it
                img.classList.add('img-fallback');
                img.removeAttribute('srcset');
                const pic = img.closest('picture');
                if (pic) pic.querySelectorAll('source').forEach(s => s.remove());
                img.src = PH;
            };
            document.querySelectorAll('img').forEach(img => {
                if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) fix(img);
            });
            document.addEventListener('error', (e) => {
                if (e.target && e.target.tagName === 'IMG') fix(e.target);
            }, true);
        })();
        ScrollReveal.init();
        ScrollProgressBar.init();
        AnimatedCounters.init();
        FaqAccordion.init();
        BackToTop.init();
        FlashMessages.init();
        ProductGallery.init();
        ProductsSlider.init();
        FormValidation.init();
        SmoothScroll.init();
        LazyLoader.init();
        ParallaxEffect.init();
        MagneticButtons.init();
        WhatsAppIntegration.init();
        HeroAnimation.init();
        TypingEffect.init();
        CursorGlow.init();
        SiteAssets.init();
        CustomCursor.init();
        RippleEffect.init();
    });

})();
