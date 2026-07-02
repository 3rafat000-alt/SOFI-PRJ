@extends('layouts.installer')

@section('title', 'إعداد قاعدة البيانات')
@section('subtitle', 'تكوين اتصال قاعدة البيانات الخاصة بالنظام')

@php $currentStep = 2; @endphp

@section('content')
<div class="space-y-7">
    @if($errors->any())
        <div class="installer-error-box fade-in">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('installer.database.store') }}" class="space-y-6" id="dbForm" novalidate>
        @csrf

        <div>
            <label class="installer-label">نظام إدارة قواعد البيانات</label>
            <select name="db_driver" id="dbDriver" class="installer-input">
                <option value="sqlite" {{ old('db_driver', 'sqlite') === 'sqlite' ? 'selected' : '' }}>SQLite — موصى به ومدمج (لا يحتاج خادم)</option>
                <option value="mysql" {{ old('db_driver') === 'mysql' ? 'selected' : '' }}>MySQL / MariaDB</option>
                <option value="pgsql" {{ old('db_driver') === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
            </select>
        </div>

        <!-- MySQL/PostgreSQL -->
        <div id="dbFields" class="space-y-5" style="display: none;">
            <div class="grid grid-cols-2 gap-5">
                <div class="field-group">
                    <label class="installer-label">المُضيف</label>
                    <input type="text" name="db_host" id="dbHost" value="{{ old('db_host', '127.0.0.1') }}"
                           class="installer-input" placeholder="127.0.0.1" maxlength="255" autocomplete="off">
                    <p class="error-msg"></p>
                </div>
                <div class="field-group">
                    <label class="installer-label">المِنفذ</label>
                    <input type="text" name="db_port" id="dbPort" value="{{ old('db_port', '3306') }}"
                           class="installer-input" placeholder="3306" maxlength="5" autocomplete="off">
                    <p class="error-msg"></p>
                </div>
            </div>
            <div class="field-group">
                <label class="installer-label">اسم قاعدة البيانات</label>
                <input type="text" name="db_name" id="dbName" value="{{ old('db_name', 'sakk_wallet') }}"
                       class="installer-input" placeholder="sakk_wallet" maxlength="64" autocomplete="off">
                <p class="error-msg"></p>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <div class="field-group">
                    <label class="installer-label">اسم المستخدم</label>
                    <input type="text" name="db_user" id="dbUser" value="{{ old('db_user', 'root') }}"
                           class="installer-input" placeholder="root" maxlength="64" autocomplete="off">
                    <p class="error-msg"></p>
                </div>
                <div class="field-group">
                    <label class="installer-label">كلمة المرور</label>
                    <div class="relative">
                        <input type="password" name="db_password" id="dbPassword"
                               class="installer-input" placeholder="اختياري" maxlength="128" autocomplete="off">
                        <button type="button" onclick="togglePassword('dbPassword', this)" class="absolute left-4 top-1/2 -translate-y-1/2 p-1.5" style="color: var(--ink-2, #6E5F63);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="error-msg"></p>
                </div>
            </div>
        </div>

        <!-- SQLite info -->
        <div class="installer-info-box">
            <div class="flex items-start gap-4">
                <svg class="w-7 h-7 mt-0.5 flex-shrink-0" fill="none" stroke="#B58A3C" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p style="font-weight: 700; color: var(--wine, #6E1B2D);">معلومات SQLite</p>
                    <p class="text-sm mt-1.5" style="color: #7a6a4a;">سيتم إنشاء ملف قاعدة البيانات تلقائياً في <code class="px-2.5 py-1 rounded text-xs font-mono" style="background:#F1E7CE; color: var(--wine, #6E1B2D);">database/database.sqlite</code>. لا حاجة لأي معلومات اتصال.</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center pt-6" style="border-top: 1px solid #E8DED6;">
            <a href="{{ route('installer.requirements') }}" class="installer-btn-ghost">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                رجوع
            </a>
            <button type="submit" id="submitBtn" class="installer-btn-primary">
                اختبار الاتصال
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const driver = document.getElementById('dbDriver');
    const fields = document.getElementById('dbFields');
    const sqliteInfo = document.getElementById('sqliteInfo');
    const form = document.getElementById('dbForm');
    const submitBtn = document.getElementById('submitBtn');

    function toggleDriver() {
        if (driver.value === 'sqlite') {
            fields.style.display = 'none'; sqliteInfo.style.display = 'block'; clearRequired();
        } else {
            fields.style.display = 'block'; sqliteInfo.style.display = 'none'; setRequired();
        }
        clearErrors();
    }
    function setRequired() {
        ['dbHost','dbPort','dbName','dbUser'].forEach(id => { const e = document.getElementById(id); if(e){e.required=true;e.dataset.required='true';} });
    }
    function clearRequired() {
        ['dbHost','dbPort','dbName','dbUser'].forEach(id => { const e = document.getElementById(id); if(e){e.required=false;delete e.dataset.required;} });
    }
    function clearErrors() {
        document.querySelectorAll('.error-msg').forEach(e => { e.classList.add('hidden'); e.textContent = ''; });
        document.querySelectorAll('.input-error, .input-success').forEach(e => e.classList.remove('input-error','input-success'));
    }
    driver.addEventListener('change', toggleDriver);
    toggleDriver();

    const validators = {
        dbHost: { el: document.getElementById('dbHost'), validate: v => { if(!v) return 'حقل المُضيف إجباري'; if(v.length>255) return 'المُضيف طويل جداً (255 محرفاً كحد أقصى)'; if(/\s/.test(v)) return 'لا يُسمح بفراغات في اسم المُضيف'; return null; } },
        dbPort: { el: document.getElementById('dbPort'), validate: v => { if(!v) return 'حقل المِنفذ إجباري'; if(!/^\d+$/.test(v)) return 'المِنفذ يجب أن يكون عدداً صحيحاً'; const p=parseInt(v,10); if(p<1||p>65535) return 'المِنفذ خارج النطاق المسموح (1–65535)'; return null; } },
        dbName: { el: document.getElementById('dbName'), validate: v => { if(!v) return 'اسم قاعدة البيانات إجباري'; if(v.length>64) return 'الاسم طويل جداً (64 محرفاً كحد أقصى)'; if(/[^a-zA-Z0-9_]/.test(v)) return 'يُسمح فقط بالأحرف اللاتينية والأرقام والشرطة السفلية'; return null; } },
        dbUser: { el: document.getElementById('dbUser'), validate: v => { if(!v) return 'اسم المستخدم إجباري'; if(v.length>64) return 'الاسم طويل جداً (64 محرفاً كحد أقصى)'; return null; } }
    };

    Object.keys(validators).forEach(k => {
        const { el, validate } = validators[k];
        if (!el) return;
        const fn = () => {
            if (!el.required && !el.value) { el.classList.remove('input-error','input-success'); const g=el.closest('.field-group'); const e=g?g.querySelector('.error-msg'):null; if(e){e.classList.add('hidden');e.textContent='';} return; }
            const err = validate(el.value); el.classList.remove('input-error','input-success');
            const g = el.closest('.field-group'); const errEl = g ? g.querySelector('.error-msg') : null;
            if (err) { el.classList.add('input-error'); if(errEl){errEl.textContent=err;errEl.classList.remove('hidden');} }
            else { if(el.value) el.classList.add('input-success'); if(errEl){errEl.classList.add('hidden');errEl.textContent='';} }
        };
        el.addEventListener('blur', fn);
        el.addEventListener('input', function() { if(this.classList.contains('input-error')) fn(); });
    });

    form.addEventListener('submit', function(e) {
        if (driver.value === 'sqlite') return;
        let valid = true;
        Object.keys(validators).forEach(k => {
            const { el, validate } = validators[k];
            if (!el || !el.required) return;
            const err = validate(el.value);
            if (err) { valid=false; el.classList.add('input-error'); const g=el.closest('.field-group'); const errEl=g?g.querySelector('.error-msg'):null; if(errEl){errEl.textContent=err;errEl.classList.remove('hidden');} }
        });
        if (!valid) { e.preventDefault(); submitBtn.classList.add('shake'); setTimeout(()=>submitBtn.classList.remove('shake'),300); }
    });
});

function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') { input.type='text'; btn.innerHTML='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>'; }
    else { input.type='password'; btn.innerHTML='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'; }
}
</script>
@endsection
