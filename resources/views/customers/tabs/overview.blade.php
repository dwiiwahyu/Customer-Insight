<div class="overview-wrap">

    {{-- Kiri: informasi pelanggan --}}
    <div class="d-panel">
        <h2 class="d-panel-title">Informasi Pelanggan</h2>

        @php
            $bayarTerakhir = $payment['latest_status'] ? $statusBayar[$payment['latest_status']][0] : '-';
            $productGroup  = $customer->products->pluck('product_group')->filter()->unique()->implode(', ');
        @endphp

        <dl class="info-list">
            <div><dt>NIPNAS</dt><dd>{{ $customer->customer_code }}</dd></div>
            <div><dt>STANDART NAME</dt><dd>{{ $customer->name }}</dd></div>
            <div><dt>INDUSTRI_HO</dt><dd>{{ $customer->industry ?: '-' }}</dd></div>
            <div><dt>SUBSEGMEN_HO</dt><dd>{{ $customer->subsegment ?: '-' }}</dd></div>
            <div><dt>PRODUCT_GROUP</dt><dd>{{ $productGroup ?: '-' }}</dd></div>
            <div><dt>Status pelanggan</dt><dd>{{ ucfirst($customer->status) }}</dd></div>
            <div><dt>Status pembayaran</dt><dd>{{ $bayarTerakhir }}</dd></div>
            <div><dt>SA_DATEL</dt><dd>{{ $customer->wilayah }}</dd></div>
            <div><dt>SA_STO</dt><dd>{{ $customer->sto ?: '-' }}</dd></div>
            <div><dt>ACCOUNT_NUM</dt><dd>{{ $customer->internet_number ?: '-' }}</dd></div>
            <div><dt>Nama AM</dt><dd>{{ $customer->am_name ?: '-' }}</dd></div>
            <div><dt>Tanggal pasang</dt><dd>{{ $tgl($customer->install_date) }}</dd></div>
        </dl>
    </div>

    {{-- Kanan: ringkasan --}}
    <div class="d-panel">
        <h2 class="d-panel-title">Ringkasan Pelanggan</h2>

        <div class="sum-grid">
            @foreach ($overview as $o)
                <div class="sum-card tone-{{ $o['tone'] }}">
                    <span class="sum-icon">{{ $o['icon'] }}</span>
                    <span class="sum-label">{{ $o['label'] }}</span>
                    <strong class="sum-value">{{ $o['value'] }}</strong>
                    <small>{{ $o['note'] }}</small>
                </div>
            @endforeach
        </div>

        <div class="health-box">
            <div class="score-ring lg {{ $skorClass }}" style="--score: {{ $skor }}">
                <div class="score-ring-inner">
                    <strong>{{ $skor }}</strong>
                    <small>{{ $skorLabel }}</small>
                </div>
            </div>
            <div>
                <h3>Customer Health Score</h3>
                <p>Berdasarkan indikator perilaku pembayaran dan status langganan pelanggan.</p>
            </div>
        </div>
    </div>

</div>