@extends('layouts.admin')

@section('title', 'قنوات الإشعارات')
@section('breadcrumbs')
<span class="breadcrumb-item">إعدادات النظام</span>
<span class="breadcrumb-item">قنوات الإشعارات</span>
@endsection

@include('admin.system._shell')

{{-- Styles moved to base.css --}}

@php
    $recipientLabels = ['admin' => 'المدير', 'customer' => 'المستخدم', 'merchant' => 'التاجر', 'agent' => 'الوكيل'];
    $recipientIcons = ['admin' => 'admin_panel_settings', 'customer' => 'person', 'merchant' => 'storefront', 'agent' => 'support_agent'];
    $cols = [
        'via_email' => ['بريد', 'mail'], 'via_sms' => ['SMS', 'sms'],
        'via_push' => ['إشعار فوري', 'notifications_active'], 'via_in_app' => ['داخل التطبيق', 'phone_iphone'],
    ];
    $totalEvents = $channels->count();
@endphp

@section('content')
<form method="POST" action="{{ route('admin.system.channels.update') }}">
    @csrf @method('PUT')

    <div class="sys-head">
        <div class="sys-head-ico"><x-heroicon name="notifications_active" /></div>
        <div class="sys-head-txt">
            <h1>قنوات الإشعارات</h1>
            <p>حدّد كيف يصل كل تنبيه لكل طرف: بريد إلكتروني، رسالة نصية، إشعار فوري، أو داخل التطبيق. أوقف العمود الأخير لتعطيل الحدث بالكامل.</p>
        </div>
        <div class="sys-head-actions">
            <div class="sys-head-stat"><div class="n">{{ $totalEvents }}</div><div class="l">حدث</div></div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        <div class="card-body">
            <div class="ch-legend">
                <span class="item"><x-heroicon name="mail" /> البريد الإلكتروني</span>
                <span class="item"><x-heroicon name="sms" /> رسالة نصية SMS</span>
                <span class="item"><x-heroicon name="notifications_active" /> إشعار فوري Push</span>
                <span class="item"><x-heroicon name="phone_iphone" /> داخل التطبيق</span>
                <span class="item"><x-heroicon name="power_settings_new" /> مفتاح التفعيل العام للحدث</span>
            </div>
        </div>
    </div>

    @forelse($channels as $eventKey => $rows)
    <div class="card" style="margin-bottom:1.1rem">
        <div class="gw-head">
            <div class="gw-logo" style="--brand-soft:var(--accent-soft);--brand:var(--accent)"><x-heroicon name="campaign" /></div>
            <div class="meta">
                <div class="nm">{{ $rows->first()->event_label_ar }}</div>
                <div class="ds" style="direction:ltr;text-align:start">{{ $eventKey }}</div>
            </div>
        </div>
        <div class="table-container" style="border:none">
            <table class="ch-matrix">
                <thead>
                    <tr>
                        <th class="ch-recipient">الطرف المستلِم</th>
                        @foreach($cols as [$lbl, $ico])
                            <th><x-heroicon :name="$ico" />{{ $lbl }}</th>
                        @endforeach
                        <th><x-heroicon name="power_settings_new" />مفعّل</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    <tr>
                        <td class="ch-recipient">
                            <x-heroicon :name="$recipientIcons[$row->recipient] ?? 'person'" />
                            {{ $recipientLabels[$row->recipient] ?? $row->recipient }}
                        </td>
                        @foreach(array_keys($cols) as $flag)
                        <td>
                            <label class="switch sm">
                                <input type="hidden" name="channels[{{ $row->id }}][{{ $flag }}]" value="0">
                                <input type="checkbox" name="channels[{{ $row->id }}][{{ $flag }}]" value="1" {{ $row->$flag ? 'checked' : '' }}>
                                <span class="switch-track"></span><span class="switch-thumb"></span>
                            </label>
                        </td>
                        @endforeach
                        <td>
                            <label class="switch sm">
                                <input type="hidden" name="channels[{{ $row->id }}][is_active]" value="0">
                                <input type="checkbox" name="channels[{{ $row->id }}][is_active]" value="1" {{ $row->is_active ? 'checked' : '' }}>
                                <span class="switch-track"></span><span class="switch-thumb"></span>
                            </label>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="card"><div class="table-empty">
        <x-heroicon name="notifications_off" class="table-empty-icon" />
        لا توجد أحداث إشعارات بعد. شغّل <code>php artisan db:seed --class=SystemConfigSeeder</code>.
    </div></div>
    @endforelse

    @if($totalEvents)
    <div class="save-bar">
        <span class="hint"><x-heroicon name="info" class="icon-sm" style="vertical-align:middle" /> يُطبَّق التغيير على كل الأطراف فور الحفظ.</span>
        <button type="submit" class="btn btn-primary"><x-heroicon name="save" class="icon-sm" /> حفظ كل القنوات</button>
    </div>
    @endif
</form>
@endsection
