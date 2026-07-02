<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>صكّ — لا يوجد حساب شريك</title>
    <link rel="stylesheet" href="/sakk-assets/sakk-tokens.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--marble, #f7f3ee);
            font-family: var(--font, 'IBM Plex Sans Arabic', sans-serif);
            color: var(--ink, #1c1c1e);
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(110,27,45,.10);
            padding: 52px 44px;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }
        .logo-wrap {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--wine,#6E1B2D) 0%, #a12540 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 32px;
            font-weight: 900;
            margin: 0 auto 24px;
            box-shadow: 0 4px 18px rgba(110,27,45,.30);
        }
        h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--wine, #6E1B2D);
            margin: 0 0 14px;
        }
        p {
            font-size: 16px;
            line-height: 1.75;
            color: var(--ink-2, #555);
            margin: 0 0 32px;
        }
        .steps {
            background: var(--marble, #f7f3ee);
            border-radius: 12px;
            padding: 20px 24px;
            text-align: right;
            margin-bottom: 32px;
        }
        .steps h2 {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink, #1c1c1e);
            margin: 0 0 12px;
        }
        .steps ol {
            margin: 0;
            padding-inline-start: 20px;
            color: var(--ink-2, #555);
            font-size: 14px;
            line-height: 1.8;
        }
        .back-link {
            display: inline-block;
            background: var(--wine, #6E1B2D);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            padding: 13px 36px;
            border-radius: 10px;
            text-decoration: none;
        }
        .back-link:hover { background: #591626; }
        @media (max-width: 480px) {
            .card { padding: 36px 22px; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-wrap">ص</div>

        <h1>لا تملك حساب شريك بعد</h1>

        <p>
            لا تملك حساب {{ $portalLabel ?? 'شريك' }} مرتبطاً بهذا البريد الإلكتروني.
            قدّم طلب الانضمام من تطبيق <strong>صكّ</strong>،
            وبعد مراجعة الطلب والموافقة عليه سنرسل لك رابط الدخول مباشرةً على بريدك الإلكتروني.
        </p>

        <div class="steps">
            <h2>كيف تنضم؟</h2>
            <ol>
                <li>حمّل تطبيق <strong>صكّ</strong> وسجّل حساباً شخصياً.</li>
                <li>اختر «انضم كـ {{ $portalLabel ?? 'شريك' }}» من قائمة التطبيق.</li>
                <li>أكمل البيانات وارفع المستندات المطلوبة.</li>
                <li>بعد الموافقة الإدارية، ستصلك رسالة بريد إلكتروني تحتوي رابط الدخول إلى البوابة.</li>
            </ol>
        </div>

        <a href="{{ $loginRoute ?? url('/') }}" class="back-link">العودة إلى تسجيل الدخول</a>
    </div>
</body>
</html>
