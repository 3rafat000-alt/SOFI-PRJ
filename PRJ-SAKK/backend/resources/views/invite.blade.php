<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F8F5F0">
    <meta name="robots" content="noindex, nofollow">
    <title>دعوة إلى محفظة صكّ</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}

        body{
            font-family: 'Inter', 'Noto Kufi Arabic', system-ui, sans-serif;
            background: #F3EFE9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            background-image:
                radial-gradient(ellipse 70% 50% at 20% 0, rgba(107, 15, 26, .04), transparent),
                radial-gradient(ellipse 60% 40% at 80% 100%, rgba(201, 149, 60, .06), transparent);
        }

        /* ── Card ── */
        .card{
            width: 100%;
            max-width: 424px;
            background: #FDFCFA;
            border-radius: 32px;
            box-shadow:
                0 2px 8px rgba(0,0,0,.04),
                0 16px 48px rgba(107, 15, 26, .08),
                0 32px 80px rgba(107, 15, 26, .06);
            overflow: hidden;
            position: relative;
        }

        /* ── Seal / Decorative top ── */
        .card-seal{
            height: 6px;
            background: linear-gradient(90deg,
                #6B0F1A 0%,
                #C9953C 35%,
                #6B0F1A 50%,
                #C9953C 65%,
                #6B0F1A 100%);
            background-size: 200% 100%;
            animation: sealShimmer 6s ease-in-out infinite;
        }

        @keyframes sealShimmer{
            0%, 100%{ background-position: 0% 0; }
            50%{ background-position: 100% 0; }
        }

        /* ── Hero ── */
        .hero{
            padding: 2rem 1.75rem 1.6rem;
            text-align: center;
        }

        .brand{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 20px;
            background: linear-gradient(145deg, #6B0F1A, #8A1A2A);
            color: #fff;
            font-size: 28px;
            font-weight: 900;
            font-family: 'Noto Kufi Arabic', sans-serif;
            box-shadow: 0 8px 24px rgba(107, 15, 26, .25);
            margin-bottom: 1rem;
        }

        .hero h1{
            font-family: 'Noto Kufi Arabic', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: #1A1A1A;
            line-height: 1.7;
        }

        .hero h1 .name{
            color: #6B0F1A;
            position: relative;
        }

        .hero h1 .name::after{
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #C9953C, transparent);
            border-radius: 2px;
        }

        .hero p{
            font-size: .86rem;
            color: #6B6B6B;
            margin-top: .5rem;
            line-height: 1.8;
        }

        /* ── Reward Pill ── */
        .reward-wrap{
            display: flex;
            justify-content: center;
            margin-top: 1.2rem;
        }

        .reward{
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            background: linear-gradient(135deg, #6B0F1A 0%, #8A1A2A 100%);
            padding: .35rem .4rem .35rem 1.25rem;
            border-radius: 100px;
            box-shadow: 0 4px 16px rgba(107, 15, 26, .2);
        }

        .reward-icon{
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFD700;
        }

        .reward-icon .material-icons-round{
            font-size: 18px;
        }

        .reward-amount{
            font-size: 1.35rem;
            font-weight: 800;
            color: #FFD700;
            letter-spacing: -.02em;
        }

        .reward-label{
            font-size: .72rem;
            color: rgba(255,255,255,.8);
            font-weight: 500;
        }

        /* ── Body ── */
        .body{
            padding: 0 1.75rem 2rem;
        }

        /* ── Divider ── */
        .divider{
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: .25rem 0 1.25rem;
        }

        .divider-line{
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(107, 15, 26, .12), transparent);
        }

        .divider-dot{
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #C9953C;
        }

        /* ── Invalid Alert ── */
        .invalid{
            display: flex;
            align-items: center;
            gap: .65rem;
            background: #FFFAF0;
            border: 1px solid #F5E6CC;
            border-radius: 14px;
            padding: .75rem 1rem;
            margin-bottom: 1.25rem;
            font-size: .8rem;
            color: #8B6F3C;
            font-weight: 500;
            line-height: 1.6;
        }

        .invalid .material-icons-round{
            font-size: 20px;
            color: #D4A84B;
        }

        /* ── Code Box ── */
        .code-box{
            border-radius: 18px;
            padding: 1.2rem;
            background: #FFFFFF;
            border: 1px solid #EBE5DD;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .code-box::before{
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #C9953C, transparent);
        }

        .code-lbl-row{
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            margin-bottom: .5rem;
        }

        .code-lbl-row .material-icons-round{
            font-size: 14px;
            color: #C9953C;
        }

        .code-box .lbl{
            font-size: .7rem;
            color: #9B9B9B;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .code-row{
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
        }

        .code{
            font-family: 'SF Mono', 'Fira Code', 'Inter', monospace;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 6px;
            color: #6B0F1A;
            direction: ltr;
            user-select: all;
        }

        .copy-btn{
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid #EDE8E0;
            background: #FFFFFF;
            color: #6B6B6B;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s ease;
            flex-shrink: 0;
        }

        .copy-btn:hover{
            border-color: #C9953C;
            color: #6B0F1A;
            background: #FFFAF0;
        }

        .copy-btn:active{
            transform: scale(.92);
        }

        .copy-btn.copied{
            border-color: #4CAF50;
            color: #4CAF50;
            background: #F1F8E9;
        }

        .copy-btn .material-icons-round{
            font-size: 20px;
        }

        /* ── Buttons ── */
        .btn-group{
            display: flex;
            flex-direction: column;
            gap: .65rem;
            margin-top: 1.2rem;
        }

        .btn{
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            padding: 1rem;
            border-radius: 16px;
            font-family: inherit;
            font-weight: 700;
            font-size: .92rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s ease;
        }

        .btn-primary{
            background: linear-gradient(145deg, #6B0F1A, #8A1A2A);
            color: #fff;
            box-shadow: 0 6px 20px rgba(107, 15, 26, .25);
        }

        .btn-primary:hover{
            transform: translateY(-2px);
            box-shadow: 0 10px 32px rgba(107, 15, 26, .35);
        }

        .btn-primary:active{
            transform: translateY(0);
        }

        .btn-ghost{
            background: #FAF7F3;
            color: #6B0F1A;
            border: 1.5px solid #EBE5DD;
        }

        .btn-ghost:hover{
            background: #FFFAF0;
            border-color: #C9953C;
        }

        .btn-ghost:active{
            transform: scale(.98);
        }

        .btn .material-icons-round{
            font-size: 20px;
        }

        /* ── Steps ── */
        .steps{
            margin-top: 1.8rem;
        }

        .steps-heading{
            font-family: 'Noto Kufi Arabic', sans-serif;
            font-size: .9rem;
            font-weight: 700;
            color: #1A1A1A;
            margin-bottom: .9rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .steps-heading .material-icons-round{
            font-size: 18px;
            color: #C9953C;
        }

        .step{
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            padding: .7rem 0;
            border-bottom: 1px solid #F0EAE2;
        }

        .step:last-child{
            border-bottom: none;
        }

        .num-circle{
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #FFFFFF;
            border: 2px solid #6B0F1A;
            color: #6B0F1A;
            font-size: .75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: ltr;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .step.completed .num-circle{
            background: #6B0F1A;
            color: #fff;
            border-color: #6B0F1A;
        }

        .step-text{
            font-size: .83rem;
            color: #4A4A4A;
            line-height: 1.7;
            font-weight: 400;
        }

        .step-text b{
            color: #6B0F1A;
            font-weight: 700;
        }

        /* ── Footer ── */
        .foot{
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #F0EAE2;
        }

        .foot-logo{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Noto Kufi Arabic', sans-serif;
            font-size: .8rem;
            font-weight: 700;
            color: #6B0F1A;
            text-decoration: none;
        }

        .foot-logo-mark{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 7px;
            background: linear-gradient(145deg, #6B0F1A, #8A1A2A);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .foot-desc{
            font-size: .68rem;
            color: #B0B0B0;
            margin-top: .4rem;
            font-weight: 400;
        }

        /* ── Toast ── */
        .toast{
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #1A1A1A;
            color: #fff;
            padding: .65rem 1.25rem .65rem 1rem;
            border-radius: 14px;
            font-size: .82rem;
            font-weight: 500;
            opacity: 0;
            transition: all .4s cubic-bezier(.22,1,.36,1);
            pointer-events: none;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: .5rem;
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
        }

        .toast .material-icons-round{
            font-size: 18px;
        }

        .toast.show{
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ── Responsive ── */
        @media (max-width: 460px){
            body{ padding: .75rem; }
            .card{ border-radius: 24px; }
            .hero{ padding: 1.5rem 1.25rem 1.2rem; }
            .hero h1{ font-size: 1.1rem; }
            .body{ padding: 0 1.25rem 1.5rem; }
            .code{ font-size: 1.6rem; letter-spacing: 4px; }
        }

        @media (prefers-reduced-motion: reduce){
            .card-seal{ animation: none; }
            .btn-primary:hover{ transform: none; }
        }
    </style>
</head>
<body>

    <div class="card" role="main">

        <!-- Top seal -->
        <div class="card-seal"></div>

        <!-- Hero -->
        <div class="hero">
            <div class="brand">صك</div>

            @if($valid)
                <h1><span class="name">{{ $inviterName }}</span> يدعوك <br>إلى محفظة صكّ</h1>
                <p>حوّل واستقبل الأموال فوراً، أنشئ بطاقات، وادفع بسهولة.</p>
            @else
                <h1>انضمّ إلى محفظة صكّ</h1>
                <p>حوّل واستقبل الأموال فوراً، أنشئ بطاقات، وادفع بسهولة.</p>
            @endif

            <div class="reward-wrap">
                <div class="reward">
                    <span class="reward-icon">
                        <span class="material-icons-round">workspace_premium</span>
                    </span>
                    <span class="reward-amount">${{ rtrim(rtrim(number_format($reward, 2), '0'), '.') }}</span>
                    <span class="reward-label">مكافأة الإحالة</span>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="body">

            <div class="divider">
                <span class="divider-line"></span>
                <span class="divider-dot"></span>
                <span class="divider-line"></span>
            </div>

            @unless($valid)
                <div class="invalid">
                    <span class="material-icons-round">info</span>
                    لم نتعرّف على كود الدعوة، لكن يمكنك تحميل التطبيق والتسجيل عادةً.
                </div>
            @endunless

            <!-- Code Box -->
            <div class="code-box">
                <div class="code-lbl-row">
                    <span class="material-icons-round">link</span>
                    <span class="lbl">كود الإحالة</span>
                </div>
                <div class="code-row">
                    <span class="code" id="code">{{ $code }}</span>
                    <button class="copy-btn" id="copyBtn" title="نسخ" aria-label="نسخ كود الإحالة">
                        <span class="material-icons-round">content_copy</span>
                    </button>
                </div>
            </div>

            <!-- Buttons -->
            <div class="btn-group">
                <a class="btn btn-primary" href="{{ $apkUrl }}" download="sakk.apk" rel="nofollow">
                    <span class="material-icons-round">download</span>
                    حمّل التطبيق (أندرويد)
                </a>
                <a class="btn btn-ghost" href="{{ $appLink }}" id="openApp">
                    <span class="material-icons-round">open_in_new</span>
                    افتح في تطبيق صكّ
                </a>
            </div>

            <!-- Steps -->
            <div class="steps">
                <div class="steps-heading">
                    <span class="material-icons-round">emoji_events</span>
                    كيف تربح صديقك المكافأة؟
                </div>
                <div class="step">
                    <span class="num-circle">1</span>
                    <span class="step-text">حمّل التطبيق وثبّته على هاتفك.</span>
                </div>
                <div class="step">
                    <span class="num-circle">2</span>
                    <span class="step-text">سجّل حساب جديد — كود الإحالة <b>{{ $code }}</b> يُملأ تلقائياً.</span>
                </div>
                <div class="step">
                    <span class="num-circle">3</span>
                    <span class="step-text">وثّق هويتك (KYC).</span>
                </div>
                <div class="step">
                    <span class="num-circle">4</span>
                    <span class="step-text">أودِع أول &lrm;$100&lrm; — تُضاف المكافأة إلى محفظة من دعاك فوراً.</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="foot">
                <span class="foot-logo">
                    <span class="foot-logo-mark">صك</span>
                    صكّ
                </span>
                <div class="foot-desc">محفظتك المالية الموثوقة</div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast">
        <span class="material-icons-round" id="toastIcon">check_circle</span>
        <span id="toastMsg">تم النسخ</span>
    </div>

    <script>
        (function(){
            var copyBtn = document.getElementById('copyBtn');
            var codeEl = document.getElementById('code');
            var toast = document.getElementById('toast');
            var toastMsg = document.getElementById('toastMsg');
            var toastIcon = document.getElementById('toastIcon');

            function show(msg, icon){
                toastIcon.textContent = icon || 'check_circle';
                toastMsg.textContent = msg;
                toast.classList.add('show');
                clearTimeout(toast._t);
                toast._t = setTimeout(function(){ toast.classList.remove('show'); }, 2000);
            }

            copyBtn.addEventListener('click', function(){
                var code = codeEl.textContent.trim();
                if(!navigator.clipboard){
                    show('النسخ غير مدعوم', 'error');
                    return;
                }
                navigator.clipboard.writeText(code).then(function(){
                    copyBtn.classList.add('copied');
                    var orig = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<span class="material-icons-round">check</span>';
                    show('تم نسخ الكود!', 'check_circle');
                    setTimeout(function(){
                        copyBtn.classList.remove('copied');
                        copyBtn.innerHTML = orig;
                    }, 1600);
                }).catch(function(){
                    show('تعذر النسخ', 'error');
                });
            });
        })();
    </script>
</body>
</html>
