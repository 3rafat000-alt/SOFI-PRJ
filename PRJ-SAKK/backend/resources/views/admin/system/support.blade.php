@extends('layouts.admin')

@section('title', 'الدعم الفني')
@section('breadcrumbs')
<span class="breadcrumb-item">إعدادات النظام</span>
<span class="breadcrumb-item">الدعم الفني</span>
@endsection

@include('admin.system._shell')

@php
    $enabled = (bool) ($cfg['support_enabled'] ?? true);
@endphp

@section('content')
<div class="sys-head">
    <div class="sys-head-ico"><x-heroicon name="support_agent" /></div>
    <div class="sys-head-txt">
        <h1>الدعم الفني — قنوات التواصل</h1>
        <p>القنوات التي يراها المستخدم في شاشة «تواصل معنا» داخل التطبيق. التطبيق يقرأها مباشرة من <code>/api/v1/app/support</code> — أي تعديل هنا يظهر فوراً دون تحديث التطبيق.</p>
    </div>
    <div class="sys-head-actions">
        <div class="sys-head-stat">
            <div class="n">{{ $enabled ? 'ظاهر' : 'مخفي' }}</div>
            <div class="l">حالة القسم</div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="sys-note" style="--note:var(--success);margin-bottom:1rem">
        <x-heroicon name="check_circle" />
        <div class="bd">{{ session('success') }}</div>
    </div>
@endif

<form method="POST" action="{{ route('admin.system.support.update') }}">
    @csrf @method('PUT')

    {{-- ════════ المجموعة 1 — الإظهار ════════ --}}
    <div class="sys-group">
        <div class="sys-group-label">
            <x-heroicon name="toggle_on" />
            <span class="t">الإظهار في التطبيق</span>
            <span class="line"></span>
        </div>

        <div class="card"><div class="card-body">
            <label class="toggle-pill" style="cursor:pointer">
                <span>
                    <span class="t">إظهار قسم الدعم الفني</span>
                    <span class="d">عند الإيقاف يُخفى قسم «تواصل معنا» من التطبيق.</span>
                </span>
                <span class="switch">
                    <input type="hidden" name="support_enabled" value="0">
                    <input type="checkbox" name="support_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                    <span class="switch-track"></span><span class="switch-thumb"></span>
                </span>
            </label>
        </div></div>
    </div>

    {{-- ════════ المجموعة 2 — قنوات التواصل ════════ --}}
    <div class="sys-group">
        <div class="sys-group-label">
            <x-heroicon name="contact_support" />
            <span class="t">قنوات التواصل</span>
            <span class="line"></span>
            <span class="c">اترك الحقل فارغاً لإخفاء القناة</span>
        </div>

        <div class="card"><div class="card-body">
            <div class="field-grid">
                <div class="field">
                    <label class="label">البريد الإلكتروني للدعم</label>
                    <input type="email" name="support_email" class="input input-mono" dir="ltr"
                           placeholder="support@zanjour.com"
                           value="{{ old('support_email', $cfg['support_email'] ?? '') }}">
                    @error('support_email')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="label">رقم الهاتف</label>
                    <input type="text" name="support_phone" class="input input-mono" dir="ltr"
                           placeholder="+963 9xx xxx xxx"
                           value="{{ old('support_phone', $cfg['support_phone'] ?? '') }}">
                    @error('support_phone')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="label">رقم واتساب</label>
                    <input type="text" name="support_whatsapp" class="input input-mono" dir="ltr"
                           placeholder="+963 9xx xxx xxx"
                           value="{{ old('support_whatsapp', $cfg['support_whatsapp'] ?? '') }}">
                    <div class="d" style="font-size:.7rem;color:var(--text-muted);margin-top:.3rem">
                        يفتح محادثة واتساب مباشرة (wa.me). الرقم بصيغة دولية بدون رموز.
                    </div>
                    @error('support_whatsapp')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="label">تيليجرام (معرّف أو رابط)</label>
                    <input type="text" name="support_telegram" class="input input-mono" dir="ltr"
                           placeholder="@sakk_support"
                           value="{{ old('support_telegram', $cfg['support_telegram'] ?? '') }}">
                    @error('support_telegram')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
            </div>
        </div></div>
    </div>

    {{-- ════════ المجموعة 3 — التفاصيل ════════ --}}
    <div class="sys-group">
        <div class="sys-group-label">
            <x-heroicon name="schedule" />
            <span class="t">ساعات العمل والرسالة</span>
            <span class="line"></span>
        </div>

        <div class="card"><div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
            <div class="field">
                <label class="label">ساعات العمل</label>
                <input type="text" name="support_hours" class="input"
                       placeholder="السبت – الخميس · 9 صباحاً – 5 مساءً"
                       value="{{ old('support_hours', $cfg['support_hours'] ?? '') }}">
                @error('support_hours')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="label">رسالة ترحيبية للمستخدم</label>
                <textarea name="support_message" rows="3" class="input">{{ old('support_message', $cfg['support_message'] ?? '') }}</textarea>
                @error('support_message')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="label">رابط الأسئلة الشائعة (اختياري)</label>
                <input type="url" name="support_faq_url" class="input input-mono" dir="ltr"
                       placeholder="https://zanjour.com/faq"
                       value="{{ old('support_faq_url', $cfg['support_faq_url'] ?? '') }}">
                @error('support_faq_url')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
        </div></div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:.6rem;margin-top:.5rem">
        <button type="submit" class="btn btn-primary">
            <x-heroicon name="save" /> حفظ بيانات الدعم
        </button>
    </div>
</form>
@endsection
