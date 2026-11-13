@extends('layouts.app')

@section('title', 'Daftar Pelanggan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">
@endpush

@php
    $statusPelangganBadge = [
        'aktif'    => ['Aktif', 'badge-green'],
        'nonaktif' => ['Nonaktif', 'badge-red'],
    ];

    $statusBayarBadge = [
        'tepat_waktu' => ['Tepat waktu', 'badge-green'],
        'terlambat'   => ['Terlambat', 'badge-yellow'],
        'tunggakan'   => ['Tunggakan', 'badge-orange'],
    ];

    $adaFilter = request()->hasAny(['q', 'product_group', 'subsegment', 'status', 'payment_status', 'sto']);
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Daftar Pelanggan</h1>
            <p class="page-subtitle">Kelola dan lihat informasi pelanggan secara terpusat.</p>
        </div>

        {{-- Tombol tambah pelanggan: dropdown manual / upload CSV --}}
        <div class="add-menu" id="addMenu">
            <button type="button" class="btn-add" id="addBtn" aria-haspopup="true">
                Tambah pelanggan <span>+</span>
            </button>

            <div class="add-dropdown" id="addDropdown">
                <a href="{{ route('customers.create') }}">Tambah manual</a>
                <button type="button" id="openUpload">Upload CSV</button>
            </div>
        </div>
    </div>

    {{-- ===== Pesan hasil tambah / import ===== --}}
    @if (session('status'))
        <div class="alert alert-success">{!! nl2br(e(session('status'))) !!}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{!! nl2br(e(session('error'))) !!}</div>
    @endif

    @if ($errors->hasAny(['csv', 'type', 'revenue_months']))
        <div class="alert alert-danger">
            {{ $errors->first('csv') ?: ($errors->first('type') ?: $errors->first('revenue_months')) }}
        </div>
    @endif

    {{-- ===== Pencarian & filter ===== --}}
    <form method="GET" action="{{ route('customers.index') }}" class="filter-bar" id="filterForm">
        <div class="filter-search">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/>
                <line x1="21" y1="21" x2="16.5" y2="16.5"/>
            </svg>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari NIPNAS, nama, atau nomor akun">
        </div>

        <select name="product_group" class="filter-select">
            <option value="">PRODUCT_GROUP</option>
            @foreach ($productGroups as $g)
                <option value="{{ $g }}" @selected(request('product_group') === $g)>{{ $g }}</option>
            @endforeach
        </select>

        <select name="subsegment" class="filter-select">
            <option value="">SUBSEGMEN_HO</option>
            @foreach ($subsegments as $s)
                <option value="{{ $s }}" @selected(request('subsegment') === $s)>{{ $s }}</option>
            @endforeach
        </select>

        <select name="status" class="filter-select">
            <option value="">Status Pelanggan</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>

        <select name="payment_status" class="filter-select">
            <option value="">Status Pembayaran</option>
            <option value="tepat_waktu" @selected(request('payment_status') === 'tepat_waktu')>Tepat waktu</option>
            <option value="terlambat" @selected(request('payment_status') === 'terlambat')>Terlambat</option>
            <option value="tunggakan" @selected(request('payment_status') === 'tunggakan')>Tunggakan</option>
        </select>

        <select name="sto" class="filter-select">
            <option value="">SA_STO</option>
            @foreach ($stos as $t)
                <option value="{{ $t }}" @selected(request('sto') === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </form>

    @if ($adaFilter)
        <a href="{{ route('customers.index') }}" class="reset-filter">✕ Reset filter</a>
    @endif

    {{-- ===== Tabel ===== --}}
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIPNAS</th>
                    <th>STANDART_NAME</th>
                    <th>PRODUCT_GROUP</th>
                    <th>SUBSEGMEN_HO</th>
                    <th>STATUS<br>PELANGGAN</th>
                    <th>STATUS<br>PEMBAYARAN</th>
                    <th>SA_STO</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $c)
                    @php
                        [$sLabel, $sClass] = $statusPelangganBadge[$c->status] ?? ['-', ''];
                        [$pLabel, $pClass] = $statusBayarBadge[$c->latest_payment_status] ?? ['-', ''];

                        // satu pelanggan bisa punya beberapa product group
                        $productGroup = $c->products->pluck('product_group')->filter()->unique()->implode(', ');
                    @endphp
                    <tr>
                        <td>{{ $customers->firstItem() + $loop->index }}</td>
                        <td>{{ $c->customer_code }}</td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $productGroup ?: '-' }}</td>
                        <td>{{ $c->subsegment ?: '-' }}</td>
                        <td><span class="badge {{ $sClass }}">{{ $sLabel }}</span></td>
                        <td><span class="badge {{ $pClass }}">{{ $pLabel }}</span></td>
                        <td>{{ $c->sto ?: '-' }}</td>
                        <td><a href="{{ route('customers.show', $c) }}" class="badge badge-purple">Lihat detail</a></td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="9">Tidak ada pelanggan yang cocok dengan pencarian/filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===== Footer tabel ===== --}}
    <div class="table-footer">
        <span class="table-info">
            @if ($customers->total() > 0)
                Menampilkan {{ $customers->firstItem() }}–{{ $customers->lastItem() }} dari {{ $customers->total() }} pelanggan
            @endif
        </span>

        {{ $customers->links('vendor.pagination.custom') }}
    </div>

    {{-- ===== Jendela upload CSV ===== --}}
    <div class="modal" id="uploadModal" hidden>
        <div class="modal-box">
            <h3>Upload CSV</h3>

            <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data" id="uploadForm">
                @csrf

                <div class="modal-field">
                    <label for="type">Jenis data</label>
                    <select id="type" name="type">
                        <option value="master">Data pelanggan (master)</option>
                        <option value="payments">Riwayat pembayaran</option>
                    </select>
                </div>

                <p class="modal-hint" id="hintMaster">
                    Kolom wajib: NIPNAS dan STANDART NAME. NIPNAS yang sudah ada diperbarui, bukan dobel.
                </p>
                <p class="modal-hint" id="hintPayments" hidden>
                    Kolom wajib: NIPNAS, TANGGAL_TAGIHAN, JUMLAH_TAGIHAN. Pelanggan harus sudah ada.
                    Riwayat pembayaran pelanggan yang ada di file akan diganti.
                </p>

                <div class="modal-field">
                    <label for="csv">File CSV</label>
                    <input type="file" id="csv" name="csv" accept=".csv,.txt" required>
                </div>

                {{-- Opsi khusus data pelanggan --}}
                <div class="modal-group" id="masterOptions">
                    <div class="modal-field">
                        <label for="revenue_months">Nilai REVENUE di file adalah per</label>
                        <select id="revenue_months" name="revenue_months">
                            <option value="1">Bulan (sama dengan ABONEMEN)</option>
                            <option value="12">Tahun / total kontrak</option>
                        </select>
                    </div>

                    <label class="check">
                        <input type="checkbox" name="dummy" value="1" checked>
                        <span>Buat data pembayaran &amp; pemakaian dummy untuk pelanggan baru</span>
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-ghost" id="closeUpload">Batal</button>
                    <button type="submit" class="btn-primary" id="uploadSubmit">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/customers.js') }}"></script>
@endpush