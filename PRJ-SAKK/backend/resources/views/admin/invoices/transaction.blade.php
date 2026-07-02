<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة {{ $transaction->reference }} — صكك</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @php
        // مولّد نقاط مضلّع لرسم ختم البحرة الدمشقية (SVG line-art).
        $poly = function ($cx, $cy, $r, $sides, $rotDeg) {
            $pts = [];
            for ($i = 0; $i < $sides; $i++) {
                $a = deg2rad(360 / $sides * $i + $rotDeg);
                $pts[] = round($cx + $r * cos($a), 1) . ',' . round($cy + $r * sin($a), 1);
            }
            return implode(' ', $pts);
        };

        $statusValue = is_object($transaction->status) ? $transaction->status->value : $transaction->status;
        $typeValue = is_object($transaction->type) ? $transaction->type->value : $transaction->type;

        $statusMap = [
            'completed' => ['مكتملة', '#1F9D55', '#E4F6EC'],
            'pending'   => ['قيد المعالجة', 'var(--accent-dark)', '#F7EEDA'],
            'processing'=> ['قيد المعالجة', 'var(--accent-dark)', '#F7EEDA'],
            'failed'    => ['فاشلة', '#C0392B', '#FBEAE8'],
            'cancelled' => ['ملغاة', '#C0392B', '#FBEAE8'],
            'reversed'  => ['معكوسة', 'var(--text-secondary)', '#EFE9E2'],
            'refunded'  => ['مُستردة', 'var(--text-secondary)', '#EFE9E2'],
        ];
        $st = $statusMap[$statusValue] ?? [$statusValue, 'var(--text-secondary)', '#EFE9E2'];

        $typeMap = [
            'deposit' => 'إيداع', 'withdrawal' => 'سحب', 'transfer_out' => 'تحويل صادر',
            'transfer_in' => 'تحويل وارد', 'card_load' => 'شحن بطاقة', 'card_payment' => 'دفع ببطاقة',
            'fee' => 'رسوم', 'reward' => 'مكافأة', 'exchange' => 'صرف عملة',
        ];
        $typeLabel = $typeMap[$typeValue] ?? $typeValue;
    @endphp
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Cairo',sans-serif;background:var(--bg);color:var(--text-primary);
            letter-spacing:-0.01em;padding:28px 16px;-webkit-print-color-adjust:exact;print-color-adjust:exact}

        .toolbar{max-width:780px;margin:0 auto 18px;display:flex;gap:10px;justify-content:flex-end}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border-radius:12px;
            font-family:inherit;font-weight:700;font-size:14px;cursor:pointer;border:none;text-decoration:none;transition:.15s}
        .btn-gold{background:var(--accent);color:#fff;box-shadow:0 6px 16px -6px rgba(110,27,45,.6)}
        .btn-ghost{background:#fff;color:var(--text-primary);border:1.5px solid rgba(0,0,0,0.08)}

        /* ===== A4 paper ===== */
        .paper{position:relative;max-width:780px;margin:0 auto;background:#fff;border-radius:22px;overflow:hidden;
            box-shadow:0 24px 60px -24px rgba(74,19,32,.28);border:1px solid rgba(0,0,0,0.08)}

        .watermark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:0}
        .watermark svg{width:560px;height:560px;transform:translate(18%,-8%)}

        .content{position:relative;z-index:1}

        /* ===== Header band (wine velvet) ===== */
        .head{background:linear-gradient(135deg,#7A2236 0%,var(--sukk-primary-dark) 100%);color:#fff;padding:32px 40px;
            display:flex;justify-content:space-between;align-items:flex-start;position:relative;overflow:hidden}
        .head .seal{position:absolute;left:-40px;top:-40px;opacity:.18}
        .brand{display:flex;align-items:center;gap:14px;position:relative}
        .brand img{width:54px;height:54px;border-radius:14px;background:rgba(255,255,255,.08);padding:4px}
        .brand h1{font-size:22px;font-weight:900;line-height:1.1}
        .brand p{font-size:12px;color:var(--accent);font-weight:700;letter-spacing:.12em}
        .doc{text-align:left;position:relative}
        .doc .tag{display:inline-block;background:var(--accent-ring);color:var(--accent);
            padding:4px 12px;border-radius:999px;font-size:11px;font-weight:800;margin-bottom:8px}
        .doc h2{font-size:15px;font-weight:800;color:#fff}
        .doc .ref{font-size:13px;color:rgba(255,255,255,.7);direction:ltr;margin-top:2px}

        /* gold rule */
        .gold-rule{height:3px;background:var(--accent)}

        .body{padding:30px 40px 8px}
        .meta{display:flex;justify-content:space-between;gap:16px;margin-bottom:26px}
        .meta .blk .lbl{font-size:11px;color:var(--text-secondary);font-weight:700;margin-bottom:4px}
        .meta .blk .val{font-size:14px;font-weight:700;color:var(--text-primary)}
        .meta .blk .val.ltr{direction:ltr;text-align:right}
        .status{padding:7px 16px;border-radius:999px;font-size:12.5px;font-weight:800;align-self:flex-start}

        table{width:100%;border-collapse:collapse;margin-bottom:8px}
        thead th{text-align:right;font-size:11px;font-weight:800;color:var(--text-secondary);text-transform:uppercase;
            letter-spacing:.04em;padding:10px 14px;background:var(--bg);border-bottom:2px solid var(--accent)}
        thead th:last-child{text-align:left}
        tbody td{padding:14px;font-size:14px;border-bottom:1px solid rgba(0,0,0,0.08);color:var(--text-primary);font-weight:600}
        tbody td:last-child{text-align:left;direction:ltr}
        tbody .desc{color:var(--text-secondary);font-weight:500;font-size:12.5px}

        .totals{display:flex;justify-content:flex-end;padding:8px 0 26px}
        .totals .box{width:300px}
        .totals .row{display:flex;justify-content:space-between;padding:8px 4px;font-size:13.5px;color:var(--text-secondary)}
        .totals .row span:last-child{color:var(--text-primary);font-weight:700;direction:ltr}
        .grand{margin-top:10px;background:linear-gradient(135deg,#7A2236,var(--sukk-primary-dark));color:#fff;
            border-radius:16px;padding:18px 22px;display:flex;justify-content:space-between;align-items:center}
        .grand .l{font-weight:700;color:var(--accent);font-size:13px}
        .grand .v{font-size:26px;font-weight:900;direction:ltr}

        .foot{padding:22px 40px 34px;text-align:center;border-top:1px solid rgba(0,0,0,0.08);position:relative}
        .foot .stars{color:var(--accent);letter-spacing:6px;font-size:12px;margin-bottom:10px}
        .foot p{color:var(--text-secondary);font-size:12.5px}
        .foot .small{font-size:11px;margin-top:4px;color:var(--text-muted)}

        @media print{
            @page{size:A4;margin:0}
            body{background:#fff;padding:0}
            .toolbar{display:none}
            .paper{box-shadow:none;border:none;border-radius:0;max-width:100%}
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-gold" onclick="window.print()">
            ⬇ تحميل PDF / طباعة
        </button>
        <a class="btn btn-ghost" href="{{ route('admin.transactions.show', $transaction->id) }}">عودة</a>
    </div>

    <div class="paper">
        <!-- faint Damascene medallion watermark -->
        <div class="watermark" aria-hidden="true">
            <svg viewBox="0 0 200 200" fill="none" stroke="#6E1B2D" stroke-width="1.1" opacity="0.05">
                <polygon points="{{ $poly(100,100,92,8,22.5) }}"/>
                <polygon points="{{ $poly(100,100,88,4,0) }}"/>
                <polygon points="{{ $poly(100,100,88,4,45) }}"/>
                <circle cx="100" cy="100" r="60"/>
                <polygon points="{{ $poly(100,100,46,8,0) }}"/>
                <polygon points="{{ $poly(100,100,42,4,22.5) }}"/>
                <polygon points="{{ $poly(100,100,42,4,67.5) }}"/>
                <polygon points="{{ $poly(100,100,17,8,22.5) }}"/>
            </svg>
        </div>

        <div class="content">
            <!-- Header -->
            <div class="head">
                <svg class="seal" width="180" height="180" viewBox="0 0 200 200" fill="none" stroke="#6E1B2D" stroke-width="1.2">
                    <polygon points="{{ $poly(100,100,92,8,22.5) }}"/>
                    <polygon points="{{ $poly(100,100,88,4,0) }}"/>
                    <polygon points="{{ $poly(100,100,88,4,45) }}"/>
                    <polygon points="{{ $poly(100,100,46,8,0) }}"/>
                </svg>
                <div class="brand">
                    <img src="/images/logo.svg" alt="صكك">
                    <div>
                        <h1>صكك</h1>
                        <p>SAKK WALLET</p>
                    </div>
                </div>
                <div class="doc">
                    <span class="tag">فاتورة رقمية</span>
                    <h2>INVOICE</h2>
                    <p class="ref">{{ $transaction->reference }}</p>
                </div>
            </div>
            <div class="gold-rule"></div>

            <div class="body">
                <!-- Meta -->
                <div class="meta">
                    <div class="blk">
                        <p class="lbl">صادرة إلى</p>
                        <p class="val">
                            @if($transaction->user)
                                {{ $transaction->user->first_name }} {{ $transaction->user->last_name }}
                            @else
                                عميل صكك
                            @endif
                        </p>
                        @if($transaction->user && $transaction->user->email)
                            <p class="val ltr" style="color:var(--text-secondary);font-weight:500;font-size:12.5px">{{ $transaction->user->email }}</p>
                        @endif
                    </div>
                    <div class="blk" style="text-align:left">
                        <p class="lbl">تاريخ الإصدار</p>
                        <p class="val ltr">{{ $transaction->created_at->format('Y/m/d') }}</p>
                        <p class="val ltr" style="color:var(--text-secondary);font-weight:500;font-size:12.5px">{{ $transaction->created_at->format('H:i') }}</p>
                    </div>
                    <span class="status" style="background:{{ $st[2] }};color:{{ $st[1] }}">{{ $st[0] }}</span>
                </div>

                <!-- Items -->
                <table>
                    <thead>
                        <tr>
                            <th>الوصف</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                {{ $transaction->title ?? 'عملية مالية' }}
                                @if($transaction->description)
                                    <div class="desc">{{ $transaction->description }}</div>
                                @endif
                            </td>
                            <td>{{ $typeLabel }}</td>
                            <td>{!! \App\Support\Money::format(abs($transaction->amount), $transaction->currency) !!}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="totals">
                    <div class="box">
                        <div class="row"><span>المبلغ الأساسي</span><span>{!! \App\Support\Money::format(abs($transaction->amount), $transaction->currency) !!}</span></div>
                        <div class="row"><span>الرسوم</span><span>{!! \App\Support\Money::format($transaction->fee ?? 0, $transaction->currency) !!}</span></div>
                        @if($transaction->wallet)
                        <div class="row"><span>المحفظة</span><span>{{ $transaction->wallet->currency }}</span></div>
                        @endif
                        <div class="grand">
                            <span class="l">الإجمالي الصافي</span>
                            <span class="v">{!! \App\Support\Money::format(abs($transaction->net_amount ?? $transaction->amount), $transaction->currency) !!}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="foot">
                <div class="stars">✦ ✦ ✦</div>
                <p>شكراً لاستخدامك صكك — محفظتك المالية الرقمية</p>
                <p class="small">© {{ date('Y') }} صكك | SAKK Wallet — جميع الحقوق محفوظة · هذه فاتورة صادرة إلكترونياً ولا تتطلب توقيعاً</p>
            </div>
        </div>
    </div>
</body>
</html>
