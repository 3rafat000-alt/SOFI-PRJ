@extends('layouts.installer')

@section('title', 'فحص المتطلبات')
@section('subtitle', 'نتحقق من توافق الخادم مع متطلبات النظام قبل البدء')

@php $currentStep = 1; @endphp

@section('content')
<div class="space-y-7">
    <!-- PHP Version -->
    <div>
        <h3 class="text-lg sm:text-xl font-bold flex items-center gap-3 mb-4" style="color: var(--ink, #2A1A1F);">
            <svg class="w-6 h-6" fill="none" stroke="#6E1B2D" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            إصدار PHP والمكتبات
        </h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach($phpRequirements as $req => $met)
            <div class="flex items-center justify-between px-5 py-4 rounded-2xl border" style="background: #FAF8F5; border-color: #E8DED6;">
                <span class="text-base font-semibold" style="color: var(--ink, #2A1A1F);">{{ $req }}</span>
                <span class="flex items-center gap-2 text-sm font-bold px-4 py-1.5 rounded-xl {{ $met ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($met)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        @endif
                    </svg>
                    {{ $met ? 'متوفر' : 'غير متوفر' }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- PHP Extensions -->
    <div>
        <h3 class="text-lg sm:text-xl font-bold flex items-center gap-3 mb-4" style="color: var(--ink, #2A1A1F);">
            <svg class="w-6 h-6" fill="none" stroke="#6E1B2D" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
            </svg>
            الإضافات
        </h3>
        <div class="grid grid-cols-3 gap-4">
            @foreach($extensions as $ext => $installed)
            <div class="flex items-center justify-between px-5 py-4 rounded-2xl border" style="background: #FAF8F5; border-color: #E8DED6;">
                <span class="text-base font-semibold" style="color: var(--ink, #2A1A1F);">{{ $ext }}</span>
                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $installed ? 'bg-green-100' : 'bg-red-100' }}">
                    <svg class="w-5 h-5 {{ $installed ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($installed)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        @endif
                    </svg>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Directory Permissions -->
    <div>
        <h3 class="text-lg sm:text-xl font-bold flex items-center gap-3 mb-4" style="color: var(--ink, #2A1A1F);">
            <svg class="w-6 h-6" fill="none" stroke="#B58A3C" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            صلاحيات المجلدات
        </h3>
        <div class="grid grid-cols-4 gap-4">
            @foreach($permissions as $dir => $writable)
            <div class="flex items-center justify-between px-5 py-4 rounded-2xl border" style="background: #FAF8F5; border-color: {{ $writable ? '#E8DED6' : '#FECACA' }};">
                <span class="text-base font-mono truncate ltr text-left font-medium" style="color: var(--ink, #2A1A1F);">{{ $dir }}</span>
                <span class="flex items-center gap-2 text-sm font-bold px-4 py-1.5 rounded-xl {{ $writable ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($writable)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        @endif
                    </svg>
                    {{ $writable ? 'قابل للكتابة' : 'غير قابل' }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-end pt-6 mt-6" style="border-top: 1px solid #E8DED6;">
        @if($allPassed)
            <a href="{{ route('installer.database') }}" class="installer-btn-primary">
                متابعة ← قاعدة البيانات
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
            </a>
        @else
            <button disabled class="px-8 py-3 rounded-xl font-semibold text-base cursor-not-allowed" style="background: #E8DED6; color: #C4B5A4;">
                قم بإصلاح المتطلبات أولاً
            </button>
        @endif
    </div>
</div>
@endsection
