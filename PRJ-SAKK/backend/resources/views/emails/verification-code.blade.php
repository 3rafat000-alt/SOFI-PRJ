<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صكك | رمز التحقق</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');
        body { font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f1f5f9; }
        .wrapper { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%); padding: 40px 30px; text-align: center; }
        .header img { width: 80px; height: 80px; border-radius: 20px; margin-bottom: 16px; }
        .header h1 { color: #ffffff; font-size: 24px; font-weight: 800; margin: 0; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin: 8px 0 0; }
        .content { padding: 40px 30px; text-align: center; }
        .content h2 { color: #1e293b; font-size: 20px; font-weight: 700; margin: 0 0 12px; }
        .content p { color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 24px; }
        .code-box { display: inline-block; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 24px 40px; margin: 16px 0; }
        .code { font-size: 42px; font-weight: 800; letter-spacing: 8px; color: #3b82f6; direction: ltr; }
        .expiry { color: #94a3b8; font-size: 13px; margin-top: 24px; }
        .footer { background: #f8fafc; padding: 24px 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 0; }
        .footer a { color: #3b82f6; text-decoration: none; }
        .warning { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin: 24px 0 0; color: #b45309; font-size: 13px; }
        @media (max-width: 480px) {
            .wrapper { border-radius: 0; }
            .header { padding: 30px 20px; }
            .content { padding: 30px 20px; }
            .code { font-size: 32px; letter-spacing: 6px; }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <div class="wrapper">
                    <!-- Header -->
                    <div class="header">
                        <img src="{{ asset('images/logo.svg') }}" alt="صكك" style="width: 80px; height: 80px; border-radius: 20px; margin-bottom: 16px; background: #ffffff; padding: 4px;">
                        <h1>صكك | SAKK Wallet</h1>
                        <p>محفظتك الرقمية الآمنة</p>
                    </div>
                    
                    <!-- Content -->
                    <div class="content">
                        <h2>رمز التحقق</h2>
                        <p>لإكمال عملية التحقق، يرجى استخدام رمز التحقق التالي:</p>
                        
                        <div class="code-box">
                            <div class="code">{{ $code }}</div>
                        </div>
                        
                        <p class="expiry">⏱️ صلاحية الرمز: 15 دقيقة</p>
                        
                        <div class="warning">
                            <strong>⚠️ تنبيه أمني:</strong> إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد وعدم مشاركته مع أي شخص.
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="footer">
                        <p>© {{ date('Y') }} صكك | SAKK Wallet. جميع الحقوق محفوظة.</p>
                        <p style="margin-top: 8px;">هل تحتاج مساعدة؟ <a href="mailto:{{ config('mail.support_address') }}">{{ config('mail.support_address') }}</a></p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
