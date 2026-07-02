<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة الإدارة') — TaskSync Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5; --primary-600: #4338CA; --primary-50: #EEF2FF;
            --bg: #F1F5F9; --card: #FFFFFF; --border: #E5E7EB; --sidebar: #FFFFFF;
            --text: #0F172A; --muted: #64748B; --ok: #16A34A; --warn: #D97706; --err: #DC2626;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Tajawal', system-ui, sans-serif; background: var(--bg);
            color: var(--text); line-height: 1.6; }
        .shell { display: grid; grid-template-columns: 256px 1fr; min-height: 100vh; }
        /* Sidebar */
        .sidebar { background: var(--sidebar); border-inline-start: 1px solid var(--border);
            padding: 20px 16px; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar .logo { display: flex; align-items: center; gap: 10px; padding: 8px 8px 20px;
            border-bottom: 1px solid var(--border); margin-bottom: 16px; }
        .sidebar .logo .mark { width: 38px; height: 38px; border-radius: 11px; color: #fff;
            background: linear-gradient(135deg, var(--primary), #818CF8); display: flex;
            align-items: center; justify-content: center; font-weight: 800; }
        .sidebar .logo b { font-size: 15px; font-weight: 800; }
        .sidebar .logo span { font-size: 11px; color: var(--muted); display: block; }
        .nav a { display: flex; align-items: center; gap: 11px; padding: 11px 12px;
            border-radius: 11px; color: var(--muted); text-decoration: none; font-weight: 600;
            font-size: 14px; margin-bottom: 3px; transition: background .12s, color .12s; }
        .nav a:hover { background: var(--bg); color: var(--text); }
        .nav a.active { background: var(--primary-50); color: var(--primary); }
        .nav a .ic { width: 18px; height: 18px; flex-shrink: 0; }
        .nav .group { font-size: 11px; color: #94A3B8; font-weight: 700; padding: 16px 12px 6px; }
        /* Main */
        .main { display: flex; flex-direction: column; min-width: 0; }
        .topbar { background: #fff; border-bottom: 1px solid var(--border); padding: 14px 28px;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
        .topbar h1 { font-size: 18px; font-weight: 800; }
        .topbar .who { display: flex; align-items: center; gap: 12px; }
        .topbar .who .av { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-50);
            color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; }
        .content { padding: 28px; flex: 1; }
        /* Components */
        .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .kpi { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; }
        .kpi .l { color: var(--muted); font-size: 13px; font-weight: 600; }
        .kpi .v { font-size: 30px; font-weight: 800; margin-top: 6px; }
        .kpi .d { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .panel { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
        .panel .head { padding: 18px 20px; border-bottom: 1px solid var(--border); display: flex;
            align-items: center; justify-content: space-between; }
        .panel .head h2 { font-size: 16px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: start; padding: 13px 20px; font-size: 14px; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 700; font-size: 12px; background: #FAFBFC; }
        tr:last-child td { border-bottom: none; }
        .tag { font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 99px; display: inline-block; }
        .tag.green { background: #DCFCE7; color: var(--ok); }
        .tag.gray { background: #F1F5F9; color: var(--muted); }
        .tag.amber { background: #FEF3C7; color: var(--warn); }
        .tag.red { background: #FEE2E2; color: var(--err); }
        .tag.indigo { background: var(--primary-50); color: var(--primary); }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1px solid var(--border);
            border-radius: 10px; background: #fff; color: var(--text); font-family: inherit; font-weight: 600;
            font-size: 13px; cursor: pointer; text-decoration: none; transition: background .12s; }
        .btn:hover { background: var(--bg); }
        .btn.primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn.primary:hover { background: var(--primary-600); }
        .btn.danger { color: var(--err); border-color: #FECACA; }
        .btn.danger:hover { background: #FEF2F2; }
        .btn.sm { padding: 6px 10px; font-size: 12px; }
        .flash { background: #DCFCE7; color: var(--ok); padding: 12px 16px; border-radius: 12px;
            margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .empty { padding: 48px; text-align: center; color: var(--muted); }
        .field { margin-bottom: 18px; }
        .field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .field input, .field textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--border);
            border-radius: 11px; font-family: inherit; font-size: 14px; }
        .field input:focus, .field textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-50); }
        .pagination { padding: 16px 20px; display: flex; gap: 6px; justify-content: center; }
        .pagination a, .pagination span { padding: 6px 11px; border-radius: 8px; font-size: 13px; text-decoration: none; color: var(--muted); }
        .pagination a:hover { background: var(--bg); }
        .pagination .active span { background: var(--primary); color: #fff; }
        @media (max-width: 880px) { .shell { grid-template-columns: 1fr; } .sidebar { display: none; } }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="logo">
            <div class="mark">TS</div>
            <div><b>TaskSync Pro</b><span>لوحة الإدارة</span></div>
        </div>
        <nav class="nav">
            @php $r = request()->route()?->getName(); @endphp
            <a href="{{ route('admin.dashboard') }}" class="{{ str_starts_with($r ?? '', 'admin.dashboard') ? 'active' : '' }}">📊 لوحة التحكم</a>
            <div class="group">الإدارة</div>
            <a href="{{ route('admin.users.index') }}" class="{{ str_starts_with($r ?? '', 'admin.users') ? 'active' : '' }}">👥 المستخدمون</a>
            <a href="{{ route('admin.workspaces.index') }}" class="{{ str_starts_with($r ?? '', 'admin.workspaces') ? 'active' : '' }}">🏢 المساحات</a>
            <div class="group">المحتوى</div>
            <a href="{{ route('admin.projects.index') }}" class="{{ str_starts_with($r ?? '', 'admin.projects') ? 'active' : '' }}">📁 المشاريع</a>
            <a href="{{ route('admin.tasks.index') }}" class="{{ str_starts_with($r ?? '', 'admin.tasks') ? 'active' : '' }}">✅ المهام</a>
            <div class="group">النظام</div>
            <a href="{{ route('admin.settings.index') }}" class="{{ str_starts_with($r ?? '', 'admin.settings') ? 'active' : '' }}">⚙️ الإعدادات</a>
            <a href="{{ route('admin.logs.index') }}" class="{{ str_starts_with($r ?? '', 'admin.logs') ? 'active' : '' }}">📜 السجلات</a>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <h1>@yield('title', 'لوحة التحكم')</h1>
            <div class="who">
                <span style="font-size:13px;color:var(--muted)">{{ auth('admin')->user()?->name }}</span>
                <div class="av">{{ mb_substr(auth('admin')->user()?->name ?? 'A', 0, 1) }}</div>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf
                    <button class="btn sm danger" type="submit">خروج</button>
                </form>
            </div>
        </header>
        <main class="content">
            @if (session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
