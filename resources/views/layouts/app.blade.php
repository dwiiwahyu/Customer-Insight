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

    <div class="main">
        @include('partials.topbar')

        <main class="content">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('js/layout.js') }}"></script>

    {{-- JS khusus halaman, diisi lewat @push('scripts') --}}
    @stack('scripts')
</body>
</html>
