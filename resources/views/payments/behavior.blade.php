
@extends('layouts.app')

@section('title', 'Perilaku Pembayaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">

    {{-- CSS tabel pelanggan --}}
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">

    <link rel="stylesheet" href="{{ asset('css/payment-behavior.css') }}">
@endpush

@php
    $tgl = fn ($d) => $d
        ->locale('id')
        ->translatedFormat('j M Y');
@endphp

@section('content')

    {{-- ===== Header ===== --}}
    <h1 class="page-title">Perilaku Pembayaran</h1>

    <p class="page-subtitle">
        Analisis kebiasaan dan pola pembayaran pelanggan.
    </p>

    {{-- ===== 4 kartu ringkasan ===== --}}
    <section class="pb-stats">

        <div class="pb-stat">

            <span>Pelanggan Dipantau</span>

            <strong>
                {{ number_format($summary['monitored'], 0, ',', '.') }}
            </strong>

            <small>pelanggan aktif</small>

        </div>

        <div class="pb-stat">

            <span>Perlu Tindakan</span>

            <strong class="text-red">
                {{ number_format($summary['need_action'], 0, ',', '.') }}
            </strong>

            <small>memiliki tunggakan</small>

        </div>

        <div class="pb-stat">

            <span>Rata-Rata Keterlambatan</span>

            <strong>
                {{ number_format($summary['avg_delay'], 1, ',', '.') }}
                hari
            </strong>

            <small>dari pelanggan yang pernah telat</small>

        </div>

        <div class="pb-stat">

            <span>Pelanggan Rutin</span>

            <strong class="text-green">
                {{ $summary['rutin_pct'] }}%
            </strong>

            <small>dari seluruh pelanggan</small>

        </div>

    </section>

    {{-- ===== Grafik ===== --}}
    <section class="pb-charts">

        {{-- Grafik keterlambatan --}}
        <div class="pb-panel">

            <h3 class="pb-title">
                Rata-Rata Keterlambatan per Bulan
            </h3>

            <div class="pb-box">

                <canvas
                    id="delayChart"
                    data-chart="{{ json_encode($charts['delay']) }}">
                </canvas>

            </div>

        </div>

        {{-- Grafik pola pembayaran --}}
        <div class="pb-panel">

            <h3 class="pb-title">
                Komposisi Pola Pembayaran
            </h3>

            <div class="pb-donut">

                <div class="pb-donut-box">

                    <canvas
                        id="polaChart"
                        data-chart="{{ json_encode($charts['pola']) }}">
                    </canvas>

                </div>

                <ul class="pb-legend">

                    <li>
                        <i class="pb-dot dot-green"></i>
                        Rutin ({{ $summary['pola_counts']['rutin'] }})
                    </li>

                    <li>
                        <i class="pb-dot dot-yellow"></i>
                        Kadang Telat
                        ({{ $summary['pola_counts']['kadang_telat'] }})
                    </li>

                    <li>
                        <i class="pb-dot dot-orange"></i>
                        Sering Telat
                        ({{ $summary['pola_counts']['sering_telat'] }})
                    </li>

                    <li>
                        <i class="pb-dot dot-red"></i>
                        Menunggak
                        ({{ $summary['pola_counts']['menunggak'] }})
                    </li>

                </ul>

            </div>

        </div>

    </section>

    {{-- =====================================================
         PERINGATAN SUDAH DIPINDAHKAN KE HALAMAN NOTIFIKASI
    ====================================================== --}}

    {{-- ===== Tabel pola pembayaran ===== --}}
    <div class="pb-head pb-table-head">

        <h3 class="pb-title">
            Pola Pembayaran Pelanggan
        </h3>

        <form method="GET"
              action="{{ route('payments.behavior') }}"
              id="polaForm">

            <select name="pola" class="filter-select">

                <option value="">Semua Pola</option>

                @foreach ($polaOptions as $key => [$label])

                    <option value="{{ $key }}"
                            @selected($polaFilter === $key)>

                        {{ $label }}

                    </option>

                @endforeach

            </select>

        </form>

    </div>

    {{-- ===== Tabel pelanggan ===== --}}
    <div class="table-wrap">

        <table class="data-table">

            <thead>

                <tr>
                    <th>No</th>

                    <th>Nama Pelanggan</th>

                    <th>Kebiasaan<br>Bayar</th>

                    <th>Rata-Rata<br>Telat</th>

                    <th>Telat /<br>Total</th>

                    <th>Status<br>Tagihan Terbaru</th>

                    <th>Pola</th>

                    <th>Kirim<br>Reminder</th>

                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($rows as $r)

                    @php
                        $c = $r['customer'];
                    @endphp

                    <tr>

                        {{-- Nomor --}}
                        <td>
                            {{ $rows->firstItem() + $loop->index }}
                        </td>

                        {{-- Nama pelanggan --}}
                        <td class="cell-left">

                            <a href="{{ route('customers.show', $c) }}#pembayaran"
                               class="cust-link">

                                {{ $c->name }}

                            </a>

                            <small>
                                #{{ $c->customer_code }}
                            </small>

                        </td>

                        {{-- Kebiasaan pembayaran --}}
                        <td>
                            {{ $r['typical_day']
                                ? 'Tanggal ' . $r['typical_day']
                                : '-' }}
                        </td>

                        {{-- Rata-rata keterlambatan --}}
                        <td>
                            {{ $r['avg_delay'] }} hari
                        </td>

                        {{-- Total keterlambatan --}}
                        <td>
                            {{ $r['late_count'] + $r['unpaid_count'] }}/{{ $r['total'] }}
                        </td>

                        {{-- Status tagihan --}}
                        <td>

                            <span class="badge {{ $r['status_class'] }}">

                                {{ $r['status_label'] }}

                            </span>

                        </td>

                        {{-- Pola pembayaran --}}
                        <td>

                            <span class="badge {{ $r['pola_class'] }}">

                                {{ $r['pola_label'] }}

                            </span>

                        </td>

                        {{-- Reminder --}}
                        <td>

                            @if ($r['reminder'])

                                {{ $tgl($r['reminder']['send']) }}

                                <small class="cell-note">

                                    untuk tgl
                                    {{ $tgl($r['reminder']['expected']) }}

                                </small>

                            @elseif ($r['unpaid_count'] > 0)

                                <span class="text-red">
                                    Tagih dulu
                                </span>

                            @else

                                -

                            @endif

                        </td>

                        {{-- Detail pelanggan --}}
                        <td>

                            <a href="{{ route('customers.show', $c) }}#pembayaran"
                               class="badge badge-purple">

                                Lihat detail

                            </a>

                        </td>

                    </tr>

                @empty

                    <tr class="empty-row">

                        <td colspan="9">

                            Tidak ada pelanggan dengan pola ini.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    {{-- ===== Footer tabel ===== --}}
    <div class="table-footer">

        <span class="table-info">

            @if ($rows->total() > 0)

                Menampilkan
                {{ $rows->firstItem() }}–{{ $rows->lastItem() }}

                dari {{ $rows->total() }} pelanggan

            @endif

        </span>

        {{ $rows->links('vendor.pagination.custom') }}

    </div>

@endsection

@push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script src="{{ asset('js/payment-behavior.js') }}"></script>

@endpush
