<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $portal['brand'] ?? 'بوابة صك') — SAKK</title>
    <link rel="stylesheet" href="/sakk-assets/sakk-tokens.css">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:var(--font);background:var(--marble);color:var(--ink);font-size:var(--fs-base);-webkit-font-smoothing:antialiased}
        a{color:inherit;text-decoration:none}
        .shell{display:flex;min-height:100vh}
        /* Sidebar */
        .side{width:262px;background:linear-gradient(180deg,#fff 0%,var(--marble) 100%);border-inline-start:1px solid var(--glass-border);padding:22px 16px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column}
        .brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:var(--fs-lg);margin-bottom:6px;padding:6px 8px}
        .brand .logo{width:34px;height:34px;border-radius:10px;background:var(--grad-accent);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;box-shadow:var(--sh-wine)}
        .brand small{display:block;color:var(--ink-2);font-weight:500;font-size:var(--fs-xs)}
        .nav{margin-top:18px;flex:1}
        .nav a{display:flex;align-items:center;gap:11px;padding:11px 13px;border-radius:var(--r-md);color:var(--ink-2);font-weight:600;margin-bottom:4px;transition:background var(--t-fast),color var(--t-fast)}
        .nav a .ic{width:20px;text-align:center}
        .nav a:hover{background:var(--wine-soft);color:var(--wine)}
        .nav a.active{background:var(--wine);color:#fff;box-shadow:var(--sh-wine)}
        .side-foot{border-top:1px solid var(--glass-border);padding-top:14px;color:var(--ink-hint);font-size:var(--fs-xs)}
        /* Main */
        .main{flex:1;padding:30px 38px;max-width:1180px}
        .topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:26px}
        h1{font-size:var(--fs-2xl);margin:0;font-weight:700;letter-spacing:-.01em}
        .sub{color:var(--ink-2);margin-top:4px;font-size:var(--fs-sm)}
        .right{display:flex;gap:12px;align-items:center}
        .chip{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:var(--r-full);font-size:var(--fs-xs);font-weight:700}
        .chip.success{background:var(--success-soft);color:var(--success)}
        .chip.warning{background:var(--warning-soft);color:var(--warning)}
        .chip.danger{background:var(--error-soft);color:var(--error)}
        /* Cards / primitives */
        .card{background:var(--surface);border:1px solid var(--glass-border);border-radius:var(--r-lg);padding:24px;margin-bottom:20px;box-shadow:var(--sh-sm)}
        .grid{display:grid;gap:18px}.g3{grid-template-columns:repeat(3,1fr)}.g2{grid-template-columns:repeat(2,1fr)}
        @media(max-width:820px){.g3,.g2{grid-template-columns:1fr}.side{display:none}}
        .stat{font-size:var(--fs-3xl);font-weight:700;color:var(--wine);font-variant-numeric:tabular-nums}
        .stat small{display:block;color:var(--ink-2);font-size:var(--fs-sm);font-weight:600;margin-top:6px}
        label{display:block;font-weight:600;font-size:var(--fs-sm);margin:12px 0 6px;color:var(--ink-2)}
        input,select,textarea{width:100%;padding:11px 13px;border:1px solid var(--glass-border);border-radius:var(--r-md);font:inherit;background:var(--surface);color:var(--ink);transition:border var(--t-fast),box-shadow var(--t-fast)}
        input:focus,select:focus,textarea:focus{outline:none;border-color:var(--wine);box-shadow:0 0 0 3px var(--wine-soft)}
        .btn{display:inline-flex;align-items:center;gap:8px;background:var(--wine);color:#fff;border:0;padding:11px 22px;border-radius:var(--r-md);font-weight:700;cursor:pointer;font:inherit;box-shadow:var(--sh-wine);transition:transform var(--t-fast),background var(--t-fast)}
        .btn:hover{background:var(--wine-dark);transform:translateY(-1px)}
        .btn.ghost{background:var(--surface);color:var(--wine);border:1px solid var(--wine);box-shadow:none}
        .btn.gold{background:var(--grad-gold);box-shadow:var(--sh-gold)}
        .btn.sm{padding:7px 14px;font-size:var(--fs-sm)}
        table{width:100%;border-collapse:collapse}
        th,td{text-align:start;padding:12px 10px;border-bottom:1px solid var(--glass-border)}
        th{font-size:var(--fs-xs);color:var(--ink-2);font-weight:700;text-transform:uppercase;letter-spacing:.03em}
        td .money,.money{font-variant-numeric:tabular-nums}
        .pill{display:inline-block;padding:3px 11px;border-radius:var(--r-full);font-size:var(--fs-xs);font-weight:700}
        .pill.ok{background:var(--success-soft);color:var(--success)}
        .pill.warn{background:var(--warning-soft);color:var(--warning)}
        .pill.danger{background:var(--error-soft);color:var(--error)}
        .pill.muted{background:var(--input-bg);color:var(--ink-2)}
        .flash{padding:13px 16px;border-radius:var(--r-md);margin-bottom:18px;font-weight:600;font-size:var(--fs-sm)}
        .flash.ok{background:var(--success-soft);color:var(--success)}
        .flash.err{background:var(--error-soft);color:var(--error)}
        .muted{color:var(--ink-2)} .row{display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap}
        .sect{font-size:var(--fs-lg);font-weight:700;margin:0 0 14px}
        .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:var(--fs-sm);background:var(--input-bg);padding:6px 10px;border-radius:var(--r-sm);word-break:break-all}
    </style>
    @stack('head')
</head>
<body>
@php $p = $portal ?? ['brand'=>'بوابة صك','nav'=>[],'logout'=>null,'entity'=>null]; $ent = $p['entity'] ?? null; @endphp
<div class="shell">
    <aside class="side">
        <div class="brand">
            <span class="logo">ص</span>
            <span>{{ $p['brand'] }}<small>SAKK · صك</small></span>
        </div>
        <nav class="nav">
            @foreach($p['nav'] as $item)
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['match']) ? 'active' : '' }}">
                    <span class="ic">{!! $item['icon'] ?? '•' !!}</span><span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="side-foot">صكّ — منصّة المدفوعات · {{ now()->year }}</div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>@yield('title', 'لوحة التحكم')</h1>
                @if($ent)<div class="sub">{{ $ent['name'] }} · {{ $ent['code'] }}</div>@endif
            </div>
            <div class="right">
                @if($ent && !empty($ent['status_label']))
                    <span class="chip {{ ($ent['status_color'] ?? '') === 'success' ? 'success' : (($ent['status_color'] ?? '') === 'danger' ? 'danger' : 'warning') }}">{{ $ent['status_label'] }}</span>
                @endif
                @if($p['logout'])
                    <form method="POST" action="{{ route($p['logout']) }}">@csrf<button class="btn ghost sm">خروج</button></form>
                @endif
            </div>
        </div>

        @if(session('success'))<div class="flash ok">{{ session('success') }}</div>@endif
        @foreach($errors->all() as $e)<div class="flash err">{{ $e }}</div>@endforeach

        @yield('content')
    </main>
</div>
</body>
</html>
