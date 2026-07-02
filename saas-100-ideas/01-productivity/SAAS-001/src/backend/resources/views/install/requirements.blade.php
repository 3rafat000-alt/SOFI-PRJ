@extends('install.layout')

@section('content')
    <h2>متطلبات النظام</h2>
    <p class="sub">نتحقق من بيئة الخادم قبل المتابعة.</p>

    @foreach ($checks as $c)
        <div class="check">
            <span class="lbl">{{ $c['label'] }}</span>
            @if ($c['passed'])
                <span class="badge ok">✓ متوفر</span>
            @else
                <span class="badge err">✕ {{ $c['hint'] }}</span>
            @endif
        </div>
    @endforeach

    <div class="row">
        @if ($canProceed)
            <a href="{{ route('install.database') }}" class="btn">التالي — قاعدة البيانات</a>
        @else
            <button class="btn" disabled>عالج المتطلبات الناقصة أولاً</button>
        @endif
    </div>
@endsection
