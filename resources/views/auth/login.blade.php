<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>Masuk · Warkop Pak Haji</title>
    <link rel="stylesheet" href="{{ asset('css/owner.css') }}">
</head>
<body>
    <main class="login-page">
        <form method="post" action="{{ route('crew.login') }}" class="login-form">
            @csrf
            <div class="brand-mark"></div>
            <h1>Warkop Pak Haji</h1>
            <p>Masuk untuk owner atau kasir</p>
            <label class="field">Username<input name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Username crew"></label>
            <label class="field">Password<input name="password" type="password" required autocomplete="current-password" placeholder="Password"></label>
            @error('username')<p role="alert" class="error-box">{{ $message }}</p>@enderror
            <button class="button-primary button-full">Masuk</button>
            <p>Halaman dan akses akan menyesuaikan peran akun.</p>
        </form>
        <p class="login-footer">Pelanggan? Scan QR meja untuk memesan tanpa login.</p>
    </main>
</body>
</html>
