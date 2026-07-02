<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صكك | {{ $subject ?? 'إشعار' }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');
        body { font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f1f5f9; }
        .wrapper { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%); padding: 40px 30px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 800; margin: 0; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin: 8px 0 0; }
        .content { padding: 40px 30px; }
        .content h2 { color: #1e293b; font-size: 20px; font-weight: 700; margin: 0 0 16px; }
        .content p { color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .details { background: #f8fafc; border-radius: 12px; padding: 24px; margin: 24px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; color: #475569; font-size: 14px; }
        .details td:first-child { font-weight: 600; color: #1e293b; width: 40%; }
        .details tr:not(:last-child) td { border-bottom: 1px solid #e2e8f0; }
        .btn { display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%); color: #ffffff; padding: 14px 32px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 15px; margin: 16px 0; }
        .footer { background: #f8fafc; padding: 24px 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 0; }
        .footer a { color: #3b82f6; text-decoration: none; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .status-success { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        @media (max-width: 480px) {
            .wrapper { border-radius: 0; }
            .header { padding: 30px 20px; }
            .content { padding: 30px 20px; }
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
                        <h1>صكك | SAKK Wallet</h1>
                        <p>محفظتك الرقمية الآمنة</p>
                    </div>
                    
                    <!-- Content -->
                    <div class="content">
                        <h2>{{ $subject ?? 'إشعار' }}</h2>
                        <p>{{ $message ?? 'لديك إشعار جديد في حسابك.' }}</p>
                        
                        @if(isset($details))
                        <div class="details">
                            <table>
                                @foreach($details as $key => $value)
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td>{{ $value }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </div>
                        @endif
                        
                        @if(isset($action_url))
                        <a href="{{ $action_url }}" class="btn">{{ $action_text ?? 'عرض التفاصيل' }}</a>
                        @endif
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
