<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAKK — دفع آمن</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f3f1ee;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
        }
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo div {
            width: 56px; height: 56px;
            background: #1a3a2b;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
            color: #fff;
            font-weight: 700;
            font-size: 20px;
        }
        .logo p {
            font-size: 18px; font-weight: 600; color: #1a1a1a;
        }
        .amount {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .desc {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-bottom: 32px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #f5f5f5;
            border-radius: 20px;
            font-size: 12px;
            color: #666;
        }
        .info {
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        .info-row span:first-child { color: #888; }
        .info-row span:last-child { color: #333; font-weight: 500; }
        .mock-badge {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #c9a84c;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
        }
        .btn-primary {
            background: #1a6b3c;
            color: #fff;
            margin-top: 24px;
        }
        .btn-primary:hover { background: #155530; }
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
            margin-top: 8px;
        }
        .btn-secondary:hover { background: #e8e8e8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <div>SAKK</div>
            <p>بوابة الدفع الآمن</p>
            <span class="badge">بيئة تطوير</span>
        </div>

        <div class="amount">{{ number_format((float)$amount, 2) }} {{ $currency }}</div>
        <p class="desc">اشتراك سوريا هومز العقارية</p>

        <div class="info">
            <div class="info-row">
                <span>رقم العملية</span>
                <span>{{ $tid }}</span>
            </div>
            <div class="info-row">
                <span>التاجر</span>
                <span>سوريا هومز العقارية</span>
            </div>
            <div class="info-row">
                <span>الجهة</span>
                <span>SAKK</span>
            </div>
        </div>

        <a href="?transaction_id={{ urlencode($tid) }}&reference_id={{ urlencode($ref) }}&amount={{ urlencode($amount) }}&currency={{ urlencode($currency) }}&confirm=1"
           class="btn btn-primary">
            تأكيد الدفع
        </a>
        <a href="/" class="btn btn-secondary">
            إلغاء
        </a>

        <div class="mock-badge">
            ⚡ هذه محاكاة للاختبار — لا تتم أي عملية دفع حقيقية
        </div>
    </div>
</body>
</html>
