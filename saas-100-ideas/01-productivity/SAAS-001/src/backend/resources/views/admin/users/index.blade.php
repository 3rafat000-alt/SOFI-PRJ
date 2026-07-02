@extends('admin.layout')
@section('title', 'المستخدمون')

@section('content')
    <div class="panel">
        <div class="head">
            <h2>المستخدمون ({{ $users->total() }})</h2>
            <form method="GET" style="display:flex;gap:8px">
                <input class="field" style="margin:0;padding:8px 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit"
                       type="search" name="q" value="{{ $q }}" placeholder="بحث بالاسم أو البريد">
                <button class="btn">بحث</button>
            </form>
        </div>
        <table>
            <thead><tr>
                <th>الاسم</th><th>البريد</th><th>المساحات</th><th>المهام</th><th>الحالة</th><th>التسجيل</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($users as $u)
                <tr>
                    <td style="font-weight:600">{{ $u->name }}</td>
                    <td style="color:var(--muted)">{{ $u->email }}</td>
                    <td>{{ $u->workspaces_count }}</td>
                    <td>{{ $u->created_tasks_count }}</td>
                    <td>
                        @if ($u->trashed())
                            <span class="tag red">معطّل</span>
                        @else
                            <span class="tag green">نشط</span>
                        @endif
                    </td>
                    <td style="color:var(--muted)">{{ $u->created_at?->format('Y-m-d') }}</td>
                    <td style="text-align:left">
                        @if ($u->trashed())
                            <form method="POST" action="{{ route('admin.users.restore', $u->id) }}">@csrf
                                <button class="btn sm">إعادة تفعيل</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}"
                                  onsubmit="return confirm('تعطيل هذا المستخدم؟')">@csrf @method('DELETE')
                                <button class="btn sm danger">تعطيل</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty">لا يوجد مستخدمون</div></td></tr>
            @endforelse
            </tbody>
        </table>
        @include('admin.partials.pagination', ['paginator' => $users])
    </div>
@endsection
