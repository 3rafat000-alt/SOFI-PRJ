<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.svg">
    <title>التحقق بخطوتين — صك | SAKK</title>
    <link href="{{ asset('sakk-assets/sakk-tokens.css') }}?v={{ filemtime(public_path('sakk-assets/sakk-tokens.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/tokens.css') }}?v={{ filemtime(public_path('css/admin/tokens.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/base.css') }}?v={{ filemtime(public_path('css/admin/base.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/utilities.css') }}?v={{ filemtime(public_path('css/admin/utilities.css')) }}" rel="stylesheet">
    <script defer src="{{ asset('vendor/alpine/alpine-3.14.8.min.js') }}"></script>
</head>
<body class="login-page">
    <div class="card-sukk-login" style="max-width: 32rem"
         x-data="{ enabling: false, confirming: false, disabling: false, regenerating: false }">

        {{-- Brand --}}
        <div class="login-brand">
            <div class="login-brand-mark"><img src="/images/logo.svg" alt="صك"></div>
            <div class="login-brand-name">صك</div>
            <div class="login-brand-sub">SAKK ADMIN</div>
        </div>

        {{-- Title --}}
        <h1 class="login-title">التحقق بخطوتين</h1>
        <p class="login-subtitle">أمّن حسابك الإداري برمز تحقق من تطبيق مصادقة.</p>

        {{-- Success flash --}}
        @if(session('success'))
        <div class="login-alert fade-in" role="status" aria-live="polite" style="background: var(--success-light); color: var(--success-dark)">
            <p>{{ session('success') }}</p>
        </div>
        @endif

        {{-- Errors --}}
        @if($errors->any())
        <div class="login-alert fade-in" role="alert" aria-live="assertive" id="twoFactorErrors">
            @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        {{-- Recovery codes — one-time reveal (shown above whichever state below) --}}
        @if($recovery_codes)
        <section class="login-alert fade-in" role="alert" aria-live="assertive"
                  style="background: var(--warning-light); color: #8A5F1F; text-align: start"
                  aria-label="رموز الاسترداد">
            <p style="font-weight: 700; margin-bottom: var(--space-xs)">
                احفظ رموز الاسترداد هذه الآن — لن تظهر مرة أخرى
            </p>
            <p style="margin-bottom: var(--space-sm)">
                استخدم أحد هذه الرموز لتسجيل الدخول في حال فقدت الوصول إلى تطبيق المصادقة.
            </p>
            <div class="grid grid-cols-2"
                 style="gap: var(--space-xs); font-family: monospace; direction: ltr; text-align: center;">
                @foreach($recovery_codes as $recoveryCode)
                <span class="badge badge-warning" style="justify-content: center">{{ $recoveryCode }}</span>
                @endforeach
            </div>
            @php($recoveryCodesJoined = implode("\n", $recovery_codes))
            <button type="button" class="btn btn-secondary w-full" style="margin-top: var(--space-sm)"
                    x-data="{ copied: false }"
                    @click="navigator.clipboard.writeText(@js($recoveryCodesJoined)); copied = true; setTimeout(() => copied = false, 2000)"
                    :aria-label="copied ? 'تم النسخ' : 'نسخ جميع الرموز'">
                <span x-text="copied ? 'تم نسخ الرموز' : 'نسخ جميع الرموز'"></span>
            </button>
        </section>
        @endif

        @if(!$enabled && !$pending)
            {{-- STATE A: 2FA disabled — start enrollment --}}
            <section aria-labelledby="state-disabled-title">
                <div class="badge badge-inactive" style="margin-bottom: var(--space-sm)">
                    <span>غير مفعّل</span>
                </div>
                <p id="state-disabled-title" class="login-subtitle" style="margin-bottom: var(--space-md)">
                    التحقق بخطوتين غير مفعّل حالياً على حسابك. فعّله لإضافة طبقة حماية إضافية عند تسجيل الدخول.
                </p>
                <form method="POST" action="{{ route('admin.profile.2fa.enable') }}" id="enableForm" novalidate
                      @submit="enabling = true">
                    @csrf
                    <div class="login-field">
                        <label class="label" for="enablePassword">كلمة المرور الحالية</label>
                        <input type="password" name="password" id="enablePassword"
                               autocomplete="current-password"
                               value="" required
                               class="input @error('password') input-error @enderror"
                               dir="ltr"
                               aria-describedby="enablePassword-error">
                        <p class="field-error login-field-error" id="enablePassword-error"
                           @if(!$errors->has('password')) hidden @endif>{{ $errors->first('password') }}</p>
                    </div>
                    <button type="submit" class="btn btn-primary w-full" :class="enabling ? 'btn-loading' : ''">
                        <span>بدء تفعيل التحقق بخطوتين</span>
                    </button>
                </form>
            </section>

        @elseif($pending)
            {{-- STATE B: mid-enrollment — scan QR, confirm code --}}
            <section aria-labelledby="state-pending-title">
                <div class="badge badge-pending" style="margin-bottom: var(--space-sm); color: #8A5F1F">
                    <span>قيد التفعيل</span>
                </div>
                <p id="state-pending-title" class="login-subtitle" style="margin-bottom: var(--space-md)">
                    امسح رمز QR ضوئياً باستخدام تطبيق المصادقة (مثل Google Authenticator)، ثم أدخل الرمز المكوّن من 6 أرقام لتأكيد التفعيل.
                </p>

                <div style="display:flex; justify-content:center; margin-bottom: var(--space-md)">
                    <img src="{{ $pending['qr_code_url'] }}" alt="رمز QR لتفعيل التحقق بخطوتين"
                         width="200" height="200" style="border-radius: var(--radius-md)">
                </div>

                <div class="login-field">
                    <label class="label" for="pendingSecret">أو أدخل هذا المفتاح يدوياً</label>
                    <div style="display:flex; gap: var(--space-xs)">
                        <input type="text" id="pendingSecret" class="input" readonly
                               value="{{ $pending['secret'] }}" dir="ltr"
                               style="text-align:center; letter-spacing:0.1em; font-family: monospace"
                               aria-readonly="true">
                        <button type="button" class="btn btn-secondary"
                                x-data="{ copied: false }"
                                @click="navigator.clipboard.writeText(@js($pending['secret'])); copied = true; setTimeout(() => copied = false, 2000)"
                                :aria-label="copied ? 'تم النسخ' : 'نسخ المفتاح'">
                            <span x-text="copied ? 'تم النسخ' : 'نسخ'"></span>
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.profile.2fa.confirm') }}" id="confirmForm" novalidate
                      @submit="confirming = true">
                    @csrf
                    <div class="login-field">
                        <label class="label" for="confirmCode">رمز التأكيد</label>
                        <input type="text" name="code" id="confirmCode"
                               inputmode="numeric" autocomplete="one-time-code"
                               value="" required autofocus
                               class="input @error('code') input-error @enderror"
                               placeholder="000000" maxlength="6" dir="ltr"
                               style="text-align:center; letter-spacing:0.25em"
                               aria-describedby="confirmCode-error">
                        <p class="field-error login-field-error" id="confirmCode-error"
                           @if(!$errors->has('code')) hidden @endif>{{ $errors->first('code') }}</p>
                    </div>
                    <button type="submit" class="btn btn-primary w-full" :class="confirming ? 'btn-loading' : ''">
                        <span>تأكيد التفعيل</span>
                    </button>
                </form>
            </section>

        @else
            {{-- STATE C: 2FA active — disable / regenerate recovery codes --}}
            <section aria-labelledby="state-active-title">
                <div class="badge badge-active" style="margin-bottom: var(--space-sm)">
                    <span>مفعّل</span>
                </div>
                <p id="state-active-title" class="login-subtitle" style="margin-bottom: var(--space-md)">
                    التحقق بخطوتين مفعّل على حسابك. أدخل رمز التحقق الحالي أو أحد رموز الاسترداد لتوليد رموز جديدة أو لإيقاف الميزة.
                </p>

                <form method="POST" action="{{ route('admin.profile.2fa.recovery') }}" id="recoveryForm" novalidate
                      @submit="regenerating = true" style="margin-bottom: var(--space-lg)">
                    @csrf
                    <div class="login-field">
                        <label class="label" for="recoveryPassword">كلمة المرور الحالية</label>
                        <input type="password" name="password" id="recoveryPassword"
                               autocomplete="current-password"
                               value="" required
                               class="input"
                               dir="ltr"
                               aria-describedby="recoveryPassword-error">
                        <p class="field-error login-field-error" id="recoveryPassword-error" hidden></p>
                    </div>
                    <div class="login-field">
                        <label class="label" for="recoveryCode">رمز التحقق (لتوليد رموز استرداد جديدة)</label>
                        <input type="text" name="code" id="recoveryCode"
                               inputmode="numeric" autocomplete="one-time-code"
                               value="" required
                               class="input"
                               placeholder="000000 أو رمز استرداد" maxlength="20" dir="ltr"
                               style="text-align:center; letter-spacing:0.15em"
                               aria-describedby="recoveryCode-error">
                        <p class="field-error login-field-error" id="recoveryCode-error" hidden></p>
                    </div>
                    <button type="submit" class="btn btn-secondary w-full" :class="regenerating ? 'btn-loading' : ''">
                        <span>توليد رموز استرداد جديدة</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.profile.2fa.disable') }}" id="disableForm" novalidate
                      @submit="disabling = true">
                    @csrf
                    <div class="login-field">
                        <label class="label" for="disablePassword">كلمة المرور الحالية</label>
                        <input type="password" name="password" id="disablePassword"
                               autocomplete="current-password"
                               value="" required
                               class="input"
                               dir="ltr"
                               aria-describedby="disablePassword-error">
                        <p class="field-error login-field-error" id="disablePassword-error" hidden></p>
                    </div>
                    <div class="login-field">
                        <label class="label" for="disableCode">رمز التحقق (لإيقاف التحقق بخطوتين)</label>
                        <input type="text" name="code" id="disableCode"
                               inputmode="numeric" autocomplete="one-time-code"
                               value="" required
                               class="input"
                               placeholder="000000 أو رمز استرداد" maxlength="20" dir="ltr"
                               style="text-align:center; letter-spacing:0.15em"
                               aria-describedby="disableCode-error">
                        <p class="field-error login-field-error" id="disableCode-error" hidden></p>
                    </div>
                    <button type="submit" class="btn btn-danger w-full" :class="disabling ? 'btn-loading' : ''">
                        <span>إيقاف التحقق بخطوتين</span>
                    </button>
                </form>
            </section>
        @endif

        {{-- Footer --}}
        <p class="login-foot">&copy; {{ date('Y') }} صك | SAKK — جميع الحقوق محفوظة</p>
    </div>
</body>
</html>
