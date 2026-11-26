<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>@yield('title', 'Dashboard') · Warkop Pak Haji</title>
    <link rel="stylesheet" href="{{ asset('css/owner.css') }}">
</head>
<body>
    <div class="owner-shell">
        <aside class="owner-sidebar">
            <a href="{{ route('owner.dashboard') }}" class="owner-brand">Warkop Pak Haji</a>
            <nav class="owner-nav" aria-label="Navigasi owner">
                <a href="{{ route('owner.dashboard') }}" class="{{ request()->routeIs('owner.dashboard*') ? 'is-current' : '' }}">Dashboard</a>
                <a href="{{ route('owner.transactions') }}" class="{{ request()->routeIs('owner.transactions') ? 'is-current' : '' }}">Transaksi</a>
                <a href="{{ route('owner.expenses') }}" class="{{ request()->routeIs('owner.expenses*') ? 'is-current' : '' }}">Pengeluaran</a>
                <a href="{{ route('owner.debts') }}" class="{{ request()->routeIs('owner.debts*') ? 'is-current' : '' }}">Bon pelanggan</a>
                <a href="{{ route('owner.menus.index') }}" class="{{ request()->routeIs('owner.menus.*') ? 'is-current' : '' }}">Menu & harga</a>
                <a href="{{ route('owner.users') }}" class="{{ request()->routeIs('owner.users*') ? 'is-current' : '' }}">Pengguna</a>
            </nav>
            <div class="owner-user">
                <p><strong>{{ auth()->user()->name }}</strong></p>
                <p class="owner-user-role">Owner</p>
                <form method="post" action="{{ route('owner.logout') }}">
                    @csrf
                    <button class="button-secondary owner-logout">Keluar</button>
                </form>
            </div>
        </aside>

        <div class="owner-main">
            <nav class="owner-mobile-nav" aria-label="Navigasi owner">
                <a href="{{ route('owner.dashboard') }}" class="{{ request()->routeIs('owner.dashboard*') ? 'is-current' : '' }}">Dashboard</a>
                <a href="{{ route('owner.transactions') }}" class="{{ request()->routeIs('owner.transactions') ? 'is-current' : '' }}">Transaksi</a>
                <a href="{{ route('owner.expenses') }}" class="{{ request()->routeIs('owner.expenses*') ? 'is-current' : '' }}">Pengeluaran</a>
                <a href="{{ route('owner.debts') }}" class="{{ request()->routeIs('owner.debts*') ? 'is-current' : '' }}">Bon</a>
                <a href="{{ route('owner.menus.index') }}" class="{{ request()->routeIs('owner.menus.*') ? 'is-current' : '' }}">Menu & harga</a>
                <a href="{{ route('owner.users') }}" class="{{ request()->routeIs('owner.users*') ? 'is-current' : '' }}">Pengguna</a>
                <form method="post" action="{{ route('owner.logout') }}">
                    @csrf
                    <button>Keluar</button>
                </form>
            </nav>
            <main class="owner-content">
                @if (session('status'))
                    <div role="status" class="flash">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
