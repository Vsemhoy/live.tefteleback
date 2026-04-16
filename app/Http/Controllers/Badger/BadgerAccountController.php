<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudAccount;
use App\Models\BudLayer;
use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BadgerAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $layer = BudLayer::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'base'],
            ['id' => (string) Str::ulid(), 'name' => 'Base', 'is_active' => 1]
        );

        $accounts = BudAccount::where('user_id', $user->id)
            ->where('layer_id', $layer->id)
            ->where('is_archived', 0)
            ->withCount(['transactions' => fn($q) => $q->whereNull('deleted_at')])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($account) use ($layer) {
                $account->balance_today    = $this->calcBalanceToday($account, $layer);
                $account->has_transactions = $account->transactions_count > 0;
                return $account;
            });

        return response()->json(['status' => 1, 'content' => $accounts]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'literals'         => 'nullable|string|max:3',
            'type'             => 'required|in:cash,card,credit,deposit,phantom',
            'currency'         => 'nullable|string|size:3',
            'color'            => 'nullable|string|max:20',
            'sort_order'       => 'nullable|integer',
            'opening_balance'  => 'nullable|integer',
            'opened_at'        => 'nullable|date',
            'closed_at'        => 'nullable|date',
            'interest_rate'    => 'nullable|integer',  // INT: 2350 = 23.5%
            'interest_start'   => 'nullable|date',
        ]);

        $layer = BudLayer::where('user_id', $user->id)
            ->where('type', 'base')
            ->firstOrFail();

        $account = BudAccount::create([
            'id'      => (string) Str::ulid(),
            'user_id' => $user->id,
            'layer_id'=> $layer->id,
            ...$data,
        ]);

        return response()->json(['status' => 1, 'content' => $account], 201);
    }

    public function update(Request $request, string $id)
    {
        $account = BudAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate([
            'name'            => 'nullable|string|max:100',
            'literals'        => 'nullable|string|max:3',
            'type'            => 'nullable|in:cash,card,credit,deposit,phantom',
            'currency'        => 'nullable|string|size:3',
            'color'           => 'nullable|string|max:20',
            'sort_order'      => 'nullable|integer',
            'opening_balance' => 'nullable|integer',
            'is_archived'     => 'nullable|boolean',
            'opened_at'       => 'nullable|date',
            'closed_at'       => 'nullable|date',
            // interest_rate и interest_start лочатся если есть транзакции
            'interest_rate'   => 'nullable|integer',
            'interest_start'  => 'nullable|date',
        ]);

        // Защита: не менять ставку если уже есть транзакции
        $hasTx = BudTransaction::where('account_id', $id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasTx) {
            unset($data['interest_rate'], $data['interest_start']);
        }

        $account->update($data);

        return response()->json(['status' => 1, 'content' => $account->fresh()]);
    }

    public function destroy(Request $request, string $id)
    {
        $account = BudAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $account->delete();

        return response()->json(['status' => 1, 'message' => 'Account deleted']);
    }

    private function calcBalanceToday(BudAccount $account, BudLayer $layer): int
    {
        $today    = now()->format('Y-m-d');
        $prevKey  = now()->subMonth()->format('Y-m');

        // Opening = closing предыдущего месяца
        $prevTotal = BudMonthTotal::where('account_id', $account->id)
            ->where('layer_id', $layer->id)
            ->where('month_key', $prevKey)
            ->first();

        $opening = $prevTotal?->closing_balance ?? 0;

        // Delta = все транзакции с начала месяца по сегодня
        $txs = BudTransaction::where('account_id', $account->id)
            ->where('layer_id', $layer->id)
            ->where('is_disabled', 0)
            ->whereNull('deleted_at')
            ->whereMonth('occurred_at', now()->month)
            ->whereYear('occurred_at', now()->year)
            ->where('occurred_at', '<=', $today)
            ->get();

        $delta = $txs->sum(function ($tx) {
            // + для income, transfer_in, reconciliation положительных
            // − для expense, transfer_out, reconciliation отрицательных (is_negative)
            if (in_array($tx->flow_kind, ['income', 'transfer_in'])) {
                return $tx->amount;
            }
            if ($tx->flow_kind === 'reconciliation') {
                return $tx->is_negative ? -$tx->amount : $tx->amount;
            }
            return -$tx->amount; // expense, transfer_out, adjustment
        });

        return $opening + $delta;
    }
}