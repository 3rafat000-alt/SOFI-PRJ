@extends('admin.layout')
@section('title', 'لوحة التحكم')

@section('content')
    <div class="kpis">
        <div class="kpi"><div class="l">المستخدمون</div><div class="v">{{ number_format($stats['users']) }}</div></div>
        <div class="kpi"><div class="l">المساحات</div><div class="v">{{ number_format($stats['workspaces']) }}</div></div>
        <div class="kpi"><div class="l">المشاريع</div><div class="v">{{ number_format($stats['projects']) }}</div></div>
        <div class="kpi"><div class="l">المهام</div><div class="v">{{ number_format($stats['tasks']) }}</div>
            <div class="d">{{ number_format($stats['done_tasks']) }} منجزة</div></div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <div class="panel">
            <div class="head"><h2>أحدث المستخدمين</h2>
                <a href="{{ route('admin.users.index') }}" class="btn sm">عرض الكل</a></div>
            @forelse ($recentUsers as $u)
                <div style="display:flex; align-items:center; gap:12px; padding:13px 20px; border-bottom:1px solid var(--border)">
                    <div class="tag indigo" style="width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center">{{ mb_substr($u->name,0,1) }}</div>
                    <div style="flex:1"><div style="font-weight:600;font-size:14px">{{ $u->name }}</div>
                        <div style="font-size:12px;color:var(--muted)">{{ $u->email }}</div></div>
                    <div style="font-size:12px;color:var(--muted)">{{ $u->created_at?->diffForHumans() }}</div>
                </div>
            @empty
                <div class="empty">لا يوجد مستخدمون</div>
            @endforelse
        </div>

        <div class="panel">
            <div class="head"><h2>أحدث النشاطات</h2>
                <a href="{{ route('admin.logs.index') }}" class="btn sm">عرض الكل</a></div>
            @forelse ($recentActivity as $log)
                <div style="padding:13px 20px; border-bottom:1px solid var(--border)">
                    <div style="font-size:14px">{{ $log->description }}</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px">
                        {{ $log->user?->name ?? 'النظام' }} · {{ $log->created_at?->diffForHumans() }}</div>
                </div>
            @empty
                <div class="empty">لا توجد نشاطات</div>
            @endforelse
        </div>
    </div>
@endsection
