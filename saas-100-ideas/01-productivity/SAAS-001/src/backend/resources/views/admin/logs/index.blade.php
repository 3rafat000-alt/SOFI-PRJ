@extends('admin.layout')
@section('title', 'السجلات')

@section('content')
    <div class="panel">
        <div class="head">
            <h2>سجل النشاطات ({{ $logs->total() }})</h2>
            <form method="GET" style="display:flex;gap:8px">
                <select name="event" style="padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit">
                    <option value="">كل الأحداث</option>
                    @foreach ($events as $e)
                        <option value="{{ $e }}" @selected($event === $e)>{{ $e }}</option>
                    @endforeach
                </select>
                <button class="btn">تصفية</button>
            </form>
        </div>
        <table>
            <thead><tr><th>الوصف</th><th>الحدث</th><th>المستخدم</th><th>التوقيت</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->description }}</td>
                    <td><span class="tag gray">{{ $log->event }}</span></td>
                    <td style="color:var(--muted)">{{ $log->user?->name ?? 'النظام' }}</td>
                    <td style="color:var(--muted)">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty">لا توجد سجلات</div></td></tr>
            @endforelse
            </tbody>
        </table>
        @include('admin.partials.pagination', ['paginator' => $logs])
    </div>
@endsection
