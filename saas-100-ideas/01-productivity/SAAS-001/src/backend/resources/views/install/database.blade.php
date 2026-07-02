@extends('install.layout')

@section('content')
    <h2>قاعدة البيانات</h2>
    <p class="sub">سيتم تشغيل التهجير (migrations) على الاتصال المختار.</p>

    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('install.database.run') }}">
        @csrf

        <div class="field">
            <label>نوع الاتصال</label>
            <select name="connection" id="connection" onchange="toggleFields()">
                <option value="sqlite" @selected($current['connection'] === 'sqlite')>SQLite (موصى به للتجربة)</option>
                <option value="mysql" @selected($current['connection'] === 'mysql')>MySQL</option>
                <option value="pgsql" @selected($current['connection'] === 'pgsql')>PostgreSQL</option>
            </select>
            <p class="hint" style="margin-top:6px">للاتصالات الأخرى عدّل ملف .env ثم اختر النوع هنا لتشغيل التهجير.</p>
        </div>

        <div class="field" style="display:flex; align-items:center; gap:8px">
            <input type="checkbox" name="seed" value="1" id="seed" checked style="width:auto">
            <label for="seed" style="margin:0">تعبئة بيانات تجريبية (مساحة عمل + مهام)</label>
        </div>

        <div class="row">
            <a href="{{ route('install.index') }}" class="btn ghost">رجوع</a>
            <button type="submit" class="btn">تشغيل التهجير والمتابعة</button>
        </div>
    </form>
@endsection
