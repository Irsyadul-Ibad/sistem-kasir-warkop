<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function open(User $user, int $openingCash): Shift
    {
        return DB::transaction(function () use ($user, $openingCash) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (Shift::where('user_id', $user->id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['shift' => 'Masih ada shift aktif. Tutup shift tersebut terlebih dahulu.']);
            }

            $shift = Shift::create([
                'user_id' => $user->id,
                'opening_cash' => $openingCash,
                'opened_at' => now(),
            ]);
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'shift.opened',
                'entity_type' => 'shift',
                'entity_id' => $shift->id,
                'before_data' => null,
                'after_data' => $shift->only(['opening_cash', 'opened_at']),
            ]);

            return $shift;
        });
    }

    public function close(User $user, Shift $shift, int $actualCash): Shift
    {
        return DB::transaction(function () use ($user, $shift, $actualCash) {
            $shift = Shift::whereKey($shift->id)->lockForUpdate()->firstOrFail();
            abort_unless($shift->user_id === $user->id, 403);
            if ($shift->status !== 'active') {
                throw ValidationException::withMessages(['shift' => 'Shift ini sudah ditutup.']);
            }

            $closedAt = now();
            $cashSales = (int) Transaction::where('user_id', $user->id)
                ->where('status', 'paid')
                ->where('payment_method', 'cash')
                ->where('paid_at', '>=', $shift->opened_at)
                ->where('paid_at', '<=', $closedAt)
                ->sum('total');
            $cashDebtPayments = (int) DebtPayment::where('user_id', $user->id)
                ->where('payment_method', 'cash')
                ->where('paid_at', '>=', $shift->opened_at)
                ->where('paid_at', '<=', $closedAt)
                ->sum('amount');
            $cashExpenses = (int) Expense::where('user_id', $user->id)
                ->where('created_at', '>=', $shift->opened_at)
                ->where('created_at', '<=', $closedAt)
                ->sum('amount');
            $cashSales += $cashDebtPayments;
            $expectedCash = $shift->opening_cash + $cashSales - $cashExpenses;

            $shift->update([
                'cash_sales' => $cashSales,
                'cash_expenses' => $cashExpenses,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $actualCash - $expectedCash,
                'status' => 'closed',
                'closed_at' => $closedAt,
            ]);
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'shift.closed',
                'entity_type' => 'shift',
                'entity_id' => $shift->id,
                'before_data' => ['status' => 'active'],
                'after_data' => $shift->only(['cash_sales', 'cash_expenses', 'expected_cash', 'actual_cash', 'difference', 'closed_at']),
            ]);

            return $shift->refresh();
        });
    }
}
