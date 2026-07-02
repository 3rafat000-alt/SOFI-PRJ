<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'تنصيب') - صكّ · SAKK</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.svg">

    {{-- SAKK design tokens — single source of truth --}}
    <link href="{{ asset('sakk-assets/sakk-tokens.css') }}" rel="stylesheet">

    {{-- Tailwind via CDN with SRI (pinned) — fast prototyping for forms --}}
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wine:       '#6E1B2D',
                        'wine-dark':'#4A1320',
                        'wine-soft':'#F7E9EC',
                        gold:       '#B58A3C',
                        'gold-brt': '#C9A24B',
                        marble:     '#F7F3EE',
                        ink:        '#2A1A1F',
                        'ink-2':    '#6E5F63',
                    },
                    fontFamily: {
                        sans: ['IBM Plex Sans Arabic', 'system-ui', 'sans-serif'],
                    },
                    borderRadius: {
                        sm:  '8px',
                        md:  '16px',
                        lg:  '24px',
                        xl:  '32px',
                        full:'999px',
                    },
                }
            }
        }
    </script>

    <style>
        /* ───── SAKK Installer — design system overrides ───── */
        body {
            font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
            background: var(--marble, #F7F3EE);
            color: var(--ink, #2A1A1F);
        }
        .installer-card {
            background: var(--surface, #FFFFFF);
            border: 1px solid #E8DED6;
            border-radius: 24px;
            box-shadow: 0 4px 24px rgba(74,19,32,.08);
        }
        .installer-label {
            font-size: .875rem;
            font-weight: 600;
            color: var(--ink-2, #6E5F63);
            margin-bottom: .6rem;
        }
        .installer-input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: #fff;
            border: 1px solid #E8DED6;
            border-radius: 16px;
            font-size: 1rem;
            color: var(--ink, #2A1A1F);
            transition: border-color .15s, box-shadow .15s;
            font-family: inherit;
        }
        .installer-input:focus {
            outline: none;
            border-color: var(--wine, #6E1B2D);
            box-shadow: 0 0 0 3px rgba(110,27,45,.10);
        }
        .installer-input.error {
            border-color: #dc2626;
        }
        .installer-input.error:focus {
            box-shadow: 0 0 0 3px rgba(220,38,38,.12);
        }
        .installer-input.success {
            border-color: #16a34a;
        }
        .installer-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.75rem;
            background: var(--wine, #6E1B2D);
            color: #fff;
            border: none;
            border-radius: 16px;
            font-weight: 600;
            font-size: .9375rem;
            font-family: inherit;
            cursor: pointer;
            transition: opacity .15s, box-shadow .15s, transform .1s;
        }
        .installer-btn-primary:hover {
            opacity: .9;
            box-shadow: 0 4px 20px rgba(110,27,45,.25);
        }
        .installer-btn-primary:active {
            transform: scale(.97);
        }
        .installer-btn-primary:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
        .installer-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.5rem;
            background: transparent;
            color: var(--ink-2, #6E5F63);
            border: 1px solid #E8DED6;
            border-radius: 16px;
            font-weight: 600;
            font-size: .875rem;
            font-family: inherit;
            cursor: pointer;
            transition: border-color .15s, color .15s;
        }
        .installer-btn-ghost:hover {
            border-color: var(--wine, #6E1B2D);
            color: var(--wine, #6E1B2D);
        }
        .installer-error-box {
            padding: 1.25rem;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 16px;
            color: #991B1B;
        }
        .installer-info-box {
            padding: 1.5rem;
            border-radius: 16px;
            background: #FFFBEB;
            border: 1px solid #FDE68A;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)}
            25%{transform:translateX(-5px)}
            75%{transform:translateX(5px)}
        }
        .fade-in { animation: fadeIn .35s ease-out; }
        .slide-up { animation: slideUp .45s ease-out; }
        .shake { animation: shake .3s ease-out; }
        .error-msg { color: #dc2626; font-size: .8125rem; margin-top: .4rem; display: none; }
        .input-error { border-color: #dc2626 !important; }
        .input-error:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.12) !important; }
        .input-success { border-color: #16a34a !important; }
        .input-success:focus { box-shadow: 0 0 0 3px rgba(34,197,94,.12) !important; }
        .req-item {
            background: #f3f4f6;
            color: #9ca3af;
            padding: 4px 10px;
            border-radius: 6px;
            text-align: center;
            transition: all .3s;
            font-size: .875rem;
        }
        .req-item.met {
            background: #dcfce7;
            color: #16a34a;
        }
        select.installer-input {
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%236E5F63%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E");
            background-position: left 1rem center;
            background-repeat: no-repeat;
            background-size: 1.25rem;
            padding-left: 3rem;
        }
        .step-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            transition: all .3s;
        }
        .step-dot.active { background: var(--wine, #6E1B2D); box-shadow: 0 0 0 4px rgba(110,27,45,.15); }
        .step-dot.done   { background: #16a34a; }
        .step-dot.pending { background: #D8CFC8; }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    {{-- Subtle decorative backdrop --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-48 -right-48 w-[40rem] h-[40rem] rounded-full" style="background: rgba(110,27,45,.03);"></div>
        <div class="absolute -bottom-48 -left-48 w-[40rem] h-[40rem] rounded-full" style="background: rgba(181,138,60,.03);"></div>
    </div>

    @php
        $stepIcons = [
            'desktop',
            'server',
            'user-circle',
            'cog',
            'check-circle',
        ];
        $stepNames = ['المتطلبات', 'قاعدة البيانات', 'المشرف', 'الإعدادات', 'اكتمال'];
        $currentStep = $currentStep ?? 1;
    @endphp

    <div class="relative w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col flex-1">
        {{-- Top bar --}}
        <header class="flex items-center justify-between py-5 sm:py-6 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl overflow-hidden shadow-sm border" style="border-color: #E8DED6; background: #fff;">
                    <img src="/images/logo.svg" alt="صكّ" class="w-full h-full object-contain rounded-lg">
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold leading-tight" style="color: var(--wine, #6E1B2D);">صكّ</h1>
                    <p style="color: var(--ink-2, #6E5F63); font-size: .8125rem;">معالج التنصيب</p>
                </div>
            </div>

            {{-- Progress --}}
            <div class="flex items-center gap-3">
                <div class="hidden md:flex items-center gap-2">
                    @foreach($stepNames as $i => $name)
                        @php
                            $st = $i + 1 < $currentStep ? 'done' : ($i + 1 === $currentStep ? 'active' : 'pending');
                        @endphp
                        <span style="font-size: .8125rem; font-weight: 600; padding: .3rem .7rem; border-radius: 999px;
                            {{ $st === 'active' ? 'background: rgba(110,27,45,.08); color: var(--wine, #6E1B2D);' : '' }}
                            {{ $st === 'done' ? 'background: rgba(22,163,74,.08); color: #16a34a;' : '' }}
                            {{ $st === 'pending' ? 'color: #C4B5A4;' : '' }}">
                            {{ $name }}
                        </span>
                        @if(!$loop->last)
                            <svg class="w-4 h-4" fill="none" stroke="#D8CFC8" viewBox="0 0 24 24" style="stroke: #D8CFC8;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        @endif
                    @endforeach
                </div>
                {{-- Progress bar --}}
                <div style="width: 100px; height: 6px; background: #E8DED6; border-radius: 999px; overflow: hidden;">
                    <div style="height: 100%; border-radius: 999px; background: var(--wine, #6E1B2D); transition: width .5s ease-out; width: {{ $currentStep * 20 }}%;"></div>
                </div>
                <span style="font-size: .8125rem; font-weight: 600; color: #6E5F63;">{{ $currentStep }} / 5</span>
            </div>
        </header>

        {{-- Step header --}}
        <div class="flex items-center gap-3 mb-5 flex-shrink-0">
            <div style="width: 48px; height: 48px; border-radius: 16px; background: rgba(110,27,45,.06); border: 1px solid rgba(110,27,45,.12); display: flex; align-items: center; justify-content: center;">
                <svg class="w-6 h-6" fill="none" stroke="#6E1B2D" viewBox="0 0 24 24">
                    @switch($currentStep)
                        @case(1)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            @break
                        @case(2)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zm0 0l4.5 3M20 7l-4.5 3M4 13l4.5 3m7-6l4.5-3M12 11v6m-2-8a2 2 0 104 0 2 2 0 00-4 0z"/>
                            @break
                        @case(3)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            @break
                        @case(4)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            @break
                        @case(5)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @break
                    @endswitch
                </svg>
            </div>
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--ink, #2A1A1F);">@yield('title', $stepNames[$currentStep - 1])</h2>
                <p style="color: var(--ink-2, #6E5F63); font-size: .9375rem; margin-top: .15rem;">@yield('subtitle', '')</p>
            </div>
        </div>

        {{-- Content --}}
        <main class="flex-1 flex items-start justify-center pb-4">
            <div class="w-full installer-card p-6 sm:p-8 lg:p-10 slide-up">
                @yield('content')
            </div>
        </main>

        {{-- Footer --}}
        <footer class="text-center py-3 flex-shrink-0" style="color: #C4B5A4; font-size: .8125rem;">
            &copy; {{ date('Y') }} صكّ · SAKK
        </footer>
    </div>
</body>
</html>
