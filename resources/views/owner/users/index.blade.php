@extends('layouts.owner')

@section('title', 'Pengguna')

@section('content')
    <header class="page-header">
        <div><p class="eyebrow">Akun crew dan hak akses</p><h1 class="page-title">Pengguna</h1></div>
        <a href="{{ route('owner.users.create') }}" class="button-primary">+ Tambah pengguna</a>
    </header>
    <section class="transaction-list">
        <div class="transaction-list-head">Akun crew</div>
        @forelse ($users as $user)
            <article class="menu-item">
                <div><h2>{{ $user->name }} <span class="badge {{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></h2><p>{{ '@'.$user->username }} · {{ $user->role === 'owner' ? 'Owner' : 'Kasir' }}</p></div>
                <a href="{{ route('owner.users.edit', $user) }}" class="button-secondary">Ubah akun</a>
            </article>
        @empty
            <p class="empty-state">Belum ada akun crew.</p>
        @endforelse
        @if ($users->hasPages())<div class="pagination">{{ $users->links() }}</div>@endif
    </section>
@endsection
