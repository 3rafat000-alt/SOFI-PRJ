@extends('layouts.admin')

@section('title', 'تعديل التاجر - ' . $merchant->store_name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.merchants.show', $merchant) }}" class="btn btn-sm btn-sukk-icon">
            <x-heroicon name="arrow_forward" />
        </a>
        <div>
            <h1 class="text-2xl font-extrabold" style="color: var(--text-primary)">تعديل التاجر</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted)">{{ $merchant->merchant_code }} — {{ $merchant->store_name }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.merchants.update', $merchant) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="store" />
                    بيانات المتجر
                </h3>
            </div>
            <div class="card-body space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="label label-required">اسم المتجر</label>
                        <input type="text" name="store_name" value="{{ old('store_name', $merchant->store_name) }}" required
                               class="input @error('store_name') input-error @enderror">
                        @error('store_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label label-required">النوع</label>
                        <select name="type" required
                                class="input @error('type') input-error @enderror">
                            @foreach(\App\Http\Controllers\Admin\MerchantController::TYPES as $val => $label)
                            <option value="{{ $val }}" {{ old('type', $merchant->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="label">اسم المالك</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name', $merchant->owner_name) }}" class="input">
                    </div>
                    <div>
                        <label class="label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $merchant->email) }}" class="input" dir="ltr">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="label">رقم الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone', $merchant->phone) }}" class="input" dir="ltr">
                    </div>
                    <div>
                        <label class="label">الموقع الإلكتروني</label>
                        <input type="url" name="website_url" value="{{ old('website_url', $merchant->website_url) }}" class="input" dir="ltr">
                    </div>
                </div>
                <div>
                    <label class="label">الوصف</label>
                    <textarea name="description" rows="3"
                              class="input">{{ old('description', $merchant->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="location_on" />
                    الموقع
                </h3>
            </div>
            <div class="card-body space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="label">العنوان</label>
                        <input type="text" name="address" value="{{ old('address', $merchant->address) }}" class="input">
                    </div>
                    <div>
                        <label class="label">المدينة</label>
                        <input type="text" name="city" value="{{ old('city', $merchant->city) }}" class="input">
                    </div>
                    <div>
                        <label class="label">المحافظة</label>
                        <input type="text" name="governorate" value="{{ old('governorate', $merchant->governorate) }}" class="input">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="tune" />
                    الإعدادات
                </h3>
            </div>
            <div class="card-body space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="label">نسبة العمولة (%)</label>
                        <input type="number" name="commission_rate" value="{{ old('commission_rate', $merchant->commission_rate) }}" step="0.01" min="0" max="100"
                               class="input" dir="ltr">
                    </div>
                    <div>
                        <label class="label">البيئة</label>
                        <select name="environment" class="input">
                            <option value="sandbox" {{ old('environment', $merchant->environment) === 'sandbox' ? 'selected' : '' }}>تجريبي (Sandbox)</option>
                            <option value="production" {{ old('environment', $merchant->environment) === 'production' ? 'selected' : '' }}>إنتاجي (Production)</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $merchant->is_active) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                    <span class="text-sm font-bold" style="color: var(--text-primary)">نشط</span>

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_verified" value="1" {{ old('is_verified', $merchant->is_verified) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                    <span class="text-sm font-bold" style="color: var(--text-primary)">موثق</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <x-heroicon name="note" />
                    ملاحظات
                </h3>
            </div>
            <div class="card-body">
                <textarea name="notes" rows="3"
                          class="input"
                          placeholder="ملاحظات داخلية...">{{ old('notes', $merchant->notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.merchants.show', $merchant) }}" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
                <x-heroicon name="save" />
                حفظ التغييرات
            </button>
        </div>
    </form>
</div>
@endsection
