@php
    $doc = $doc ?? null;
    $entity = $entity ?? null;
    $domain = 'companies';
    if (!$doc) return;
    $expired  = $doc->expiry_date && $doc->expiry_date->isPast();
    $expiring = $doc->expiry_date && !$expired && $doc->expiry_date->diffInDays(now()) <= 30;
@endphp

@once @endonce

<div class="cmpdvw-s">
    {{-- LEFT: Document Preview --}}
    <div class="cmpdvw-p">
        @if($doc->file_path)
            @php
                $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                $url = route('admin.secure-file', ['path' => encrypt($doc->file_path)]);
            @endphp
            @if(in_array($ext, ['jpg','jpeg','png','gif','webp','svg']))
                <img src="{{ $url }}" alt="{{ $doc->type_label }}" loading="lazy" style="max-height:500px;">
            @elseif(in_array($ext, ['pdf']))
                <iframe src="{{ $url }}#toolbar=0" style="border:none;" title="{{ $doc->type_label }}"></iframe>
            @else
                <div class="cmpdvw-ph">
                    <x-heroicon name="insert_drive_file" />
                    <p style="font-size:.85rem;font-weight:700;">{{ $doc->file_name ?? 'ملف' }}</p>
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                        <x-heroicon name="download" class="text-sm" aria-hidden="true" />
                        تحميل الملف
                    </a>
                </div>
            @endif
        @else
            <div class="cmpdvw-ph">
                <x-heroicon name="description" />
                <p style="font-size:.85rem;font-weight:700;">لا يوجد ملف مرفوع</p>
            </div>
        @endif
    </div>

    {{-- RIGHT: Metadata + Checklist + Actions --}}
    <div class="cmpdvw-m">
        <div class="cmpdvw-c">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                <h3 class="font-extrabold" style="color:var(--text-primary);font-size:.9rem;">{{ $doc->type_label }}</h3>
                <span class="badge badge-{{ $doc->status_color }}">
                    {{ $doc->status === 'pending' ? 'قيد المراجعة' : ($doc->status === 'approved' ? 'معتمد' : 'مرفوض') }}
                </span>
            </div>
            <div>
                <div class="cmpdvw-r">
                    <span class="cmpdvw-l">رقم المستند</span>
                    <span class="cmpdvw-v">{{ $doc->document_number ?? '—' }}</span>
                </div>
                <div class="cmpdvw-r">
                    <span class="cmpdvw-l">جهة الإصدار</span>
                    <span class="cmpdvw-v">{{ $doc->issuing_authority ?? '—' }}</span>
                </div>
                <div class="cmpdvw-r">
                    <span class="cmpdvw-l">تاريخ الإصدار</span>
                    <span class="cmpdvw-v">{{ $doc->issue_date?->format('Y/m/d') ?? '—' }}</span>
                </div>
                <div class="cmpdvw-r">
                    <span class="cmpdvw-l">تاريخ الانتهاء</span>
                    <span class="cmpdvw-v">
                        @if($doc->expiry_date)
                            <span class="badge {{ $expired ? 'badge-danger' : ($expiring ? 'badge-warning' : 'badge-secondary') }}" dir="ltr">
                                {{ $doc->expiry_date->format('Y/m/d') }}
                                {{ $expired ? '· منتهٍ' : ($expiring ? '· قريباً' : '') }}
                            </span>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="cmpdvw-r">
                    <span class="cmpdvw-l">حجم الملف</span>
                    <span class="cmpdvw-v">{{ $doc->file_size ? number_format($doc->file_size / 1024, 1) . ' KB' : '—' }}</span>
                </div>
                <div class="cmpdvw-r">
                    <span class="cmpdvw-l">تاريخ الرفع</span>
                    <span class="cmpdvw-v">{{ $doc->created_at->format('Y/m/d · H:i') }}</span>
                </div>
            </div>
            @if($doc->rejection_reason)
            <div style="margin-top:0.75rem;padding:0.75rem 1rem;border-radius:var(--radius-sm);background:var(--danger-light);">
                <p style="font-size:.72rem;font-weight:600;color:var(--danger);margin-bottom:0.2rem;">سبب الرفض:</p>
                <p style="font-size:.78rem;color:var(--danger);">{{ $doc->rejection_reason }}</p>
            </div>
            @endif
        </div>

        <div class="cmpdvw-c">
            <h4 style="font-size:.8rem;font-weight:800;color:var(--text-primary);margin-bottom:0.6rem;">
                <x-heroicon name="checklist" style="font-size:.95rem;vertical-align:middle;margin-left:0.25rem;" />
                قائمة التحقق
            </h4>
            <ul class="cmpdvw-cl">
                <li class="{{ $doc->document_number ? 'is-checked' : '' }}">
                    <x-heroicon name="check_circle" x-show="$doc->document_number" />
