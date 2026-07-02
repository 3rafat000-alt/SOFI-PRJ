@extends('layouts.installer')

@section('title', 'الإعدادات')
@section('subtitle', 'تكوين التفضيلات الأساسية للنظام')

@php $currentStep = 4; @endphp

@section('content')
<div class="space-y-7">
    @if($errors->any())
        <div class="installer-error-box fade-in">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('installer.settings.store') }}" class="space-y-6" id="settingsForm" novalidate>
        @csrf

        <!-- App Name + App URL -->
        <div class="grid grid-cols-2 gap-5">
            <div class="field-group">
                <label class="installer-label">اسم التطبيق</label>
                <input type="text" name="app_name" id="appName" value="{{ old('app_name', 'صكّ | SAKK') }}" required
                       class="installer-input" maxlength="100">
                <p class="error-msg"></p>
            </div>
            <div class="field-group">
                <label class="installer-label">رابط التطبيق</label>
                <input type="url" name="app_url" id="appUrl" value="{{ old('app_url', url('/')) }}" required
                       class="installer-input" placeholder="https://example.com" maxlength="255">
                <p class="error-msg"></p>
            </div>
        </div>

        <!-- Currency + Exchange Rate -->
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="installer-label">العملة الافتراضية</label>
                <select name="default_currency" id="defaultCurrency" class="installer-input">
                    <option value="USD" selected>USD — الدولار الأمريكي</option>
                    <option value="SYP">SYP — الليرة السورية</option>
                </select>
            </div>
            <div class="field-group">
                <label class="installer-label">سعر الصرف USD/SYP</label>
                <input type="text" inputmode="decimal" name="exchange_rate_syp" id="exchangeRate" value="{{ old('exchange_rate_syp', '135') }}" required
                       class="installer-input" maxlength="20">
                <p class="error-msg"></p>
            </div>
        </div>

        <!-- Fees -->
        <div>
            <label class="installer-label">رسوم المعاملات (%)</label>
            <div class="grid grid-cols-3 gap-5">
                <div class="field-group">
                    <label style="font-size: .8125rem; color: var(--ink-2, #6E5F63); margin-bottom: .4rem; display: block;">إيداع</label>
                    <div class="relative">
                        <input type="text" inputmode="decimal" name="fee_deposit" id="feeDeposit" value="{{ old('fee_deposit', '1') }}" required
                               class="installer-input" maxlength="10">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium" style="color: var(--ink-2, #6E5F63);">%</span>
                    </div>
                    <p class="error-msg"></p>
                </div>
                <div class="field-group">
                    <label style="font-size: .8125rem; color: var(--ink-2, #6E5F63); margin-bottom: .4rem; display: block;">سحب</label>
                    <div class="relative">
                        <input type="text" inputmode="decimal" name="fee_withdrawal" id="feeWithdrawal" value="{{ old('fee_withdrawal', '1.5') }}" required
                               class="installer-input" maxlength="10">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium" style="color: var(--ink-2, #6E5F63);">%</span>
                    </div>
                    <p class="error-msg"></p>
                </div>
                <div class="field-group">
                    <label style="font-size: .8125rem; color: var(--ink-2, #6E5F63); margin-bottom: .4rem; display: block;">بطاقة</label>
                    <div class="relative">
                        <input type="text" inputmode="decimal" name="fee_card" id="feeCard" value="{{ old('fee_card', '0') }}" required
                               class="installer-input" maxlength="10">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium" style="color: var(--ink-2, #6E5F63);">%</span>
                    </div>
                    <p class="error-msg"></p>
                </div>
            </div>
        </div>

        <!-- ShamCash Platform Wallet -->
        <div class="field-group">
            <label class="installer-label">عنوان محفظة المنصة (ShamCash)</label>
            <input type="text" name="shamcash_wallet" id="shamcashWallet"
                   value="{{ old('shamcash_wallet', 'nZiAmtjNaut9KJ3GGLzHvdmfXEw8Fom9LrWr66Cjc7o') }}" required
                   class="installer-input font-mono" placeholder="أدخل عنوان محفظة ShamCash" maxlength="128" dir="ltr">
            <p class="error-msg"></p>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center pt-6" style="border-top: 1px solid #E8DED6;">
            <a href="{{ route('installer.admin') }}" class="installer-btn-ghost">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                رجوع
            </a>
            <button type="submit" id="settingsSubmitBtn" class="installer-btn-primary">
                حفظ وإنهاء التنصيب
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settingsForm');
    const submitBtn = document.getElementById('settingsSubmitBtn');

    function isNumeric(v) { return !isNaN(parseFloat(v)) && isFinite(v); }

    const validators = {
        appName: {
            el: document.getElementById('appName'),
            validate: v => {
                if (!v) return 'اسم التطبيق إجباري';
                if (v.length > 100) return 'الاسم طويل جداً (الحد الأقصى 100 محرف)';
                return null;
            }
        },
        appUrl: {
            el: document.getElementById('appUrl'),
            validate: v => {
                if (!v) return 'رابط التطبيق إجباري';
                if (v.length > 255) return 'الرابط طويل جداً';
                try {
                    const url = new URL(v);
                    if (!['http:', 'https:'].includes(url.protocol)) return 'الرابط يجب أن يبدأ بـ http:// أو https://';
                    return null;
                } catch {
                    return 'الرابط غير صالح — تأكد من إدخال رابط كامل (مثال: https://example.com)';
                }
            }
        },
        exchangeRate: {
            el: document.getElementById('exchangeRate'),
            validate: v => {
                if (!v) return 'سعر الصرف إجباري';
                if (!isNumeric(v)) return 'يجب أن يكون سعر الصرف رقماً صحيحاً أو عشرياً';
                const n = parseFloat(v);
                if (n < 1) return 'سعر الصرف يجب أن يكون 1 أو أكثر';
                if (n > 999999999) return 'القيمة كبيرة جداً';
                return null;
            }
        },
        feeDeposit: {
            el: document.getElementById('feeDeposit'),
            validate: v => {
                if (!v) return 'حقل رسوم الإيداع إجباري';
                if (!isNumeric(v)) return 'يجب أن تكون الرسوم رقماً';
                const n = parseFloat(v);
                if (n < 0) return 'الرسوم لا يمكن أن تكون أقل من 0%';
                if (n > 100) return 'الرسوم لا يمكن أن تتجاوز 100%';
                return null;
            }
        },
        feeWithdrawal: {
            el: document.getElementById('feeWithdrawal'),
            validate: v => {
                if (!v) return 'حقل رسوم السحب إجباري';
                if (!isNumeric(v)) return 'يجب أن تكون الرسوم رقماً';
                const n = parseFloat(v);
                if (n < 0) return 'الرسوم لا يمكن أن تكون أقل من 0%';
                if (n > 100) return 'الرسوم لا يمكن أن تتجاوز 100%';
                return null;
            }
        },
        feeCard: {
            el: document.getElementById('feeCard'),
            validate: v => {
                if (!v) return 'حقل رسوم البطاقة إجباري';
                if (!isNumeric(v)) return 'يجب أن تكون الرسوم رقماً';
                const n = parseFloat(v);
                if (n < 0) return 'الرسوم لا يمكن أن تكون أقل من 0%';
                if (n > 100) return 'الرسوم لا يمكن أن تتجاوز 100%';
                return null;
            }
        },
        shamcashWallet: {
            el: document.getElementById('shamcashWallet'),
            validate: v => {
                if (!v) return 'عنوان محفظة ShamCash إجباري';
                if (v.length < 20) return 'العنوان قصير جداً — الطبعات الصالحة تبدأ من 20 محرفاً';
                if (v.length > 128) return 'العنوان طويل جداً (الحد الأقصى 128 محرفاً)';
                if (!/^[A-Za-z0-9]+$/.test(v)) return 'العنوان يجب أن يحتوي على أحرف لاتينية وأرقام فقط';
                return null;
            }
        }
    };

    Object.values(validators).forEach(f => {
        if (!f.el) return;
        const validateField = () => {
            const error = f.validate(f.el.value);
            f.el.classList.remove('input-error', 'input-success');
            const group = f.el.closest('.field-group');
            const errEl = group ? group.querySelector('.error-msg') : null;
            if (error) {
                f.el.classList.add('input-error');
                if (errEl) { errEl.textContent = error; errEl.classList.remove('hidden'); }
            } else {
                if (f.el.value) f.el.classList.add('input-success');
                if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }
            }
        };
        f.el.addEventListener('blur', validateField);
        f.el.addEventListener('input', function() {
            if (this.classList.contains('input-error')) validateField();
        });
    });

    form.addEventListener('submit', function(e) {
        let valid = true;
        Object.values(validators).forEach(f => {
            if (!f.el) return;
            const error = f.validate(f.el.value);
            if (error) {
                valid = false;
                f.el.classList.add('input-error');
                const group = f.el.closest('.field-group');
                const errEl = group ? group.querySelector('.error-msg') : null;
                if (errEl) { errEl.textContent = error; errEl.classList.remove('hidden'); }
            } else {
                f.el.classList.remove('input-error');
            }
        });
        if (!valid) {
            e.preventDefault();
            submitBtn.classList.add('shake');
            setTimeout(() => submitBtn.classList.remove('shake'), 300);
        }
    });
});
</script>
@endsection
