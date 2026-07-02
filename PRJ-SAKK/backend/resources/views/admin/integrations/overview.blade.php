@extends('layouts.admin')

@section('title', 'التكاملات')
@section('breadcrumbs')
<span class="breadcrumb-item">التكاملات</span>
<span class="breadcrumb-item">لوحة الربط</span>
@endsection

@section('content')
<div class="space-y-8" x-data="integrationsApp">

    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 p-6 lg:p-8">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl" style="background: rgba(255,255,255,0.12); color: #fbbf24;">
                    <x-heroicon name="lan" />
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-extrabold text-white">مركز الربط</h1>
                    <p class="text-sm mt-1" style="color: rgba(255,255,255,0.6);">إدارة اتصالات الخدمات الخارجية والتكاملات</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);">
                    <span class="badge badge-success" style="width: 8px; height: 8px; padding: 0; border-radius: 50%; box-shadow: 0 0 6px rgba(31,157,85,0.5);"></span>
                    <span style="color: rgba(255,255,255,0.6);"><b class="text-white" x-text="activeCount"></b> متصل</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);">
                    <span class="badge badge-secondary" style="width: 8px; height: 8px; padding: 0; border-radius: 50%;"></span>
                    <span style="color: rgba(255,255,255,0.6);"><b class="text-white" x-text="offlineCount"></b> غير متصل</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-2">
        <button @click="filter = 'all'" class="btn btn-sm" :class="filter === 'all' ? 'btn-primary' : 'btn-secondary'">الكل</button>
        @foreach($categories as $key => $cat)
        <button @click="filter = '{{ $key }}'" class="btn btn-sm" :class="filter === '{{ $key }}' ? 'btn-primary' : 'btn-secondary'">
            <x-heroicon :name="$cat['icon']" style="font-size: 1rem;" /> {{ $cat['label'] }}
        </button>
        @endforeach
    </div>

    <div class="space-y-8">

    {{-- Integration Cards --}}
    <div class="integration-grid">
        @foreach($integrations as $integration)
        @php
            $cat = $categories[$integration->category] ?? null;
            $catColor = $cat['color'] ?? 'var(--text-muted)';
            $status = !$integration->is_active ? 'offline' : ($integration->error_count > 0 ? 'error' : 'online');
            $statusLabel = $status === 'online' ? 'متصل' : ($status === 'error' ? 'خطأ' : 'غير نشط');
        @endphp
        <div class="int-card" x-show="filter === 'all' || filter === '{{ $integration->category }}'">
            <div class="int-card-accent" style="background: {{ $catColor }};"></div>
            <div class="int-card-body">
                <div class="int-card-top">
                    <div class="int-card-icon" style="background: {{ $catColor }}12; color: {{ $catColor }};">
                        <x-heroicon :name="$integration->icon ?: ($cat['icon'] ?? 'extension')" />
                    </div>
                    <div class="int-card-meta">
                        <span class="int-card-env env-{{ $integration->environment }}">{{ $integration->environment }}</span>
                        <span class="int-card-badge" style="background: {{ $status === 'online' ? 'var(--success-soft)' : ($status === 'error' ? 'var(--danger-soft)' : 'var(--surface-hover)') }}; color: {{ $status === 'online' ? 'var(--success)' : ($status === 'error' ? 'var(--danger)' : 'var(--text-muted)') }};">{{ $statusLabel }}</span>
                    </div>
                </div>
                <h3 class="int-card-title">{{ $integration->name_ar }}</h3>
                <p class="int-card-sub">{{ $integration->name }}</p>
                <p class="int-card-desc">{{ $integration->description_ar ?? $integration->description }}</p>
                <div class="int-card-info-line">
                    <x-heroicon name="schedule" />
                    @if($integration->last_synced_at)<span>آخر اختبار: {{ $integration->last_synced_at->diffForHumans() }}</span>@else<span>لم يختبر بعد</span>@endif
                </div>
                <div class="int-card-actions">
                    <button @click="openModal('int-{{ $integration->id }}')" class="int-card-btn int-card-btn-primary">
                        <x-heroicon name="settings" /> إعدادات
                    </button>
                    <form method="POST" action="{{ route('admin.integrations.test', $integration) }}" class="inline">
                        @csrf
                        <button type="submit" class="int-card-btn int-card-btn-ghost" title="اختبار" @click="testIntegration($event, '{{ $integration->id }}')">
                            <x-heroicon name="wifi_tethering" />
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.integrations.toggle', $integration) }}" class="inline mr-auto">
                        @csrf
                        <button type="submit" class="int-card-btn {{ $integration->is_active ? 'int-card-btn-danger' : 'int-card-btn-success' }}">
                            <x-heroicon name="power_settings_new" /> {{ $integration->is_active ? 'فصل' : 'تشغيل' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @php
        $serviceIcons = [
            'sms' => 'sms', 'mail' => 'mail', 'firebase_otp' => 'verified_user',
            'recaptcha' => 'security', 'whatsapp' => 'chat', 'telegram' => 'send',
        ];
        $secretFields = ['twilio_token', 'mail_password', 'secret_key', 'api_key', 'bot_token'];
    @endphp

            @foreach($services as $service)
            @php $svcActive = $service->is_active; @endphp
            <div class="int-card" x-show="filter === 'all' || filter === 'services'">
                <div class="int-card-accent" style="background: var(--accent);"></div>
                <div class="int-card-body">
                    <div class="int-card-top">
                        <div class="int-card-icon" style="background: var(--accent-soft, rgba(217,119,6,0.08)); color: var(--accent);">
                            <x-heroicon :name="$serviceIcons[$service->key] ?? 'extension'" />
                        </div>
                        <div class="int-card-meta">
                            <span class="int-card-badge" style="background: {{ $svcActive ? 'var(--success-soft)' : 'var(--surface-hover)' }}; color: {{ $svcActive ? 'var(--success)' : 'var(--text-muted)' }};">{{ $svcActive ? 'مفعل' : 'متوقف' }}</span>
                        </div>
                    </div>
                    <h3 class="int-card-title">{{ $service->name_ar }}</h3>
                    <p class="int-card-sub">{{ $service->name }}</p>
                    <div class="int-card-info-line">
                        <x-heroicon name="security" />
                        <span>{{ $service->group === 'security' ? 'خدمة أمان' : 'خدمة اتصالات' }}</span>
                    </div>
                    <div class="int-card-actions">
                        <button @click="openModal('svc-{{ $service->id }}')" class="int-card-btn int-card-btn-primary">
                            <x-heroicon name="tune" /> إعدادات
                        </button>
                        <form method="POST" action="{{ route('admin.system.services.test', $service) }}" class="inline">
                            @csrf
                            <button type="submit" class="int-card-btn int-card-btn-ghost" title="اختبار">
                                <x-heroicon name="wifi_tethering" />
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.system.services.update', $service) }}" class="inline mr-auto">
                            @csrf @method('PUT')
                            <input type="hidden" name="is_active" value="{{ $svcActive ? '0' : '1' }}">
                            <button type="submit" class="int-card-btn {{ $svcActive ? 'int-card-btn-danger' : 'int-card-btn-success' }}">
                                <x-heroicon name="power_settings_new" /> {{ $svcActive ? 'إيقاف' : 'تفعيل' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach

</div>



{{-- ════════════ INTEGRATION MODALS ════════════ --}}
@foreach($integrations as $integration)
@php
    $mCat = $categories[$integration->category] ?? null;
    $mCatColor = $mCat['color'] ?? 'var(--text-muted)';
    $mStatus = !$integration->is_active ? 'offline' : ($integration->error_count > 0 ? 'error' : 'online');
    $mCreds = $integration->credentials ?? [];
@endphp
<div x-show="activeModal === 'int-{{ $integration->id }}'" x-cloak class="modal-backdrop" @click.self="closeModal">
    <div class="modal-box" @click.stop>
        <div class="modal-head">
            <div class="flex items-center gap-3 flex-1">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" style="background: {{ $mCatColor }}12; color: {{ $mCatColor }};">
                    <x-heroicon name="{{ $integration->key }}" />
                </div>
                <div class="flex-1">
                    <h3 class="modal-title">{{ $integration->name_ar }}</h3>
                    <p class="modal-sub">{{ $integration->name }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="modal-status-badge" style="background: {{ $mStatus === 'online' ? 'var(--success-soft)' : ($mStatus === 'error' ? 'var(--danger-soft)' : 'var(--surface-hover)') }}; color: {{ $mStatus === 'online' ? 'var(--success)' : ($mStatus === 'error' ? 'var(--danger)' : 'var(--text-muted)') }};">
                    {{ $mStatus === 'online' ? 'موصول' : ($mStatus === 'error' ? 'خطأ' : 'غير موصول') }}
                </span>
                <button @click="closeModal" class="modal-close">&times;</button>
            </div>
        </div>
        <form id="intForm-{{ $integration->id }}" @submit.prevent="saveInt({{ $integration->id }})" class="modal-body">
            @csrf @method('PUT')

            <div x-show="testResult['{{ $integration->id }}']" x-transition class="test-banner"
                 :class="testOk['{{ $integration->id }}'] ? 'test-ok' : 'test-fail'"
                 x-text="testResult['{{ $integration->id }}']"></div>

            <div class="modal-section">
                <div class="modal-section-title"><x-heroicon name="key" /> بيانات الربط</div>
                <div class="space-y-2-5">
                    @forelse($mCreds as $key => $value)
                    @php $hasVal = !empty($value); @endphp
                    <div class="cred-field">
                        <label class="modal-label">{{ $key }}</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="credentials[{{ $key }}]" value=""
                                   class="modal-input flex-1" style="direction: ltr; text-align: left; font-family: 'JetBrains Mono', monospace;"
                                   placeholder="{{ $hasVal ? '•••••• (اتركه فارغاً للإبقاء)' : 'أدخل القيمة' }}"
                                   id="cred-{{ $integration->id }}-{{ $key }}" autocomplete="off">
                            @if($hasVal)
                            <span class="cred-set">✅</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-xs" style="color: var(--text-muted);">لا توجد بيانات ربط مطلوبة</p>
                    @endforelse
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title"><x-heroicon name="tune" /> الإعدادات</div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="modal-label">الاسم (عربي)</label>
                        <input type="text" name="name_ar" value="{{ $integration->name_ar }}" class="modal-input">
                    </div>
                    <div>
                        <label class="modal-label">الاسم (إنجليزي)</label>
                        <input type="text" name="name" value="{{ $integration->name }}" class="modal-input">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="modal-label">البيئة</label>
                    <select name="environment" class="modal-input">
                        <option value="production" {{ $integration->environment === 'production' ? 'selected' : '' }}>Production</option>
                        <option value="sandbox" {{ $integration->environment === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                        <option value="development" {{ $integration->environment === 'development' ? 'selected' : '' }}>Development</option>
                    </select>
                </div>
            </div>

            <div class="modal-foot" x-show="intOtpState[{{ $integration->id }}] !== 'required'">
                <button type="button" class="modal-btn modal-btn-ghost" @click="testInt({{ $integration->id }})" x-ref="testBtn-{{ $integration->id }}">
                    <x-heroicon name="wifi_tethering" /> اختبار
                </button>
                <div class="flex items-center gap-3">
                    <label @click="intActive[{{ $integration->id }}] = !intActive[{{ $integration->id }}]; $event.preventDefault()" class="flex items-center gap-2 cursor-pointer text-sm font-semibold select-none" style="color: var(--text-secondary);">
                         <span class="switch" style="transform: scale(0.75);">
                             <span class="switch-track" :class="intActive[{{ $integration->id }}] ? 'active' : ''"></span>
                             <span class="switch-thumb" :class="intActive[{{ $integration->id }}] ? 'active' : ''"></span>
                         </span>
                         <span x-text="intActive[{{ $integration->id }}] ? 'مفعل' : 'معطل'"></span>
                     </label>
                    <button type="submit" class="modal-btn modal-btn-primary">
                        <x-heroicon name="save" /> حفظ
                    </button>
                </div>
            </div>
            <div x-show="intOtpState[{{ $integration->id }}] === 'required'" class="modal-foot" style="flex-direction: column; gap: 0.75rem;">
                <div class="text-sm font-bold" style="color: var(--text-secondary);">🔐 تم إرسال رمز التحقق إلى بريدك الإلكتروني</div>
                <div class="flex items-center gap-2 w-full">
                    <input type="text" x-model="intOtpCode[{{ $integration->id }}]" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                           class="modal-input" style="text-align: center; font-size: 1.25rem; letter-spacing: 0.5em; direction: ltr; font-family: 'JetBrains Mono', monospace;"
                           placeholder="000000">
                    <button type="button" class="modal-btn modal-btn-primary whitespace-nowrap" @click="confirmIntOtp({{ $integration->id }})">
                        <x-heroicon name="verified" /> تأكيد
                    </button>
                </div>
                <button type="button" class="text-xs font-semibold" style="color: var(--text-muted); align-self: center; background: none; border: none; cursor: pointer;" @click="resendIntOtp({{ $integration->id }})">إعادة إرسال الرمز</button>
            </div>
        </form>
    </div>
</div>
@endforeach

    {{-- ════════════ SERVICE MODALS ════════════ --}}
    @foreach($services as $service)
    @php
        $svcActive = $service->is_active;
        $svcFields = \App\Http\Controllers\Admin\IntegrationController::SERVICE_FIELD_LABELS[$service->key] ?? [];
    @endphp
    <div x-show="activeModal === 'svc-{{ $service->id }}'" x-cloak class="modal-backdrop" @click.self="closeModal">
        <div class="modal-box" @click.stop>
            <div class="modal-head">
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" style="background: var(--accent-soft, rgba(217,119,6,0.08)); color: var(--accent);">
                        <x-heroicon :name="$serviceIcons[$service->key] ?? 'extension'" />
                    </div>
                    <div class="flex-1">
                        <h3 class="modal-title">{{ $service->name_ar }}</h3>
                        <p class="modal-sub">{{ $service->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="modal-status-badge" style="background: {{ $svcActive ? 'var(--success-soft)' : 'var(--surface-hover)' }}; color: {{ $svcActive ? 'var(--success)' : 'var(--text-muted)' }};">{{ $svcActive ? 'مفعل' : 'متوقف' }}</span>
                    <button @click="closeModal" class="modal-close">&times;</button>
                </div>
            </div>
            <form id="svcForm-{{ $service->id }}" @submit.prevent="saveSvc({{ $service->id }})" class="modal-body">
                @csrf @method('PUT')

                <div x-show="svcTestResult[{{ $service->id }}]" x-transition class="test-banner"
                     :class="svcTestOk[{{ $service->id }}] ? 'test-ok' : 'test-fail'"
                     x-text="svcTestResult[{{ $service->id }}]"></div>

                <div class="modal-section">
                    <div class="modal-section-title"><x-heroicon name="key" /> مفاتيح التفعيل</div>
                    <div class="space-y-2-5">
                        @foreach($svcFields as $fieldKey => $label)
                            @php
                                $isSecret = in_array($fieldKey, $secretFields);
                                $val = $service->getCredential($fieldKey);
                                $hasValue = !empty($val);
                            @endphp
                        <div class="cred-field">
                            <label class="modal-label">{{ $label }}</label>
                            <div class="flex items-center gap-2">
                                <input type="{{ $isSecret ? 'password' : 'text' }}"
                                       name="credentials[{{ $fieldKey }}]" class="modal-input flex-1"
                                       value=""
                                       placeholder="{{ $hasValue ? '•••••• (اتركه فارغاً للإبقاء)' : 'أدخل القيمة' }}">
                                @if($hasValue)
                                <span class="cred-set">✅</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div x-show="svcOtpState[{{ $service->id }}] !== 'required'" class="modal-foot">
                    <button type="button" class="modal-btn modal-btn-ghost" @click="testSvc({{ $service->id }})" x-ref="svcTestBtn-{{ $service->id }}">
                        <x-heroicon name="wifi_tethering" /> اختبار
                    </button>
                    <div class="flex items-center gap-3">
                        <label @click="svcActive[{{ $service->id }}] = !svcActive[{{ $service->id }}]; $event.preventDefault()" class="flex items-center gap-2 cursor-pointer text-sm font-semibold select-none" style="color: var(--text-secondary);">
                            <span class="switch" style="transform: scale(0.75);">
                                <span class="switch-track" :class="svcActive[{{ $service->id }}] ? 'active' : ''"></span>
                                <span class="switch-thumb" :class="svcActive[{{ $service->id }}] ? 'active' : ''"></span>
                            </span>
                            <span x-text="svcActive[{{ $service->id }}] ? 'مفعل' : 'متوقف'"></span>
                        </label>
                        <button type="submit" class="modal-btn modal-btn-primary">
                            <x-heroicon name="save" /> حفظ
                        </button>
                    </div>
                </div>
                <div x-show="svcOtpState[{{ $service->id }}] === 'required'" class="modal-foot" style="flex-direction: column; gap: 0.75rem;">
                    <div class="text-sm font-bold" style="color: var(--text-secondary);">🔐 تم إرسال رمز التحقق إلى بريدك الإلكتروني</div>
                    <div class="flex items-center gap-2 w-full">
                        <input type="text" x-model="svcOtpCode[{{ $service->id }}]" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                               class="modal-input" style="text-align: center; font-size: 1.25rem; letter-spacing: 0.5em; direction: ltr; font-family: 'JetBrains Mono', monospace;"
                               placeholder="000000">
                        <button type="button" class="modal-btn modal-btn-primary whitespace-nowrap" @click="confirmSvcOtp({{ $service->id }})">
                            <x-heroicon name="verified" /> تأكيد
                        </button>
                    </div>
                    <button type="button" class="text-xs font-semibold" style="color: var(--text-muted); align-self: center; background: none; border: none; cursor: pointer;" @click="resendSvcOtp({{ $service->id }})">إعادة إرسال الرمز</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach

    {{-- Toast --}}
    <div x-show="testMsg" x-transition x-cloak class="fixed top-6 left-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur"
         :class="testOk['toast'] ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'"
         style="min-width: 280px;">
        <span class="text-sm font-bold" x-text="testMsg"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
Alpine.data('integrationsApp', () => ({
    filter: '{{ request('category', 'all') }}',
    activeModal: null,
    activeCount: {{ $integrations->where('is_active', true)->count() + $services->where('is_active', true)->count() }},
    offlineCount: {{ $integrations->where('is_active', false)->count() + $services->where('is_active', false)->count() }},
    testMsg: '',
    testOk: {},
    testResult: {},
    intActive: {},
    intOtpState: {},
    intOtpCode: {},
    intPendingToken: {},
    svcActive: {},
    svcOtpState: {},
    svcOtpCode: {},
    svcPendingToken: {},
    svcTestResult: {},
    svcTestOk: {},

    init() {
        @foreach($integrations as $integration)
        this.intActive[{{ $integration->id }}] = {{ $integration->is_active ? 'true' : 'false' }};
        @endforeach
        @foreach($services as $service)
        this.svcActive[{{ $service->id }}] = {{ $service->is_active ? 'true' : 'false' }};
        @endforeach
    },

    openModal(id) { this.activeModal = id; document.body.style.overflow = 'hidden'; },
    closeModal() {
        this.activeModal = null; document.body.style.overflow = '';
        Object.keys(this.intOtpState).forEach(k => { this.intOtpState[k] = null; this.intOtpCode[k] = ''; });
        Object.keys(this.svcOtpState).forEach(k => { this.svcOtpState[k] = null; this.svcOtpCode[k] = ''; });
    },

    async testIntegration(event, id) {
      event.preventDefault();
      const btn = event.currentTarget;
      const orig = btn.innerHTML;
      btn.innerHTML = '<span class="spinner" style="width: 14px; height: 14px; border-width: 1.5px;"></span>';
      try {
        const res = await fetch(btn.form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
        const data = await res.json();
        this.testMsg = data.message;
        this.testOk['toast'] = data.success;
      } catch(e) { this.testMsg = 'فشل الاتصال'; this.testOk['toast'] = false; }
      btn.innerHTML = orig;
      setTimeout(() => this.testMsg = '', 4000);
    },

    async testInt(id) {
      const btn = this.$refs['testBtn-' + id];
      const orig = btn.innerHTML;
      btn.innerHTML = '<span class="spinner" style="width: 14px; height: 14px; border-width: 1.5px;"></span>';
      try {
        const res = await fetch('{{ url("/admin/integrations") }}/' + id + '/test', {
          method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        });
        const data = await res.json();
        this.testResult[id] = data.message;
        this.testOk[id] = data.success;
      } catch(e) { this.testResult[id] = 'فشل الاتصال بالخادم'; this.testOk[id] = false; }
      btn.innerHTML = orig;
    },

    async saveInt(id) {
      const form = document.getElementById('intForm-' + id);
      const data = Object.fromEntries(new FormData(form));
      data.is_active = this.intActive[id];
      try {
        const res = await fetch('{{ url("/admin/integrations") }}/' + id, {
          method: 'PUT',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(data),
        });
        const result = await res.json();
        if (result.requires_otp) {
          this.intOtpState[id] = 'required';
          this.intPendingToken[id] = result.pending_token;
          this.testMsg = result.message;
          this.testOk['toast'] = true;
        } else if (result.success) {
          this.testMsg = result.message;
          this.testOk['toast'] = true;
          this.closeModal();
          setTimeout(() => location.reload(), 800);
        } else {
          this.testMsg = result.message;
          this.testOk['toast'] = false;
        }
      } catch(e) { this.testMsg = 'حدث خطأ في الحفظ'; this.testOk['toast'] = false; }
      setTimeout(() => this.testMsg = '', 4000);
    },

    async confirmIntOtp(id) {
      if (!this.intOtpCode[id] || this.intOtpCode[id].length < 4) {
        this.testMsg = 'يرجى إدخال رمز التحقق كاملاً';
        this.testOk['toast'] = false;
        setTimeout(() => this.testMsg = '', 3000);
        return;
      }
      try {
        const res = await fetch('{{ url("/admin/integrations") }}/' + id, {
          method: 'PUT',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ pending_token: this.intPendingToken[id], otp_code: this.intOtpCode[id] }),
        });
        const result = await res.json();
        if (result.success) {
          this.testMsg = result.message;
          this.testOk['toast'] = true;
          this.closeModal();
          setTimeout(() => location.reload(), 800);
        } else {
          this.testMsg = result.message;
          this.testOk['toast'] = false;
        }
      } catch(e) { this.testMsg = 'حدث خطأ في التحقق'; this.testOk['toast'] = false; }
      setTimeout(() => this.testMsg = '', 4000);
    },

    async resendIntOtp(id) {
      const form = document.getElementById('intForm-' + id);
      const data = Object.fromEntries(new FormData(form));
      data.is_active = this.intActive[id];
      data.resend_otp = true;
      try {
        const res = await fetch('{{ url("/admin/integrations") }}/' + id, {
          method: 'PUT',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(data),
        });
        const result = await res.json();
        if (result.pending_token) this.intPendingToken[id] = result.pending_token;
        this.testMsg = 'تم إرسال رمز جديد';
        this.testOk['toast'] = true;
      } catch(e) { this.testMsg = 'فشل إعادة الإرسال'; this.testOk['toast'] = false; }
      setTimeout(() => this.testMsg = '', 4000);
    },

    async testSvc(id) {
      const btn = this.$refs['svcTestBtn-' + id];
      const orig = btn.innerHTML;
      btn.innerHTML = '<span class="spinner" style="width: 14px; height: 14px; border-width: 1.5px;"></span>';
      try {
        const res = await fetch('{{ url("/admin/system/services") }}/' + id + '/test', {
          method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        });
        const data = await res.json();
        this.svcTestResult[id] = data.message;
        this.svcTestOk[id] = data.success;
      } catch(e) { this.svcTestResult[id] = 'فشل الاتصال بالخادم'; this.svcTestOk[id] = false; }
      btn.innerHTML = orig;
    },

    async saveSvc(id) {
      const form = document.getElementById('svcForm-' + id);
      const data = Object.fromEntries(new FormData(form));
      data.is_active = this.svcActive[id] ? '1' : '0';
      try {
        const res = await fetch('{{ url("/admin/system/services") }}/' + id, {
          method: 'PUT',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(data),
        });
        const result = await res.json();
        if (result.requires_otp) {
          this.svcOtpState[id] = 'required';
          this.svcPendingToken[id] = result.pending_token;
          this.testMsg = result.message;
          this.testOk['toast'] = true;
        } else if (result.success) {
          this.testMsg = result.message;
          this.testOk['toast'] = true;
          this.closeModal();
          setTimeout(() => location.reload(), 800);
        } else {
          this.testMsg = result.message;
          this.testOk['toast'] = false;
        }
      } catch(e) { this.testMsg = 'حدث خطأ في الحفظ'; this.testOk['toast'] = false; }
      setTimeout(() => this.testMsg = '', 4000);
    },

    async confirmSvcOtp(id) {
      if (!this.svcOtpCode[id] || this.svcOtpCode[id].length < 4) {
        this.testMsg = 'يرجى إدخال رمز التحقق كاملاً';
        this.testOk['toast'] = false;
        setTimeout(() => this.testMsg = '', 3000);
        return;
      }
      try {
        const res = await fetch('{{ url("/admin/system/services") }}/' + id, {
          method: 'PUT',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ pending_token: this.svcPendingToken[id], otp_code: this.svcOtpCode[id] }),
        });
        const result = await res.json();
        if (result.success) {
          this.testMsg = result.message;
          this.testOk['toast'] = true;
          this.closeModal();
          setTimeout(() => location.reload(), 800);
        } else {
          this.testMsg = result.message;
          this.testOk['toast'] = false;
        }
      } catch(e) { this.testMsg = 'حدث خطأ في التحقق'; this.testOk['toast'] = false; }
      setTimeout(() => this.testMsg = '', 4000);
    },

    async resendSvcOtp(id) {
      const form = document.getElementById('svcForm-' + id);
      const data = Object.fromEntries(new FormData(form));
      data.is_active = this.svcActive[id] ? '1' : '0';
      data.resend_otp = true;
      try {
        const res = await fetch('{{ url("/admin/system/services") }}/' + id, {
          method: 'PUT',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(data),
        });
        const result = await res.json();
        if (result.pending_token) this.svcPendingToken[id] = result.pending_token;
        this.testMsg = 'تم إرسال رمز جديد';
        this.testOk['toast'] = true;
      } catch(e) { this.testMsg = 'فشل إعادة الإرسال'; this.testOk['toast'] = false; }
      setTimeout(() => this.testMsg = '', 4000);
    }
   }));
 });
</script>
@endpush