<x-heroicon name="radio_button_unchecked" x-show="!($doc->document_number)" />
                    رقم المستند موجود وصحيح
                </li>
                <li class="{{ $doc->issue_date ? 'is-checked' : '' }}">
                    <x-heroicon name="check_circle" x-show="$doc->issue_date" />
<x-heroicon name="radio_button_unchecked" x-show="!($doc->issue_date)" />
                    تاريخ الإصدار مدون
                </li>
                <li class="{{ ($doc->expiry_date && !$expired) ? 'is-checked' : '' }}">
                    <x-heroicon name="check_circle" x-show="($doc->expiry_date && !$expired)" />
<x-heroicon name="radio_button_unchecked" x-show="!(($doc->expiry_date && !$expired))" />
                    {{ $doc->expiry_date ? ($expired ? 'منتهي الصلاحية' : 'ساري المفعول') : 'غير محدد' }}
                </li>
                <li class="{{ $doc->issuing_authority ? 'is-checked' : '' }}">
                    <x-heroicon name="check_circle" x-show="$doc->issuing_authority" />
<x-heroicon name="radio_button_unchecked" x-show="!($doc->issuing_authority)" />
                    جهة الإصدار موثوقة
                </li>
                <li class="{{ $doc->file_path ? 'is-checked' : '' }}">
                    <x-heroicon name="check_circle" x-show="$doc->file_path" />
<x-heroicon name="radio_button_unchecked" x-show="!($doc->file_path)" />
                    الملف مرفوع وقابل للقراءة
                </li>
            </ul>
        </div>

        @if($doc->status === 'pending')
        <div class="cmpdvw-actions">
            <form method="POST" action="{{ route('admin.' . $domain . '.documents.approve', $doc) }}"
                  onsubmit="return confirm('اعتماد مستند « {{ $doc->type_label }} »؟')">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <x-heroicon name="check" class="text-sm" aria-hidden="true" />
                    اعتماد
                </button>
            </form>
            <button type="button" class="btn btn-danger btn-sm"
                    @click="$dispatch('open-doc-reject', {
                        rejectUrl: @js(route('admin.' . $domain . '.documents.reject', $doc)),
                        merchantName: @js($entity?->store_name ?? $entity?->name ?? ''),
                        docType: @js($doc->type_label)
                    })">
                <x-heroicon name="close" class="text-sm" aria-hidden="true" />
                رفض
            </button>
        </div>
        @endif

        @if($doc->verified_at)
        <p style="font-size:.65rem;color:var(--text-muted);text-align:center;margin-top:var(--space-xs);" dir="ltr">
            تمت المراجعة: {{ $doc->verified_at->format('Y/m/d H:i') }}
        </p>
        @endif

        @if($doc->file_path)
        <a href="{{ route('admin.secure-file', ['path' => encrypt($doc->file_path)]) }}" target="_blank" rel="noopener"
           class="btn btn-secondary btn-sm" style="align-self:flex-start;">
            <x-heroicon name="open_in_new" class="text-sm" aria-hidden="true" />
            فتح في نافذة جديدة
        </a>
        @endif
    </div>
</div>
