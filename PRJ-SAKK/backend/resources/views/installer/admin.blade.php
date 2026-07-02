@extends('layouts.installer')

@section('title', 'حساب المشرف')
@section('subtitle', 'إنشاء حساب المدير الرئيسي للنظام')

@php $currentStep = 3; @endphp

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

    <form method="POST" action="{{ route('installer.admin.store') }}" class="space-y-6" id="adminForm" novalidate>
        @csrf

        <div class="grid grid-cols-2 gap-5">
            <div class="field-group">
                <label class="installer-label">الاسم الأول</label>
                <input type="text" name="first_name" id="firstName" value="{{ old('first_name') }}" required
                       class="installer-input" placeholder="مثال: محمد" maxlength="50" autocomplete="given-name">
                <p class="error-msg"></p>
            </div>
            <div class="field-group">
                <label class="installer-label">الاسم الأخير</label>
                <input type="text" name="last_name" id="lastName" value="{{ old('last_name') }}" required
                       class="installer-input" placeholder="مثال: أحمد" maxlength="50" autocomplete="family-name">
                <p class="error-msg"></p>
            </div>
        </div>

        <div class="field-group">
            <label class="installer-label">البريد الإلكتروني</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                   class="installer-input" placeholder="admin@example.com" maxlength="255" autocomplete="email">
            <p class="error-msg"></p>
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div class="field-group">
                <label class="installer-label">كلمة المرور</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           class="installer-input" placeholder="8 أحرف على الأقل" maxlength="128" autocomplete="new-password">
                    <button type="button" onclick="togglePassword('password', this)" class="absolute left-4 top-1/2 -translate-y-1/2 p-1.5" style="color: var(--ink-2, #6E5F63);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p class="error-msg"></p>
            </div>
            <div class="field-group">
                <label class="installer-label">تأكيد كلمة المرور</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="passwordConfirm" required
                           class="installer-input" placeholder="أعد إدخال كلمة المرور" maxlength="128" autocomplete="new-password">
                    <button type="button" onclick="togglePassword('passwordConfirm', this)" class="absolute left-4 top-1/2 -translate-y-1/2 p-1.5" style="color: var(--ink-2, #6E5F63);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p class="error-msg"></p>
            </div>
        </div>

        <!-- Password Strength Meter -->
        <div id="strengthMeter" class="hidden space-y-2">
            <div class="flex items-center gap-3">
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div id="strengthBar" class="h-full rounded-full transition-all duration-300" style="width:0%"></div>
                </div>
                <span id="strengthLabel" class="text-sm font-medium min-w-[60px] text-center"></span>
            </div>
            <div class="flex gap-3 text-sm" id="requirementsList">
                <div class="req-item" data-req="length">8+ أحرف</div>
                <div class="req-item" data-req="upper">حرف كبير A-Z</div>
                <div class="req-item" data-req="lower">حرف صغير a-z</div>
                <div class="req-item" data-req="number">رقم 0-9</div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center pt-6" style="border-top: 1px solid #E8DED6;">
            <a href="{{ route('installer.database') }}" class="installer-btn-ghost">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                رجوع
            </a>
            <button type="submit" id="adminSubmitBtn" class="installer-btn-primary">
                إنشاء الحساب
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<style>
.req-item{background:#f3f4f6;color:#9ca3af;padding:4px 10px;border-radius:6px;text-align:center;transition:all .3s;font-size:0.875rem}
.req-item.met{background:#dcfce7;color:#16a34a}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('adminForm');
    const submitBtn = document.getElementById('adminSubmitBtn');

    const fields = {
        firstName: {
            el: document.getElementById('firstName'),
            validate: v => {
                if (!v) return 'حقل الاسم الأول إجباري';
                if (v.length < 2) return 'الاسم الأول يحتوي على حرف واحد فقط (يلزم حرفان على الأقل)';
                if (v.length > 50) return 'الاسم الأول طويل جداً (الحد الأقصى 50 محرفاً)';
                if (/[0-9]/.test(v)) return 'لا يُسمح بإدراج أرقام في حقل الاسم';
                if (/[!@#$%^&*(),.?":{}|<>]/.test(v)) return 'لا يُسمح برموز خاصة في حقل الاسم';
                return null;
            }
        },
        lastName: {
            el: document.getElementById('lastName'),
            validate: v => {
                if (!v) return 'حقل الاسم الأخير إجباري';
                if (v.length < 2) return 'الاسم الأخير يحتوي على حرف واحد فقط (يلزم حرفان على الأقل)';
                if (v.length > 50) return 'الاسم الأخير طويل جداً (الحد الأقصى 50 محرفاً)';
                if (/[0-9]/.test(v)) return 'لا يُسمح بإدراج أرقام في حقل الاسم';
                if (/[!@#$%^&*(),.?":{}|<>]/.test(v)) return 'لا يُسمح برموز خاصة في حقل الاسم';
                return null;
            }
        },
        email: {
            el: document.getElementById('email'),
            validate: v => {
                if (!v) return 'حقل البريد الإلكتروني إجباري';
                if (v.length > 255) return 'البريد الإلكتروني طويل جداً';
                if (v.includes('..')) return 'صيغة البريد الإلكتروني غير صحيحة';
                if (v.startsWith('.') || v.endsWith('.')) return 'البريد الإلكتروني لا يمكن أن يبدأ أو ينتهي بنقطة';
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'صيغة البريد الإلكتروني غير صحيحة (مثال: user@domain.com)';
                return null;
            }
        },
        password: {
            el: document.getElementById('password'),
            validate: v => {
                if (!v) return 'حقل كلمة المرور إجباري';
                if (v.length < 8) return 'كلمة المرور قصيرة جداً — يجب أن تحتوي على 8 أحرف على الأقل';
                if (v.length > 128) return 'كلمة المرور طويلة جداً (الحد الأقصى 128 محرفاً)';
                if (!/[A-Z]/.test(v)) return 'يجب أن تحتوي كلمة المرور على حرف كبير واحد على الأقل (A–Z)';
                if (!/[a-z]/.test(v)) return 'يجب أن تحتوي كلمة المرور على حرف صغير واحد على الأقل (a–z)';
                if (!/[0-9]/.test(v)) return 'يجب أن تحتوي كلمة المرور على رقم واحد على الأقل (0–9)';
                return null;
            }
        },
        passwordConfirm: {
            el: document.getElementById('passwordConfirm'),
            validate: v => {
                if (!v) return 'حقل تأكيد كلمة المرور إجباري';
                const pwd = document.getElementById('password').value;
                if (v !== pwd) return 'كلمة المرور غير متطابقة — تأكد من إدخال نفس كلمة المرور في الحقلين';
                return null;
            }
        }
    };

    Object.values(fields).forEach(f => {
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

    // Password strength
    const pwdEl = document.getElementById('password');
    const meter = document.getElementById('strengthMeter');
    const bar = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');

    function checkStrength() {
        const v = pwdEl.value;
        if (!v) { meter.classList.add('hidden'); return; }
        meter.classList.remove('hidden');

        const checks = {
            length: v.length >= 8,
            upper: /[A-Z]/.test(v),
            lower: /[a-z]/.test(v),
            number: /[0-9]/.test(v)
        };

        document.querySelectorAll('.req-item').forEach(el => {
            el.classList.toggle('met', checks[el.dataset.req]);
        });

        const score = Object.values(checks).filter(Boolean).length;
        bar.style.width = (score / 4 * 100) + '%';

        if (score <= 1) { bar.style.background = '#ef4444'; label.textContent = 'ضعيفة'; label.style.color = '#ef4444'; }
        else if (score === 2) { bar.style.background = '#f59e0b'; label.textContent = 'متوسطة'; label.style.color = '#f59e0b'; }
        else if (score === 3) { bar.style.background = '#3b82f6'; label.textContent = 'جيدة'; label.style.color = '#3b82f6'; }
        else { bar.style.background = '#22c55e'; label.textContent = 'قوية'; label.style.color = '#22c55e'; }
    }

    pwdEl.addEventListener('input', checkStrength);

    document.getElementById('passwordConfirm').addEventListener('input', function() {
        const pwd = document.getElementById('password').value;
        const group = this.closest('.field-group');
        const errEl = group ? group.querySelector('.error-msg') : null;
        this.classList.remove('input-error', 'input-success');
        if (!this.value) return;
        if (this.value !== pwd) {
            this.classList.add('input-error');
            if (errEl) { errEl.textContent = 'كلمة المرور غير متطابقة'; errEl.classList.remove('hidden'); }
        } else {
            this.classList.add('input-success');
            if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }
        }
    });

    form.addEventListener('submit', function(e) {
        let valid = true;
        Object.values(fields).forEach(f => {
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

function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
    }
}
</script>
@endsection
