<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Expense;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerWebController extends Controller
{
    public function loginForm()
    {
        if (Auth::check() && Auth::user()->role === 'owner' && Auth::user()->is_active) {
            return redirect()->route('owner.dashboard');
        }

        return view('owner.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials + ['role' => 'owner', 'is_active' => true])) {
            return back()->withErrors(['username' => 'Username atau password owner salah.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('owner.dashboard'));
    }

    public function dashboard(Request $request)
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $period = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? $now->format('Y-m');
        $monthStart = CarbonImmutable::createFromFormat('Y-m-d', $period.'-01', 'Asia/Jakarta')->startOfMonth();
        $monthStartUtc = $monthStart->utc();
        $monthEndUtc = $monthStart->addMonth()->utc();

        $monthTransactions = Transaction::query()
            ->where('status', 'paid')
            ->where('payment_method', '!=', 'debt')
            ->where('paid_at', '>=', $monthStartUtc)
            ->where('paid_at', '<', $monthEndUtc);
        $debtIncome = (int) DB::table('debt_payments')
            ->whereNull('deleted_at')
            ->where('paid_at', '>=', $monthStartUtc)
            ->where('paid_at', '<', $monthEndUtc)
            ->sum('amount');
        $income = (int) (clone $monthTransactions)->sum('total') + $debtIncome;
        $transactionCount = (int) (clone $monthTransactions)->count();
        $expenseQuery = Expense::query()
            ->where('expense_date', '>=', $monthStart->toDateString())
            ->where('expense_date', '<', $monthStart->addMonth()->toDateString());
        $expenseTotal = (int) (clone $expenseQuery)->sum('amount');
        $dailyTransactions = (clone $monthTransactions)->get(['total', 'paid_at']);
        $days = $monthStart->daysInMonth;
        $dailyIncome = collect(range(1, $days))->mapWithKeys(fn ($day) => [$day => 0]);
        foreach ($dailyTransactions as $transaction) {
            $day = $transaction->paid_at->timezone('Asia/Jakarta')->day;
            $dailyIncome[$day] += $transaction->total;
        }
        $debtPayments = DB::table('debt_payments')
            ->whereNull('deleted_at')
            ->where('paid_at', '>=', $monthStartUtc)
            ->where('paid_at', '<', $monthEndUtc)
            ->get(['amount', 'payment_method', 'paid_at']);
        foreach ($debtPayments as $payment) {
            $day = CarbonImmutable::parse($payment->paid_at)->timezone('Asia/Jakarta')->day;
            $dailyIncome[$day] += $payment->amount;
        }
        $maxDailyIncome = max(1, (int) $dailyIncome->max());
        $paymentAmounts = (clone $monthTransactions)
            ->selectRaw('payment_method, SUM(total) as amount')
            ->groupBy('payment_method')
            ->pluck('amount', 'payment_method');
        foreach ($debtPayments->groupBy('payment_method') as $method => $payments) {
            $paymentAmounts[$method] = (int) ($paymentAmounts[$method] ?? 0) + (int) $payments->sum('amount');
        }
        $paymentMethods = collect(['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'debt' => 'Bon'])
            ->map(fn ($label, $method) => [
                'label' => $label,
                'amount' => (int) ($paymentAmounts[$method] ?? 0),
                'percent' => $income ? (int) round(((int) ($paymentAmounts[$method] ?? 0) / $income) * 100) : 0,
            ]);
        $topMenus = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'paid')
            ->where('transactions.paid_at', '>=', $monthStartUtc)
            ->where('transactions.paid_at', '<', $monthEndUtc)
            ->select('transaction_items.menu_name_snapshot')
            ->selectRaw('SUM(transaction_items.qty) as quantity, SUM(transaction_items.line_total) as amount')
            ->groupBy('transaction_items.menu_name_snapshot')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();
        $expenseByCategory = DB::table('expenses')
            ->join('categories', 'categories.id', '=', 'expenses.category_id')
            ->whereNull('expenses.deleted_at')
            ->where('expenses.expense_date', '>=', $monthStart->toDateString())
            ->where('expenses.expense_date', '<', $monthStart->addMonth()->toDateString())
            ->select('categories.name')
            ->selectRaw('SUM(expenses.amount) as amount')
            ->groupBy('categories.name')
            ->orderByDesc('amount')
            ->get();

        $todayStartUtc = $now->startOfDay()->utc();
        $todayEndUtc = $todayStartUtc->addDay();
        $recentTransactions = Transaction::with('user')
            ->where('status', 'paid')
            ->where('paid_at', '>=', $todayStartUtc)
            ->where('paid_at', '<', $todayEndUtc)
            ->latest('paid_at')
            ->limit(5)
            ->get();

        return view('owner.dashboard', [
            'monthLabel' => $monthStart->locale('id')->translatedFormat('F Y'),
            'period' => $period,
            'income' => $income,
            'expenseTotal' => $expenseTotal,
            'netProfit' => $income - $expenseTotal,
            'transactionCount' => $transactionCount,
            'averageTransaction' => $transactionCount ? intdiv($income, $transactionCount) : 0,
            'activeMenuCount' => Menu::where('is_active', true)->count(),
            'dailyIncome' => $dailyIncome,
            'maxDailyIncome' => $maxDailyIncome,
            'paymentMethods' => $paymentMethods,
            'topMenus' => $topMenus,
            'expenseByCategory' => $expenseByCategory,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    public function transactions(Request $request)
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = CarbonImmutable::parse($data['date'] ?? now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta');
        $startUtc = $date->startOfDay()->utc();
        $endUtc = $startUtc->addDay();

        $transactions = Transaction::with(['user', 'items'])
            ->where('status', 'paid')
            ->where('paid_at', '>=', $startUtc)
            ->where('paid_at', '<', $endUtc)
            ->latest('paid_at')
            ->paginate(20)
            ->withQueryString();

        return view('owner.transactions', [
            'date' => $date->toDateString(),
            'transactions' => $transactions,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}
