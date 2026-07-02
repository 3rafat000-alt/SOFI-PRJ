<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.svg">
    <title>التحقق الثنائي — صك | SAKK</title>
    <link href="{{ asset('sakk-assets/sakk-tokens.css') }}?v={{ filemtime(public_path('sakk-assets/sakk-tokens.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/tokens.css') }}?v={{ filemtime(public_path('css/admin/tokens.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/base.css') }}?v={{ filemtime(public_path('css/admin/base.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin/utilities.css') }}?v={{ filemtime(public_path('css/admin/utilities.css')) }}" rel="stylesheet">
    <script defer src="{{ asset('vendor/alpine/alpine-3.14.8.min.js') }}"></script>
</head>
<body class="login-page">
    <div class="card-sukk-login" x-data="{ verifying: false }">

        {{-- Brand --}}
        <div class="login-brand">
            <div class="login-brand-mark"><img src="/images/logo.svg" alt="صك"></div>
            <div class="login-brand-name">صك</div>
            <div class="login-brand-sub">SAKK ADMIN</div>
        </div>

        {{-- Title --}}
        <h1 class="login-title">التحقق الثنائي</h1>
        <p class="login-subtitle">أدخل رمز التحقق من تطبيق المصادقة، أو أحد رموز الاسترداد.</p>

        {{-- Errors --}}
        @if($errors->any())
        <div class="login-alert fade-in" role="alert" aria-live="assertive">
            @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.login.2fa.verify') }}" id="twoFactorForm" novalidate
              @submit="verifying = true">
            @csrf

            {{-- Code --}}
            <div class="login-field">
                <label class="label" for="twoFactorCode">رمز التحقق</label>
                <input type="text" name="code" id="twoFactorCode"
                       inputmode="numeric" autocomplete="one-time-code"
                       value="" required autofocus
                       class="input" placeholder="000000"
                       maxlength="20" dir="ltr" style="text-align:center; letter-spacing:0.25em;">
                <p class="field-error login-field-error" hidden></p>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn btn-primary w-full" :class="verifying ? 'btn-loading' : ''">
                <span>تأكيد</span>
            </button>
        </form>

        {{-- Footer --}}
        <p class="login-foot">&copy; {{ date('Y') }} صك | SAKK — جميع الحقوق محفوظة</p>
    </div>
</body>
</html>
