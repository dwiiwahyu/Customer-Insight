<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Customer Insight</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- CSS kerangka (dipakai semua halaman) --}}
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">

    {{-- CSS khusus halaman, diisi lewat @push('styles') --}}
    @stack('styles')
</head>
<body>

    @include('partials.sidebar')

    {{-- Lapisan gelap di belakang sidebar (hanya muncul di layar kecil saat menu dibuka) --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main">
        {{-- Tombol hamburger (hanya tampil di layar kecil) --}}
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu" aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        @include('partials.topbar')

        <main class="content">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('js/layout.js') }}"></script>
    <script src="{{ asset('js/sidebar-toggle.js') }}"></script>

    {{-- JS khusus halaman, diisi lewat @push('scripts') --}}
    @stack('scripts')
</body>
</html>
