<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudLayer;
use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BadgerTransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = BudTransaction::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereBetween('occurred_at', [
                $request->get('start'),
                $request->get('end'),
            ])
            ->orderBy('occurred_at', 'desc')
            ->orderBy('sort_order');

        // Filter by accounts — comma-separated
        if ($request->filled('account_id')) {
            $ids = explode(',', $request->get('account_id'));
            $query->whereIn('account_id', $ids);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|string',
            'target_account_id' => 'nullable|string',
            'flow_kind' => 'required|in:expense,income,transfer_out,transfer_in,adjustment',
            'amount' => 'required|integer|min:1', // копейки, > 0
            'occurred_at' => 'required|date',
            'title' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'status' => 'nullable|in:cleared,pending',
            'group_id' => 'nullable|string',
        ]);

        $user = $request->user();
        $layer = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();
        $monthKey = Carbon::parse($data['occurred_at'])->format('Y-m');

        DB::transaction(function () use ($data, $user, $layer, $monthKey) {
            $tx = BudTransaction::create([
                ...$data,
                'user_id' => $user->id,
                'layer_id' => $layer->id,
                'month_key' => $monthKey,
            ]);

            // If transfer — create paired transaction
            if ($data['flow_kind'] === 'transfer_out' && $data['target_account_id']) {
                BudTransaction::create([
                    'user_id' => $user->id,
                    'layer_id' => $layer->id,
                    'account_id' => $data['target_account_id'],
                    'target_account_id' => $data['account_id'],
                    'flow_kind' => 'transfer_in',
                    'amount' => $data['amount'],
                    'occurred_at' => $data['occurred_at'],
                    'month_key' => $monthKey,
                    'title' => $data['title'] ?? null,
                    'status' => $data['status'] ?? 'cleared',
                    // Link the pair
                    'original_transaction_id' => $tx->id,
                ]);
            }

            // Mark month_totals as dirty
            $this->markDirty($user->id, $layer->id, $data['account_id'], $monthKey);
            if ($data['target_account_id'] ?? null) {
                $this->markDirty($user->id, $layer->id, $data['target_account_id'], $monthKey);
            }
        });
    }

    public function show(Request $request, string $id)
    {
        return BudTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'account_id' => 'nullable|string',
            'target_account_id' => 'nullable|string',
            'flow_kind' => 'nullable|in:expense,income,transfer_out,transfer_in,adjustment',
            'amount' => 'nullable|integer|min:1', // копейки, > 0
            'occurred_at' => 'nullable|date',
            'title' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'status' => 'nullable|in:cleared,pending',
            'group_id' => 'nullable|string',
        ]);

        $tx = BudTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $tx->update($data);

        return $tx;
    }

    public function destroy(Request $request, string $id)
    {
        $tx = BudTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $tx->delete();

        return response()->json(['message' => 'Transaction deleted']);
    }

    public function move(Request $request, string $id)
    {
        $data = $request->validate([
            'occurred_at' => 'nullable|date',
            'account_id' => 'nullable|string',
        ]);

        $tx = BudTransaction::where('user_id', $request->user()->id)->findOrFail($id);
        $old = ['account_id' => $tx->account_id, 'month_key' => $tx->month_key];

        if ($data['occurred_at'] ?? null) {
            $tx->occurred_at = $data['occurred_at'];
            $tx->month_key = Carbon::parse($data['occurred_at'])->format('Y-m');
        }
        if ($data['account_id'] ?? null) {
            $tx->account_id = $data['account_id'];
        }
        $tx->save();

        // Mark dirty both months and both accounts
        $layer = BudLayer::where('user_id', $request->user()->id)->where('type', 'base')->first();
        $this->markDirty($request->user()->id, $layer->id, $old['account_id'], $old['month_key']);
        $this->markDirty($request->user()->id, $layer->id, $tx->account_id, $tx->month_key);

        return $tx;
    }

    private function markDirty(string $userId, string $layerId, string $accountId, string $monthKey): void
    {
        BudMonthTotal::updateOrCreate(
            ['layer_id' => $layerId, 'account_id' => $accountId, 'month_key' => $monthKey],
            ['user_id' => $userId, 'is_dirty' => 1, 'updated_at' => now()]
        );
    }
}
