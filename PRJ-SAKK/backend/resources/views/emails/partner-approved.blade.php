<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صكّ | تمت الموافقة على طلبك</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');
        body { font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f0eb; }
        .wrapper { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #6E1B2D 0%, #a12540 100%); padding: 40px 30px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 26px; font-weight: 800; margin: 0; letter-spacing: -0.5px; }
        .header p { color: rgba(255,255,255,0.80); font-size: 14px; margin: 8px 0 0; }
        .badge { display: inline-block; background: #B58A3C; color: #fff; font-size: 13px; font-weight: 700; border-radius: 20px; padding: 4px 16px; margin-top: 12px; }
        .content { padding: 40px 30px; }
        .content h2 { color: #6E1B2D; font-size: 22px; font-weight: 800; margin: 0 0 16px; }
        .content p { color: #4a4a4a; font-size: 16px; line-height: 1.7; margin: 0 0 20px; }
        .cta-block { background: #f9f5f0; border: 2px solid #e8d9c5; border-radius: 12px; padding: 28px; text-align: center; margin: 24px 0; }
        .cta-block p { margin: 0 0 6px; color: #6b6b6b; font-size: 14px; }
        .cta-email { font-size: 15px; color: #6E1B2D; font-weight: 700; direction: ltr; margin-bottom: 20px; }
        .cta-btn { display: inline-block; background: #6E1B2D; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 14px 40px; border-radius: 10px; }
        .cta-btn:hover { background: #591626; }
        .cta-url { font-size: 12px; color: #999; margin-top: 12px; word-break: break-all; direction: ltr; }
        .info-box { background: #fffbf0; border-right: 4px solid #B58A3C; border-radius: 8px; padding: 16px 20px; margin-top: 24px; }
        .info-box p { margin: 0; font-size: 14px; color: #7a5c1e; line-height: 1.6; }
        .footer { background: #f5f0eb; padding: 24px 30px; text-align: center; border-top: 1px solid #e5ddd0; }
        .footer p { color: #999; font-size: 12px; margin: 0; }
        @media (max-width: 480px) {
            .wrapper { border-radius: 0; }
            .header, .content { padding: 28px 18px; }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <div class="wrapper">
                    <div class="header">
                        <h1>صكّ | SAKK</h1>
                        <p>منصة المدفوعات الرقمية</p>
                        <span class="badge">{{ $portalBrand }}</span>
                    </div>

                    <div class="content">
                        <h2>مبارك! تمت الموافقة على طلبك</h2>

                        <p>
                            عزيزي <strong>{{ $entityName }}</strong>،<br>
                            يسعدنا إبلاغك بأن طلب انضمامك إلى منصة <strong>صكّ</strong> قد تمت مراجعته والموافقة عليه.
                            يمكنك الآن تسجيل الدخول إلى بوابتك الخاصة وإدارة حسابك.
                        </p>

                        <div class="cta-block">
                            <p>تسجيل الدخول بالبريد الإلكتروني</p>
                            <div class="cta-email">{{ $loginEmail }}</div>
                            <a href="{{ $loginUrl }}" class="cta-btn">الدخول إلى البوابة</a>
                            <div class="cta-url">{{ $loginUrl }}</div>
                        </div>

                        <div class="info-box">
                            <p>
                                إذا واجهت أي مشكلة في تسجيل الدخول أو احتجت إلى مساعدة،
                                يرجى التواصل مع فريق الدعم عبر:
                                <strong>{{ config('mail.support_address', 'support@sakk.app') }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="footer">
                        <p>© {{ date('Y') }} صكّ | SAKK. جميع الحقوق محفوظة.</p>
                        <p style="margin-top: 6px;">هذا البريد أُرسل تلقائياً عند اعتماد طلبك. لا تردّ على هذا البريد.</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
