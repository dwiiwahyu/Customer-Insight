@php
    // Ikon sesuai wireframe (SVG, mengikuti warna lingkaran)
    $icons = [
        'chart' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 20h16v2H4zM5 11h3v8H5zm5-6h3v14h-3zm5 4h3v10h-3z"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        'star'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17l-6.1 3.6 1.4-6.8L2.2 9.1l6.9-.8z"/></svg>',
        'gift'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 8h18v4H3zM4 13h16v8H4z"/><path d="M12 8v13" stroke="#fff" stroke-width="2" fill="none"/><path d="M12 8C10 3 6 4 7.5 7 8.3 8.7 12 8 12 8zM12 8c2-5 6-4 4.5-1C15.7 8.7 12 8 12 8z"/></svg>',
    ];
@endphp

{{-- ===== 3 kartu atas ===== --}}
<div class="insight-cards">
    @foreach ($insight['cards'] as $card)
        <div class="insight-card">
            <span class="badge {{ $card['tag_class'] }}">{{ $card['tag'] }}</span>
            <h3>{{ $card['title'] }}</h3>
            <p>{{ $card['text'] }}</p>
        </div>
    @endforeach
</div>

{{-- ===== Ringkasan + rekomendasi tindakan ===== --}}
<div class="insight-bottom">
    <div class="d-panel">
        <h3 class="d-chart-title">Ringkasan Insight</h3>
        <ul class="summary-list">
            @foreach ($insight['summary'] as $item)
                <li>
                    <span class="summary-icon icon-{{ $item['tone'] }}">{!! $icons[$item['icon']] ?? '' !!}</span>
                    <span>{{ $item['text'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="d-panel">
        <h3 class="d-chart-title">Rekomendasi Tindakan</h3>
        <ul class="action-list">
            @foreach ($insight['actions'] as [$bold, $rest])
                <li>
                    <span class="check-box">✓</span>
                    <span><b>{{ $bold }}</b>{{ $rest }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>