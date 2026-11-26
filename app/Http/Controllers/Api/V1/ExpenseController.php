<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExpenseController extends Controller
{
    public function receipt(Request $request, Expense $expense): StreamedResponse
    {
        abort_unless($request->user()->role === 'owner' || $expense->user_id === $request->user()->id, 403);
        abort_unless($expense->receipt_path, 404);

        return Storage::disk('public')->response($expense->receipt_path);
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $date = CarbonImmutable::parse($data['date'] ?? now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta')->toDateString();
        $query = Expense::with(['category', 'user'])->whereDate('expense_date', $date);

        if ($request->user()->role !== 'owner') {
            $query->where('user_id', $request->user()->id);
        }

        $total = (int) (clone $query)->sum('amount');
        $expenses = $query->latest('created_at')->paginate($request->integer('per_page', 100));

        return ExpenseResource::collection($expenses)
            ->additional(['summary' => ['date' => $date, 'total' => $total]]);
    }

    public function store(ExpenseRequest $request, ExpenseService $service)
    {
        $data = $request->validated();
        $path = $request->file('receipt')?->store('expense-receipts', 'public');
        unset($data['receipt']);

        try {
            $expense = $service->create($request->user(), $data + ['receipt_path' => $path]);
        } catch (Throwable $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $error;
        }

        return (new ExpenseResource($expense->load('user')))->response()->setStatusCode(201);
    }

    public function update(ExpenseRequest $request, Expense $expense, ExpenseService $service)
    {
        $oldPath = $expense->receipt_path;
        $data = $request->validated();
        $path = $request->file('receipt')?->store('expense-receipts', 'public');
        unset($data['receipt']);
        if ($path) {
            $data['receipt_path'] = $path;
        }

        $updated = $service->update($request->user(), $expense, $data);
        if ($path && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return new ExpenseResource($updated->load('user'));
    }

    public function destroy(Request $request, Expense $expense, ExpenseService $service)
    {
        $path = $expense->receipt_path;
        $service->delete($request->user(), $expense);
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return response()->noContent();
    }
}
