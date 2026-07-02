@extends('layouts.admin')

@section('title', 'لوحة التحكم')
@section('breadcrumbs')
<span class="mx-1">/</span><span>نظرة عامة</span>
@endsection

@php
    $st = $stats ?? [];
    $g  = $growth ?? ['users' => 0, 'transactions' => 0, 'revenue' => 0];
@endphp

@section('topbar-actions')
    @if(($st['pending_kyc'] ?? 0) > 0)
    <div class="nv-action-group">
        <a href="{{ route('admin.users', ['kyc_status' => 'submitted']) }}" class="nv-action-btn nv-action-btn--primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            مراجعة KYC
            <span class="nv-action-badge">{{ $st['pending_kyc'] }}</span>
        </a>
        <span class="nv-action-dot"></span>
    </div>
    @endif
    <a href="{{ route('admin.transactions') }}" class="nv-action-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        المعاملات
    </a>
@endsection

@section('content')
    @include('admin.dashboard.index')
@endsection
