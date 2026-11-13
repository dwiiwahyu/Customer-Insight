@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Berikut ringkasan kondisi pelanggan dan insight terbaru.</p>

    {{-- ===== 5 kartu statistik ===== --}}
    <section class="stat-grid">
        @foreach ($cards as $card)
            @php
                $change = $card['change'];
                $isGood = $card['higher_is_bad'] ? $change < 0 : $change > 0;

                if ($change == 0) {
                    $cls = 'is-flat';
                    $arrow = '–';
                } else {
                    $cls = $isGood ? 'is-good' : 'is-bad';
                    $arrow = $change > 0 ? '↑' : '↓';
                }
            @endphp

            <div class="stat-card">
                <span class="stat-label">{{ $card['label'] }}</span>
                <strong class="stat-value">{{ number_format($card['value'], 0, ',', '.') }}</strong>
                <span class="stat-change">
                    <b class="{{ $cls }}">{{ $arrow }}{{ number_format(abs($change), 1, ',', '.') }}%</b> dari bulan lalu
                </span>
            </div>
        @endforeach
    </section>

    {{-- ===== Grafik ===== --}}
    <section class="chart-grid">
        <div class="panel">
            <h3 class="panel-title">Tren Perilaku Pembayaran</h3>
            <div class="chart-box">
                <canvas id="trendChart" data-chart="{{ json_encode($trend) }}"></canvas>
            </div>
        </div>

        <div class="panel">
            <h3 class="panel-title">Status Pelanggan</h3>
            <div class="donut-wrap">
                <div class="donut-box">
                    <canvas id="statusChart" data-chart="{{ json_encode($donut) }}"></canvas>
                </div>
                <ul class="donut-legend">
                    <li><i class="dot dot-green"></i>Tepat Waktu</li>
                    <li><i class="dot dot-orange"></i>Terlambat</li>
                    <li><i class="dot dot-red"></i>Tunggakan</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ===== Kotak peringatan & peluang ===== --}}
    <section class="alert-grid">
        <div class="alert-card alert-danger">
            <div class="alert-head">
                <span class="alert-icon icon-danger">!</span>
                <div>
                    <h4>Perlu Perhatian</h4>
                    <p><b>{{ number_format($jumlahTelat, 0, ',', '.') }}</b> pelanggan mengalami keterlambatan pembayaran</p>
                </div>
            </div>
            {{-- filter payment_status dibuat di Langkah 7 --}}
            <a href="{{ route('customers.index', ['payment_status' => 'terlambat']) }}" class="alert-link">Lihat detail..</a>
        </div>

        <div class="alert-card alert-info">
            <div class="alert-head">
                <span class="alert-icon icon-info">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a7 7 0 0 0-4 12.7V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.3A7 7 0 0 0 12 2zM9 20h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-1z"/>
                    </svg>
                </span>
                <div>
                    <h4>Peluang Layanan</h4>
                    <p><b>{{ number_format($jumlahPeluang, 0, ',', '.') }}</b> pelanggan berpotensi upgrade paket</p>
                </div>
            </div>
            <a href="{{ route('insights.index') }}" class="alert-link">Lihat detail..</a>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush