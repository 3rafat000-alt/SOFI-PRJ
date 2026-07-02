<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صكك | الدعم الفني</title>
    <style>
        body { font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f1f5f9; }
        .wrapper { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%); padding: 36px 30px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 800; margin: 0; }
        .header p { color: rgba(255,255,255,0.85); font-size: 13px; margin: 6px 0 0; }
        .content { padding: 36px 30px; text-align: right; }
        .content h2 { color: #1e293b; font-size: 19px; font-weight: 700; margin: 0 0 6px; }
        .ticket-no { display: inline-block; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 13px; padding: 5px 12px; border-radius: 8px; direction: ltr; margin-bottom: 18px; }
        .content p { color: #475569; font-size: 15px; line-height: 1.7; margin: 0 0 14px; }
        .btn { display: inline-block; background: #3b82f6; color: #ffffff !important; text-decoration: none; font-weight: 700; font-size: 15px; padding: 12px 28px; border-radius: 10px; margin-top: 10px; }
        .footer { background: #f8fafc; padding: 22px 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 12px; margin: 0; }
        .footer a { color: #3b82f6; text-decoration: none; }
        @media (max-width: 480px) {
            .wrapper { border-radius: 0; }
            .header, .content { padding: 26px 20px; }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <div class="wrapper">
                    <div class="header">
                        <h1>صكك | الدعم الفني</h1>
                        <p>SAKK Support</p>
                    </div>

                    <div class="content">
                        <h2>{{ $heading }}</h2>
                        <span class="ticket-no">{{ $ticketNumber }}</span>

                        @foreach ($lines as $line)
                            <p>{{ $line }}</p>
                        @endforeach

                        @if ($actionUrl)
                            <p style="text-align:center; margin-top: 8px;">
                                <a href="{{ $actionUrl }}" class="btn">{{ $actionLabel ?? 'عرض التذكرة' }}</a>
                            </p>
                        @endif
                    </div>

                    <div class="footer">
                        <p>© {{ date('Y') }} صكك | SAKK Wallet. جميع الحقوق محفوظة.</p>
                        <p style="margin-top: 8px;">الدعم الفني: <a href="mailto:{{ config('mail.support_address') }}">{{ config('mail.support_address') }}</a></p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
