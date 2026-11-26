@extends('layouts.owner')

@section('title', $menu->exists ? 'Ubah menu' : 'Tambah menu')

@section('content')
    <header>
        <a href="{{ route('owner.menus.index') }}" class="button-link">← Menu & harga</a>
        <h1 class="page-title">{{ $menu->exists ? 'Ubah menu' : 'Tambah menu' }}</h1>
        <p class="section-note">Perubahan harga dicatat di log audit.</p>
    </header>

    <form method="post" action="{{ $menu->exists ? route('owner.menus.update', $menu) : route('owner.menus.store') }}" class="form-card">
        @csrf
        @if ($menu->exists)
            @method('put')
        @endif

        <label class="field">
            Nama menu
            <input name="name" value="{{ old('name', $menu->name) }}" required maxlength="100">
            @error('name') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            Kategori
            <select name="category_id" required @disabled($categories->isEmpty())>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $menu->category_id) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            Harga (rupiah)
            <input name="price" type="number" inputmode="numeric" min="0" step="1" value="{{ old('price', $menu->price) }}" required>
            @error('price') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label class="checkbox-field">
            <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $menu->is_active))>
            Aktif dijual
        </label>

        @if ($categories->isEmpty())
            <p class="error-box">Belum ada kategori menu. Jalankan seeder kategori terlebih dahulu.</p>
        @endif

        <div class="form-actions">
            <button @disabled($categories->isEmpty()) class="button-primary">Simpan</button>
            <a href="{{ route('owner.menus.index') }}" class="button-secondary">Batal</a>
        </div>
    </form>
@endsection
