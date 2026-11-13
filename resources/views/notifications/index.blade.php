
@extends('layouts.app')

@section('title', 'Notifikasi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">
    <link rel="stylesheet" href="{{ asset('css/payment-behavior.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
@endpush

@section('content')

    <div class="nt-page">

        {{-- ===== Header halaman ===== --}}
        <div class="nt-header">
            <div>
                <h1 class="page-title">Notifikasi</h1>

                <p class="page-subtitle">
                    Pantau seluruh peringatan dan rekomendasi tindakan pelanggan.
                </p>
            </div>
        </div>

        {{-- ===== Peringatan & rekomendasi tindakan ===== --}}
        <section class="pb-panel pb-section" id="notificationPanel">

            <div class="pb-head">

                <h3 class="pb-title">
                    Peringatan &amp; Rekomendasi Tindakan
                </h3>

                {{-- Filter kategori --}}
                <div class="chips">

                    <button type="button"
                            class="chip active"
                            data-filter="all"
                            aria-pressed="true">

                        Semua ({{ count($notifications) }})

                    </button>

                    <button type="button"
                            class="chip"
                            data-filter="abnormal"
                            aria-pressed="false">

                        Tidak Normal ({{ $counts['abnormal'] ?? 0 }})

                    </button>

                    <button type="button"
                            class="chip"
                            data-filter="tunggakan"
                            aria-pressed="false">

                        Tunggakan ({{ $counts['tunggakan'] ?? 0 }})

                    </button>

                    <button type="button"
                            class="chip"
                            data-filter="sering_telat"
                            aria-pressed="false">

                        Sering Telat ({{ $counts['sering_telat'] ?? 0 }})

                    </button>

                </div>

            </div>

            {{-- ===== Daftar peringatan ===== --}}
            <ul class="notif-list">

                @forelse ($notifications as $n)

                    <li class="notif notif-{{ $n['tone'] }}"
                        data-type="{{ $n['type'] }}">

                        {{-- Ikon peringatan --}}
                        <span class="notif-icon">
                            {{ $n['icon'] }}
                        </span>

                        {{-- Isi peringatan --}}
                        <div class="notif-body">

                            <div class="notif-head">

                                <strong>
                                    {{ $n['title'] }}
                                </strong>

                                <span class="badge {{ $n['badge'][1] }}">
                                    {{ $n['badge'][0] }}
                                </span>

                            </div>

                            <p>
                                {{ $n['text'] }}
                            </p>

                            <p class="notif-action">
                                <b>Tindakan:</b>
                                {{ $n['action'] }}
                            </p>

                        </div>

                        {{-- Detail pelanggan --}}
                        <a href="{{ route('customers.show', $n['customer_id']) }}#pembayaran"
                           class="badge badge-purple">

                            Lihat detail

                        </a>

                    </li>

                @empty

                    <li class="notif-empty">
                        Tidak ada peringatan.
                        Semua pelanggan dalam kondisi normal.
                    </li>

                @endforelse

                {{-- Pesan jika kategori yang dipilih kosong --}}
                <li class="notif-empty"
                    id="notificationEmpty"
                    hidden>

                    Tidak ada peringatan pada kategori ini.

                </li>

            </ul>

        </section>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/notifications.js') }}"></script>
@endpush
