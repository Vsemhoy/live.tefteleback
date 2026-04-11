<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudAccount;
use App\Models\BudLayer;
use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
use Illuminate\Http\Request;

class BadgerAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Защита: если юзер не авторизован
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Достаём base layer, если нет — создаём автоматически
        $layer = BudLayer::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'base'],
            ['id' => (string) \Illuminate\Support\Str::ulid(), 'name' => 'Base', 'is_active' => 1]
        );

        return BudAccount::where('user_id', $user->id)
            ->where('layer_id', $layer->id)
            ->where('is_archived', 0)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($account) use ($layer) {
                $account->balance_today = $this->calcBalanceToday($account, $layer);
                return $account;
            });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,card,credit,deposit,phantom',
            'currency' => 'nullable|string|size:3',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
            'opening_balance' => 'nullable|integer',
        ]);

        $user = $request->user();
        $layer = BudLayer::where('user_id', $user->id)
            ->where('type', 'base')
            ->first();

        $account = BudAccount::create([
            'user_id' => $user->id,
            'layer_id' => $layer->id,
            ...$data,
        ]);

        return $account;
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
            'type' => 'nullable|in:cash,card,credit,deposit,phantom',
            'currency' => 'nullable|string|size:3',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
            'opening_balance' => 'nullable|integer',
            'is_archived' => 'nullable|boolean',
        ]);

        $account = BudAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $account->update($data);

        return $account;
    }

    public function destroy(Request $request, string $id)
    {
        $account = BudAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $account->delete();

        return response()->json(['message' => 'Account deleted']);
    }

    private function calcBalanceToday(BudAccount $account, BudLayer $layer): int
    {
        $today = now()->format('Y-m-d');
        $monthKey = now()->format('Y-m');
        $prevKey = now()->subMonth()->format('Y-m');

        // opening = closing предыдущего месяца
        $prevTotal = BudMonthTotal::where('account_id', $account->id)
            ->where('layer_id', $layer->id)
            ->where('month_key', $prevKey)
            ->first();

        $opening = $prevTotal?->closing_balance ?? $account->opening_balance;

        // delta = сумма транзакций с начала месяца по сегодня
        $delta = BudTransaction::where('account_id', $account->id)
            ->where('layer_id', $layer->id)
            ->where('is_disabled', 0)
            ->whereNull('deleted_at')
            ->whereMonth('occurred_at', now()->month)
            ->whereYear('occurred_at', now()->year)
            ->where('occurred_at', '<=', $today)
            ->get()
            ->sum(fn ($tx) => in_array($tx->flow_kind, ['income', 'transfer_in'])
                ? $tx->amount : -$tx->amount
            );

        return $opening + $delta;
    }
}
