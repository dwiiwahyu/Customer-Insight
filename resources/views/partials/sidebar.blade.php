<aside class="sidebar">
       <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <span class="brand-logo">
            {{-- Ikon: grafik batang dengan garis tren naik --}}
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4.5 20v-5"/>
                <path d="M9 20v-8"/>
                <path d="M13.5 20v-6"/>
                <path d="M18 20v-9"/>
                <path d="M3.5 11 8 6.5l4 3L20 3"/>
                <path d="M15.5 3H20v4.5"/>
            </svg>
        </span>
        <span class="brand-text">CUSTOMER<br>INSIGHT</span>
    </a>
    <nav class="sidebar-menu">
        <a href="{{ route('dashboard') }}"
           class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>

        <a href="{{ route('customers.index') }}"
           class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">Pelanggan</a>

        <a href="{{ route('payments.behavior') }}"
           class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">Perilaku Pembayaran</a>


         <a href="{{ route('service-usage.index') }}"
            class="{{ request()->routeIs('service-usage.*') ? 'active' : '' }}">Penggunaan Layanan</a>

        <a href="{{ route('insights.index') }}"
           class="{{ request()->routeIs('insights.*') ? 'active' : '' }}">Insight &amp; Rekomendasi</a>
    </nav>
</aside>