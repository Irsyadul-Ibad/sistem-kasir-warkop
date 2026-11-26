@extends('layouts.owner')

@section('title', $user->exists ? 'Ubah pengguna' : 'Tambah pengguna')

@section('content')
    <header class="page-header"><div><p class="eyebrow">Kelola akun crew</p><h1 class="page-title">{{ $user->exists ? 'Ubah pengguna' : 'Tambah pengguna' }}</h1></div></header>
    <form method="post" action="{{ $user->exists ? route('owner.users.update', $user) : route('owner.users.store') }}" class="form-card">
        @csrf
        @if ($user->exists) @method('put') @endif
        <label class="field">Nama<input name="name" required maxlength="120" value="{{ old('name', $user->name) }}"></label>
        <label class="field">Username<input name="username" required maxlength="60" pattern="[A-Za-z0-9_-]+" value="{{ old('username', $user->username) }}"></label>
        <label class="field">Peran
            <select name="role" required>
                <option value="cashier" @selected(old('role', $user->role) === 'cashier')>Kasir</option>
                <option value="owner" @selected(old('role', $user->role) === 'owner')>Owner</option>
            </select>
        </label>
        <label class="field">{{ $user->exists ? 'Password baru (opsional)' : 'Password' }}<input name="password" type="password" minlength="8" {{ $user->exists ? '' : 'required' }} autocomplete="new-password"></label>
        <label class="checkbox-field"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->exists ? $user->is_active : true))> Akun aktif</label>
        @if ($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
        <div class="form-actions"><button class="button-primary">Simpan akun</button><a href="{{ route('owner.users') }}" class="button-secondary">Batal</a></div>
    </form>
@endsection
