<?php

namespace App\Services;

use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
use Carbon\Carbon;

class BadgerBalanceService
{
    public function markDirty(string $userId, string $layerId, string $accountId, string $monthKey): void
    {
        BudMonthTotal::updateOrCreate(
            ['layer_id' => $layerId, 'account_id' => $accountId, 'month_key' => $monthKey],
            ['user_id' => $userId, 'is_dirty' => 1, 'updated_at' => now()]
        );
    }

    public function recalcMonth(string $layerId, string $accountId, string $monthKey): void
    {
        // opening = closing previous month
        $prev = Carbon::createFromFormat('Y-m', $monthKey)->subMonth()->format('Y-m');
        $prevTotal = BudMonthTotal::where([
            'layer_id' => $layerId,
            'account_id' => $accountId,
            'month_key' => $prev,
        ])->first();
        $opening = $prevTotal?->closing_balance ?? 0;

        $txs = BudTransaction::where('account_id', $accountId)
            ->where('layer_id', $layerId)
            ->where('month_key', $monthKey)
            ->where('is_disabled', 0)
            ->whereNull('deleted_at')
            ->get();

        $income = $txs->where('flow_kind', 'income')->sum('amount');
        $expense = $txs->where('flow_kind', 'expense')->sum('amount');
        $transferIn = $txs->where('flow_kind', 'transfer_in')->sum('amount');
        $transferOut = $txs->where('flow_kind', 'transfer_out')->sum('amount');
        $adjustment = $txs->whereIn('flow_kind', ['adjustment'])->sum('amount');

        $closing = $opening + $income - $expense + $transferIn - $transferOut + $adjustment;

        BudMonthTotal::updateOrCreate(
            ['layer_id' => $layerId, 'account_id' => $accountId, 'month_key' => $monthKey],
            [
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'income_total' => $income,
                'expense_total' => $expense,
                'transfer_in_total' => $transferIn,
                'transfer_out_total' => $transferOut,
                'adjustment_total' => $adjustment,
                'tx_count' => $txs->count(),
                'is_dirty' => 0,
                'updated_at' => now(),
            ]
        );
    }
}
