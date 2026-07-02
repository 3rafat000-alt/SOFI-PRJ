@extends('admin.layout')
@section('title', 'تفاصيل المساحة')

@section('content')
    <a href="{{ route('admin.workspaces.index') }}" class="btn sm" style="margin-bottom:18px">→ رجوع للمساحات</a>

    <div class="kpis">
        <div class="kpi"><div class="l">الأعضاء</div><div class="v">{{ $workspace->members_count }}</div></div>
        <div class="kpi"><div class="l">المشاريع</div><div class="v">{{ $workspace->projects_count }}</div></div>
        <div class="kpi"><div class="l">الخطة</div><div class="v" style="font-size:20px;padding-top:8px"><span class="tag {{ $workspace->plan === 'pro' ? 'indigo' : 'gray' }}">{{ $workspace->plan }}</span></div></div>
    </div>

    <div class="panel">
        <div class="head"><h2>{{ $workspace->name }}</h2>
            <span style="font-size:13px;color:var(--muted)">المالك: {{ $workspace->owner?->name }}</span></div>
        <table>
            <thead><tr><th>العضو</th><th>البريد</th><th>الدور</th></tr></thead>
            <tbody>
            @forelse ($workspace->members as $m)
                <tr>
                    <td style="font-weight:600">{{ $m->name }}</td>
                    <td style="color:var(--muted)">{{ $m->email }}</td>
                    <td><span class="tag {{ $m->pivot->role === 'owner' ? 'indigo' : 'gray' }}">{{ $m->pivot->role }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3"><div class="empty">لا يوجد أعضاء</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
