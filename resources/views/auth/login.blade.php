<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Customer Insight</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
<div class="login-page">

    {{-- Sisi kiri: logo + ilustrasi --}}
    <section class="login-left">
        <img class="login-logo" src="{{ asset('images/logo-telkom.png') }}" alt="Telkom Indonesia">
        <img class="login-illustration" src="{{ asset('images/login-illustration.png') }}" alt="">
    </section>

    {{-- Sisi kanan: form --}}
    <section class="login-right">
        <form class="login-form" method="POST" action="{{ route('login.process') }}">
            @csrf

            <h1>Selamat Datang</h1>
            <p class="login-subtitle">Silakan masuk ke akun Anda</p>

            @error('login')
                <div class="login-alert">{{ $message }}</div>
            @enderror

            <label for="login">Username/Email</label>
            <input type="text" id="login" name="login" value="{{ old('login') }}"
                   placeholder="Masukkan username atau email" autofocus>

            <label for="password">Password</label>
            <div class="password-wrap">
                <input type="password" id="password" name="password" placeholder="Masukkan password">
                <button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan password">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                        <line class="eye-slash" x1="3" y1="3" x2="21" y2="21"/>
                    </svg>
                </button>
            </div>

            @error('password')
                <small class="field-error">{{ $message }}</small>
            @enderror

            <div class="login-options">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    <span>Ingat Saya</span>
                </label>
                <a href="#" class="forgot">Lupa password?</a>
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>
    </section>

</div>

<script src="{{ asset('js/login.js') }}"></script>
</body>
</html>