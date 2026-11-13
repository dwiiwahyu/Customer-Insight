@extends('layouts.app')

@section('title', 'Insight & Rekomendasi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insight-overview.css') }}">
@endpush

@php
    $nf = fn ($n) => number_format($n, 0, ',', '.');
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    // 3 rekomendasi utama (angka dari InsightService)
    $texts = [
        'tunggakan'  => 'Terdeteksi ' . $nf($summary['unpaid_customers']) . ' pelanggan dengan tagihan belum dibayar senilai ' . $rp($summary['unpaid_amount']) . '.',
        'upgrade'    => $nf($summary['upgrade']) . ' pelanggan menunjukkan pola pemakaian tinggi yang cocok untuk paket lebih tinggi.',
        'usage_drop' => 'Terdapat ' . $nf($summary['usage_drop']) . ' pelanggan aktif dengan penurunan pemakaian selama 2 bulan terakhir.',
    ];
@endphp

@section('content')
    {{-- ===== Header ===== --}}
    <div class="ov-header">
        <div>
            <h1 class="page-title">Insight &amp; Rekomendasi</h1>
            <p class="page-subtitle">Berikut ringkasan insight dan rekomendasi.</p>
        </div>

        @if ($periode)
            <div class="ov-date" title="Rentang tanggal tagihan pada data pembayaran">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M7 2v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7zm-2 8h14v10H5V10z"/>
                </svg>
                Data: {{ $periode }}
            </div>
        @endif
    </div>

    {{-- ===== 4 kartu ringkasan ===== --}}
    <section class="ov-stats">
        <div class="ov-stat">
            <span class="ov-stat-title">Resiko Tinggi</span>
            <strong>{{ $nf($summary['churn_high']) }}</strong>
            <small>pelanggan beresiko tinggi</small>
        </div>

        <div class="ov-stat">
            <span class="ov-stat-title">Perlu Perhatian</span>
            <strong>{{ $nf($summary['churn_mid']) }}</strong>
            <small>pelanggan perlu perhatian</small>
        </div>

        <div class="ov-stat">
            <span class="ov-stat-title">Peluang</span>
            <strong>{{ $nf($summary['upgrade']) }}</strong>
            <small>pelanggan berpotensi upgrade</small>
        </div>

        <div class="ov-stat">
            <span class="ov-stat-title">Tren Positif</span>
            <strong>{{ $nf($summary['positive']) }}</strong>
            <small>pelanggan membayar tepat waktu</small>
        </div>
    </section>

    {{-- ===== Rekomendasi Utama + Insight Cepat ===== --}}
    <section class="ov-main">
        <div class="ov-panel">
            <h2 class="ov-panel-title">Rekomendasi Utama</h2>

            <ul class="ov-reco-list">
                @foreach ($texts as $key => $text)
                    @php $reco = $recommendations[$key]; @endphp
                    <li class="ov-reco">
                        <div class="ov-reco-icon ov-icon-{{ $reco['tone'] }}">
                            @include('insights.partials.icon', ['tone' => $reco['tone']])
                        </div>

                        <div class="ov-reco-body">
                            <h3>{{ $reco['title'] }}</h3>
                            <p>{{ $reco['count'] > 0 ? $text : 'Belum ada pelanggan yang terdeteksi.' }}</p>
                        </div>

                        @if ($reco['count'] > 0)
                            <a href="{{ route('insights.detail', $key) }}" class="ov-btn ov-btn-sm">Lihat detail</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="ov-panel ov-quick">
            <h2 class="ov-panel-title">Insight Cepat</h2>

            <div class="ov-quick-art" aria-hidden="true">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="#ee2d24">
                    <path d="M6 2h8l6 6v14H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm7 1.5V9h5.5L13 3.5z" opacity=".95"/>
                    <path d="M10.5 11l.9 2.3 2.3.9-2.3.9-.9 2.3-.9-2.3-2.3-.9 2.3-.9.9-2.3z" fill="#fff"/>
                </svg>
            </div>

            <p class="ov-quick-text">
                @if ($summary['churn_high'] > 0)
                    Fokus pada <strong>{{ $nf($summary['churn_high']) }} pelanggan beresiko tinggi</strong>
                    (punya 2 tagihan atau lebih yang belum dibayar, atau berstatus nonaktif).
                    @if ($summary['high_unpaid_amount'] > 0)
                        Tagihan belum dibayar dari kelompok ini: <strong>{{ $rp($summary['high_unpaid_amount']) }}</strong>.
                    @endif
                @else
                    Saat ini tidak ada pelanggan dengan risiko churn tinggi.
                @endif
            </p>

            <a href="{{ route('insights.full') }}" class="ov-btn ov-btn-block">Lihat rekomendasi lengkap</a>
        </div>
    </section>
@endsection