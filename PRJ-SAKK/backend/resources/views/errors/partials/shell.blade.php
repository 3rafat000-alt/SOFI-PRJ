{{--
    SAKK · صك — Error Shell (v7 · Full-Screen Redesign)
    100vh × 100vw · no scroll · huge background code · SAKK brand
    Parameters:
      $code     string|int   large status code
      $tone     string       neutral | danger | warning | gold
      $icon     string       key into SVG map below
      $title    string       Arabic heading
      $message  string       Arabic body copy
      $actions  array        [ ['label','href'|'onclick','primary'=>bool,'icon'=>key], ... ]
      $note     string       optional extra note below message
--}}
@php
    $tone    = $tone    ?? 'neutral';
    $icon    = $icon    ?? 'alert';
    $actions = $actions ?? [];
    $note    = $note    ?? '';

    // ---- Tone palette (SAKK brand) ----
    $tones = [
        'danger'  => ['ink' => '#6B0F24', 'soft' => 'rgba(107,15,36,0.07)',  'a' => '#6B0F24', 'b' => '#4A0A18', 'bg' => '#6B0F24'],
        'warning' => ['ink' => '#B58A3C', 'soft' => 'rgba(181,138,60,0.08)', 'a' => '#B58A3C', 'b' => '#8F6B2A', 'bg' => '#B58A3C'],
        'neutral' => ['ink' => '#6E5F63', 'soft' => 'rgba(110,95,99,0.06)',  'a' => '#6E5F63', 'b' => '#2A1A1F', 'bg' => '#6E5F63'],
        'gold'    => ['ink' => '#B58A3C', 'soft' => 'rgba(181,138,60,0.08)', 'a' => '#B58A3C', 'b' => '#8F6B2A', 'bg' => '#B58A3C'],
    ];
    $t = $tones[$tone] ?? $tones['neutral'];

    // ---- Inline SVG map (24×24, stroke, currentColor) ----
    $svg = [
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'lock'    => '<path d="M7 10V8a5 5 0 0110 0v2"/><rect x="4.5" y="10" width="15" height="10" rx="0.4"/><path d="M12 14v3"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'alert'   => '<path d="M10.3 4.1L2.7 17a1.9 1.9 0 001.65 2.85h15.3A1.9 1.9 0 0021.3 17L13.7 4.1a1.9 1.9 0 00-3.4 0z"/><path d="M12 9.5v4"/><path d="M12 17h.01"/>',
        'wrench'  => '<path d="M14.5 6.2a4 4 0 00-5.3 5.3L3 17.7 6.3 21l6.2-6.2a4 4 0 005.3-5.3l-2.6 2.6-2.3-.6-.6-2.3 2.6-2.6z"/>',
        'timer'   => '<circle cx="12" cy="12" r="9"/><path d="M12 6v6l3 2"/>',
        'rocket'  => '<path d="M12 2s-3 4-3 11a3 3 0 006 0c0-7-3-11-3-11z"/><circle cx="12" cy="19" r="2"/>',
        'home'    => '<path d="M3 11.5L12 4l9 7.5"/><path d="M5.5 10v10h5v-6h3v6h5V10"/>',
        'back'    => '<path d="M19 12H5"/><path d="M11 6l-6 6 6 6"/>',
        'refresh' => '<path d="M20 11A8 8 0 006 6.3L4 8"/><path d="M4 4v4h4"/><path d="M4 13a8 8 0 0014 4.7L20 16"/><path d="M20 20v-4h-4"/>',
        'login'   => '<path d="M14 3h5a1 1 0 011 1v16a1 1 0 01-1 1h-5"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h12"/>',
        'install' => '<path d="M12 3v12m0 0l-4-4m4 4l4-4M3 17v1a2 2 0 002 2h14a2 2 0 002-2v-1"/>',
    ];
    $iconSvg = function ($key) use ($svg) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'
             . ($svg[$key] ?? $svg['alert'] ?? '') . '</svg>';
    };
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} — صك | SAKK</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --sukk-primary: #6B0F24;
            --sukk-primary-dark: #4A0A18;
            --sukk-primary-hover: #530B1C;
            --gold: #B58A3C;
            --gold-deep: #8F6B2A;
            --bg: #F7F3EE;
            --surface: #ffffff;
            --text-primary: #2A1A1F;
            --text-secondary: #6E5F63;
            --text-muted: #86787B;
            --radius-sm: 6px;
            --tone-ink: {{ $t['ink'] }};
            --tone-soft: {{ $t['soft'] }};
            --tone-bg: {{ $t['bg'] }};
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'IBM Plex Sans Arabic', system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em;
            position: relative;
            background-image:
                radial-gradient(70rem 40rem at 15% 85%, var(--tone-soft), transparent 60%),
                radial-gradient(60rem 35rem at 85% 15%, rgba(107,15,36,0.03), transparent 50%);
        }

        /* ── HUGE background status code ── */
        .err-bg-code {
            position: fixed;
            bottom: -0.08em;
            left: -0.03em;
            font-size: 42vw;
            font-weight: 900;
            line-height: 0.85;
            color: rgba(42,26,31,0.035);
            pointer-events: none;
            user-select: none;
            z-index: 0;
            letter-spacing: -0.06em;
            direction: ltr;
        }
        /* Secondary smaller code for depth */
        .err-bg-code-sub {
            position: fixed;
            top: -0.12em;
            right: -0.02em;
            font-size: 22vw;
            font-weight: 900;
            line-height: 0.9;
            color: var(--tone-soft);
            pointer-events: none;
            user-select: none;
            z-index: 0;
            letter-spacing: -0.05em;
            direction: ltr;
            opacity: 0.5;
        }

        /* ── Main content layer ── */
        .err-wrap {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            width: 100vw;
            padding: 2rem;
            text-align: center;
        }

        /* ── Brand pill ── */
        .err-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            margin-bottom: 2rem;
            padding: 0.4rem 1rem 0.4rem 0.8rem;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 100px;
            border: 1px solid rgba(42,26,31,0.06);
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .err-brand:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }
        .err-brand-mark {
            width: 26px; height: 26px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--sukk-primary), var(--sukk-primary-dark));
            border-radius: 50%;
            font-size: 0.65rem; font-weight: 900; color: #fff;
            flex-shrink: 0;
        }
        .err-brand-text { font-size: 0.8rem; font-weight: 800; color: var(--text-primary); }
        .err-brand-sub { font-size: 0.45rem; font-weight: 600; letter-spacing: 0.2em; color: var(--text-muted); direction: ltr; margin-top: -1px; }

        /* ── Icon circle ── */
        .err-icon {
            width: 68px; height: 68px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            background: var(--tone-ink);
            margin-bottom: 1.25rem;
            box-shadow:
                0 8px 24px rgba(107,15,36,0.12),
                0 2px 6px rgba(0,0,0,0.04);
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .err-icon:hover { transform: scale(1.06); }
        .err-icon svg { width: 28px; height: 28px; }

        /* ── Status code (inline) ── */
        .err-code-inline {
            font-size: 4.5rem;
            font-weight: 900;
            line-height: 1;
            color: var(--tone-ink);
            letter-spacing: -0.04em;
            direction: ltr;
            margin-bottom: 0.35rem;
        }

        /* ── Text ── */
        .err-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.3;
            margin-bottom: 0.3rem;
        }
        .err-msg {
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.85;
            color: var(--text-secondary);
            max-width: 32rem;
        }
        .err-note {
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.7;
            color: var(--text-muted);
            margin-top: 0.6rem;
            padding: 0.5rem 1rem;
            background: var(--tone-soft);
            border-radius: var(--radius-sm);
            max-width: 28rem;
        }

        /* ── Actions ── */
        .err-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 1.5rem;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.7rem 1.5rem;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            white-space: nowrap;
            line-height: 1.2;
            min-height: 2.6rem;
        }
        .err-btn svg { width: 18px; height: 18px; flex-shrink: 0; }
        .err-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(107,15,36,0.2);
        }
        .err-btn-primary {
            background: var(--sukk-primary);
            color: #fff;
            box-shadow: 0 1px 3px rgba(42,26,31,0.08);
        }
        .err-btn-primary:hover {
            background: var(--sukk-primary-hover);
            box-shadow: 0 4px 14px rgba(107,15,36,0.2);
            transform: translateY(-1px);
        }
        .err-btn-secondary {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: var(--text-secondary);
            border: 1px solid rgba(42,26,31,0.06);
        }
        .err-btn-secondary:hover {
            background: #fff;
            color: var(--sukk-primary);
            border-color: rgba(107,15,36,0.12);
            transform: translateY(-1px);
        }

        /* ── Footer ── */
        .err-foot {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            color: var(--text-muted);
            z-index: 1;
            text-align: center;
            padding: 0 1rem;
            white-space: nowrap;
        }
        .err-foot a {
            color: var(--sukk-primary);
            font-weight: 700;
            text-decoration: none;
        }
        .err-foot a:hover { text-decoration: underline; }

        /* ── Decorative corner elements ── */
        .err-corner-tl, .err-corner-br {
            position: fixed;
            width: 80px; height: 80px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .err-corner-tl {
            top: -30px; left: -30px;
            background: radial-gradient(circle, var(--tone-soft), transparent 70%);
        }
        .err-corner-br {
            bottom: -30px; right: -30px;
            background: radial-gradient(circle, var(--tone-soft), transparent 70%);
        }

        /* ── Entrance animation ── */
        @media (prefers-reduced-motion: no-preference) {
            .err-wrap { animation: err-fade-up 0.6s cubic-bezier(0.16,0.7,0.2,1) both; }
            .err-bg-code { animation: err-slide-left 0.8s cubic-bezier(0.16,0.7,0.2,1) both 0.1s; }
            .err-bg-code-sub { animation: err-slide-right 0.7s cubic-bezier(0.16,0.7,0.2,1) both 0.15s; }
            @keyframes err-fade-up {
                from { opacity: 0; transform: translateY(24px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes err-slide-left {
                from { transform: translateX(40px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes err-slide-right {
                from { transform: translateX(-40px); opacity: 0; }
                to { transform: translateX(0); opacity: 0.5; }
            }
        }

        @media (max-width: 600px) {
            .err-bg-code { font-size: 55vw; }
            .err-bg-code-sub { font-size: 28vw; }
            .err-code-inline { font-size: 3.2rem; }
            .err-title { font-size: 1.2rem; }
            .err-msg { font-size: 0.85rem; }
            .err-icon { width: 56px; height: 56px; }
            .err-icon svg { width: 22px; height: 22px; }
            .err-foot { font-size: 0.7rem; white-space: normal; }
        }
    </style>
</head>
<body>
    {{-- Decorative corner blobs --}}
    <div class="err-corner-tl" aria-hidden="true"></div>
    <div class="err-corner-br" aria-hidden="true"></div>

    {{-- Huge background code --}}
    <div class="err-bg-code" aria-hidden="true">{{ $code }}</div>
    <div class="err-bg-code-sub" aria-hidden="true">{{ $code }}</div>

    <div class="err-wrap">
        {{-- Brand pill --}}
        <a href="/" class="err-brand" aria-label="الصفحة الرئيسية">
            <span class="err-brand-mark">ص</span>
            <span>
                <div class="err-brand-text">صك</div>
                <div class="err-brand-sub">SAKK</div>
            </span>
        </a>

        {{-- Icon --}}
        <div class="err-icon" aria-hidden="true">{!! $iconSvg($icon) !!}</div>

        {{-- Status code inline --}}
        <div class="err-code-inline" dir="ltr">{{ $code }}</div>

        {{-- Text --}}
        <h1 class="err-title">{{ $title }}</h1>
        <p class="err-msg">{{ $message }}</p>

        {{-- Optional note --}}
        @if($note)
            <p class="err-note">{{ $note }}</p>
        @endif

        {{-- Actions --}}
        @if(!empty($actions))
        <div class="err-actions">
            @foreach($actions as $a)
                @php
                    $primary = $a['primary'] ?? false;
                    $cls     = $primary ? 'err-btn err-btn-primary' : 'err-btn err-btn-secondary';
                    $ic      = isset($a['icon']) ? $iconSvg($a['icon']) : '';
                @endphp
                @if(isset($a['onclick']))
                    <button type="button" class="{{ $cls }}" onclick="{{ $a['onclick'] }}">{!! $ic !!}<span>{{ $a['label'] }}</span></button>
                @else
                    <a href="{{ $a['href'] ?? '/' }}" class="{{ $cls }}">{!! $ic !!}<span>{{ $a['label'] }}</span></a>
                @endif
            @endforeach
        </div>
        @endif
    </div>

    {{-- Footer --}}
    <p class="err-foot">
        تواصل مع <a href="mailto:{{ config('mail.support_address', 'support@sakk.com') }}">الدعم الفني</a> إن استمرّت المشكلة
    </p>
</body>
</html>
