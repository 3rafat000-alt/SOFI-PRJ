@extends('layouts.admin')

@section('title', 'إضافة وكيل جديد')
@section('breadcrumbs')
<a href="{{ route('admin.agents.index') }}" class="breadcrumb-item">الوكلاء</a>
<span class="breadcrumb-item">إضافة وكيل</span>
@endsection



@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.agents.index') }}" class="btn btn-sukk-icon" title="رجوع">
            <x-heroicon name="arrow_forward" />
        </a>
        <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background: var(--sidebar-active); color: var(--accent);">
            <x-heroicon name="add_business" />
        </div>
        <div>
            <h1 class="text-2xl font-extrabold" style="color: var(--text-primary);">إضافة وكيل جديد</h1>
            <p class="text-sm" style="color: var(--text-muted);">وكيل سحب وإيداع نقدي جديد</p>
        </div>
    </div>

    @if($errors->any())
    <div class="card" style="border-color: var(--danger);">
        <div class="card-body flex items-start gap-3">
            <x-heroicon name="error" style="color: var(--danger);" />
            <div>
                <p class="text-sm font-bold" style="color: var(--danger);">يرجى تصحيح الأخطاء التالية:</p>
                <ul class="text-sm mt-1 list-disc pr-5" style="color: var(--danger);">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.agents.store') }}" class="space-y-6">
        @csrf

        {{-- Agent identity --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title"><x-heroicon name="person" /> بيانات الوكيل</h3></div>
            <div class="card-body space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="label label-required">اسم الوكيل</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="input @error('name') input-error @enderror" placeholder="اسم المحل أو الوكيل">
                        @error('name') <p class="hint" style="color: var(--danger);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">اسم المالك</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name') }}" class="input" placeholder="اسم صاحب المحل">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="label">رقم الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="input" dir="ltr" placeholder="09xxxxxxxx">
                    </div>
                    <div>
                        <label class="label">كود الوكيل</label>
                        <input type="text" name="agent_code" value="{{ old('agent_code') }}" class="input font-mono" dir="ltr" placeholder="AG-XXXXXX">
                        <p class="hint">يُترك فارغاً لإنشاء كود تلقائي.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Location --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title"><x-heroicon name="location_on" /> العنوان والموقع</h3></div>
            <div class="card-body space-y-5">
                <div>
                    <label class="label label-required">العنوان</label>
                    <input type="text" name="address" value="{{ old('address') }}" required class="input @error('address') input-error @enderror" placeholder="وصف العنوان التفصيلي">
                    @error('address') <p class="hint" style="color: var(--danger);">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="label label-required">المدينة</label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="input @error('city') input-error @enderror" placeholder="دمشق">
                        @error('city') <p class="hint" style="color: var(--danger);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">المحافظة</label>
                        <input type="text" name="governorate" value="{{ old('governorate') }}" class="input" placeholder="محافظة دمشق">
                    </div>
                </div>
                @include('admin.agents._location-map')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="label label-required">خط العرض (Latitude)</label>
                        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude') }}" required class="input @error('latitude') input-error @enderror" dir="ltr" placeholder="33.5138">
                        @error('latitude') <p class="hint" style="color: var(--danger);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label label-required">خط الطول (Longitude)</label>
                        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude') }}" required class="input @error('longitude') input-error @enderror" dir="ltr" placeholder="36.2765">
                        @error('longitude') <p class="hint" style="color: var(--danger);">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Services & settings --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title"><x-heroicon name="handyman" /> الخدمات والإعدادات</h3></div>
            <div class="card-body space-y-5">
                <div>
                    <label class="label">الخدمات المتاحة</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                        <label class="svc-card">
                            <input type="checkbox" name="services[]" value="cash_in" {{ in_array('cash_in', old('services', [])) ? 'checked' : '' }}>
                            <x-heroicon name="south_west" />
                            <span class="text-sm font-bold" style="color: var(--text-primary);">إيداع نقدي</span>
                        </label>
                        <label class="svc-card">
                            <input type="checkbox" name="services[]" value="cash_out" {{ in_array('cash_out', old('services', [])) ? 'checked' : '' }}>
                            <x-heroicon name="north_east" />
                            <span class="text-sm font-bold" style="color: var(--text-primary);">سحب نقدي</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="label">ساعات العمل</label>
                        <input type="text" name="working_hours" value="{{ old('working_hours') }}" class="input" placeholder="9:00 ص - 9:00 م">
                    </div>
                    <div>
                        <label class="label">نسبة العمولة</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="commission_rate" value="{{ old('commission_rate', 0) }}" class="input" dir="ltr">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="label">الحد الأدنى</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="min_amount" value="{{ old('min_amount', 0) }}" class="input" dir="ltr">
                            <span class="input-group-text">ل.س</span>
                        </div>
                    </div>
                    <div>
                        <label class="label">الحد الأقصى</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="max_amount" value="{{ old('max_amount') }}" class="input" dir="ltr" placeholder="بدون حد">
                            <span class="input-group-text">ل.س</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label">ملاحظات</label>
                    <textarea name="notes" rows="3" class="input" placeholder="أي ملاحظات إدارية...">{{ old('notes') }}</textarea>
                </div>

                <div>
                    <label class="label">الإعدادات</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1">
                        <label class="svc-card">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <x-heroicon name="toggle_on" />
                            <span class="text-sm font-bold" style="color: var(--text-primary);">نشط</span>
                        </label>
                        <label class="svc-card">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                            <x-heroicon name="star" />
                            <span class="text-sm font-bold" style="color: var(--text-primary);">مميز</span>
                        </label>
                        <label class="svc-card">
                            <input type="checkbox" name="is_verified" value="1" {{ old('is_verified', true) ? 'checked' : '' }}>
                            <x-heroicon name="verified" />
                            <span class="text-sm font-bold" style="color: var(--text-primary);">موثق</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action bar --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
                <x-heroicon name="save" class="text-sm" />
                حفظ الوكيل
            </button>
        </div>
    </form>
</div>
@endsection
