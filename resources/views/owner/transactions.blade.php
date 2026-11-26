@extends('layouts.owner')

@section('title', 'Transaksi')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Riwayat kasir</p>
            <h1 class="page-title">Transaksi</h1>
        </div>
        <form method="get" class="date-filter">
            <label class="field">
                Tanggal
                <input type="date" name="date" value="{{ $date }}">
            </label>
            <button class="button-primary">Tampilkan</button>
        </form>
    </header>

    <section class="transaction-list">
        <div class="transaction-list-head">
            <p><strong>{{ $transactions->total() }} transaksi</strong> · {{ $date }}</p>
        </div>
        <div>
            @forelse ($transactions as $transaction)
                <article class="transaction-row">
                    <div>
                        <p><strong>{{ $transaction->invoice_no }}</strong></p>
                        <p class="transaction-meta">{{ $transaction->paid_at?->timezone('Asia/Jakarta')->format('H:i') }} · {{ $transaction->user?->name ?? 'Crew' }}</p>
                    </div>
                    <div class="transaction-meta">
                        <span>{{ $transaction->items->sum('qty') }} item</span>
                        <span>{{ ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'debt' => 'Bon'][$transaction->payment_method] ?? ucfirst($transaction->payment_method) }}</span>
                        @if ($transaction->table_label)
                            <span>{{ $transaction->table_label }}</span>
                        @endif
                    </div>
                    <p><strong>Rp {{ number_format($transaction->total, 0, ',', '.') }}</strong></p>
                </article>
            @empty
                <p class="empty-state">Belum ada transaksi lunas pada tanggal ini.</p>
            @endforelse
        </div>
        @if ($transactions->hasPages())
            <div class="pagination">{{ $transactions->links() }}</div>
        @endif
    </section>
@endsection
