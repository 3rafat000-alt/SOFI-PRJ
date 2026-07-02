@extends('layouts.admin')

@section('title', 'تفاصيل النشاط')

@section('breadcrumbs')
<a href="{{ route('admin.audit.index') }}" class="breadcrumb-item">سجل النشاطات</a>
<span class="breadcrumb-item">تفاصيل النشاط</span>
@endsection

@section('content')
<div dir="rtl" style="display:flex;flex-direction:column;gap:var(--sp-6)">

    {{-- ================================================================
         BACK BUTTON
         ================================================================ --}}
    <div>
        <a href="{{ route('admin.audit.index') }}"
           class="btn btn-ghost btn-sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="transform:scaleX(-1)"><polyline points="15 18 9 12 15 6"/></svg>
            العودة إلى سجل النشاطات
        </a>
    </div>

    {{-- ================================================================
         BASIC INFO CARD
         ================================================================ --}}
    @php
        $actionBadge = match ($log->action) {
            'created', 'create', 'store'        => 'success',
            'updated', 'update'                 => 'warning',
            'deleted', 'delete', 'destroy'      => 'danger',
            'login', 'logout'                   => 'slate',
            default                             => 'slate',
        };
        $adminName = $log->user?->first_name
            ? $log->user->first_name . ' ' . ($log->user->last_name ?? '')
            : ($log->user?->email ?? '—');
    @endphp

    <x-admin.card title="معلومات النشاط" icon="info">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4)">
            {{-- Left column --}}
            <div style="display:flex;flex-direction:column;gap:var(--sp-3)">
                {{-- Timestamp --}}
                <div>
                    <p class="label" style="margin-bottom:2px">تاريخ النشاط</p>
                    <p style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary)" dir="ltr">
                        {{ $log->created_at?->format('Y/m/d H:i:s') }}
                    </p>
                    <p style="font-size:var(--font-size-xs);color:var(--text-muted)">
                        {{ $log->created_at?->diffForHumans() }}
                    </p>
                </div>

                {{-- Admin --}}
                <div>
                    <p class="label" style="margin-bottom:2px">المشرف</p>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div aria-hidden="true"
                             style="
                                 width:36px;height:36px;border-radius:50%;flex-shrink:0;
                                 background:linear-gradient(135deg,var(--primary),var(--primary-dark));
                                 display:flex;align-items:center;justify-content:center;
                                 font-weight:600;font-size:14px;
                                 color:#fff;box-shadow:0 0 0 2px var(--border);
                                 user-select:none">
                            {{ $log->user ? mb_strtoupper(mb_substr($log->user->first_name ?? '?', 0, 1)) : '?' }}
                        </div>
                        <div>
                            <p style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin:0">
                                {{ $adminName }}
                            </p>
                            <p style="font-size:var(--font-size-xs);color:var(--text-muted);margin:1px 0 0">
                                {{ $log->user?->email ?? '' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Action --}}
                <div>
                    <p class="label" style="margin-bottom:2px">الإجراء</p>
                    <x-admin.badge :type="$actionBadge" :dot="true" style="font-size:var(--font-size-sm)">
                        {{ $log->action }}
                    </x-admin.badge>
                </div>
            </div>

            {{-- Right column --}}
            <div style="display:flex;flex-direction:column;gap:var(--sp-3)">
                {{-- Model info --}}
                <div>
                    <p class="label" style="margin-bottom:2px">نوع السجل</p>
                    <p style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary)">
                        {{ class_basename($log->model_type ?? '') }}
                    </p>
                    @if($log->model_type)
                        <p style="font-size:var(--text-xs);color:var(--text-muted);margin-top:1px;word-break:break-all">
                            {{ $log->model_type }}
                        </p>
                    @endif
                </div>

                {{-- Model ID --}}
                <div>
                    <p class="label" style="margin-bottom:2px">معرف السجل</p>
                    <p style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary)" dir="ltr">
                        {{ $log->model_id ? '#' . $log->model_id : '—' }}
                    </p>
                </div>

                {{-- Device --}}
                @if($log->device_type)
                <div>
                    <p class="label" style="margin-bottom:2px">نوع الجهاز</p>
                    <p style="font-size:var(--text-sm);color:var(--text-secondary)">
                        {{ $log->device_type }}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </x-admin.card>

    {{-- ================================================================
         REQUEST INFO CARD
         ================================================================ --}}
    <x-admin.card title="معلومات الطلب" icon="globe">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4)">
            {{-- IP Address --}}
            <div>
                <p class="label" style="margin-bottom:2px">عنوان IP</p>
                <p style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary)" dir="ltr">
                    {{ $log->ip_address ?? '—' }}
                </p>
            </div>

            {{-- User Agent --}}
            <div style="grid-column:1/-1">
                <p class="label" style="margin-bottom:2px">وكيل المتصفح (User Agent)</p>
                <p style="font-size:var(--font-size-xs);color:var(--text-secondary);word-break:break-all;direction:ltr;text-align:left;font-family:monospace;background:var(--surface-hover);padding:8px 12px;border-radius:var(--radius-lg);overflow-wrap:break-word">
                    {{ $log->user_agent ?? '—' }}
                </p>
            </div>
        </div>
    </x-admin.card>

    {{-- ================================================================
         OLD / NEW VALUES DIFF CARD
         ================================================================ --}}
    @if(!empty($oldValues) || !empty($newValues))
    <x-admin.card title="تغييرات البيانات" icon="compare_arrows">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4)">
            {{-- Old values --}}
            <div>
                <p class="label" style="margin-bottom:8px;color:var(--text-muted)">القيم القديمة</p>
                @if(!empty($oldValues))
                    @foreach($oldValues as $key => $value)
                    <div style="margin-bottom:6px;padding:6px 10px;background:var(--danger-light);border-radius:var(--radius-md);border-inline-start:3px solid var(--danger)">
                        <p style="font-size:var(--text-xs);color:var(--text-muted);margin:0;direction:ltr;text-align:left;font-family:monospace">{{ $key }}</p>
                        <p style="font-size:var(--text-sm);color:var(--text-primary);margin:2px 0 0;word-break:break-word;direction:ltr;text-align:left;font-family:monospace">
                            {{ is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value ?? '—') }}
                        </p>
                    </div>
                    @endforeach
                @else
                    <p style="font-size:var(--text-sm);color:var(--text-muted);font-style:italic">لا توجد قيم قديمة</p>
                @endif
            </div>

            {{-- New values --}}
            <div>
                <p class="label" style="margin-bottom:8px;color:var(--text-muted)">القيم الجديدة</p>
                @if(!empty($newValues))
                    @foreach($newValues as $key => $value)
                    <div style="margin-bottom:6px;padding:6px 10px;background:var(--success-light);border-radius:var(--radius-md);border-inline-start:3px solid var(--success)">
                        <p style="font-size:var(--text-xs);color:var(--text-muted);margin:0;direction:ltr;text-align:left;font-family:monospace">{{ $key }}</p>
                        <p style="font-size:var(--text-sm);color:var(--text-primary);margin:2px 0 0;word-break:break-word;direction:ltr;text-align:left;font-family:monospace">
                            {{ is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value ?? '—') }}
                        </p>
                    </div>
                    @endforeach
                @else
                    <p style="font-size:var(--text-sm);color:var(--text-muted);font-style:italic">لا توجد قيم جديدة</p>
                @endif
            </div>
        </div>
    </x-admin.card>
    @endif

    {{-- ================================================================
         METADATA CARD
         ================================================================ --}}
    @if(!empty($metadata))
    <x-admin.card title="بيانات إضافية" icon="data_usage">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-3)">
            @foreach($metadata as $key => $value)
            <div style="padding:8px 12px;background:var(--surface-hover);border-radius:var(--radius-lg)">
                <p style="font-size:var(--text-xs);color:var(--text-muted);margin:0;direction:ltr;text-align:left;font-family:monospace">{{ $key }}</p>
                <p style="font-size:var(--text-sm);color:var(--text-primary);margin:4px 0 0;word-break:break-word;direction:ltr;text-align:left;font-family:monospace">
                    {{ is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ($value ?? '—') }}
                </p>
            </div>
            @endforeach
        </div>
    </x-admin.card>
    @endif

    {{-- ================================================================
         RAW DATA CARD — fallback if old/new not shown above but exist
         ================================================================ --}}
    @if(empty($oldValues) && empty($newValues) && ($log->old_values || $log->new_values))
    <x-admin.card title="البيانات الخام" icon="code">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4)">
            @if($log->old_values)
            <div>
                <p class="label" style="margin-bottom:6px">old_values (JSON)</p>
                <pre style="direction:ltr;text-align:left;font-family:monospace;font-size:var(--font-size-xs);background:var(--surface-hover);padding:12px;border-radius:var(--radius-lg);overflow-x:auto;white-space:pre-wrap;word-break:break-word;color:var(--text-secondary)">{{ json_encode($log->old_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
            @if($log->new_values)
            <div>
                <p class="label" style="margin-bottom:6px">new_values (JSON)</p>
                <pre style="direction:ltr;text-align:left;font-family:monospace;font-size:var(--font-size-xs);background:var(--surface-hover);padding:12px;border-radius:var(--radius-lg);overflow-x:auto;white-space:pre-wrap;word-break:break-word;color:var(--text-secondary)">{{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
        </div>
    </x-admin.card>
    @endif

</div>
@endsection
