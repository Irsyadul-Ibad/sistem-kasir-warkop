@extends('layouts.owner')

@section('title', 'Dashboard')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Ringkasan usaha</p>
            <h1 class="page-title">Dashboard</h1>
        </div>
        <form class="period-chip" method="get" action="{{ route('owner.dashboard') }}">
            <label for="month">Periode</label>
            <input id="month" type="month" name="month" value="{{ $period }}" onchange="this.form.submit()">
        </form>
    </header>

    <section aria-label="Ringkasan bulan ini" class="summary-grid">
        <article class="metric-card">
            <p class="metric-label">Pemasukan</p>
            <p class="metric-value metric-positive">Rp {{ number_format($income, 0, ',', '.') }}</p>
            <p class="metric-note">Transaksi lunas · {{ $monthLabel }}</p>
        </article>
        <article class="metric-card">
            <p class="metric-label">Pengeluaran</p>
            <p class="metric-value metric-negative">Rp {{ number_format($expenseTotal, 0, ',', '.') }}</p>
            <p class="metric-note">Pengeluaran tercatat · {{ $monthLabel }}</p>
        </article>
        <article class="metric-card">
            <p class="metric-label">Laba bersih</p>
            <p class="metric-value">Rp {{ number_format($netProfit, 0, ',', '.') }}</p>
            <p class="metric-note">Pemasukan dikurangi pengeluaran</p>
        </article>
        <article class="metric-card">
            <p class="metric-label">Transaksi</p>
            <p class="metric-value">{{ number_format($transactionCount, 0, ',', '.') }}</p>
            <p class="metric-note">Rata-rata Rp {{ number_format($averageTransaction, 0, ',', '.') }}</p>
        </article>
    </section>

    <section class="report-grid">
        <div class="panel">
            <h2 class="panel-title">Pemasukan harian</h2>
            <p class="panel-subtitle">Transaksi lunas · {{ $monthLabel }}</p>
            <div class="chart-bars" aria-label="Grafik pemasukan harian">
                @foreach ($dailyIncome as $day => $amount)
                    @php($height = $amount ? max(3, (int) round(($amount / $maxDailyIncome) * 100)) : 0)
                    <div class="chart-column" title="{{ $day }} {{ $monthLabel }} · Rp {{ number_format($amount, 0, ',', '.') }}">
                        <div class="chart-bar {{ $day === now('Asia/Jakarta')->day && $period === now('Asia/Jakarta')->format('Y-m') ? 'chart-bar-current' : '' }}" style="height: {{ $height }}%"></div>
                        @if ($day === 1 || $day % 5 === 0 || $day === count($dailyIncome))
                            <span>{{ $day }}</span>
                        @else
                            <span aria-hidden="true">&nbsp;</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <div class="panel">
            <h2 class="panel-title">Metode bayar</h2>
            <p class="panel-subtitle">Dari pemasukan lunas</p>
            <div class="stat-list">
                @foreach ($paymentMethods as $method)
                    <div class="stat-row">
                        <div class="stat-row-label"><span>{{ $method['label'] }}</span><strong>{{ $method['percent'] }}%</strong></div>
                        <div class="stat-track"><span style="width: {{ $method['percent'] }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="panel">
            <h2 class="panel-title">Menu terlaris</h2>
            <p class="panel-subtitle">{{ $monthLabel }}</p>
            <div class="rank-list">
                @forelse ($topMenus as $menu)
                    <div class="rank-row"><span>{{ $menu->menu_name_snapshot }}</span><strong>{{ number_format($menu->quantity, 0, ',', '.') }} terjual</strong></div>
                @empty
                    <p class="empty-state">Belum ada penjualan pada periode ini.</p>
                @endforelse
            </div>
        </div>
        <div class="panel">
            <div class="panel-header">
                <div><h2 class="panel-title">Pengeluaran per kategori</h2><p class="panel-subtitle">{{ $monthLabel }}</p></div>
                <a href="{{ route('owner.expenses') }}" class="panel-link">Kelola</a>
            </div>
            <div class="rank-list">
                @forelse ($expenseByCategory as $category)
                    <div class="rank-row"><span>{{ $category->name }}</span><strong>Rp {{ number_format($category->amount, 0, ',', '.') }}</strong></div>
                @empty
                    <p class="empty-state">Belum ada pengeluaran pada periode ini.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel recent-panel">
        <div class="panel-header">
            <div><h2 class="panel-title">Transaksi terbaru hari ini</h2><p class="panel-subtitle">Waktu Asia/Jakarta</p></div>
            <a href="{{ route('owner.transactions') }}" class="panel-link">Lihat semua</a>
        </div>
        <div class="transaction-rows">
            @forelse ($recentTransactions as $transaction)
                <div class="transaction-row">
                    <div>
                        <p><strong>{{ $transaction->invoice_no }}</strong></p>
                        <p class="transaction-meta">{{ $transaction->user?->name ?? 'Crew' }} · {{ $transaction->paid_at?->timezone('Asia/Jakarta')->format('H:i') }}</p>
                    </div>
                    <p><strong>Rp {{ number_format($transaction->total, 0, ',', '.') }}</strong></p>
                </div>
            @empty
                <p class="empty-state">Belum ada transaksi lunas hari ini.</p>
            @endforelse
        </div>
    </section>
@endsection
