@extends('admin.layout')
@section('title', 'المساحات')

@section('content')
    <div class="panel">
        <div class="head">
            <h2>المساحات ({{ $workspaces->total() }})</h2>
            <form method="GET" style="display:flex;gap:8px">
                <input style="padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit"
                       type="search" name="q" value="{{ $q }}" placeholder="بحث بالاسم">
                <button class="btn">بحث</button>
            </form>
        </div>
        <table>
            <thead><tr><th>المساحة</th><th>المالك</th><th>الخطة</th><th>الأعضاء</th><th>المشاريع</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            @forelse ($workspaces as $w)
                <tr>
                    <td style="font-weight:600">{{ $w->name }}<div style="font-size:12px;color:var(--muted)">{{ $w->slug }}</div></td>
                    <td style="color:var(--muted)">{{ $w->owner?->name ?? '—' }}</td>
                    <td><span class="tag {{ $w->plan === 'pro' ? 'indigo' : 'gray' }}">{{ $w->plan }}</span></td>
                    <td>{{ $w->members_count }}</td>
                    <td>{{ $w->projects_count }}</td>
                    <td style="color:var(--muted)">{{ $w->created_at?->format('Y-m-d') }}</td>
                    <td style="text-align:left"><a class="btn sm" href="{{ route('admin.workspaces.show', $w->id) }}">تفاصيل</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty">لا توجد مساحات</div></td></tr>
            @endforelse
            </tbody>
        </table>
        @include('admin.partials.pagination', ['paginator' => $workspaces])
    </div>
@endsection
