@extends('layouts.owner')

@section('title', 'Bon pelanggan')

@section('content')
    <header class="page-header">
        <div><p class="eyebrow">Total sisa bon · Rp {{ number_format($outstanding, 0, ',', '.') }}</p><h1 class="page-title">Bon pelanggan</h1></div>
    </header>
    <section class="transaction-list">
        <div class="transaction-list-head">Bon berjalan</div>
        @forelse ($debts as $debt)
            @php($paid = (int) ($debt->paid_amount ?? 0))
            @php($remaining = $debt->total - $paid)
            <article class="debt-row">
                <div>
                    <h2>{{ $debt->customer?->name ?? 'Pelanggan' }}</h2>
                    <p class="transaction-meta">{{ $debt->invoice_no }} · {{ $debt->customer?->phone ?? 'Tanpa nomor telepon' }}</p>
                    <p class="transaction-meta">{{ $debt->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }} · umur {{ (int) $debt->created_at->timezone('Asia/Jakarta')->startOfDay()->diffInDays(now('Asia/Jakarta')->startOfDay()) }} hari</p>
                </div>
                <div class="debt-balance"><span>Sisa bon</span><strong>Rp {{ number_format($remaining, 0, ',', '.') }}</strong><small>Total Rp {{ number_format($debt->total, 0, ',', '.') }} · dibayar Rp {{ number_format($paid, 0, ',', '.') }}</small></div>
                <form method="post" action="{{ route('owner.debts.pay', $debt) }}" class="debt-payment-form">
                    @csrf
                    <label class="field">Nominal pembayaran<input name="amount" type="number" min="1" max="{{ $remaining }}" step="1" value="{{ $remaining }}" required></label>
                    <label class="field">Metode
                        <select name="payment_method" required>
                            <option value="cash">Tunai</option><option value="qris">QRIS</option><option value="transfer">Transfer</option>
                        </select>
                    </label>
                    <button class="button-primary">Catat pelunasan</button>
                </form>
            </article>
        @empty
            <p class="empty-state">Tidak ada bon pelanggan yang berjalan.</p>
        @endforelse
    </section>
@endsection
