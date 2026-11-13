@php
    $latestKey = $payment['latest_status'];
    $latestTone = $latestKey ? $statusBayar[$latestKey][2] : 'gray';
@endphp

{{-- ===== 4 kartu mini ===== --}}
<div class="d-stats">
    <div class="d-stat">
        <span>Skor Pelanggan</span>
        <strong>{{ $skor }}<small>/100</small></strong>
    </div>
    <div class="d-stat">
        <span>Rata-Rata Keterlambatan</span>
        <strong>{{ $payment['avg_delay'] }} hari</strong>
    </div>
    <div class="d-stat">
        <span>Status Pembayaran</span>
        <strong class="text-{{ $latestTone }}">{{ $latestKey ? $statusBayar[$latestKey][0] : '-' }}</strong>
    </div>
    <div class="d-stat">
        <span>Total Tunggakan</span>
        <strong>{{ $rupiah($payment['unpaid_amount']) }}</strong>
    </div>
</div>

{{-- ===== Grafik ===== --}}
<div class="d-charts">
    <div class="d-panel">
        <h3 class="d-chart-title">Tren Perilaku Pembayaran</h3>
        <div class="d-box">
            <canvas id="payTrendChart" data-chart="{{ json_encode($charts['payTrend']) }}"></canvas>
        </div>
    </div>

    <div class="d-panel">
        <h3 class="d-chart-title">Distribusi Status Pembayaran</h3>
        <div class="d-donut">
            <div class="d-donut-box">
                <canvas id="payDonutChart" data-chart="{{ json_encode($charts['payDonut']) }}"></canvas>
            </div>
            <ul class="d-legend">
                <li><i class="dot dot-green"></i>Tepat waktu ({{ $payment['distribution']['tepat_waktu'] }})</li>
                <li><i class="dot dot-orange"></i>Terlambat ({{ $payment['distribution']['terlambat'] }})</li>
                <li><i class="dot dot-red"></i>Tunggakan ({{ $payment['distribution']['tunggakan'] }})</li>
            </ul>
        </div>
    </div>
</div>

{{-- ===== Riwayat pembayaran ===== --}}
<h3 class="section-title">Riwayat Pembayaran</h3>
<div class="detail-table-wrap">
    <table class="detail-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Tagihan</th>
                <th>Tanggal Pembayaran</th>
                <th>Jumlah Tagihan</th>
                <th>Keterlambatan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($history as $p)
                @php [$label, $badgeClass] = $statusBayar[$p->status]; @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $tgl($p->invoice_date) }}</td>
                    <td>{{ $tgl($p->paid_date) }}</td>
                    <td>{{ $rupiah($p->amount) }}</td>
                    <td class="{{ $p->delay_days > 0 ? 'delay-late' : 'delay-ok' }}">
                        {{ $p->delay_days > 0 ? '+ ' . $p->delay_days . ' hari' : '0 hari' }}
                    </td>
                    <td><span class="badge {{ $badgeClass }}">{{ $label }}</span></td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="6">Belum ada riwayat pembayaran.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>