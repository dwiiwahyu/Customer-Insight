
@extends('layouts.app')

@section('title', 'Penggunaan Layanan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">
    <link rel="stylesheet" href="{{ asset('css/service-usage.css') }}">
@endpush

@php
    $s = $data['summary'];

    $nf = fn ($value) => number_format(
        $value,
        0,
        ',',
        '.'
    );

    $rupiah = fn ($value) => 'Rp ' . number_format(
        $value,
        0,
        ',',
        '.'
    );
@endphp

@section('content')

    <div class="su-page">

        {{-- ========================================= --}}
        {{-- HEADER --}}
        {{-- ========================================= --}}

        <div class="su-header">

            <div>
                <h1 class="page-title">
                    Penggunaan Layanan
                </h1>

                <p class="su-subtitle">
                    Ringkasan pelanggan dan produk layanan
                    berdasarkan wilayah.
                </p>
            </div>
        </div>

        {{-- ========================================= --}}
        {{-- FILTER WILAYAH DAN PAKET --}}
        {{-- ========================================= --}}

        <form method="GET"
              action="{{ route('service-usage.index') }}"
              class="su-filters"
              id="suFilterForm">

            {{-- Filter wilayah --}}
            <div class="su-filter">

                <label for="wilayah">
                    Wilayah
                </label>

                <select name="wilayah" id="wilayah">

                    <option value="Semua"
                            @selected($filters['wilayah'] === 'Semua')>

                        Semua Wilayah

                    </option>

                    @foreach ($wilayahOptions as $w)

                        <option value="{{ $w }}"
                                @selected($filters['wilayah'] === $w)>

                            {{ $w }}

                        </option>

                    @endforeach

                </select>

            </div>

            {{-- Filter paket --}}
            <div class="su-filter">

                <label for="paket">
                    Paket
                </label>

                <select name="paket" id="paket">

                    <option value="Semua"
                            @selected($filters['paket'] === 'Semua')>

                        Semua Paket

                    </option>

                    @foreach ($paketOptions as $p)

                        <option value="{{ $p }}"
                                @selected($filters['paket'] === $p)>

                            {{ $p }}

                        </option>

                    @endforeach

                </select>

            </div>

            {{-- Tombol reset --}}
            @if (
                $filters['wilayah'] !== 'Semua' ||
                $filters['paket'] !== 'Semua'
            )

                <a href="{{ route('service-usage.index') }}"
                   class="su-reset">

                    Reset filter

                </a>

            @endif

        </form>

        {{-- ========================================= --}}
        {{-- KARTU STATISTIK --}}
        {{-- ========================================= --}}

        <div class="su-stats">

            {{-- Total pelanggan --}}
            <div class="su-card su-stat">

                <div class="su-stat-title">
                    Total Pelanggan
                </div>

                <div class="su-stat-value">

                    <span class="su-underline">

                        {{ $nf($s['total_customers']) }}

                    </span>

                    <small>pelanggan</small>

                </div>

                <div class="su-stat-delta">

                    @if ($filters['paket'] !== 'Semua')

                        Pengguna {{ $filters['paket'] }}

                    @else

                        Pelanggan seluruh kategori

                    @endif

                </div>

            </div>

            {{-- Pelanggan aktif --}}
            <div class="su-card su-stat">

                <div class="su-stat-title">
                    Pelanggan Aktif
                </div>

                <div class="su-stat-value">

                    <span class="su-text-green">

                        {{ $nf($s['active_customers']) }}

                    </span>

                    <small>pelanggan</small>

                </div>

                <div class="su-stat-delta up">

                    {{ $s['active_percentage'] }}%

                    <span>dari total pelanggan</span>

                </div>

            </div>

            {{-- Pelanggan nonaktif --}}
            <div class="su-card su-stat">

                <div class="su-stat-title">
                    Pelanggan Nonaktif
                </div>

                <div class="su-stat-value">

                    <span class="su-text-red">

                        {{ $nf($s['inactive_customers']) }}

                    </span>

                    <small>pelanggan</small>

                </div>

                <div class="su-stat-delta down">

                    {{ $s['inactive_percentage'] }}%

                    <span>dari total pelanggan</span>

                </div>

            </div>

        </div>

        {{-- Keterangan filter yang sedang dipilih --}}
        <p class="su-note">

            Menampilkan

            <strong>
                {{ $nf($s['total_customers']) }} pelanggan
            </strong>

            @if ($filters['wilayah'] !== 'Semua')

                di wilayah

                <strong>{{ $filters['wilayah'] }}</strong>

            @else

                dari seluruh wilayah

            @endif

            dengan kategori

            <strong>

                {{ $filters['paket'] === 'Semua'
                    ? 'semua paket layanan'
                    : $filters['paket'] }}

            </strong>.

            Total layanan:
            <strong>{{ $nf($s['total_products']) }}</strong>.

        </p>

        @if ($s['other_customers'] > 0)

            <p class="su-note su-note-warn">

                Terdapat {{ $s['other_customers'] }}
                pelanggan dengan status selain aktif
                atau nonaktif.

            </p>

        @endif

        {{-- ========================================= --}}
        {{-- GRAFIK --}}
        {{-- ========================================= --}}

        <div class="su-charts"
             id="suChartData"
             data-products="{{ json_encode($data['products']) }}"
             data-status="{{ json_encode($data['status']) }}">

            {{-- Grafik distribusi produk --}}
            <div class="su-card su-chart-card">

                <div class="su-chart-head">

                    <h3>
                        Distribusi Produk Layanan
                    </h3>

                </div>

                @if (!empty($data['products']['labels']))

                    <div class="su-chart-box">

                        <canvas id="productChart"></canvas>

                    </div>

                    <p class="su-chart-note">
                        Jumlah pelanggan unik pada setiap jenis produk.
                    </p>

                @else

                    <div class="su-chart-empty">

                        Tidak ada data produk
                        untuk filter ini.

                    </div>

                @endif

            </div>

            {{-- Grafik distribusi status --}}
            <div class="su-card su-chart-card">

                <div class="su-chart-head">

                    <h3>
                        Distribusi Status Pelanggan
                    </h3>

                </div>

                @if ($s['total_customers'] > 0)

                    <div class="su-donut-wrap">

                        <div class="su-donut-box">

                            <canvas id="statusChart"></canvas>

                        </div>

                        <div class="su-legend su-legend-col">

                            <span>

                                <i class="dot dot-active"></i>

                                Aktif
                                ({{ $nf($s['active_customers']) }})

                            </span>

                            <span>

                                <i class="dot dot-inactive"></i>

                                Nonaktif
                                ({{ $nf($s['inactive_customers']) }})

                            </span>

                            @if ($s['other_customers'] > 0)

                                <span>

                                    <i class="dot dot-other"></i>

                                    Lainnya
                                    ({{ $nf($s['other_customers']) }})

                                </span>

                            @endif

                        </div>

                    </div>

                @else

                    <div class="su-chart-empty">

                        Tidak ada pelanggan
                        untuk filter ini.

                    </div>

                @endif

            </div>

        </div>

        {{-- ========================================= --}}
        {{-- TABEL PELANGGAN PENGGUNA LAYANAN --}}
        {{-- ========================================= --}}

        <section class="su-customer-section"
                 id="daftar-pelanggan">

            <div class="su-table-head">

                <div>

                    <h3>Daftar Pelanggan Pengguna Layanan</h3>

                    <p>
                        Data pelanggan berdasarkan wilayah
                        dan kategori layanan yang dipilih.
                    </p>

                </div>

                <span class="su-result-count">

                    {{ $nf($s['total_customers']) }} pelanggan

                </span>

            </div>

            <div class="table-wrap">

                <table class="data-table su-customer-table">

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>NIPNAS</th>

                            <th>Nama Pelanggan</th>

                            <th>Wilayah</th>

                            <th>Product Group</th>

                            <th>Produk Layanan</th>

                            <th>Abonemen</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse ($customers as $c)

                            @php

                                $groups = $c->products
                                    ->pluck('product_group')
                                    ->filter()
                                    ->unique()
                                    ->implode(', ');

                                $products = $c->products
                                    ->pluck('product_name')
                                    ->filter()
                                    ->unique()
                                    ->implode(', ');

                                $abonemen = $c->products
                                    ->sum('abonemen');

                                $statusClass = match ($c->status) {
                                    'aktif' => 'badge-green',
                                    'nonaktif' => 'badge-red',
                                    default => '',
                                };

                            @endphp

                            <tr>

                                <td>
                                    {{ $customers->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    {{ $c->customer_code }}
                                </td>

                                <td class="su-cell-left">

                                    <strong>{{ $c->name }}</strong>

                                </td>

                                <td>
                                    {{ $c->wilayah ?: '-' }}
                                </td>

                                <td>
                                    {{ $groups ?: '-' }}
                                </td>

                                <td class="su-cell-left">
                                    {{ $products ?: '-' }}
                                </td>

                                <td class="su-nowrap">
                                    {{ $c->products->isNotEmpty()
                                        ? $rupiah($abonemen)
                                        : '-' }}
                                </td>

                                <td>

                                    <span class="badge {{ $statusClass }}">

                                        {{ $c->status
                                            ? ucfirst($c->status)
                                            : '-' }}

                                    </span>

                                </td>

                                <td>

                                    <a href="{{ route('customers.show', $c) }}"
                                       class="badge badge-purple">

                                        Lihat detail

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr class="empty-row">

                                <td colspan="9">

                                    Tidak ada pelanggan yang
                                    sesuai dengan filter.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- Pagination --}}
            <div class="table-footer">

                <span class="table-info">

                    @if ($customers->total() > 0)

                        Menampilkan
                        {{ $customers->firstItem() }}–{{ $customers->lastItem() }}

                        dari
                        {{ $nf($customers->total()) }}
                        pelanggan

                    @else

                        Tidak ada data pelanggan.

                    @endif

                </span>

                {{ $customers->links('vendor.pagination.custom') }}

            </div>

        </section>

        {{-- ========================================= --}}
        {{-- ANALISA DAN REKOMENDASI --}}
        {{-- ========================================= --}}

        <div class="su-insights">

            {{-- Analisa pelanggan --}}
            <div class="su-insight su-insight-green">

                <div class="su-insight-icon">

                    <svg width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2.2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">

                        <path d="M3 3v18h18"/>

                        <path d="M7 15l4-4 3 3 5-6"/>

                    </svg>

                </div>

                <div class="su-insight-body">

                    <h4>Analisa Pelanggan</h4>

                    <p>
                        {{ $data['insight']['analysis'] }}
                    </p>

                </div>

            </div>

            {{-- Rekomendasi --}}
            <div class="su-insight su-insight-blue">

                <div class="su-insight-icon">

                    <svg width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="currentColor"
                         aria-hidden="true">

                        <path d="M12 2a7 7 0 0 0-4 12.7V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.3A7 7 0 0 0 12 2zm-2 18h4v1a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z"/>

                    </svg>

                </div>

                <div class="su-insight-body">

                    <h4>Rekomendasi</h4>

                    <p>
                        {{ $data['insight']['recommendation'] }}
                    </p>

                    @if ($data['insight']['show_detail'])

                        <a href="#daftar-pelanggan"
                           class="su-link">

                            Lihat daftar pelanggan

                        </a>

                    @endif

                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script src="{{ asset('js/service-usage.js') }}"></script>

@endpush
