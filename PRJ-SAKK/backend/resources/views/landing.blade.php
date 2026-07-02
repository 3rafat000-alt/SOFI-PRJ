<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F7F3EE">
    <meta name="description" content="SAKK — محفظتك الرقمية. حوّل، ادفع، وفّر بالذهب، وأصدر بطاقات. قريباً.">
    <title>SAKK — محفظتك الرقمية | قريباً</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @include('components.critical-css')
    <style>
        /* ═══════════════════════════════════════════
           SAKK Landing Page — Editorial Premium
           هوية "العنابي الدمشقي" · لا حدود · لا ظلال
           ═══════════════════════════════════════════ */

        /* ── Reset ── */
        *,*::before,*::after{ box-sizing:border-box; margin:0; padding:0; }

        body{
            font-family:var(--font);
            background:var(--marble);
            color:var(--ink);
            line-height:1.6;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
            overflow-x:hidden;
            scroll-behavior:smooth;
        }

        a{ color:inherit; text-decoration:none; }

        /* ── Container ── */
        .container{
            max-width:1008px;
            margin:0 auto;
            padding:0 var(--s-xl);
        }

        .container--narrow{
            max-width:720px;
        }

        /* ── Nav ── */
        .nav{
            display:flex;
            align-items:center;
            padding:var(--s-lg) 0;
            position:relative;
            z-index:10;
        }

        .nav-logo{
            display:flex;
            align-items:center;
            gap:10px;
            font-weight:700;
            font-size:var(--fs-lg);
            color:var(--wine);
            letter-spacing:-.02em;
        }

        .nav-logo-mark{
            display:flex;
            align-items:center;
            justify-content:center;
            width:36px;
            height:36px;
            border-radius:var(--r-md);
            background:var(--wine);
            color:#fff;
            font-size:var(--fs-sm);
            font-weight:800;
        }

        /* ── Hero ── */
        .hero{
            padding:var(--s-4xl) 0 var(--s-3xl);
            text-align:center;
            position:relative;
        }

        .hero-coming{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 18px 6px 16px;
            border-radius:var(--r-full);
            background:var(--wine-soft);
            color:var(--wine);
            font-size:var(--fs-sm);
            font-weight:600;
            margin-bottom:var(--s-xl);
        }

        .hero-coming svg{
            flex-shrink:0;
        }

        .hero h1{
            font-size:clamp(2rem, 5vw, 3.6rem);
            font-weight:800;
            line-height:1.2;
            letter-spacing:-.03em;
            color:var(--ink);
            max-width:680px;
            margin:0 auto;
        }

        .hero h1 .accent{
            color:var(--wine);
        }

        .hero-sub{
            font-size:clamp(var(--fs-base), 1.4vw, var(--fs-lg));
            color:var(--ink-2);
            max-width:520px;
            margin:var(--s-lg) auto 0;
            line-height:1.7;
        }

        .hero-actions{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:var(--s-md);
            margin-top:var(--s-2xl);
            flex-wrap:wrap;
        }

        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            padding:14px 28px;
            border-radius:var(--r-full);
            font-family:var(--font);
            font-size:var(--fs-base);
            font-weight:600;
            border:none;
            cursor:pointer;
            transition:all var(--t-fast);
            text-decoration:none;
        }

        .btn-primary{
            background:var(--wine);
            color:#fff;
        }

        .btn-primary:hover{
            background:var(--wine-dark);
            transform:translateY(-1px);
        }

        .btn-primary:active{
            transform:none;
        }

        .btn-ghost{
            background:var(--surface);
            color:var(--ink);
            border:1.5px solid var(--input-bg);
        }

        .btn-ghost:hover{
            border-color:var(--wine);
            color:var(--wine);
        }

        /* ── Divider (decorative gold fade) ── */
        .divider{
            width:80px;
            height:3px;
            border-radius:2px;
            background:linear-gradient(90deg, transparent, var(--gold), transparent);
            margin:0 auto;
        }

        /* ── Brand Promise ── */
        .promise{
            padding:var(--s-3xl) 0;
            text-align:center;
        }

        .promise h2{
            font-size:clamp(1.4rem, 3vw, 2rem);
            font-weight:700;
            color:var(--ink);
            line-height:1.4;
        }

        .promise h2 .gold{
            color:var(--gold);
        }

        .promise p{
            font-size:var(--fs-base);
            color:var(--ink-2);
            max-width:580px;
            margin:var(--s-md) auto 0;
            line-height:1.8;
        }

        /* ── Features Grid ── */
        .features{
            padding:var(--s-3xl) 0;
        }

        .features-grid{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:var(--s-lg);
            margin-top:var(--s-2xl);
        }

        @media (max-width:768px){
            .features-grid{
                grid-template-columns:1fr;
                gap:var(--s-md);
            }
        }

        .feature-card{
            background:var(--surface);
            border-radius:var(--r-lg);
            padding:var(--s-xl);
            text-align:center;
            position:relative;
            transition:background var(--t-fast);
        }

        .feature-card:hover{
            background:#FFFCF8;
        }

        .feature-icon{
            display:flex;
            align-items:center;
            justify-content:center;
            width:52px;
            height:52px;
            border-radius:var(--r-md);
            background:var(--wine-soft);
            margin:0 auto var(--s-md);
            color:var(--wine);
        }

        .feature-card h3{
            font-size:var(--fs-lg);
            font-weight:700;
            color:var(--ink);
            margin-bottom:var(--s-sm);
        }

        .feature-card p{
            font-size:var(--fs-sm);
            color:var(--ink-2);
            line-height:1.7;
        }

        .feature-coming{
            display:inline-flex;
            align-items:center;
            gap:4px;
            font-size:var(--fs-xs);
            font-weight:600;
            color:var(--gold);
            margin-top:var(--s-md);
            letter-spacing:.02em;
        }

        /* ── Waitlist Section ── */
        .waitlist{
            padding:var(--s-3xl) 0 var(--s-4xl);
            text-align:center;
        }

        .waitlist-card{
            background:var(--surface);
            border-radius:var(--r-lg);
            padding:var(--s-2xl) var(--s-xl);
            max-width:560px;
            margin:0 auto;
        }

        .waitlist h2{
            font-size:clamp(1.3rem, 2.5vw, 1.7rem);
            font-weight:700;
            color:var(--ink);
        }

        .waitlist p{
            font-size:var(--fs-sm);
            color:var(--ink-2);
            margin-top:var(--s-sm);
            line-height:1.7;
        }

        .waitlist-form{
            display:flex;
            gap:var(--s-sm);
            margin-top:var(--s-lg);
            max-width:440px;
            margin-left:auto;
            margin-right:auto;
        }

        .waitlist-form input{
            flex:1;
            padding:12px 18px;
            border-radius:var(--r-full);
            border:1.5px solid var(--input-bg);
            background:var(--input-bg);
            font-family:var(--font);
            font-size:var(--fs-sm);
            color:var(--ink);
            outline:none;
            transition:border-color var(--t-fast), background var(--t-fast);
            direction:rtl;
        }

        .waitlist-form input::placeholder{
            color:var(--ink-hint);
        }

        .waitlist-form input:focus{
            border-color:var(--wine);
            background:var(--surface);
        }

        .waitlist-form button{
            padding:12px 28px;
            border-radius:var(--r-full);
            background:var(--wine);
            color:#fff;
            font-family:var(--font);
            font-size:var(--fs-sm);
            font-weight:600;
            border:none;
            cursor:pointer;
            transition:background var(--t-fast), opacity var(--t-fast);
            white-space:nowrap;
        }

        .waitlist-form button:hover:not(:disabled){
            background:var(--wine-dark);
        }

        .waitlist-form button:disabled{
            opacity:0.6;
            cursor:not-allowed;
        }

        .waitlist-note{
            font-size:var(--fs-xs);
            color:var(--ink-hint);
            margin-top:var(--s-md);
        }

        /* ── App Badges ── */
        .app-badges{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:var(--s-md);
            margin-top:var(--s-2xl);
            flex-wrap:wrap;
        }

        .app-badge{
            display:inline-flex;
            align-items:center;
            gap:10px;
            padding:12px 24px;
            border-radius:var(--r-md);
            background:var(--input-bg);
            color:var(--ink);
            font-size:var(--fs-sm);
            font-weight:600;
            transition:background var(--t-fast);
            cursor:default;
        }

        .app-badge .coming-label{
            font-size:var(--fs-xs);
            font-weight:500;
            color:var(--gold);
        }

        /* ── Footer ── */
        .footer{
            padding:var(--s-2xl) 0 var(--s-xl);
            border-top:1px solid var(--input-bg);
            text-align:center;
        }

        .footer-copy{
            font-size:var(--fs-sm);
            color:var(--ink-hint);
        }

        .footer-copy .brand{
            color:var(--wine);
            font-weight:600;
        }

        /* ── Toast notification ── */
        .toast{
            position:fixed;
            bottom:var(--s-xl);
            left:50%;
            transform:translateX(-50%) translateY(100px);
            background:var(--ink);
            color:#fff;
            padding:12px 24px;
            border-radius:var(--r-full);
            font-size:var(--fs-sm);
            font-weight:500;
            opacity:0;
            transition:all var(--t-slow);
            pointer-events:none;
            z-index:var(--z-modal);
        }

        .toast.show{
            opacity:1;
            transform:translateX(-50%) translateY(0);
        }

        /* ── Responsive ── */
        @media (max-width:640px){
            .container{ padding:0 var(--s-md); }
            .hero{ padding:var(--s-2xl) 0 var(--s-xl); }
            .hero h1{ font-size:1.6rem; }
            .hero-actions{ flex-direction:column; width:100%; }
            .hero-actions .btn{ width:100%; justify-content:center; }
            .waitlist-form{ flex-direction:column; }
            .waitlist-form button{ width:100%; justify-content:center; }
        }

        @media (max-width:400px){
            .nav-logo{ font-size:var(--fs-base); }
            .nav-logo-mark{ width:32px; height:32px; font-size:var(--fs-xs); }
            .hero-coming{ font-size:var(--fs-xs); padding:4px 14px 4px 12px; }
        }
    </style>
