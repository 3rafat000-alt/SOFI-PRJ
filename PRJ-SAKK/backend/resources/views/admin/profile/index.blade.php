@extends('layouts.admin')

@section('title', 'الملف الشخصي')

@section('breadcrumbs')
<span class="mx-1">/</span><span>الملف الشخصي</span>
@endsection

@php
    $u        = auth()->user();
    $fn       = $u->first_name ?? 'مدير';
    $ln       = $u->last_name ?? '';
    $initials = mb_substr($fn, 0, 1) . ($ln ? mb_substr($ln, 0, 1) : '');
    $fullName = trim($fn . ' ' . $ln) ?: 'مدير النظام';
    $joined   = optional($u->created_at)->format('Y/m/d') ?? '—';
@endphp

@section('content')
<div class="dash4-space-y" x-data="profilePage">

    {{-- ═══ Identity hero — same glass gradient as the home welcome banner ═══ --}}
    <div class="dash4-welcome">
        <div class="prof-idrow">
            <span class="prof-avatar">{{ $initials }}</span>
            <div class="min-w-0">
                <h1 class="prof-name">{{ $fullName }}</h1>
                <p class="prof-email">{{ $u->email ?? '—' }}</p>
                <div class="prof-pills">
                    <span class="prof-pill prof-pill--gold">
                        <x-heroicon name="admin_panel_settings" class="w-4 h-4" />
                        مدير النظام
                    </span>
                    <span class="prof-pill">
                        <span class="prof-dot"></span>
                        نشط
                    </span>
                    <span class="prof-pill">
                        <x-heroicon name="today" class="w-4 h-4" />
                        عضو منذ {{ $joined }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ 2-column grid — mirrors the dashboard layout ═══ --}}
    <form method="POST" action="{{ route('admin.profile.update') }}" @submit="handleSubmit($event)">
        @csrf @method('PUT')
        <div class="dash4-grid">

            {{-- ── MAIN: editable cards ── --}}
            <div class="dash4-main dash4-space-y">

                {{-- Account data --}}
                <section class="prof-card">
                    <h2 class="prof-card-title">
                        <span class="ic"><x-heroicon name="person" /></span>
                        بيانات الحساب
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="label label-required">الاسم الأول</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $fn) }}" required class="input" aria-label="الاسم الأول">
                            @error('first_name')<p class="field-error"><x-heroicon name="error" />{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label label-required">اسم العائلة</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $ln) }}" required class="input" aria-label="اسم العائلة">
                            @error('last_name')<p class="field-error"><x-heroicon name="error" />{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="label label-required">البريد الإلكتروني</label>
                            <input type="email" name="email" value="{{ old('email', $u->email ?? '') }}" required class="input" aria-label="البريد الإلكتروني" dir="ltr" style="text-align:right;">
                            @error('email')<p class="field-error"><x-heroicon name="error" />{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Security / password --}}
                <section class="prof-card">
                    <h2 class="prof-card-title">
                        <span class="ic"><x-heroicon name="lock" /></span>
                        الأمان وكلمة المرور
                    </h2>
                    <p class="text-sm mb-4" style="color:var(--text-muted);">اترك الحقول فارغة إذا لم ترغب في تغيير كلمة المرور</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="label">كلمة المرور الحالية</label>
                            <div class="relative">
                                <input type="password" name="current_password" id="currentPassword" class="input" aria-label="كلمة المرور الحالية">
                                <button type="button" @click="togglePassword('currentPassword')" class="absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-muted);" aria-label="إظهار/إخفاء">
                                    <x-heroicon name="visibility" />
                                </button>
                            </div>
                            @error('current_password')<p class="field-error"><x-heroicon name="error" />{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label">كلمة المرور الجديدة</label>
                            <div class="relative">
                                <input type="password" name="password" id="newPassword" class="input" aria-label="كلمة المرور الجديدة">
                                <button type="button" @click="togglePassword('newPassword')" class="absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-muted);" aria-label="إظهار/إخفاء">
                                    <x-heroicon name="visibility" />
                                </button>
                            </div>
                            @error('password')<p class="field-error"><x-heroicon name="error" />{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label">تأكيد كلمة المرور</label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" id="confirmPassword" class="input" aria-label="تأكيد كلمة المرور">
                                <button type="button" @click="togglePassword('confirmPassword')" class="absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-muted);" aria-label="إظهار/إخفاء">
                                    <x-heroicon name="visibility" />
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Two-factor authentication entry point --}}
                <section class="prof-card">
                    <h2 class="prof-card-title">
                        <span class="ic"><x-heroicon name="shield" /></span>
                        التحقق بخطوتين (2FA)
                    </h2>
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <p class="text-sm" style="color:var(--text-muted);">
                            @if($u->two_factor_enabled ?? false)
                                مُفعّل حالياً على حسابك.
                            @else
                                غير مُفعّل — أضِف طبقة حماية إضافية لحسابك.
                            @endif
                        </p>
                        <a href="{{ route('admin.profile.2fa.show') }}" class="btn btn-secondary">
                            <x-heroicon name="shield" />
                            <span>إدارة التحقق بخطوتين</span>
                        </a>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" id="saveProfileBtn" class="btn btn-sukk-primary">
                        <x-heroicon name="save" />
                        <span class="btn-text">حفظ التغييرات</span>
                        <span class="spinner hidden"></span>
                    </button>
                </div>

            </div>

            {{-- ── SIDE: read-only summary ── --}}
            <aside class="dash4-side">
                <div class="dash4-side-card">
                    <div class="dash4-side-card-title">
                        <x-heroicon name="info" />
                        ملخّص الحساب
                    </div>
                    <div class="dash4-side-stat">
                        <span class="dash4-side-stat-label">الاسم</span>
                        <span class="dash4-side-stat-value">{{ $fullName }}</span>
                    </div>
                    <div class="dash4-side-stat">
                        <span class="dash4-side-stat-label">الدور</span>
                        <span class="dash4-side-stat-value">مدير النظام</span>
                    </div>
                    <div class="dash4-side-stat">
                        <span class="dash4-side-stat-label">الحالة</span>
                        <span class="dash4-side-stat-value" style="color:var(--success);display:inline-flex;align-items:center;gap:.35rem;"><span class="prof-dot"></span>نشط</span>
                    </div>
                    <div class="dash4-side-stat">
                        <span class="dash4-side-stat-label">تاريخ الانضمام</span>
                        <span class="dash4-side-stat-value">{{ $joined }}</span>
                    </div>
                    <div class="dash4-side-stat">
                        <span class="dash4-side-stat-label">المعرّف</span>
                        <span class="dash4-side-stat-value">#{{ $u->id ?? '—' }}</span>
                    </div>
                </div>

                <a href="{{ route('admin.profile.2fa.show') }}" class="dash4-side-card" style="text-decoration:none;color:inherit;display:block;">
                    <div class="dash4-side-card-title">
                        <x-heroicon name="lock" />
                        التحقق الثنائي (2FA)
                    </div>
                    <p class="text-sm" style="color:var(--text-secondary);line-height:1.7;">
                        تأمين حسابك الإداري برمز إضافي
                    </p>
                </a>

                <div class="dash4-side-card">
                    <div class="dash4-side-card-title">
                        <x-heroicon name="shield" />
                        نصيحة أمان
                    </div>
                    <p class="text-sm" style="color:var(--text-secondary);line-height:1.7;">
                        استخدم كلمة مرور قوية لا تقل عن 8 أحرف تجمع بين حروف وأرقام ورموز، ولا تشاركها مع أحد. غيّرها دورياً للحفاظ على أمان لوحة التحكم.
                    </p>
                </div>
            </aside>

        </div>
    </form>

</div>
@endsection

@push('styles')
<style>
    /* ── Hero identity row (over the shared .dash4-welcome gradient) ── */
    .prof-idrow { display: flex; align-items: center; gap: 1.1rem; flex-wrap: wrap; }
    .prof-avatar {
        display: flex; align-items: center; justify-content: center;
        width: 72px; height: 72px; border-radius: var(--radius-main);
        font-size: 1.6rem; font-weight: 800; color: #fff; flex-shrink: 0;
        background: rgba(255,255,255,.14);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,.20);
    }
    .prof-name { font-size: 1.5rem; font-weight: 800; line-height: 1.3; color: #fff; }
    .prof-email { font-size: .85rem; color: rgba(255,255,255,.72); margin-top: 2px; }
    .prof-pills { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .7rem; }
    .prof-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .26rem .65rem; font-size: .7rem; font-weight: 700; color: #fff;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 999px;
    }
    .prof-pill--gold {
        background: rgba(201,149,60,.28);
        border-color: rgba(222,173,79,.45);
    }
    .prof-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--success, #1F9D55); box-shadow: 0 0 0 3px rgba(31,157,85,.18); }

    /* ── Editable cards — same borderless surface language as dashboard ── */
    .prof-card {
        background: var(--surface);
        border: none;
        border-radius: 8px;
        padding: var(--space-lg);
    }
    .prof-card-title {
        display: flex; align-items: center; gap: .6rem;
        font-size: .85rem; font-weight: 800; color: var(--text-primary);
        margin-bottom: var(--space-md);
        padding-bottom: var(--space-sm);
        border-bottom: 1px solid var(--border-light);
    }
    .prof-card-title .ic {
        display: flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
        background: rgba(110, 27, 45, 0.10);
        color: var(--sukk-primary);
    }
    /* utilities.css sizes .w-*/.h-* with !important — must out-!important it */
    .prof-card-title .ic svg { width: 16px !important; height: 16px !important; }

    /* ── Fix: inputs were white-on-white (invisible) inside white cards ── */
    .prof-card .input {
        background: var(--input-bg);
        border: 1px solid var(--border);
        box-shadow: none;
    }
    .prof-card .input:hover:not(:focus) { border-color: var(--border-strong); }
    .prof-card .input:focus {
        background: var(--surface);
        border-color: var(--sukk-primary);
        box-shadow: var(--shadow-focus);
    }
    /* password reveal button sits on the inline-end (left in RTL) — pad that side */
    .prof-card .relative .input { padding-inline-end: 2.75rem; }
    /* Position the reveal button reliably: the Tailwind top-1/2 & -translate-y-1/2
       utilities are NOT in this project's CSS build, so the button fell to the
       bottom-left corner. Center it on the inline-end edge here instead. */
    .prof-card .relative { display: block; }
    .prof-card .relative > button {
        position: absolute;
        inset-inline-end: 0.7rem;
        inset-block-start: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem;
        line-height: 0;
        border-radius: var(--radius-sm);
        color: var(--text-muted);
    }
    .prof-card .relative > button:hover { color: var(--text-secondary); }
    .prof-card .relative > button:focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px var(--primary-ring);
    }
    .prof-card .relative > button svg { width: 18px !important; height: 18px !important; }

    /* ── Fix: heroicon merges w-5 h-5 → pill icons rendered too large ── */
    .prof-pill svg { width: 14px !important; height: 14px !important; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('profilePage', () => ({
            handleSubmit(e) {
                const btn = e.target.querySelector('button[type="submit"]');
                if (btn) {
                    btn.classList.add('btn-loading');
                    btn.disabled = true;
                }
                return true;
            },
            togglePassword(id) {
                const input = document.getElementById(id);
                if (input) input.type = input.type === 'password' ? 'text' : 'password';
            },
        }));
    });
</script>
@endpush
