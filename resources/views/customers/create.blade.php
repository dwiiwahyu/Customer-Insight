@extends('layouts.app')

@section('title', 'Tambah Pelanggan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer-form.css') }}">
@endpush

@section('content')
    <a href="{{ route('customers.index') }}" class="btn-back" data-back>← Kembali ke daftar</a>
    <h1 class="page-title">Tambah Pelanggan</h1>
    <p class="page-subtitle">Isi data satu pelanggan beserta satu layanannya. Tanda <b>*</b> wajib diisi.</p>

    @if ($errors->any())
        <div class="alert alert-danger">Ada isian yang belum benar. Cek pesan merah di bawah kolom yang bersangkutan.</div>
    @endif

    <form method="POST" action="{{ route('customers.store') }}" class="form-wrap">
        @csrf

        {{-- ===== Data pelanggan ===== --}}
        <section class="form-panel">
            <h2>Data Pelanggan</h2>

            <div class="form-grid">
                <div class="field">
                    <label for="customer_code">NIPNAS <b>*</b></label>
                    <input type="text" id="customer_code" name="customer_code" value="{{ old('customer_code') }}" placeholder="contoh: 2894488586" required>
                    @error('customer_code')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="name">STANDART NAME <b>*</b></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Nama pelanggan" required>
                    @error('name')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="industry">INDUSTRI_HO</label>
                    <input type="text" id="industry" name="industry" value="{{ old('industry') }}" list="dl-industry" placeholder="contoh: Sekolah">
                    <datalist id="dl-industry">
                        @foreach ($industries as $v)<option value="{{ $v }}">@endforeach
                    </datalist>
                    @error('industry')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="subsegment">SUBSEGMEN_HO</label>
                    <input type="text" id="subsegment" name="subsegment" value="{{ old('subsegment') }}" list="dl-subsegment" placeholder="contoh: Pendidikan">
                    <datalist id="dl-subsegment">
                        @foreach ($subsegments as $v)<option value="{{ $v }}">@endforeach
                    </datalist>
                    @error('subsegment')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="wilayah">SA_DATEL <b>*</b></label>
                    <input type="text" id="wilayah" name="wilayah" value="{{ old('wilayah') }}" list="dl-datel" placeholder="contoh: M1 - Malang" required>
                    <datalist id="dl-datel">
                        @foreach ($datels as $v)<option value="{{ $v }}">@endforeach
                    </datalist>
                    @error('wilayah')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="sto">SA_STO</label>
                    <input type="text" id="sto" name="sto" value="{{ old('sto') }}" list="dl-sto" placeholder="contoh: MLG - MALANG">
                    <datalist id="dl-sto">
                        @foreach ($stos as $v)<option value="{{ $v }}">@endforeach
                    </datalist>
                    @error('sto')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="am_name">NAMA_BAM (sales)</label>
                    <input type="text" id="am_name" name="am_name" value="{{ old('am_name') }}" placeholder="Nama sales / AM">
                    @error('am_name')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="status">Status pelanggan <b>*</b></label>
                    <select id="status" name="status">
                        <option value="aktif" @selected(old('status', 'aktif') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status') === 'nonaktif')>Nonaktif</option>
                    </select>
                    @error('status')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </div>
        </section>

        {{-- ===== Data layanan ===== --}}
        <section class="form-panel">
            <h2>Data Layanan</h2>

            <div class="form-grid">
                <div class="field">
                    <label for="account_num">ACCOUNT_NUM</label>
                    <input type="text" id="account_num" name="account_num" value="{{ old('account_num') }}" placeholder="7 digit, contoh: 4567890">
                    @error('account_num')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="contract_no">CONTRACT_NO</label>
                    <input type="text" id="contract_no" name="contract_no" value="{{ old('contract_no') }}" placeholder="contoh: 1-2345678909">
                    @error('contract_no')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="product_group">PRODUCT_GROUP <b>*</b></label>
                    <input type="text" id="product_group" name="product_group" value="{{ old('product_group') }}" list="dl-group" placeholder="contoh: CONNECTIVITY" required>
                    <datalist id="dl-group">
                        @foreach ($productGroups as $v)<option value="{{ $v }}">@endforeach
                    </datalist>
                    @error('product_group')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="product_name">PRODUCT_NAME <b>*</b></label>
                    <input type="text" id="product_name" name="product_name" value="{{ old('product_name') }}" list="dl-product" placeholder="contoh: HSI INDIBIZ" required>
                    <datalist id="dl-product">
                        @foreach ($productNames as $v)<option value="{{ $v }}">@endforeach
                    </datalist>
                    @error('product_name')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="circuit_speed">CIRCUIT_SPEED</label>
                    <input type="text" id="circuit_speed" name="circuit_speed" value="{{ old('circuit_speed') }}" placeholder="contoh: 100MBPS">
                    @error('circuit_speed')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="start_date">START_DAT (mulai berlangganan)</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}">
                    @error('start_date')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="field">
                    <label for="abonemen">ABONEMEN (biaya per bulan, Rp)</label>
                    <input type="number" id="abonemen" name="abonemen" value="{{ old('abonemen') }}" min="0" step="1" placeholder="contoh: 500000">
                    @error('abonemen')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </div>
        </section>

        <label class="check form-check">
            <input type="checkbox" name="dummy" value="1" @checked(old('dummy', '1'))>
            <span>Buat data pembayaran &amp; pemakaian dummy untuk pelanggan ini (sementara data asli belum ada)</span>
        </label>

        <div class="form-actions">
            <a href="{{ route('customers.index') }}" class="btn-ghost">Batal</a>
            <button type="submit" class="btn-primary">Simpan pelanggan</button>
        </div>
    </form>
@endsection