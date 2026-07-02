@extends('layouts.portal')
@section('title','المستندات')
@section('content')

<div class="card">
    <h3 class="sect">رفع مستند</h3>
    <form method="POST" action="{{ route('merchant.documents.upload') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div style="flex:1"><label>نوع المستند</label>
                <select name="document_type">@foreach($documentTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
            </div>
            <div style="flex:1"><label>الملف (PDF/صورة)</label><input type="file" name="file" required></div>
            <div><button class="btn">رفع</button></div>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="sect">المستندات المرفوعة</h3>
    <table>
        <thead><tr><th>المستند</th><th>الحالة</th><th>بتاريخ</th></tr></thead>
        <tbody>
        @forelse($documents as $d)
            <tr><td>{{ $d->type_label }}</td>
                <td><span class="pill {{ $d->status_color === 'success' ? 'ok' : ($d->status_color === 'danger' ? 'danger' : 'warn') }}">{{ $d->status }}</span></td>
                <td class="muted">{{ $d->created_at->format('Y-m-d') }}</td></tr>
        @empty
            <tr><td colspan="3" class="muted">لا مستندات بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
