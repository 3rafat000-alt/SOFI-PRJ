<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنصيب — TaskSync Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5; --primary-600: #4338CA; --primary-50: #EEF2FF;
            --bg: #F8FAFC; --card: #FFFFFF; --border: #E5E7EB;
            --text: #0F172A; --muted: #64748B; --ok: #16A34A; --err: #DC2626;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', system-ui, sans-serif; background: var(--bg);
            color: var(--text); min-height: 100vh; display: flex; align-items: center;
            justify-content: center; padding: 24px; line-height: 1.6;
        }
        .wrap { width: 100%; max-width: 640px; }
        .brand { text-align: center; margin-bottom: 28px; }
        .brand .logo {
            width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 12px;
            background: linear-gradient(135deg, var(--primary), #818CF8);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 22px;
        }
        .brand h1 { font-size: 22px; font-weight: 800; }
        .brand p { color: var(--muted); font-size: 14px; }
        .stepper { display: flex; gap: 8px; margin-bottom: 24px; }
        .stepper .s {
            flex: 1; height: 6px; border-radius: 99px; background: var(--border);
        }
        .stepper .s.active { background: var(--primary); }
        .stepper .s.done { background: var(--ok); }
        .card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 18px; padding: 32px; box-shadow: 0 8px 28px rgba(15,23,42,.05);
        }
        .card h2 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .card .sub { color: var(--muted); font-size: 14px; margin-bottom: 24px; }
        .check { display: flex; align-items: center; justify-content: space-between;
            padding: 12px 14px; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 8px; }
        .check .lbl { font-size: 14px; font-weight: 500; }
        .badge { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 99px; }
        .badge.ok { background: #DCFCE7; color: var(--ok); }
        .badge.err { background: #FEE2E2; color: var(--err); }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .field input, .field select {
            width: 100%; padding: 11px 14px; border: 1px solid var(--border);
            border-radius: 11px; font-family: inherit; font-size: 14px; background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus, .field select:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-50);
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 13px 20px; border: none; border-radius: 12px;
            background: var(--primary); color: #fff; font-family: inherit; font-weight: 700;
            font-size: 15px; cursor: pointer; text-decoration: none; transition: background .15s;
        }
        .btn:hover { background: var(--primary-600); }
        .btn.ghost { background: transparent; color: var(--muted); border: 1px solid var(--border); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .row { display: flex; gap: 12px; margin-top: 24px; }
        .alert { background: #FEE2E2; color: var(--err); padding: 12px 14px;
            border-radius: 11px; font-size: 13px; margin-bottom: 16px; }
        .hint { color: var(--muted); font-size: 12px; }
        .success-icon { width: 72px; height: 72px; border-radius: 50%; background: #DCFCE7;
            color: var(--ok); display: flex; align-items: center; justify-content: center;
            font-size: 36px; margin: 0 auto 20px; }
        .footer { text-align: center; color: var(--muted); font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <div class="logo">TS</div>
            <h1>TaskSync Pro</h1>
            <p>معالج التنصيب</p>
        </div>
        <div class="stepper">
            @for ($i = 1; $i <= 4; $i++)
                <div class="s {{ $i < ($step ?? 1) ? 'done' : ($i === ($step ?? 1) ? 'active' : '') }}"></div>
            @endfor
        </div>
        <div class="card">
            @yield('content')
        </div>
        <div class="footer">SOFI AI · TaskSync Pro · الخطوة {{ $step ?? 1 }} من 4</div>
    </div>
</body>
</html>
