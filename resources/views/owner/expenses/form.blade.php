@extends('layouts.owner')

@section('title', 'Edit pengeluaran')

@section('content')
    <header class="page-header"><div><p class="eyebrow">Pengeluaran · {{ $expense->expense_date->format('d/m/Y') }}</p><h1 class="page-title">Edit pengeluaran</h1></div></header>
    <form method="post" action="{{ route('owner.expenses.update', $expense) }}" enctype="multipart/form-data" class="form-card">
        @csrf @method('put')
        <div class="form-grid">
            <label class="field">Nominal (Rp)<input name="amount" type="number" min="1" step="1" value="{{ old('amount', $expense->amount) }}" required></label>
            <label class="field">Tanggal<input name="expense_date" type="date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required></label>
        </div>
        <label class="field">Kategori
            <select name="category_id" required>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $expense->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">Catatan<input name="description" maxlength="500" value="{{ old('description', $expense->description) }}"></label>
        <label class="field">Foto nota baru (opsional)<input name="receipt" type="file" accept="image/*"></label>
        @if ($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
        <div class="form-actions">
            <button class="button-primary">Simpan perubahan</button>
            <a class="button-secondary" href="{{ route('owner.expenses', ['date' => $expense->expense_date->format('Y-m-d')]) }}">Batal</a>
        </div>
    </form>
@endsection
