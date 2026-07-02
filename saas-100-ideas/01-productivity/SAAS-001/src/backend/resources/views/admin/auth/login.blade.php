<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول الإدارة — TaskSync Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4F46E5; --primary-600:#4338CA; --primary-50:#EEF2FF;
            --bg:#F8FAFC; --card:#fff; --border:#E5E7EB; --text:#0F172A; --muted:#64748B; --err:#DC2626; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Tajawal',system-ui,sans-serif; background:var(--bg); color:var(--text);
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .box { width:100%; max-width:420px; }
        .brand { text-align:center; margin-bottom:24px; }
        .brand .mark { width:60px; height:60px; border-radius:16px; margin:0 auto 14px; color:#fff;
            background:linear-gradient(135deg,var(--primary),#818CF8); display:flex; align-items:center;
            justify-content:center; font-weight:800; font-size:24px; }
        .brand h1 { font-size:20px; font-weight:800; }
        .brand p { color:var(--muted); font-size:14px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:32px;
            box-shadow:0 10px 30px rgba(15,23,42,.06); }
        .field { margin-bottom:18px; }
        .field label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
        .field input { width:100%; padding:12px 14px; border:1px solid var(--border); border-radius:11px;
            font-family:inherit; font-size:14px; }
        .field input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-50); }
        .btn { width:100%; padding:13px; border:none; border-radius:12px; background:var(--primary); color:#fff;
            font-family:inherit; font-weight:700; font-size:15px; cursor:pointer; transition:background .15s; }
        .btn:hover { background:var(--primary-600); }
        .alert { background:#FEE2E2; color:var(--err); padding:11px 14px; border-radius:11px; font-size:13px; margin-bottom:16px; }
        .remember { display:flex; align-items:center; gap:8px; margin-bottom:18px; font-size:13px; color:var(--muted); }
        .remember input { width:auto; }
        .foot { text-align:center; color:var(--muted); font-size:12px; margin-top:18px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="brand">
            <div class="mark">TS</div>
            <h1>لوحة الإدارة</h1>
            <p>TaskSync Pro</p>
        </div>
        <div class="card">
            @if ($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="field">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" required>
                </div>
                <label class="remember">
                    <input type="checkbox" name="remember" value="1"> تذكرني
                </label>
                <button type="submit" class="btn">تسجيل الدخول</button>
            </form>
        </div>
        <div class="foot">© {{ date('Y') }} TaskSync Pro — SOFI AI</div>
    </div>
</body>
</html>
