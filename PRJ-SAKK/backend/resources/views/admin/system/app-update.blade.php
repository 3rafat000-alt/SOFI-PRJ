@extends('layouts.admin')

@section('title', 'تحديث التطبيق')
@section('breadcrumbs')
<span class="breadcrumb-item">إعدادات النظام</span>
<span class="breadcrumb-item">تحديث التطبيق</span>
@endsection

@include('admin.system._shell')

@php
    $enabled   = (bool) ($cfg['app_update_enabled'] ?? true);
    $forceAll  = (bool) ($cfg['app_force_update'] ?? false);
@endphp

@section('content')
<div class="sys-head">
    <div class="sys-head-ico"><x-heroicon name="system_update" /></div>
    <div class="sys-head-txt">
        <h1>تحديث التطبيق — إجبار المستخدمين</h1>
        <p>اضبط أدنى إصدار مسموح. أي مستخدم على بناء أقدم من «أدنى رقم بناء» يُحجب على شاشة تحديث إجبارية لا يمكن تجاوزها، مع زر يفتح رابط التحميل أدناه. التطبيق يقرأ هذه السياسة عند كل إقلاع من <code>/api/v1/app/version</code>.</p>
    </div>
    <div class="sys-head-actions">
        <div class="sys-head-stat">
            <div class="n">{{ $enabled ? 'مفعّل' : 'متوقف' }}</div>
            <div class="l">حالة السياسة</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.system.app-update.update') }}">
    @csrf @method('PUT')

    {{-- ════════ المجموعة 1 — التفعيل ════════ --}}
    <div class="sys-group">
        <div class="sys-group-label">
            <x-heroicon name="toggle_on" />
            <span class="t">التفعيل والإجبار</span>
            <span class="line"></span>
        </div>

        <div class="card"><div class="card-body" style="display:flex;flex-direction:column;gap:.9rem">
            <label class="toggle-pill" style="cursor:pointer">
                <span>
                    <span class="t">تفعيل فحص التحديث</span>
                    <span class="d">عند الإيقاف لن يُحجب أي مستخدم مهما كان إصداره.</span>
                </span>
                <span class="switch">
                    <input type="hidden" name="app_update_enabled" value="0">
                    <input type="checkbox" name="app_update_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                    <span class="switch-track"></span><span class="switch-thumb"></span>
                </span>
            </label>

            <label class="toggle-pill" style="cursor:pointer">
                <span>
                    <span class="t">إجبار الجميع فوراً (طوارئ)</span>
                    <span class="d">يحجب كل المستخدمين بصرف النظر عن إصدارهم — استخدمه لإيقاف نسخة معطوبة فوراً.</span>
                </span>
                <span class="switch">
                    <input type="hidden" name="app_force_update" value="0">
                    <input type="checkbox" name="app_force_update" value="1" {{ $forceAll ? 'checked' : '' }}>
                    <span class="switch-track"></span><span class="switch-thumb"></span>
                </span>
            </label>
        </div></div>
    </div>

    {{-- ════════ المجموعة 2 — الإصدارات ════════ --}}
    <div class="sys-group">
        <div class="sys-group-label">
            <x-heroicon name="tag" />
            <span class="t">الإصدارات وأرقام البناء</span>
            <span class="line"></span>
            <span class="c">build = versionCode</span>
        </div>

        <div class="card"><div class="card-body">
            <div class="field-grid">
                <div class="field">
                    <label class="label">أدنى رقم بناء مطلوب (Min Build)</label>
                    <input type="number" min="1" name="app_min_build" class="input input-mono"
                           value="{{ old('app_min_build', $cfg['app_min_build'] ?? 1) }}" required>
                    <div class="d" style="font-size:.7rem;color:var(--text-muted);margin-top:.3rem">
                        من كان رقم بنائه أقل من هذا الرقم يُحجب. ارفعه لإجبار التحديث.
                    </div>
                    @error('app_min_build')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="label">أدنى إصدار مطلوب (للعرض)</label>
                    <input type="text" name="app_min_version" class="input input-mono" dir="ltr"
                           placeholder="1.0.0" value="{{ old('app_min_version', $cfg['app_min_version'] ?? '1.0.0') }}" required>
                    @error('app_min_version')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="label">رقم بناء أحدث إصدار (Latest Build)</label>
                    <input type="number" min="1" name="app_latest_build" class="input input-mono"
                           value="{{ old('app_latest_build', $cfg['app_latest_build'] ?? 1) }}" required>
                    @error('app_latest_build')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="label">أحدث إصدار (Latest Version)</label>
                    <input type="text" name="app_latest_version" class="input input-mono" dir="ltr"
                           placeholder="1.0.1" value="{{ old('app_latest_version', $cfg['app_latest_version'] ?? '1.0.0') }}" required>
                    @error('app_latest_version')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ المجموعة 3 — رابط التحميل والرسالة ════════ --}}
    <div class="sys-group">
        <div class="sys-group-label">
            <x-heroicon name="download" />
            <span class="t">رابط التحميل والرسالة</span>
            <span class="line"></span>
        </div>

        <div class="card"><div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
            <div class="field">
                <label class="label">رابط تحميل النسخة الجديدة (APK / المتجر)</label>
                <input type="url" name="app_download_url" class="input input-mono" dir="ltr"
                       placeholder="https://sakk.zanjour.com/download/sakk.apk"
                       value="{{ old('app_download_url', $cfg['app_download_url'] ?? '') }}" required>
                <div class="d" style="font-size:.7rem;color:var(--text-muted);margin-top:.3rem">
                    الزر في شاشة الحجب يفتح هذا الرابط مباشرة. استخدم رابط الـAPK المنشور أو رابط المتجر.
                </div>
                @error('app_download_url')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="label">عنوان شاشة التحديث</label>
                <input type="text" name="app_update_title" class="input"
                       value="{{ old('app_update_title', $cfg['app_update_title'] ?? 'تحديث مطلوب') }}" required>
                @error('app_update_title')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="label">نص الرسالة للمستخدم</label>
                <textarea name="app_update_message" rows="3" class="input" required>{{ old('app_update_message', $cfg['app_update_message'] ?? 'يتوفّر إصدار جديد من تطبيق صكّ. يرجى التحديث للمتابعة.') }}</textarea>
                @error('app_update_message')<div class="d" style="color:var(--danger);font-size:.7rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
        </div></div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:.6rem;margin-top:.5rem">
        <button type="submit" class="btn btn-primary">
            <x-heroicon name="save" /> حفظ السياسة
        </button>
    </div>
</form>
@endsection
