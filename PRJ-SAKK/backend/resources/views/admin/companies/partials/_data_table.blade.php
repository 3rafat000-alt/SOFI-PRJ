@php
    $companies = $companies ?? collect();
    $hasFilters = $hasFilters ?? false;
    $sortField = $sortField ?? 'created_at';
    $sortDir = $sortDir ?? 'desc';
    $showFilters = $showFilters ?? false;
@endphp

@once @endonce

<div class="card-sukk-main">
    {{-- ═══ FILTER SECTION (embedded) ═══ --}}
    @if($showFilters)
    <div class="card-body" style="padding-bottom:0;">
        @include('admin.partials._filter_section', [
            'fltId'               => 'cdt',
            'fltRoute'            => $filterRoute ?? '#',
            'fltSearchValue'      => $filterSearchValue ?? '',
            'fltSearchPlaceholder'=> $filterSearchPlaceholder ?? 'بحث…',
            'fltHasFilters'       => $filterHasFilters ?? false,
            'fltFilters'          => $filterFilters ?? [],
        ])
    </div>
    @endif

    @if($companies->count() > 0)
        <div class="card-header">
            <div class="card-title" style="font-size:.85rem;">
                <x-heroicon name="apartment" style="color:var(--sukk-primary);" />
                <span>قائمة الشركات</span>
            </div>
            <span class="text-xs font-bold" style="color:var(--text-muted);">
                عرض {{ $companies->firstItem() }}–{{ $companies->lastItem() }} من {{ number_format($companies->total()) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-container" style="border:none;border-radius:0;box-shadow:none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>@include('admin.companies._sort', ['key' => 'name', 'label' => 'الشركة'])</th>
                            <th>@include('admin.companies._sort', ['key' => 'company_code', 'label' => 'الكود'])</th>
                            <th>الهاتف</th>
                            <th>الموظفون</th>
                            <th>KYC</th>
                            <th class="text-center">الرواتب</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $co)
                        <tr>
                            {{-- Name + owner --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="cdt-avatar">
                                        <x-heroicon name="apartment" aria-hidden="true" />
                                    </div>
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.companies.show', $co) }}" class="block font-bold truncate" style="color:var(--text-primary);max-width:200px;">
                                            {{ $co->name }}
                                        </a>
                                        <div class="text-xs truncate" style="color:var(--text-muted);max-width:200px;">
                                            {{ $co->owner_name ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            {{-- Code --}}
                            <td>
                                <span class="font-mono text-xs" style="color:var(--text-secondary);background:var(--surface-hover);padding:var(--space-xs) var(--space-sm);border-radius:var(--radius-sm);border:none;width:fit-content;">
                                    {{ $co->company_code }}
                                </span>
                            </td>
                            {{-- Phone --}}
                            <td class="text-sm" dir="ltr" style="color:var(--text-secondary);">{{ $co->phone ?? '—' }}</td>
                            {{-- Employees --}}
                            <td>
                                <span class="text-sm font-bold" style="color:var(--text-primary);">{{ $co->employees_count ?? 0 }}</span>
                                <span class="text-xs" style="color:var(--text-muted);">موظف</span>
                            </td>
                            {{-- KYC --}}
                            <td><span class="badge badge-{{ $co->kyc_status_color ?: 'secondary' }}">{{ $co->kyc_status_label ?: '—' }}</span></td>
                            {{-- Payroll --}}
                            <td class="text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($co->payroll_enabled)
                                    <span class="cdt-status-dot cdt-status-dot-on" aria-hidden="true"></span>
                                    <span class="text-xs font-bold" style="color:var(--success);">مفعّل</span>
                                    @else
                                    <span class="cdt-status-dot cdt-status-dot-off" aria-hidden="true"></span>
                                    <span class="text-xs font-bold" style="color:var(--text-muted);">موقوف</span>
                                    @endif
                                    @if($co->is_verified)
                                    <x-heroicon name="verified" style="color:var(--sukk-primary);" title="موثّقة" aria-label="موثّقة" />
                                    @endif
                                </div>
                            </td>
                            {{-- Actions --}}
                            <td>
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.companies.show', $co) }}" class="btn btn-sm btn-sukk-icon" title="عرض" aria-label="عرض الشركة">
                                        <x-heroicon name="visibility" aria-hidden="true" />
                                    </a>
                                    <a href="{{ route('admin.companies.edit', $co) }}" class="btn btn-sm btn-sukk-icon" title="تعديل" aria-label="تعديل الشركة">
                                        <x-heroicon name="edit" aria-hidden="true" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.companies.destroy', $co) }}"
                                          onsubmit="return confirm('هل أنت متأكد من حذف الشركة « {{ $co->name }} »؟')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-sukk-danger btn-sukk-icon" title="حذف" aria-label="حذف الشركة">
                                            <x-heroicon name="delete" aria-hidden="true" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <x-heroicon name="apartment" class="table-empty-icon" />
                                    <p class="font-bold" style="color:var(--text-secondary);">
                                        {{ $hasFilters ? 'لا توجد شركات مطابقة للفلتر' : 'لا توجد شركات بعد' }}
                                    </p>
                                    <p class="text-sm mt-1">
                                        {{ $hasFilters ? 'جرّب تعديل معايير البحث' : 'أضف شركة جديدة لبدء إدارة توزيع الرواتب' }}
                                    </p>
                                    @unless($hasFilters)
                                    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm" style="margin-top:1rem;">
                                        <x-heroicon name="add" class="text-sm" aria-hidden="true" />
                                        إضافة شركة
                                    </a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($companies->hasPages())
        <div class="card-footer">
            {{ $companies->withQueryString()->links() }}
        </div>
        @endif
    @else
        <div class="card-body">
            <div class="table-empty">
                <x-heroicon name="apartment" class="table-empty-icon" />
                <p class="text-lg font-bold" style="color:var(--text-secondary);">{{ $hasFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد شركات بعد' }}</p>
                <p class="text-sm mt-1" style="color:var(--text-muted);">{{ $hasFilters ? 'جرّب تعديل معايير التصفية.' : 'أضف شركة جديدة لبدء إدارة توزيع الرواتب.' }}</p>
                @if($hasFilters)
                <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary mt-4">إعادة تعيين الفلاتر</a>
                @else
                <a href="{{ route('admin.companies.create') }}" class="btn btn-primary mt-4">
                    <x-heroicon name="add" class="text-sm" aria-hidden="true" />
                    إضافة شركة
                </a>
                @endif
            </div>
        </div>
    @endif
</div>
