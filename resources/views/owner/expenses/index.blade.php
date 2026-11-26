@extends('layouts.owner')

@section('title', 'Pengeluaran')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">{{ $date === now('Asia/Jakarta')->toDateString() ? 'Hari ini' : $date }} · total Rp {{ number_format($total, 0, ',', '.') }}</p>
            <h1 class="page-title">Pengeluaran</h1>
        </div>
        <form method="get" action="{{ route('owner.expenses') }}" class="date-filter">
            <label class="field" for="date">Tanggal<input id="date" type="date" name="date" value="{{ $date }}"></label>
            <button class="button-secondary">Lihat</button>
        </form>
    </header>

    <form method="post" action="{{ route('owner.expenses.store') }}" enctype="multipart/form-data" class="form-card">
        @csrf
        <h2 class="panel-title">Catat pengeluaran</h2>
        <div class="form-grid">
            <label class="field">Nominal (Rp)<input name="amount" type="number" min="1" step="1" value="{{ old('amount') }}" required placeholder="0"></label>
            <label class="field">Tanggal<input name="expense_date" type="date" value="{{ old('expense_date', $date) }}" required></label>
        </div>
        <label class="field">Kategori
            <select name="category_id" required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">Catatan<input name="description" maxlength="500" value="{{ old('description') }}" placeholder="Contoh: gula 5 kg"></label>
        <label class="field">Foto nota (opsional)<input name="receipt" type="file" accept="image/*"></label>
        @if ($errors->any())
            <div class="error-box">{{ $errors->first() }}</div>
        @endif
        <div class="form-actions"><button class="button-primary">Simpan pengeluaran</button></div>
    </form>

    <section class="transaction-list">
        <div class="transaction-list-head">Pengeluaran tercatat</div>
        @forelse ($expenses as $expense)
            <div class="expense-row">
                <div class="expense-main">
                    <strong>{{ $expense->description ?: $expense->category->name }}</strong>
                    <span>{{ $expense->category->name }} · {{ $expense->created_at->timezone('Asia/Jakarta')->format('H:i') }} · {{ $expense->user->name }}</span>
                    @if ($expense->receipt_path)<a class="panel-link" href="{{ route('owner.expenses.receipt', $expense) }}" target="_blank" rel="noreferrer">Lihat foto nota</a>@endif
                </div>
                <div class="expense-actions">
                    <strong>Rp {{ number_format($expense->amount, 0, ',', '.') }}</strong>
                    <a class="button-secondary" href="{{ route('owner.expenses.edit', $expense) }}">Edit</a>
                    <form method="post" action="{{ route('owner.expenses.destroy', $expense) }}" onsubmit="return confirm('Hapus catatan pengeluaran ini?')">
                        @csrf @method('delete')
                        <button class="button-secondary danger-button">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="empty-state">Belum ada pengeluaran pada tanggal ini.</p>
        @endforelse
        @if ($expenses->hasPages())<div class="pagination">{{ $expenses->links() }}</div>@endif
    </section>
@endsection
