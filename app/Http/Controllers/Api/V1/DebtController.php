<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DebtPayment;
use App\Models\Transaction;
use App\Services\DebtService;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index()
    {
        $debts = Transaction::query()
            ->with('customer')
            ->withSum('debtPayments as paid_amount', 'amount')
            ->where('payment_method', 'debt')
            ->where('status', 'debt')
            ->latest('created_at')
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no,
                'customer' => $transaction->customer ? [
                    'id' => $transaction->customer->id,
                    'name' => $transaction->customer->name,
                    'phone' => $transaction->customer->phone,
                ] : null,
                'total' => $transaction->total,
                'paid_amount' => (int) ($transaction->paid_amount ?? 0),
                'remaining_amount' => $transaction->total - (int) ($transaction->paid_amount ?? 0),
                'created_at' => $transaction->created_at,
                'age_days' => (int) $transaction->created_at->timezone('Asia/Jakarta')->startOfDay()->diffInDays(now('Asia/Jakarta')->startOfDay()),
            ]);

        return response()->json(['data' => $debts]);
    }

    public function pay(Request $request, Transaction $transaction, DebtService $service)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
        ]);
        $result = $service->pay($request->user(), $transaction, $data['amount'], $data['payment_method']);

        return response()->json(['data' => [
            'transaction_id' => $result['transaction']->id,
            'invoice_no' => $result['transaction']->invoice_no,
            'paid_amount' => $result['paid_amount'],
            'remaining_amount' => $result['remaining_amount'],
            'status' => $result['transaction']->status,
            'paid_at' => $result['transaction']->paid_at,
        ]]);
    }

    public function payments(Transaction $transaction)
    {
        abort_unless($transaction->payment_method === 'debt', 404);

        return response()->json([
            'data' => DebtPayment::with('user')
                ->where('transaction_id', $transaction->id)
                ->orderBy('paid_at')
                ->get()
                ->map(fn (DebtPayment $payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'paid_at' => $payment->paid_at,
                    'user' => $payment->user->name,
                ]),
        ]);
    }
}
