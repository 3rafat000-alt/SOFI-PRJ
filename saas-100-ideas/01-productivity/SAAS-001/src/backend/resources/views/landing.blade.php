<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $appName }} — منصة إدارة المهام والمشاريع للفرق العربية. لوحة Kanban، تتبع الوقت، تقارير، وتعاون لحظي.">
    <title>{{ $appName }} — إدارة المهام والمشاريع للفرق العربية</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:#4F46E5; --primary-600:#4338CA; --indigo-50:#EEF2FF;
            --bg:#FFFFFF; --soft:#F8FAFC; --border:#E8ECF3; --text:#0F172A; --muted:#64748B;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { font-family:'Tajawal',system-ui,sans-serif; color:var(--text); background:var(--bg); line-height:1.7; }
        a { text-decoration:none; color:inherit; }
        .container { max-width:1140px; margin:0 auto; padding:0 24px; }
        .btn { display:inline-flex; align-items:center; gap:8px; padding:13px 26px; border-radius:13px;
            font-family:inherit; font-weight:700; font-size:15px; cursor:pointer; border:1px solid transparent; transition:all .15s; }
        .btn.primary { background:var(--primary); color:#fff; }
        .btn.primary:hover { background:var(--primary-600); transform:translateY(-1px); box-shadow:0 8px 22px rgba(79,70,229,.28); }
        .btn.ghost { background:#fff; color:var(--text); border-color:var(--border); }
        .btn.ghost:hover { background:var(--soft); }
        /* Nav */
        nav { position:sticky; top:0; z-index:50; background:rgba(255,255,255,.85); backdrop-filter:blur(12px);
            border-bottom:1px solid var(--border); }
        nav .inner { display:flex; align-items:center; justify-content:space-between; height:68px; }
        .logo { display:flex; align-items:center; gap:10px; font-weight:900; font-size:18px; }
        .logo .mark { width:38px; height:38px; border-radius:11px; color:#fff; font-weight:800;
            background:linear-gradient(135deg,var(--primary),#818CF8); display:flex; align-items:center; justify-content:center; }
        .nav-links { display:flex; align-items:center; gap:28px; }
        .nav-links a { color:var(--muted); font-weight:600; font-size:15px; }
        .nav-links a:hover { color:var(--text); }
        .nav-cta { display:flex; align-items:center; gap:10px; }
        /* Hero */
        .hero { padding:80px 0 64px; text-align:center; position:relative; overflow:hidden; }
        .hero::before { content:''; position:absolute; top:-200px; right:50%; transform:translateX(50%);
            width:700px; height:500px; background:radial-gradient(closest-side, rgba(79,70,229,.12), transparent); z-index:-1; }
        .badge-pill { display:inline-flex; align-items:center; gap:8px; background:var(--indigo-50); color:var(--primary);
            padding:7px 16px; border-radius:99px; font-size:13px; font-weight:700; margin-bottom:24px; }
        .hero h1 { font-size:52px; font-weight:900; line-height:1.2; letter-spacing:-.02em; margin-bottom:20px; }
        .hero h1 .grad { background:linear-gradient(120deg,var(--primary),#A855F7); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .hero p { font-size:19px; color:var(--muted); max-width:620px; margin:0 auto 32px; }
        .hero .cta { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
        .hero .trust { margin-top:20px; font-size:13px; color:var(--muted); }
        /* Mock window */
        .mock { max-width:920px; margin:56px auto 0; border:1px solid var(--border); border-radius:18px;
            box-shadow:0 30px 60px rgba(15,23,42,.10); overflow:hidden; background:#fff; }
        .mock .bar { display:flex; align-items:center; gap:7px; padding:13px 16px; border-bottom:1px solid var(--border); background:var(--soft); }
        .mock .bar i { width:11px; height:11px; border-radius:50%; background:#E2E8F0; }
        .mock .board { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; padding:22px; }
        .mock .col { background:var(--soft); border-radius:13px; padding:13px; }
        .mock .col h4 { font-size:13px; color:var(--muted); margin-bottom:10px; font-weight:700; }
        .mock .ticket { background:#fff; border:1px solid var(--border); border-radius:10px; padding:11px; margin-bottom:9px; }
        .mock .ticket .t { font-size:13px; font-weight:600; margin-bottom:8px; }
        .mock .ticket .dot { width:7px; height:7px; border-radius:50%; display:inline-block; }
        /* Sections */
        section { padding:72px 0; }
        .sec-head { text-align:center; max-width:600px; margin:0 auto 48px; }
        .sec-head .k { color:var(--primary); font-weight:800; font-size:14px; }
        .sec-head h2 { font-size:36px; font-weight:900; margin:8px 0 12px; letter-spacing:-.01em; }
        .sec-head p { color:var(--muted); font-size:17px; }
        .features { display:grid; grid-template-columns:repeat(3,1fr); gap:22px; }
        .feature { border:1px solid var(--border); border-radius:16px; padding:28px; transition:all .18s; }
        .feature:hover { border-color:#C7D2FE; box-shadow:0 12px 30px rgba(15,23,42,.06); transform:translateY(-2px); }
        .feature .ic { width:48px; height:48px; border-radius:13px; background:var(--indigo-50); display:flex;
            align-items:center; justify-content:center; font-size:24px; margin-bottom:16px; }
        .feature h3 { font-size:18px; font-weight:800; margin-bottom:8px; }
        .feature p { color:var(--muted); font-size:15px; }
        .soft-bg { background:var(--soft); }
        .steps { display:grid; grid-template-columns:repeat(3,1fr); gap:32px; }
        .step { text-align:center; }
        .step .n { width:52px; height:52px; border-radius:50%; background:var(--primary); color:#fff; font-weight:900;
            font-size:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
        .step h3 { font-size:18px; font-weight:800; margin-bottom:6px; }
        .step p { color:var(--muted); font-size:15px; }
        /* CTA band */
        .cta-band { background:linear-gradient(135deg,var(--primary),#7C3AED); border-radius:24px; padding:56px 40px;
            text-align:center; color:#fff; }
        .cta-band h2 { font-size:34px; font-weight:900; margin-bottom:12px; }
        .cta-band p { opacity:.9; font-size:17px; margin-bottom:28px; }
        .cta-band .btn { background:#fff; color:var(--primary); }
        .cta-band .btn:hover { transform:translateY(-1px); box-shadow:0 10px 30px rgba(0,0,0,.2); }
        /* Footer */
        footer { border-top:1px solid var(--border); padding:40px 0; }
        footer .inner { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; }
        footer .muted { color:var(--muted); font-size:14px; }
        @media (max-width:840px){
            .hero h1{font-size:36px;} .nav-links{display:none;}
            .features,.steps,.mock .board{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>
    <nav>
        <div class="container inner">
            <div class="logo"><div class="mark">TS</div>{{ $appName }}</div>
            <div class="nav-links">
                <a href="#features">المميزات</a>
                <a href="#how">كيف يعمل</a>
                <a href="#cta">ابدأ الآن</a>
            </div>
            <div class="nav-cta">
                <a href="/app" class="btn ghost">دخول</a>
                <a href="/app/register" class="btn primary">ابدأ مجاناً</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <span class="badge-pill">🚀 منصة عربية بالكامل · RTL</span>
            <h1>أدِر مهام فريقك<br><span class="grad">بذكاء وسلاسة</span></h1>
            <p>{{ $appName }} منصة إدارة المهام والمشاريع للفرق الصغيرة — لوحة Kanban، تتبع الوقت، تقارير لحظية، وتعاون فوري. كل ما يحتاجه فريقك في مكان واحد.</p>
            <div class="cta">
                <a href="/app/register" class="btn primary">ابدأ مجاناً — بدون بطاقة</a>
                <a href="#features" class="btn ghost">اكتشف المميزات</a>
            </div>
            <div class="trust">موثوق به من فرق العمل · إعداد خلال دقيقتين</div>

            <div class="mock">
                <div class="bar"><i></i><i></i><i></i></div>
                <div class="board">
                    <div class="col"><h4>للتنفيذ</h4>
                        <div class="ticket"><div class="t">تصميم الصفحة الرئيسية</div><span class="dot" style="background:#EF4444"></span></div>
                        <div class="ticket"><div class="t">تطوير واجهة API</div><span class="dot" style="background:#3B82F6"></span></div>
                    </div>
                    <div class="col"><h4>قيد التنفيذ</h4>
                        <div class="ticket"><div class="t">إعداد إعلانات الحملة</div><span class="dot" style="background:#F59E0B"></span></div>
                    </div>
                    <div class="col"><h4>منجز</h4>
                        <div class="ticket"><div class="t">كتابة المحتوى التسويقي</div><span class="dot" style="background:#10B981"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="features">
        <div class="container">
            <div class="sec-head">
                <div class="k">المميزات</div>
                <h2>كل ما يحتاجه فريقك</h2>
                <p>أدوات قوية بواجهة بسيطة، مصممة للفرق العربية.</p>
            </div>
            <div class="features">
                <div class="feature"><div class="ic">📋</div><h3>لوحة Kanban</h3><p>اسحب وأفلت المهام بين الأعمدة، نظّم سير العمل بصرياً، وتابع التقدّم لحظياً.</p></div>
                <div class="feature"><div class="ic">⏱️</div><h3>تتبع الوقت</h3><p>مؤقت مدمج وإدخال يدوي لساعات العمل، مع تقارير دقيقة لكل مهمة ومشروع.</p></div>
                <div class="feature"><div class="ic">📊</div><h3>تقارير وتحليلات</h3><p>لوحات معلومات ورسوم بيانية للإنتاجية، الساعات، والمهام المنجزة.</p></div>
                <div class="feature"><div class="ic">👥</div><h3>تعاون الفريق</h3><p>تعليقات، إشارات (@mention)، مرفقات، وإشعارات فورية عبر WebSocket.</p></div>
                <div class="feature"><div class="ic">🏢</div><h3>مساحات عمل</h3><p>افصل مشاريع كل فريق في مساحة عمل مستقلة مع صلاحيات وأدوار.</p></div>
                <div class="feature"><div class="ic">🌐</div><h3>عربي بالكامل</h3><p>واجهة RTL أصيلة، دعم كامل للعربية، ومتاح على الويب والجوال.</p></div>
            </div>
        </div>
    </section>

    <section id="how" class="soft-bg">
        <div class="container">
            <div class="sec-head">
                <div class="k">كيف يعمل</div>
                <h2>ابدأ خلال 3 خطوات</h2>
            </div>
            <div class="steps">
                <div class="step"><div class="n">1</div><h3>أنشئ حسابك</h3><p>سجّل وأنشئ مساحة عمل لفريقك في أقل من دقيقتين.</p></div>
                <div class="step"><div class="n">2</div><h3>أضف مشاريعك</h3><p>أنشئ المشاريع والمهام، وعيّنها لأعضاء الفريق.</p></div>
                <div class="step"><div class="n">3</div><h3>تابع وأنجز</h3><p>تتبّع التقدّم، سجّل الوقت، واطّلع على التقارير.</p></div>
            </div>
        </div>
    </section>

    <section id="cta">
        <div class="container">
            <div class="cta-band">
                <h2>جاهز لتنظيم عمل فريقك؟</h2>
                <p>ابدأ مجاناً اليوم — لا حاجة لبطاقة ائتمان.</p>
                <a href="/app/register" class="btn">أنشئ حسابك الآن</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container inner">
            <div class="logo"><div class="mark">TS</div>{{ $appName }}</div>
            <div class="muted">© {{ date('Y') }} {{ $appName }} — صُنع بواسطة SOFI AI</div>
        </div>
    </footer>
</body>
</html>
