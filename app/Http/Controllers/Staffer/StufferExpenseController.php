<?php

namespace App\Http\Controllers\Staffer;

use App\Http\Controllers\Controller;
use App\Models\StfExpense;
use App\Models\StfThing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StufferExpenseController extends Controller
{
    // GET /stuffer/expenses?thing_id=
    public function index(Request $request)
    {
        $query = StfExpense::where('user_id', Auth::id())
            ->with(['register', 'transaction'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        if ($request->thing_id) {
            $query->where('thing_id', $request->thing_id);
        }

        $limit = min((int) $request->limit ?: 50, 200);

        return response()->json([
            'status' => 1,
            'content' => $query->limit($limit)->get(),
        ]);
    }

    // POST /stuffer/expenses
    public function store(Request $request)
    {
        $data = $request->validate([
            'thing_id' => 'required|string|max:26',
            'register_id' => 'nullable|string|max:26',
            'transaction_id' => 'nullable|string|max:26',
            'amount' => 'nullable|integer',
            'note' => 'nullable|string',
            'occurred_at' => 'required|date',
        ]);

        StfThing::where('user_id', Auth::id())->findOrFail($data['thing_id']);

        if (empty($data['amount']) && empty($data['transaction_id'])) {
            return response()->json([
                'status' => 0,
                'message' => 'Provide amount or link a Badger transaction',
            ], 422);
        }

        if (!empty($data['transaction_id']) && empty($data['amount'])) {
            $tx = \App\Models\BudTransaction::find($data['transaction_id']);
            if ($tx) {
                $data['amount'] = $tx->amount;
            }
        }

        $expense = StfExpense::create([
            ...$data,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'status' => 1,
            'content' => $expense->load(['register', 'transaction']),
        ], 201);
    }

    // DELETE /stuffer/expenses/{id}
    public function destroy(string $id)
    {
        $expense = StfExpense::where('user_id', Auth::id())->findOrFail($id);
        $expense->delete();

        return response()->json(['status' => 1]);
    }
}
