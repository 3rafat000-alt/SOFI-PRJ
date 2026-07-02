@extends('admin.layout')
@section('title', 'المهام')

@php
$statusTag = ['todo' => 'gray', 'in_progress' => 'amber', 'done' => 'green'];
$statusLabel = ['todo' => 'للتنفيذ', 'in_progress' => 'قيد التنفيذ', 'done' => 'منجز'];
$prioTag = ['low' => 'gray', 'medium' => 'indigo', 'high' => 'amber', 'urgent' => 'red'];
@endphp

@section('content')
    <div class="panel">
        <div class="head">
            <h2>المهام ({{ $tasks->total() }})</h2>
            <form method="GET" style="display:flex;gap:8px">
                <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit">
                    <option value="">كل الحالات</option>
                    <option value="todo" @selected($status === 'todo')>للتنفيذ</option>
                    <option value="in_progress" @selected($status === 'in_progress')>قيد التنفيذ</option>
                    <option value="done" @selected($status === 'done')>منجز</option>
                </select>
                <input style="padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit"
                       type="search" name="q" value="{{ $q }}" placeholder="بحث">
                <button class="btn">تطبيق</button>
            </form>
        </div>
        <table>
            <thead><tr><th>المهمة</th><th>المشروع</th><th>المنشئ</th><th>الأولوية</th><th>الحالة</th><th>الاستحقاق</th></tr></thead>
            <tbody>
            @forelse ($tasks as $t)
                <tr>
                    <td style="font-weight:600">{{ $t->title }}</td>
                    <td style="color:var(--muted)">{{ $t->project?->name ?? '—' }}</td>
                    <td style="color:var(--muted)">{{ $t->creator?->name ?? '—' }}</td>
                    <td><span class="tag {{ $prioTag[$t->priority] ?? 'gray' }}">{{ $t->priority }}</span></td>
                    <td><span class="tag {{ $statusTag[$t->status] ?? 'gray' }}">{{ $statusLabel[$t->status] ?? $t->status }}</span></td>
                    <td style="color:var(--muted)">{{ $t->due_date?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty">لا توجد مهام</div></td></tr>
            @endforelse
            </tbody>
        </table>
        @include('admin.partials.pagination', ['paginator' => $tasks])
    </div>
@endsection
