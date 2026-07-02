<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#7a1020">
    <meta name="robots" content="noindex, nofollow">
    <title>طلب دفعة — صكّ</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/sakk-assets/sakk-tokens.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:var(--font);color:var(--ink);background:var(--marble);
            min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.25rem}
        .card{width:100%;max-width:430px;background:#fff;border-radius:var(--r-xl,24px);
            box-shadow:var(--sh-lg,0 24px 60px rgba(0,0,0,.12));overflow:hidden}
        .hero{background:linear-gradient(160deg,var(--wine,#7a1020) 0%,var(--wine-dark,#5c0c18) 100%);
            color:#fff;padding:2.2rem 1.6rem 1.9rem;text-align:center;position:relative;overflow:hidden}
        .hero::after{content:"";position:absolute;inset:0;
            background:radial-gradient(70% 120% at 100% 0,rgba(201,162,75,.28),transparent 60%);pointer-events:none}
        .avatar{width:62px;height:62px;border-radius:50%;background:rgba(255,255,255,.16);
            display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;
            margin:0 auto .85rem;letter-spacing:1px;direction:ltr;border:2px solid rgba(246,217,138,.5)}
        .hero .who{font-size:.92rem;opacity:.92}
        .hero .who b{color:#f6d98a}
        .hero .req-label{font-size:.78rem;opacity:.8;margin-top:1.1rem}
        .amount{font-size:2.6rem;font-weight:900;line-height:1.25;margin-top:.2rem;direction:ltr}
        .note{display:inline-block;margin-top:.8rem;background:rgba(255,255,255,.14);
            padding:.45rem 1rem;border-radius:99px;font-size:.84rem;max-width:100%;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .body{padding:1.6rem 1.5rem 1.7rem}
        .status{display:flex;gap:.6rem;align-items:center;justify-content:center;text-align:center;
            padding:.85rem 1rem;border-radius:14px;font-size:.9rem;font-weight:700;margin-bottom:1.1rem}
        .status .ico{font-size:1.15rem}
        .status.paid{background:#ecfdf5;border:1px solid #a7f3d0;color:#047857}
        .status.expired{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412}
        .status.dead{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
        .status.invalid{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412}
        .btn{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;
            padding:.95rem;border-radius:14px;font-family:var(--font);font-weight:700;font-size:1rem;
            border:none;cursor:pointer;text-decoration:none;margin-top:.85rem}
        .btn-primary{background:var(--wine,#7a1020);color:#fff;box-shadow:var(--sh-wine,0 10px 24px rgba(122,16,32,.3))}
        .btn-primary:active{transform:translateY(1px)}
        .btn-ghost{background:transparent;color:var(--wine,#7a1020);border:1.5px solid rgba(122,16,32,.25)}
        .hint{text-align:center;font-size:.76rem;color:var(--ink-2,#8b8b8b);margin-top:.9rem;line-height:1.7}
        .steps{margin-top:1.7rem}
        .steps h2{font-size:.92rem;font-weight:800;margin-bottom:.85rem}
        .step{display:flex;gap:.75rem;align-items:flex-start;margin-bottom:.8rem;font-size:.85rem;line-height:1.6}
        .num{flex:0 0 24px;width:24px;height:24px;border-radius:50%;background:var(--wine,#7a1020);color:#fff;
            font-size:.74rem;font-weight:800;display:flex;align-items:center;justify-content:center;direction:ltr}
        .foot{text-align:center;font-size:.72rem;color:var(--ink-2,#9b9b9b);margin-top:1.4rem}
    </style>
</head>
<body>
    <div class="card">
        <div class="hero">
            @if($found && $requesterName)
                <div class="avatar">{{ $initials ?: 'ص' }}</div>
                <div class="who">يطلب منك <b>{{ $requesterName }}</b></div>
                <div class="req-label">المبلغ المطلوب</div>
                <div class="amount">{{ $amount }}</div>
                @if($note)
                    <div class="note">📝 {{ $note }}</div>
                @endif
            @else
                <div class="avatar">ص</div>
                <div class="who">طلب دفعة عبر <b>صكّ</b></div>
            @endif
        </div>

        <div class="body">
            @if(!$found)
                <div class="status invalid"><span class="ico">⚠️</span><span>هذا الرابط غير صالح أو لم يعد موجوداً.</span></div>
            @elseif($status === 'paid')
                <div class="status paid"><span class="ico">✓</span><span>تم دفع هذا الطلب — شكراً لك.</span></div>
            @elseif($status === 'expired')
                <div class="status expired"><span class="ico">⏳</span><span>انتهت صلاحية هذا الرابط.</span></div>
            @elseif(in_array($status, ['cancelled','rejected']))
                <div class="status dead"><span class="ico">✕</span><span>أُلغي هذا الطلب من قِبل صاحبه.</span></div>
            @endif

            @if($found && $status === 'pending')
                <a class="btn btn-primary" href="{{ $appLink }}" id="openApp">💳 افتح تطبيق صكّ وادفع</a>
                <a class="btn btn-ghost" href="{{ $apkUrl }}" download="sakk.apk" rel="nofollow">⬇ ليس لديك التطبيق؟ حمّله</a>
                <div class="hint">إن كان التطبيق مثبّتاً سيُفتح تلقائياً على شاشة الدفع. خلاف ذلك، حمّله ثم افتح هذا الرابط مجدداً لإتمام الدفع.</div>

                <div class="steps">
                    <h2>كيف تدفع؟</h2>
                    <div class="step"><span class="num">1</span><div>افتح الرابط بهاتفك — يفتح تطبيق صكّ على شاشة الدفع مباشرةً.</div></div>
                    <div class="step"><span class="num">2</span><div>راجع اسم الطالب والمبلغ، ثم أكّد الدفع.</div></div>
                    <div class="step"><span class="num">3</span><div>أدخل رمز PIN أو البصمة لإتمام التحويل بأمان.</div></div>
                </div>
            @else
                <a class="btn btn-ghost" href="{{ $apkUrl }}" download="sakk.apk" rel="nofollow">⬇ حمّل تطبيق صكّ</a>
            @endif

            <div class="foot">صكّ — محفظتك المالية الموثوقة</div>
        </div>
    </div>

    @if($found && $status === 'pending')
    <script>
        // Auto-attempt to hand off to the installed app (custom scheme) once.
        (function () {
            try {
                if (sessionStorage.getItem('sakk_pay_tried')) return;
                sessionStorage.setItem('sakk_pay_tried', '1');
                setTimeout(function () { window.location.href = @json($appLink); }, 350);
            } catch (e) {}
        })();

        // Poll payment status every 5 seconds; redirect to success_url/cancel_url when done.
        (function poll() {
            var successUrl = @json($successUrl ?? '');
            var cancelUrl = @json($cancelUrl ?? '');
            var uuid = @json($uuid ?? '');

            if (!uuid) return;

            fetch('/api/v1/payment-requests/' + uuid)
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    var st = res.data && res.data.status;
                    if (st === 'paid' && successUrl) {
                        window.location.href = successUrl;
                    } else if ((st === 'cancelled' || st === 'rejected') && cancelUrl) {
                        window.location.href = cancelUrl;
                    } else if (st === 'pending') {
                        setTimeout(poll, 5000);
                    }
                })
                .catch(function () { setTimeout(poll, 5000); });
        })();
    </script>
    @endif
</body>
</html>
