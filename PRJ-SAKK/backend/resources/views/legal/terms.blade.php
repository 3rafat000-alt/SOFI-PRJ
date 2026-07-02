@php
    $lang = request()->query('lang', 'ar');
    $isAr = $lang === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isAr ? 'الشروط والأحكام' : 'Terms of Service' }} - SAKK</title>
    <link href="https://cdn.jsdelivr.net/npm/@fontsource/cairo@5.0.18/index.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Cairo',sans-serif; background:#f9f9f8; color:#1a1a2e; padding:2rem; line-height:1.8; }
        .container { max-width:800px; margin:0 auto; background:#fff; border-radius:1rem; padding:2.5rem; box-shadow:0 2px 12px rgba(0,0,0,0.06); }
        h1 { font-size:1.75rem; font-weight:800; margin-bottom:0.5rem; color:#6E1B2D; }
        .updated { font-size:0.85rem; color:#888; margin-bottom:2rem; }
        h2 { font-size:1.15rem; font-weight:700; margin-top:1.75rem; margin-bottom:0.5rem; color:#1a1a2e; }
        p { font-size:0.95rem; color:#444; margin-bottom:1rem; }
        .lang-toggle { text-align:center; margin-bottom:1.5rem; }
        .lang-toggle a { color:#6E1B2D; font-weight:700; text-decoration:none; font-size:0.9rem; }
        .lang-toggle a:hover { text-decoration:underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="lang-toggle">
            @if ($isAr)
                <a href="?lang=en">English</a>
            @else
                <a href="?lang=ar">العربية</a>
            @endif
        </div>
        <h1>{{ $isAr ? 'الشروط والأحكام' : 'Terms of Service' }}</h1>
        <p class="updated">{{ $isAr ? 'آخر تحديث: ' . now()->format('Y-m-d') : 'Last updated: ' . now()->format('Y-m-d') }}</p>

        <h2>{{ $isAr ? 'القبول' : 'Acceptance' }}</h2>
        <p>{{ $isAr ? 'باستخدامك لخدمات صكك، فإنك توافق على هذه الشروط والأحكام. إذا لم توافق، يرجى عدم استخدام الخدمة.' : 'By using SAKK services, you agree to these terms. If you do not agree, please do not use the service.' }}</p>

        <h2>{{ $isAr ? 'المتطلبات' : 'Requirements' }}</h2>
        <p>{{ $isAr ? 'يجب أن يكون عمرك 18 سنة على الأقل لاستخدام الخدمة. يجب أن تكون المعلومات المقدمة دقيقة وكاملة.' : 'You must be at least 18 years old. Information provided must be accurate and complete.' }}</p>

        <h2>{{ $isAr ? 'الحساب' : 'Account' }}</h2>
        <p>{{ $isAr ? 'أنت مسؤول عن الحفاظ على سرية بيانات حسابك وكلمة المرور. يجب إبلاغنا فوراً عن أي استخدام غير مصرح به.' : 'You are responsible for maintaining the confidentiality of your account credentials. Notify us immediately of any unauthorized use.' }}</p>

        <h2>{{ $isAr ? 'الرسوم' : 'Fees' }}</h2>
        <p>{{ $isAr ? 'قد يتم تطبيق رسوم على بعض المعاملات. سيتم إعلامك بالرسوم قبل تنفيذ أي معاملة.' : 'Fees may apply to certain transactions. You will be informed of fees before any transaction.' }}</p>

        <h2>{{ $isAr ? 'المسؤولية' : 'Liability' }}</h2>
        <p>{{ $isAr ? 'لن نكون مسؤولين عن أي أضرار غير مباشرة ناتجة عن استخدام الخدمة. مسؤوليتنا محدودة بالمبلغ المدفوع للخدمة.' : 'We shall not be liable for indirect damages. Our liability is limited to the amount paid for the service.' }}</p>

        <h2>{{ $isAr ? 'الإلغاء' : 'Termination' }}</h2>
        <p>{{ $isAr ? 'يمكنك إلغاء حسابك في أي وقت. قد نعلق أو ننهي حسابك في حال انتهاك الشروط.' : 'You may cancel your account at any time. We may suspend or terminate accounts that violate these terms.' }}</p>

        <h2>{{ $isAr ? 'التعديلات' : 'Changes' }}</h2>
        <p>{{ $isAr ? 'قد نعدل هذه الشروط في أي وقت. سنخطرك بالتعديلات الهامة عبر البريد الإلكتروني.' : 'We may modify these terms at any time. We will notify you of significant changes via email.' }}</p>
    </div>
</body>
</html>
