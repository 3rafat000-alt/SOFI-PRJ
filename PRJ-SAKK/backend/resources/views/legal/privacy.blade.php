@php
    $lang = request()->query('lang', 'ar');
    $isAr = $lang === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' }} - SAKK</title>
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
        <h1>{{ $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' }}</h1>
        <p class="updated">{{ $isAr ? 'آخر تحديث: ' . now()->format('Y-m-d') : 'Last updated: ' . now()->format('Y-m-d') }}</p>

        <h2>{{ $isAr ? 'المقدمة' : 'Introduction' }}</h2>
        <p>{{ $isAr ? 'نحن في صكك نلتزم بحماية خصوصية مستخدمينا. توضح سياسة الخصوصية هذه كيفية جمع واستخدام وحماية معلوماتك الشخصية.' : 'At SAKK, we are committed to protecting your privacy. This policy explains how we collect, use, and protect your personal information.' }}</p>

        <h2>{{ $isAr ? 'المعلومات التي نجمعها' : 'Information We Collect' }}</h2>
        <p>{{ $isAr ? 'نقوم بجمع المعلومات التالية: الاسم، البريد الإلكتروني، رقم الهاتف، بيانات الهوية لأغراض KYC، ومعلومات الجهاز.' : 'We collect: name, email, phone number, identity documents for KYC purposes, and device information.' }}</p>

        <h2>{{ $isAr ? 'كيف نستخدم معلوماتك' : 'How We Use Your Data' }}</h2>
        <p>{{ $isAr ? 'نستخدم معلوماتك لتقديم خدمات المحفظة الرقمية، التحقق من هويتك، تحسين خدماتنا، والتواصل معك بخصوص حسابك.' : 'We use your data to provide digital wallet services, verify your identity, improve our services, and communicate with you.' }}</p>

        <h2>{{ $isAr ? 'حماية البيانات' : 'Data Protection' }}</h2>
        <p>{{ $isAr ? 'نستخدم أحدث تقنيات التشفير لحماية بياناتك. جميع المعلومات الحساسة مشفرة باستخدام AES-256.' : 'We use the latest encryption technologies to protect your data. All sensitive information is encrypted using AES-256.' }}</p>

        <h2>{{ $isAr ? 'مشاركة المعلومات' : 'Information Sharing' }}</h2>
        <p>{{ $isAr ? 'لا نشارك معلوماتك الشخصية مع أطراف ثالثة إلا بموافقتك أو عندما يقتضي القانون ذلك.' : 'We do not share your personal information with third parties except with your consent or as required by law.' }}</p>

        <h2>{{ $isAr ? 'حقوقك' : 'Your Rights' }}</h2>
        <p>{{ $isAr ? 'لديك الحق في الوصول إلى بياناتك، تصحيحها، أو حذفها. يمكنك طلب ذلك من خلال الإعدادات أو التواصل معنا.' : 'You have the right to access, correct, or delete your data. You can do this through settings or by contacting us.' }}</p>

        <h2>{{ $isAr ? 'اتصل بنا' : 'Contact Us' }}</h2>
        <p>{{ $isAr ? 'للاستفسارات حول سياسة الخصوصية، يرجى التواصل معنا عبر البريد الإلكتروني: privacy@sakkwallet.com' : 'For privacy inquiries, please contact us at: privacy@sakkwallet.com' }}</p>
    </div>
</body>
</html>
