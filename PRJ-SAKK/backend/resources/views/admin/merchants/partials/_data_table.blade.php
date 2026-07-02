@php
    use App\Models\Merchant;
    $merchants = $merchants ?? collect();
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
            'fltId'               => 'mdt',
            'fltRoute'            => $filterRoute ?? '#',
            'fltSearchValue'      => $filterSearchValue ?? '',
            'fltSearchPlaceholder'=> $filterSearchPlaceholder ?? 'بحث…',
            'fltHasFilters'       => $filterHasFilters ?? false,
            'fltFilters'          => $filterFilters ?? [],
        ])
    </div>
    @endif

    @if($merchants->count() > 0)
        <div class="card-header">
            <div class="card-title" style="font-size:.85rem;">
                <x-heroicon name="storefront" style="color:var(--sukk-primary);" />
                <span>قائمة التجار</span>
            </div>
            <span class="text-xs font-bold" style="color:var(--text-muted);">
                عرض {{ $merchants->firstItem() }}–{{ $merchants->lastItem() }} من {{ number_format($merchants->total()) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-container" style="border:none;border-radius:0;box-shadow:none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>@include('admin.merchants._sort', ['key' => 'store_name', 'label' => 'المتجر'])</th>
                            <th>@include('admin.merchants._sort', ['key' => 'merchant_code', 'label' => 'الكود'])</th>
                            <th>@include('admin.merchants._sort', ['key' => 'type', 'label' => 'النوع'])</th>
                            <th>KYC</th>
                            <th class="text-center">@include('admin.merchants._sort', ['key' => 'is_active', 'label' => 'الربط'])</th>
                            <th class="text-left">@include('admin.merchants._sort', ['key' => 'balance', 'label' => 'الرصيد'])</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($merchants as $m)
                        <tr>
                            {{-- Name + owner --}}
                            <td>
                                <div class="flex items-center" style="gap:var(--space-md);">
                                    <div class="mdt-avatar">
                                        <x-heroicon name="store" aria-hidden="true" />
                                    </div>
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.merchants.show', $m) }}" class="block font-bold truncate" style="color:var(--text-primary);max-width:220px;">
                                            {{ $m->store_name }}
                                        </a>
                                        <div class="text-xs truncate" style="color:var(--text-muted);max-width:220px;">
                                            {{ $m->owner_name ?? $m->email ?? '—' }}
                                            @if($m->city)<span style="color:var(--border-strong);"> · </span>{{ $m->city }}@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            {{-- Code + environment --}}
                            <td>
                                <div class="flex flex-col gap-1">
                                    <span class="font-mono text-xs" style="color:var(--text-secondary);background:var(--surface-hover);padding:var(--space-xs) var(--space-sm);border-radius:var(--radius-sm);border:none;width:fit-content;">
                                        {{ $m->merchant_code }}
                                    </span>
                                    <span class="badge {{ $m->environment === 'production' ? 'badge-primary' : 'badge-secondary' }}" style="width:fit-content;">
                                        <x-heroicon name="rocket_launch" aria-hidden="true" x-show="$m->environment === 'production'" />
<x-heroicon name="science" aria-hidden="true" x-show="!($m->environment === 'production')" />
                                        {{ $m->environment === 'production' ? 'إنتاج' : 'تجريبي' }}
                                    </span>
                                </div>
                            </td>
                            {{-- Type --}}
                            <td>
                                @php
                                    $tc = $m->type === 'ecommerce' ? 'badge-primary' : 'badge-secondary';
                                    $ti = $m->type === 'ecommerce' ? 'language' : ($m->type === 'both' ? 'sync_alt' : 'storefront');
                                @endphp
                                <span class="badge {{ $tc }}">
                                    <x-heroicon :name="$ti" aria-hidden="true" />
                                    {{ $m->typeLabel() }}
                                </span>
                            </td>
                            {{-- KYC --}}
                            <td><span class="badge badge-{{ $m->kyc_status_color }}">{{ $m->kyc_status_label ?: '—' }}</span></td>
                            {{-- Connection --}}
                            <td class="text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="mdt-status-dot {{ $m->is_active ? 'mdt-status-dot-on' : 'mdt-status-dot-off' }}" aria-hidden="true"></span>
                                    <span class="text-xs font-bold" style="color:{{ $m->is_active ? 'var(--success)' : 'var(--text-muted)' }};">
                                        {{ $m->is_active ? 'متصل' : 'غير متصل' }}
                                    </span>
                                    @if($m->is_verified)
                                    <x-heroicon name="verified" style="color:var(--sukk-primary);" title="موثّق" aria-label="موثّق" />
                                    @endif
                                </div>
                            </td>
                            {{-- Balance + earnings --}}
                            <td class="text-left">
                                <div class="font-extrabold" style="color:var(--text-primary);" dir="ltr">&lrm;${{ number_format($m->balance, 2) }}</div>
                                <div class="text-xs" style="color:var(--text-muted);" dir="ltr" title="إجمالي الأرباح">↑ &lrm;${{ number_format($m->total_earned, 2) }}</div>
                            </td>
                            {{-- Actions --}}
                            <td>
                                <div class="flex items-center justify-center" style="gap:var(--space-sm);">
                                    <a href="{{ route('admin.merchants.show', $m) }}" class="btn btn-sm btn-sukk-icon" title="عرض" aria-label="عرض التاجر">
                                        <x-heroicon name="visibility" aria-hidden="true" />
                                    </a>
                                    <a href="{{ route('admin.merchants.dashboard', $m) }}" class="btn btn-sm btn-sukk-icon" title="لوحة التاجر" aria-label="لوحة التاجر">
                                        <x-heroicon name="insights" aria-hidden="true" />
                                    </a>
                                    <a href="{{ route('admin.merchants.edit', $m) }}" class="btn btn-sm btn-sukk-icon" title="تعديل" aria-label="تعديل التاجر">
                                        <x-heroicon name="edit" aria-hidden="true" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.merchants.destroy', $m) }}"
                                          onsubmit="return confirm('هل أنت متأكد من حذف التاجر « {{ $m->store_name }} »؟')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-sukk-danger btn-sukk-icon" title="حذف" aria-label="حذف التاجر">
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
                                    <x-heroicon name="storefront" class="table-empty-icon" />
                                    <p class="font-bold" style="color:var(--text-secondary);">
                                        {{ $hasFilters ? 'لا يوجد تجار مطابقون للفلتر' : 'لا يوجد تجار بعد' }}
                                    </p>
                                    <p class="text-sm mt-1">
                                        {{ $hasFilters ? 'جرّب تعديل معايير البحث' : 'أضف تاجراً جديداً لبدء نظام الدفع عبر API' }}
                                    </p>
                                    @unless($hasFilters)
                                    <a href="{{ route('admin.merchants.create') }}" class="btn btn-primary btn-sm" style="margin-top:var(--space-md);">
                                        <x-heroicon name="add" class="text-sm" aria-hidden="true" />
                                        إضافة تاجر
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
        @if($merchants->hasPages())
        <div class="card-footer">
            {{ $merchants->withQueryString()->links() }}
        </div>
        @endif
    @else
        <div class="card-body">
            <div class="table-empty">
                <x-heroicon name="storefront" class="table-empty-icon" />
                <p class="text-lg font-bold" style="color:var(--text-secondary);">{{ $hasFilters ? 'لا توجد نتائج مطابقة' : 'لا يوجد تجار بعد' }}</p>
                <p class="text-sm mt-1" style="color:var(--text-muted);">{{ $hasFilters ? 'جرّب تعديل معايير التصفية.' : 'أضف تاجراً جديداً لبدء نظام الدفع عبر API' }}</p>
                @if($hasFilters)
                <a href="{{ route('admin.merchants.index') }}" class="btn btn-secondary mt-4">إعادة تعيين الفلاتر</a>
                @else
                <a href="{{ route('admin.merchants.create') }}" class="btn btn-primary mt-4">
                    <x-heroicon name="add" class="text-sm" aria-hidden="true" />
                    إضافة تاجر
                </a>
                @endif
            </div>
        </div>
    @endif
</div>
