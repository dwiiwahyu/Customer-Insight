@extends('layouts.app')

@section('title', $info['title'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/badges.css') }}">
    {{-- dipakai ulang: .table-wrap, .data-table, .pagination, dll. --}}
    <link rel="stylesheet" href="{{ asset('css/customers.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insights.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insight-overview.css') }}">
@endpush

@section('content')
    <div class="ov-detail">
        <a href="{{ route('insights.full') }}" class="ov-btn ov-btn-back" data-back>← Kembali</a>

        <h1 class="page-title">{{ $info['title'] }}</h1>
        <p class="page-subtitle">{{ $info['desc'] }}.</p>

        <div class="table-wrap">
            <table class="data-table ov-detail-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th class="cell-left">Pelanggan</th>
                        <th>Industri</th>
                        <th>Paket &amp;<br>Utilisasi</th>
                        <th>Risiko<br>Churn</th>
                        <th class="cell-left">Rekomendasi</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php $c = $r['customer']; @endphp
                        <tr>
                            <td>{{ $rows->firstItem() + $loop->index }}</td>
                            <td class="cell-left">
                                <a href="{{ route('customers.show', $c) }}#insight" class="cust-link">{{ $c->name }}</a>
                                <small>#{{ $c->customer_code }}</small>
                            </td>
                            <td>{{ $c->industry ?: '-' }}</td>
                            <td>
                                {{ $c->product }}
                                <small class="cell-note">utilisasi {{ $r['utilization'] }}%</small>
                            </td>
                            <td><span class="badge {{ $r['risk_class'] }}">{{ $r['risk_label'] }}</span></td>
                            <td class="cell-left">
                                @if ($r['recs'])
                                    <ul class="rec-list">
                                        @foreach ($r['recs'] as $rec)
                                            <li>
                                                <span class="badge {{ $tipeOptions[$rec['type']][1] }}">{{ $tipeOptions[$rec['type']][0] }}</span>
                                                <span>{{ $rec['text'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="ov-muted">Pantau pembayaran di bulan selanjutnya.</span>
                                @endif
                            </td>
                            <td><a href="{{ route('customers.show', $c) }}#insight" class="badge badge-purple">Lihat detail</a></td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7">Tidak ada pelanggan untuk jenis rekomendasi ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="table-info">
                @if ($rows->total() > 0)
                    Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $rows->total() }} pelanggan
                @endif
            </span>

            {{ $rows->links('vendor.pagination.custom') }}
        </div>
    </div>
@endsection