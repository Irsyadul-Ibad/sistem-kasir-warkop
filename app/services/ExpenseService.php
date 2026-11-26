<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(User $user, array $data): Expense
    {
        return DB::transaction(function () use ($user, $data) {
            $expense = Expense::create($data + ['user_id' => $user->id]);
            $this->audit($user, 'expense.created', $expense, null);

            return $expense->load('category');
        });
    }

    public function update(User $user, Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($user, $expense, $data) {
            $before = $expense->only(['category_id', 'amount', 'expense_date', 'description', 'receipt_path']);
            $expense->update($data);
            $this->audit($user, 'expense.updated', $expense, $before);

            return $expense->load('category');
        });
    }

    public function delete(User $user, Expense $expense): void
    {
        DB::transaction(function () use ($user, $expense) {
            $before = $expense->only(['category_id', 'amount', 'expense_date', 'description', 'receipt_path']);
            $expense->delete();
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'expense.deleted',
                'entity_type' => 'expense',
                'entity_id' => $expense->id,
                'before_data' => $before,
                'after_data' => null,
            ]);
        });
    }

    private function audit(User $user, string $action, Expense $expense, ?array $before): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => 'expense',
            'entity_id' => $expense->id,
            'before_data' => $before,
            'after_data' => $expense->only(['category_id', 'amount', 'expense_date', 'description', 'receipt_path']),
        ]);
    }
}
