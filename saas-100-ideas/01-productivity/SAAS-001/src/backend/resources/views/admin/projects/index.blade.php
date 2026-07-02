@extends('admin.layout')
@section('title', 'المشاريع')

@section('content')
    <div class="panel">
        <div class="head">
            <h2>المشاريع ({{ $projects->total() }})</h2>
            <form method="GET" style="display:flex;gap:8px">
                <input style="padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit"
                       type="search" name="q" value="{{ $q }}" placeholder="بحث بالاسم">
                <button class="btn">بحث</button>
            </form>
        </div>
        <table>
            <thead><tr><th>المشروع</th><th>المساحة</th><th>المنشئ</th><th>الحالة</th><th>المهام</th><th>التاريخ</th></tr></thead>
            <tbody>
            @forelse ($projects as $p)
                <tr>
                    <td style="font-weight:600">
                        <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $p->color }};margin-inline-end:6px"></span>
                        {{ $p->name }}
                    </td>
                    <td style="color:var(--muted)">{{ $p->workspace?->name ?? '—' }}</td>
                    <td style="color:var(--muted)">{{ $p->creator?->name ?? '—' }}</td>
                    <td><span class="tag {{ $p->status === 'active' ? 'green' : 'gray' }}">{{ $p->status }}</span></td>
                    <td>{{ $p->tasks_count }}</td>
                    <td style="color:var(--muted)">{{ $p->created_at?->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty">لا توجد مشاريع</div></td></tr>
            @endforelse
            </tbody>
        </table>
        @include('admin.partials.pagination', ['paginator' => $projects])
    </div>
@endsection
