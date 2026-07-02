@php
    $agents = $agents ?? collect();
    $hasFilters = $hasFilters ?? false;
    $sortField = $sortField ?? 'created_at';
    $sortDir = $sortDir ?? 'desc';
    $showFilters = $showFilters ?? false;
@endphp



<div class="card-sukk-main">
    {{-- ═══ FILTER SECTION (embedded) ═══ --}}
    @if($showFilters)
    <div class="card-body" style="padding-bottom:0;">
        @include('admin.partials._filter_section', [
            'fltId'               => 'adt',
            'fltRoute'            => $filterRoute ?? '#',
            'fltSearchValue'      => $filterSearchValue ?? '',
            'fltSearchPlaceholder'=> $filterSearchPlaceholder ?? 'بحث…',
            'fltHasFilters'       => $filterHasFilters ?? false,
            'fltFilters'          => $filterFilters ?? [],
        ])
    </div>
    @endif

    @if($agents->count() > 0)
        <div class="card-header">
            <div class="card-title" style="font-size:.85rem;">
                <x-heroicon name="storefront" style="color:var(--sukk-primary);" />
                <span>قائمة الوكلاء</span>
            </div>
            <span class="text-xs font-bold" style="color:var(--text-muted);">
                عرض {{ $agents->firstItem() }}–{{ $agents->lastItem() }} من {{ number_format($agents->total()) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-container" style="border:none;border-radius:0;box-shadow:none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>@include('admin.agents._sort', ['key' => 'name', 'label' => 'الوكيل'])</th>
                            <th>@include('admin.agents._sort', ['key' => 'agent_code', 'label' => 'الكود'])</th>
                            <th>@include('admin.agents._sort', ['key' => 'city', 'label' => 'الموقع'])</th>
                            <th>الهاتف</th>
                            <th>الخدمات</th>
                            <th>KYC</th>
                            <th class="text-center">@include('admin.agents._sort', ['key' => 'is_active', 'label' => 'الحالة'])</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agents as $a)
                        <tr>
                            {{-- Name + owner --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="adt-avatar">
                                        <x-heroicon name="store" aria-hidden="true" />
                                    </div>
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.agents.show', $a) }}" class="block font-bold truncate" style="color:var(--text-primary);max-width:220px;">
                                            {{ $a->name }}
                                        </a>
                                        <div class="text-xs truncate" style="color:var(--text-muted);max-width:220px;">
                                            {{ $a->owner_name ?? $a->email ?? '—' }}
                                            @if($a->is_featured)<span style="color:var(--text-muted);"> · </span><span style="color:#b45309;">مميز</span>@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            {{-- Code --}}
                            <td>
                                <span class="font-mono text-xs" style="color:var(--text-secondary);background:var(--surface-hover);padding:var(--space-xs) var(--space-sm);border-radius:var(--radius-sm);border:none;width:fit-content;">
                                    {{ $a->agent_code }}
                                </span>
                            </td>
                            {{-- Location --}}
                            <td>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-semibold" style="color:var(--text-primary);">{{ $a->city }}</span>
                                    @if($a->governorate)<span class="text-xs" style="color:var(--text-muted);">{{ $a->governorate }}</span>@endif
                                </div>
                            </td>
                            {{-- Phone --}}
                            <td class="text-sm" dir="ltr" style="color:var(--text-secondary);">{{ $a->phone ?? '—' }}</td>
                            {{-- Services --}}
                            <td>
                                <div class="flex items-center gap-1 flex-wrap">
                                    @if(in_array('cash_in', $a->services ?? []))<span class="badge badge-success">إيداع</span>@endif
                                    @if(in_array('cash_out', $a->services ?? []))<span class="badge badge-primary">سحب</span>@endif
                                    @if(empty($a->services))<span class="text-xs" style="color:var(--text-muted);">—</span>@endif
                                </div>
                            </td>
                            {{-- KYC --}}
                            <td><span class="badge badge-{{ $a->kyc_status_color ?: 'secondary' }}">{{ $a->kyc_status_label ?: '—' }}</span></td>
                            {{-- Status --}}
                            <td class="text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="adt-status-dot {{ $a->is_active ? 'adt-status-dot-on' : 'adt-status-dot-off' }}" aria-hidden="true"></span>
                                    <span class="text-xs font-bold" style="color:{{ $a->is_active ? 'var(--success)' : 'var(--text-muted)' }};">
                                        {{ $a->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                    @if($a->is_verified)
                                    <x-heroicon name="verified" style="color:var(--sukk-primary);" title="موثّق" aria-label="موثّق" />
                                    @endif
                                </div>
                            </td>
                            {{-- Actions --}}
                            <td>
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.agents.show', $a) }}" class="btn btn-sm btn-sukk-icon" title="عرض" aria-label="عرض الوكيل">
                                        <x-heroicon name="visibility" aria-hidden="true" />
                                    </a>
                                    <a href="{{ route('admin.agents.edit', $a) }}" class="btn btn-sm btn-sukk-icon" title="تعديل" aria-label="تعديل الوكيل">
                                        <x-heroicon name="edit" aria-hidden="true" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.agents.destroy', $a) }}"
                                          onsubmit="return confirm('هل أنت متأكد من حذف الوكيل « {{ $a->name }} »؟')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-sukk-danger btn-sukk-icon" title="حذف" aria-label="حذف الوكيل">
                                            <x-heroicon name="delete" aria-hidden="true" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="table-empty">
                                    <x-heroicon name="storefront" class="table-empty-icon" />
                                    <p class="font-bold" style="color:var(--text-secondary);">
                                        {{ $hasFilters ? 'لا يوجد وكلاء مطابقون للفلتر' : 'لا يوجد وكلاء بعد' }}
                                    </p>
                                    <p class="text-sm mt-1">
                                        {{ $hasFilters ? 'جرّب تعديل معايير البحث' : 'أضف وكيلاً جديداً لبدء إدارة وكلاء السحب والإيداع' }}
                                    </p>
                                    @unless($hasFilters)
                                    <a href="{{ route('admin.agents.create') }}" class="btn btn-primary btn-sm" style="margin-top:var(--space-md);">
                                        <x-heroicon name="add" class="text-sm" aria-hidden="true" />
                                        إضافة وكيل
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
        @if($agents->hasPages())
        <div class="card-footer">
            {{ $agents->withQueryString()->links() }}
        </div>
        @endif
    @else
        <div class="card-body">
            <div class="table-empty">
                <x-heroicon name="storefront" class="table-empty-icon" />
                <p class="text-lg font-bold" style="color:var(--text-secondary);">{{ $hasFilters ? 'لا توجد نتائج مطابقة' : 'لا يوجد وكلاء بعد' }}</p>
                <p class="text-sm mt-1" style="color:var(--text-muted);">{{ $hasFilters ? 'جرّب تعديل معايير التصفية.' : 'أضف وكيلاً جديداً لبدء إدارة وكلاء السحب والإيداع.' }}</p>
                @if($hasFilters)
                <a href="{{ route('admin.agents.index') }}" class="btn btn-secondary mt-4">إعادة تعيين الفلاتر</a>
                @else
                <a href="{{ route('admin.agents.create') }}" class="btn btn-primary mt-4">
                    <x-heroicon name="add" class="text-sm" aria-hidden="true" />
                    إضافة وكيل
                </a>
                @endif
            </div>
        </div>
    @endif
</div>
