@extends('layouts.app')

@section('title', 'Detail Pelanggan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}">
@endpush

@php
    // Helper kecil yang juga terbaca oleh file tab (@include)
    $statusBayar = [
        'tepat_waktu' => ['Tepat waktu', 'badge-green', 'green'],
        'terlambat'   => ['Terlambat',   'badge-yellow', 'orange'],
        'tunggakan'   => ['Tunggakan',   'badge-orange', 'red'],
    ];
    $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $tgl    = fn ($d) => $d ? $d->locale('id')->translatedFormat('j F Y') : '-';

    $skor = $customer->health_score;
    [$skorLabel, $skorClass] = $skor >= 80 ? ['Good', 'good'] : ($skor >= 60 ? ['Fair', 'fair'] : ['Poor', 'poor']);
@endphp

@section('content')
    <a href="{{ route('customers.index') }}" class="btn-back" data-back>← Kembali</a>
    <h1 class="page-title">Detail Pelanggan</h1>

    {{-- ===== Header profil + health score ===== --}}
    <div class="profile-row">
        <div class="profile-card">
            <div class="avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
            <div>
                <div class="profile-name">
                    {{ $customer->name }}
                    <span class="badge {{ $customer->status === 'aktif' ? 'badge-green' : 'badge-red' }}">
                        {{ ucfirst($customer->status) }}
                    </span>
                </div>
                <div class="profile-meta">
                    <span>#{{ $customer->customer_code }}</span><span>{{ $customer->internet_number }}</span><span>{{ $customer->phone }}</span>
                </div>
            </div>
        </div>

        <div class="score-card">
            <span>Customer<br>Health<br>Score</span>
            <div class="score-ring sm {{ $skorClass }}" style="--score: {{ $skor }}">
                <div class="score-ring-inner">
                    <strong>{{ $skor }}</strong>
                    <small>{{ $skorLabel }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Tab ===== --}}
    <nav class="tabs">
        <button type="button" class="tab" data-tab="overview">Overview</button>
        <button type="button" class="tab" data-tab="pembayaran">Pembayaran</button>
        <button type="button" class="tab" data-tab="insight">Insight</button>
    </nav>

    <section class="tab-panel" id="tab-overview">@include('customers.tabs.overview')</section>
    <section class="tab-panel" id="tab-pembayaran">@include('customers.tabs.pembayaran')</section>
    <section class="tab-panel" id="tab-insight">@include('customers.tabs.insight')</section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/customer-detail.js') }}"></script>
@endpush