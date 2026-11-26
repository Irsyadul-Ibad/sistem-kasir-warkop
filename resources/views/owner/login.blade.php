<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>Masuk owner · Warkop Pak Haji</title>
    <link rel="stylesheet" href="{{ asset('css/owner.css') }}">
</head>
<body>
    <main class="login-page">
        <form method="post" action="{{ route('owner.authenticate') }}" class="login-form">
            @csrf
            <div class="brand-mark"></div>
            <h1>Warkop Pak Haji</h1>
            <p>Masuk dashboard owner</p>

            <label class="field">
                Username
                <input name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Username owner">
            </label>
            <label class="field">
                Password
                <input name="password" type="password" required autocomplete="current-password" placeholder="Password">
            </label>

            @error('username')
                <p role="alert" class="error-box">{{ $message }}</p>
            @enderror

            <button class="button-primary button-full">Masuk</button>
            <p>Akun owner digunakan untuk laporan dan pengaturan usaha.</p>
        </form>
        <p class="login-footer">Kasir? <a href="http://127.0.0.1:5173/login">Kembali ke login kasir</a></p>
    </main>
</body>
</html>
