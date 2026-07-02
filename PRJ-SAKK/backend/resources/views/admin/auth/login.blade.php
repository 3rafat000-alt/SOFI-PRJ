<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.svg">
    <title>تسجيل الدخول — صك | SAKK</title>
    @include('components.critical-css')
    @include('components.admin-critical-css')
    <script defer src="{{ asset('vendor/alpine/alpine-3.14.8.min.js') }}"></script>
</head>
<body class="login-page">
    <div class="login-shell" x-data="{ loggingIn: false }">

        {{-- Brand showcase panel --}}
        <aside class="login-aside" aria-hidden="true">
            <div class="login-aside-glow"></div>
            <div class="login-aside-grid"></div>
            <div class="login-aside-top">
                <div class="login-aside-mark"><img src="/images/logo.svg" alt=""></div>
                <div class="login-aside-word">صك</div>
                <div class="login-aside-sub">SAKK ADMIN</div>
            </div>
            <div class="login-aside-copy">
                <p class="login-aside-kicker">لوحة التحكم</p>
                <h2 class="login-aside-head">إدارة المحفظة<br>بثقة ووضوح.</h2>
                <p class="login-aside-lede">منصة صكّ للمدفوعات — تحكّم كامل في العمليات والمستخدمين والامتثال من مكان واحد.</p>
            </div>
            <div class="login-aside-foot">&copy; {{ date('Y') }} صك | SAKK</div>
        </aside>

        {{-- Form panel --}}
        <div class="login-main">

        {{-- Mobile brand (aside hidden on small screens) --}}
        <div class="login-brand login-brand--compact">
            <div class="login-brand-mark"><img src="/images/logo.svg" alt="صك"></div>
            <div class="login-brand-name">صك</div>
            <div class="login-brand-sub">SAKK ADMIN</div>
        </div>

        {{-- Title --}}
        <h1 class="login-title">تسجيل الدخول</h1>
        <p class="login-subtitle">أدخل بيانات حسابك الإداري للوصول إلى لوحة التحكم.</p>

        {{-- Errors --}}
        @if($errors->any())
        <div class="login-alert fade-in" role="alert" aria-live="assertive">
            @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.login.submit') }}" id="loginForm" novalidate
              @submit="loggingIn = true">
            @csrf

            {{-- Email --}}
            <div class="login-field">
                <label class="label" for="loginEmail">البريد الإلكتروني</label>
                <input type="email" name="email" id="loginEmail"
                       value="{{ old('email') }}" required autofocus
                       class="input" placeholder="admin@example.com"
                       maxlength="255" autocomplete="email" dir="ltr" style="text-align:start"
                       aria-describedby="loginEmail-error">
                <p class="field-error login-field-error" id="loginEmail-error" hidden></p>
            </div>

            {{-- Password --}}
            <div class="login-field">
                <label class="label" for="loginPassword">كلمة المرور</label>
                <div class="login-pw-wrap">
                    <input type="password" name="password" id="loginPassword" required
                           class="input" placeholder="أدخل كلمة المرور"
                           maxlength="128" autocomplete="current-password"
                           aria-describedby="loginPassword-error">
                    <button type="button" class="login-pw-toggle" onclick="togglePassword('loginPassword', this)" aria-label="إظهار كلمة المرور">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p class="field-error login-field-error" id="loginPassword-error" hidden></p>
            </div>

            {{-- Remember --}}
            <div class="login-remember">
                <label class="login-check">
                    <input type="checkbox" name="remember">
                    <span>تذكّرني</span>
                </label>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn btn-primary w-full" :class="loggingIn ? 'btn-loading' : ''">
                <span>تسجيل الدخول</span>
            </button>
        </form>

        {{-- Footer --}}
        <p class="login-foot">&copy; {{ date('Y') }} صك | SAKK — جميع الحقوق محفوظة</p>

        </div>{{-- /login-main --}}
    </div>{{-- /login-shell --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const validators = {
        loginEmail: {
            el: document.getElementById('loginEmail'),
            validate: v => {
                if (!v) return 'حقل البريد الإلكتروني إجباري';
                if (v.length > 255) return 'البريد الإلكتروني طويل جداً';
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'صيغة البريد الإلكتروني غير صحيحة';
                return null;
            }
        },
        loginPassword: {
            el: document.getElementById('loginPassword'),
            validate: v => {
                if (!v) return 'حقل كلمة المرور إجباري';
                if (v.length > 128) return 'كلمة المرور طويلة جداً';
                return null;
            }
        }
    };

    function paint(f) {
        const error = f.validate(f.el.value);
        f.el.classList.remove('input-error', 'input-success');
        const errEl = f.el.closest('.login-field').querySelector('.login-field-error');
        if (error) {
            f.el.classList.add('input-error');
            f.el.setAttribute('aria-invalid', 'true');
            if (errEl) { errEl.textContent = error; errEl.hidden = false; }
        } else {
            if (f.el.value) f.el.classList.add('input-success');
            f.el.removeAttribute('aria-invalid');
            if (errEl) { errEl.hidden = true; errEl.textContent = ''; }
        }
        return !error;
    }

    Object.values(validators).forEach(f => {
        if (!f.el) return;
        f.el.addEventListener('blur', () => paint(f));
        f.el.addEventListener('input', function () {
            if (this.classList.contains('input-error')) paint(f);
        });
    });

    form.addEventListener('submit', function (e) {
        let valid = true;
        Object.values(validators).forEach(f => { if (f.el && !paint(f)) valid = false; });
        if (!valid) e.preventDefault();
    });
});

function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const shown = input.type === 'text';
    input.type = shown ? 'password' : 'text';
    btn.setAttribute('aria-label', shown ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
    btn.innerHTML = shown
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>';
}
</script>
</body>
</html>