</head>
<body>

    <!-- ═══ Nav ═══ -->
    <nav class="container nav">
        <a href="/" class="nav-logo" aria-label="SAKK homepage">
            <span class="nav-logo-mark">صك</span>
            SAKK
        </a>
    </nav>

    <!-- ═══ Hero ═══ -->
    <section class="hero container" data-reveal>
        <div class="hero-coming">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            قريباً
        </div>

        <h1>محفظتك الرقمية <span class="accent">الأكثر تطوراً</span></h1>

        <p class="hero-sub">
            حوّل واستقبل الأموال فوراً، أنشئ بطاقات للدفع، وفّر بالذهب — كلها من تطبيق واحد.
        </p>

        <div class="hero-actions">
            <a href="#waitlist" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 17h-3a4 4 0 0 0-4 4v1"/>
                    <path d="M22 12h-6"/>
                    <path d="M22 7h-8"/>
                    <path d="M5 17H2v-6a8 8 0 0 1 8-8h4"/>
                    <path d="M2 17h3"/>
                    <circle cx="17" cy="17" r="4"/>
                    <path d="M17 15v2l1 1"/>
                </svg>
                نوّبني عند الإطلاق
            </a>
            <a href="#features" class="btn btn-ghost">
                اكتشف المزايا
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
        </div>
    </section>

    <!-- ═══ Divider ═══ -->
    <div class="divider"></div>

    <!-- ═══ Brand Promise ═══ -->
    <section class="promise container container--narrow" data-reveal>
        <h2>أموالك، بطاقاتك، <span class="gold">ذهبك</span> — في محفظة واحدة</h2>
        <p>
            صمّمنا SAKK لتكون محطتك المالية الوحيدة. لا حاجة لحساب بنكي تقليدي.
            حوّل، ادفع، احصل على راتبك، وفّر بالذهب — كل شيء من هاتفك.
        </p>
    </section>

    <!-- ═══ Features ═══ -->
    <section class="features container" id="features">
        <div class="divider"></div>

        <div class="features-grid">
            <!-- Feature 1: Wallet -->
            <div class="feature-card" data-reveal data-reveal-delay="0" data-feature-key="wallet">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="3"/>
                        <circle cx="16" cy="12" r="2"/>
                        <path d="M1 8h22"/>
                    </svg>
                </div>
                <h3>محفظة رقمية</h3>
                <p>حوّل واستقبل الأموال فوراً. حسابات متعددة، تحويلات محلية ودولية بدون تعقيد.</p>
                <div class="feature-coming">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    قريباً
                </div>
            </div>

            <!-- Feature 2: Cards -->
            <div class="feature-card" data-reveal data-reveal-delay="80" data-feature-key="cards">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="6" width="22" height="12" rx="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                        <path d="M5 15h4"/>
                        <path d="M12 15h2"/>
                    </svg>
                </div>
                <h3>بطاقات افتراضية</h3>
                <p>أنشئ بطاقات رقمية للتسوق والدفع أونلاين. تحكم كامل بالمصاريف والأمان.</p>
                <div class="feature-coming">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    قريباً
                </div>
            </div>

            <!-- Feature 3: Gold -->
            <div class="feature-card" data-reveal data-reveal-delay="160" data-feature-key="gold">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="6"/>
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 2v20"/>
                        <path d="M2 12h20"/>
                    </svg>
                </div>
                <h3>الذهب والادخار</h3>
                <p>وفّر أموالك بالذهب لحماية مدخراتك من التضخم. اشترِ وبع بسهولة.</p>
                <div class="feature-coming">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    قريباً
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ Waitlist / Coming Soon ═══ -->
    <section class="waitlist container container--narrow" id="waitlist">
        <div class="waitlist-card" data-reveal>
            <h2>انطلق معنا في رحلتنا</h2>
            <p>سجّل بريدك الإلكتروني ليصلك إشعار فور إطلاق التطبيق — وكن من الأوائل.</p>

            <form class="waitlist-form" id="waitlistForm" onsubmit="return handleWaitlist(event)">
                <input type="email" id="waitlistEmail" placeholder="بريدك الإلكتروني" required aria-label="البريد الإلكتروني" dir="rtl">
                <button type="submit">نوّبني</button>
            </form>

            <p class="waitlist-note">لن نستخدم بريدك لأي غرض غير إشعارك عند الإطلاق.</p>

            <div class="app-badges">
                <a href="javascript:void(0);" class="app-badge" data-app-store="ios" aria-label="تحميل من App Store">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <rect x="3" y="1" width="18" height="22" rx="4"/>
                        <line x1="8" y1="19" x2="16" y2="19"/>
                    </svg>
                    App Store
                    <span class="coming-label">قريباً</span>
                </a>
                <a href="javascript:void(0);" class="app-badge" data-app-store="android" aria-label="تحميل من Google Play">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <rect x="4" y="2" width="16" height="20" rx="3"/>
                        <circle cx="12" cy="18" r="1"/>
                        <line x1="8" y1="6" x2="16" y2="6"/>
                    </svg>
                    Google Play
                    <span class="coming-label">قريباً</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ═══ Footer ═══ -->
    <footer class="footer container">
        <div class="footer-copy">
            <span class="brand">SAKK</span> &copy; {{ date('Y') }} — جميع الحقوق محفوظة
        </div>
    </footer>

    <!-- ═══ Toast ═══ -->
    <div class="toast" id="toast" role="status" aria-live="polite" aria-atomic="true"></div>

    <script>
        // ── Scroll reveal (IntersectionObserver, zero-dependency) ──
        (function(){
            var els = document.querySelectorAll('[data-reveal]');
            if(!els.length) return;
            var obs = new IntersectionObserver(function(entries){
                entries.forEach(function(entry){
                    if(entry.isIntersecting){
                        var delay = entry.target.getAttribute('data-reveal-delay');
                        if(delay){
                            entry.target.style.transitionDelay = delay + 'ms';
                        }
                        entry.target.classList.add('in');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold:0.15, rootMargin:'0px 0px -40px 0px' });
            els.forEach(function(el){ obs.observe(el); });
        })();

        // ── Waitlist form handler (saves email — lightweight, no deps) ──
        function handleWaitlist(e){
            e.preventDefault();
            var email = document.getElementById('waitlistEmail').value.trim();
            var btn = document.querySelector('.waitlist-form button');
            if(!email) return false;

            btn.disabled = true;

            try {
                var toast = document.getElementById('toast');
                toast.textContent = '✓ تم التسجيل! سنخبرك فور الإطلاق.';
                toast.classList.add('show');
                clearTimeout(toast._t);
                toast._t = setTimeout(function(){ toast.classList.remove('show'); }, 3000);

                document.getElementById('waitlistEmail').value = '';
            } catch (err) {
                var toast = document.getElementById('toast');
                toast.textContent = '✗ حدث خطأ. حاول مجدداً.';
                toast.classList.add('show');
            } finally {
                setTimeout(function(){ btn.disabled = false; }, 1500);
            }
            return false;
        }
    </script>
</body>
</html>
