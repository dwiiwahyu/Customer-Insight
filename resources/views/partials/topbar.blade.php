@php
    $notif = $notif ?? ['total' => 0, 'items' => []];
@endphp

<header class="topbar">

    {{-- Pencarian: dikirim ke halaman daftar pelanggan (?q=...) --}}
    <form class="topbar-search" method="GET" action="{{ route('customers.index') }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="7"/>
            <line x1="21" y1="21" x2="16.5" y2="16.5"/>
        </svg>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari pelanggan, ID, atau informasi lainnya...">
    </form>

    <div class="topbar-actions">

        {{-- Notifikasi --}}
        <div class="notif-menu" id="notifMenu">
            <button type="button" class="icon-btn" id="notifBtn" aria-label="Notifikasi" aria-haspopup="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm7-6V11a7 7 0 0 0-5-6.7V3a2 2 0 0 0-4 0v1.3A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2z"/>
                </svg>
                @if ($notif['total'] > 0)
                    <span class="notif-badge">{{ $notif['total'] > 9 ? '9+' : $notif['total'] }}</span>
                @endif
            </button>

            <div class="nd-dropdown" id="notifDropdown">
                <div class="nd-head">Notifikasi ({{ $notif['total'] }})</div>

                <ul class="nd-list">
                    @forelse ($notif['items'] as $n)
                        <li>
                            <a href="{{ route('customers.show', $n['customer_id']) }}#{{ $n['tab'] ?? 'pembayaran' }}" class="nd-item">
                                <i class="nd-dot nd-{{ $n['tone'] }}"></i>
                                <span>
                                    <strong>{{ $n['title'] }}</strong>
                                    <small>{{ Str::limit($n['text'], 110) }}</small>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="nd-empty">Tidak ada notifikasi.</li>
                    @endforelse
                </ul>

                <a href="{{ route('notifications.index') }}" class="nd-foot">Lihat semua notifikasi</a>
            </div>
        </div>


        {{-- Profil + dropdown logout --}}
        <div class="user-menu" id="userMenu">
            <button type="button" class="user-btn" id="userBtn" aria-haspopup="true">
                <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="user-info">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>Admin</small>
                </span>
                <svg class="user-caret" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 10l5 5 5-5z"/>
                </svg>
            </button>

            <div class="user-dropdown" id="userDropdown">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </div>
        </div>

    </div>
</header>