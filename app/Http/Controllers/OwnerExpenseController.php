<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OwnerExpenseController extends Controller
{
    public function receipt(Expense $expense): BinaryFileResponse
    {
        abort_unless($expense->receipt_path, 404);

        return response()->file(Storage::disk('public')->path($expense->receipt_path));
    }

    public function index(Request $request)
    {
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date']
            ?? now('Asia/Jakarta')->toDateString();
        $expenses = Expense::with(['category', 'user'])
            ->whereDate('expense_date', $date)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('owner.expenses.index', [
            'date' => $date,
            'total' => (int) Expense::whereDate('expense_date', $date)->sum('amount'),
            'categories' => Category::where('type', 'expense')->orderBy('sort_order')->orderBy('name')->get(),
            'expenses' => $expenses,
        ]);
    }

    public function edit(Expense $expense)
    {
        return view('owner.expenses.form', [
            'expense' => $expense->load('category'),
            'categories' => Category::where('type', 'expense')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseRequest $request, ExpenseService $service)
    {
        $data = $request->validated();
        $data['receipt_path'] = $request->file('receipt')?->store('expense-receipts', 'public');
        unset($data['receipt']);
        $service->create($request->user(), $data);

        return redirect()->route('owner.expenses', ['date' => $data['expense_date']])
            ->with('status', 'Pengeluaran berhasil dicatat.');
    }

    public function update(ExpenseRequest $request, Expense $expense, ExpenseService $service)
    {
        $oldPath = $expense->receipt_path;
        $data = $request->validated();
        $newPath = $request->file('receipt')?->store('expense-receipts', 'public');
        unset($data['receipt']);
        if ($newPath) {
            $data['receipt_path'] = $newPath;
        }
        $service->update($request->user(), $expense, $data);
        if ($newPath && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('owner.expenses', ['date' => $data['expense_date']])
            ->with('status', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Request $request, Expense $expense, ExpenseService $service)
    {
        $date = $expense->expense_date->format('Y-m-d');
        $path = $expense->receipt_path;
        $service->delete($request->user(), $expense);
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('owner.expenses', ['date' => $date])
            ->with('status', 'Pengeluaran berhasil dihapus.');
    }
}
