<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function current(Request $request)
    {
        return response()->json(['data' => Shift::where('user_id', $request->user()->id)->where('status', 'active')->latest('opened_at')->first()]);
    }

    public function open(Request $request, ShiftService $service)
    {
        $data = $request->validate(['opening_cash' => ['required', 'integer', 'min:0', 'max:2147483647']]);

        return response()->json(['data' => $service->open($request->user(), (int) $data['opening_cash'])], 201);
    }

    public function close(Request $request, Shift $shift, ShiftService $service)
    {
        $data = $request->validate(['actual_cash' => ['required', 'integer', 'min:0', 'max:2147483647']]);

        return response()->json(['data' => $service->close($request->user(), $shift, (int) $data['actual_cash'])]);
    }
}
