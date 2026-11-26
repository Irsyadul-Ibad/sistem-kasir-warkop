<?php

namespace App\Services;

use App\Models\DebtPayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebtService
{
    public function pay(User $user, Transaction $transaction, int $amount, string $paymentMethod): array
    {
        return DB::transaction(function () use ($user, $transaction, $amount, $paymentMethod) {
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($transaction->payment_method !== 'debt' || $transaction->status !== 'debt') {
                throw ValidationException::withMessages(['amount' => 'Bon ini sudah lunas.']);
            }

            $paid = (int) DebtPayment::where('transaction_id', $transaction->id)->sum('amount');
            $remaining = $transaction->total - $paid;
            if ($amount > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Nominal pelunasan melebihi sisa bon.']);
            }

            $paidAt = now();
            DebtPayment::create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'paid_at' => $paidAt,
            ]);
            $paid += $amount;
            $remaining -= $amount;
            $transaction->update([
                'amount_paid' => $paid,
                'status' => $remaining === 0 ? 'paid' : 'debt',
                'paid_at' => $remaining === 0 ? $paidAt : null,
            ]);

            return [
                'transaction' => $transaction->load('customer'),
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
            ];
        });
    }
}
