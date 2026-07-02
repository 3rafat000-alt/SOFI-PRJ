<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} — SAKK</title>
    <link rel="stylesheet" href="/sakk-assets/sakk-tokens.css">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:var(--font);min-height:100vh;display:flex;background:var(--grad-hero);color:var(--ink)}
        .split{display:flex;width:100%}
        .brandside{flex:1;display:none;flex-direction:column;justify-content:center;padding:64px;background:var(--grad-accent);color:#fff;position:relative;overflow:hidden}
        .brandside::after{content:"";position:absolute;inset:0;background:radial-gradient(600px 300px at 90% 10%,rgba(255,255,255,.15),transparent 60%)}
        .brandside .big{font-size:var(--fs-5xl);font-weight:700;line-height:1.1;position:relative}
        .brandside p{font-size:var(--fs-lg);opacity:.92;max-width:420px;position:relative;margin-top:18px}
        .brandside .mark{width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;margin-bottom:26px;position:relative}
        .formside{flex:1;display:flex;align-items:center;justify-content:center;padding:32px}
        .box{background:var(--surface);border:1px solid var(--glass-border);border-radius:var(--r-xl);padding:40px;width:100%;max-width:400px;box-shadow:var(--sh-lg)}
        .box h1{color:var(--wine);font-size:var(--fs-2xl);margin:0 0 4px}
        .box .lead{color:var(--ink-2);margin:0 0 24px;font-size:var(--fs-sm)}
        label{display:block;font-weight:600;font-size:var(--fs-sm);margin:14px 0 6px;color:var(--ink-2)}
        input{width:100%;padding:12px 13px;border:1px solid var(--glass-border);border-radius:var(--r-md);font:inherit;background:var(--input-bg)}
        input:focus{outline:none;border-color:var(--wine);box-shadow:0 0 0 3px var(--wine-soft);background:var(--surface)}
        .btn{width:100%;background:var(--wine);color:#fff;border:0;padding:13px;border-radius:var(--r-md);font-weight:700;cursor:pointer;margin-top:22px;font:inherit;box-shadow:var(--sh-wine);transition:background var(--t-fast)}
        .btn:hover{background:var(--wine-dark)}
        .err{background:var(--error-soft);color:var(--error);padding:11px 14px;border-radius:var(--r-md);font-weight:600;font-size:var(--fs-sm);margin-bottom:14px}
        @media(min-width:900px){.brandside{display:flex}}
    </style>
</head>
<body>
<div class="split">
    <div class="brandside">
        <div class="mark">ص</div>
        <div class="big">{{ $brand }}</div>
        <p>{{ $intro ?? 'أدر أعمالك على منصّة صكّ للمدفوعات — بهوية عنّابية دمشقية، بسيطة واحترافية.' }}</p>
    </div>
    <div class="formside">
        <form class="box" method="POST" action="{{ $action }}">
            @csrf
            <h1>{{ $brand }}</h1>
            <p class="lead">سجّل الدخول بحسابك في صكّ</p>
            @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            <label>كلمة المرور</label>
            <input type="password" name="password" required>
            <button class="btn">دخول</button>
        </form>
    </div>
</div>
</body>
</html>
