@extends('layouts.owner')

@section('title', 'Menu & harga')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Pengaturan produk</p>
            <h1 class="page-title">Menu & harga</h1>
        </div>
        <a href="{{ route('owner.menus.create') }}" class="button-primary">+ Tambah menu</a>
    </header>

    <section class="menu-list" aria-label="Daftar menu">
        @forelse ($menus as $menu)
            <article class="menu-item">
                <div>
                    <div>
                        <h2>{{ $menu->name }}
                        <span class="badge {{ $menu->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $menu->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span></h2>
                    </div>
                    <p>{{ $menu->category?->name ?? 'Tanpa kategori' }}</p>
                </div>
                <p><strong>Rp {{ number_format($menu->price, 0, ',', '.') }}</strong></p>
                <a href="{{ route('owner.menus.edit', $menu) }}" class="button-secondary">Ubah menu</a>
            </article>
        @empty
            <div class="metric-card empty-state">
                <strong>Belum ada menu</strong>
                <span>Tambahkan menu pertama untuk mulai menjual.</span>
            </div>
        @endforelse
    </section>
@endsection
