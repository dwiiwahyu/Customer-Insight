@extends('layouts.app')

@section('title', 'Rekomendasi Lengkap')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">
    {{-- dipakai ulang: .table-wrap, .data-table, dll. --}}
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insight-overview.css') }}">
@endpush

@section('content')
    <a href="{{ route('insights.index') }}" class="ov-btn ov-btn-back">← Kembali ke Insight &amp; Rekomendasi</a>

    <h1 class="page-title">Rekomendasi Lengkap</h1>
    <p class="page-subtitle">Berikut ringkasan rekomendasi lengkap.</p>

    <div class="table-wrap">
        <table class="data-table ov-full-table">
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th class="cell-left">Judul Rekomendasi</th>
                    <th>Jumlah Pelanggan</th>
                    <th>Prioritas</th>
                    <th>Tanggal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recommendations as $key => $r)
                    <tr>
                        <td>
                            <span class="ov-mini-icon ov-icon-{{ $r['tone'] }}">
                                @include('insights.partials.icon', ['tone' => $r['tone']])
                            </span>
                        </td>
                        <td class="cell-left">
                            <strong>{{ $r['title'] }}</strong>
                            <small class="ov-desc">{{ $r['desc'] }}</small>
                        </td>
                        <td><strong>{{ number_format($r['count'], 0, ',', '.') }}</strong></td>
                        <td><span class="badge {{ $r['priority_class'] }}">{{ $r['priority_label'] }}</span></td>
                        <td>{{ $tanggal }}</td>
                        <td>
                            @if ($r['count'] > 0)
                                <a href="{{ route('insights.detail', $key) }}" class="badge badge-purple">Lihat detail</a>
                            @else
                                <span class="ov-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection