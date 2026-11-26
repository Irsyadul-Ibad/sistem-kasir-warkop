<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\DebtService;
use Illuminate\Http\Request;

class OwnerDebtController extends Controller
{
    public function index()
    {
        $debts = Transaction::query()
            ->with('customer')
            ->withSum('debtPayments as paid_amount', 'amount')
            ->where('payment_method', 'debt')
            ->where('status', 'debt')
            ->latest('created_at')
            ->get();

        return view('owner.debts.index', [
            'debts' => $debts,
            'outstanding' => (int) $debts->sum(fn ($debt) => $debt->total - (int) ($debt->paid_amount ?? 0)),
        ]);
    }

    public function pay(Request $request, Transaction $transaction, DebtService $service)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
        ]);
        $service->pay($request->user(), $transaction, (int) $data['amount'], $data['payment_method']);

        return redirect()->route('owner.debts')->with('status', 'Pembayaran bon berhasil dicatat.');
    }
}
