<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // TransactionController
public function store(TransactionRequest $request, TransactionService $service)
{
    $transaction = $service->create($request->user(), $request->validated());

    return (new TransactionResource($transaction))
        ->response()
        ->setStatusCode($transaction->wasRecentlyCreated ? 201 : 200);
}
}
