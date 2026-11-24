<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(TransactionRequest $request, TransactionService $service)
    {
        $transaction = $service->create($request->user(), $request->validated());

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode($transaction->wasRecentlyCreated ? 201 : 200);
    }

    public function index(Request $request)
    {
        $request->validate([
            'date'     => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // hari dihitung dengan zona waktu Asia/Jakarta, data disimpan dalam UTC
        $date  = $request->input('date') ?? now('Asia/Jakarta')->toDateString();
        $start = Carbon::parse($date, 'Asia/Jakarta')->startOfDay()->utc();
        $end   = $start->copy()->addDay();

        $query = Transaction::with('items')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end)
            ->latest('paid_at');

        // kasir hanya melihat transaksinya sendiri; owner melihat semua
        if ($request->user()->role !== 'owner') {
            $query->where('user_id', $request->user()->id);
        }

        return TransactionResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function show(Request $request, Transaction $transaction)
    {
        if ($request->user()->role !== 'owner'
            && $transaction->user_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        return new TransactionResource($transaction->load('items'));
    }
}