<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
use Carbon\Carbon;


class BadgerClosingController extends Controller
{



    // getMonthsFrom — ТОЛЬКО возвращает массив месяцев, ничего не пересчитывает
    private function getMonthsFrom(string $layerId, string $accountId, string $fromMonthKey): array
    {
        $currentMonthKey = now()->format('Y-m');

        $months = BudMonthTotal::where('layer_id', $layerId)
            ->where('account_id', $accountId)
            ->where('month_key', '>=', $fromMonthKey)
            ->where('month_key', '<=', $currentMonthKey)
            ->orderBy('month_key', 'asc')
            ->pluck('month_key')
            ->toArray();

        // Фоллбэк — генерируем диапазон если записей нет
        if (empty($months)) {
            $months = [];
            $cursor = Carbon::createFromFormat('Y-m', $fromMonthKey)->startOfMonth();
            $limit  = Carbon::createFromFormat('Y-m', $currentMonthKey)->startOfMonth();
            while ($cursor->lte($limit)) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        }

        return $months; // ← просто возвращаем, не трогаем БД
    }

    // recalcFromMonth — ТОЛЬКО оркестрирует пересчёт
    public function recalcFromMonth(string $userId, string $layerId, string $accountId, string $fromMonthKey): void
    {
        // Передаём все три аргумента
        $months = $this->getMonthsFrom($layerId, $accountId, $fromMonthKey);

        foreach ($months as $monthKey) {
            $prev = Carbon::createFromFormat('Y-m', $monthKey)
                        ->subMonth()->format('Y-m');

            $prevTotal = BudMonthTotal::where([
                'layer_id'   => $layerId,
                'account_id' => $accountId,
                'month_key'  => $prev,
            ])->first();

            $opening = $prevTotal?->closing_balance ?? 0;

            $txs = BudTransaction::where('account_id', $accountId)
                ->where('layer_id', $layerId)
                ->where('month_key', $monthKey)
                ->where('is_disabled', 0)
                ->whereNull('deleted_at')
                ->get();

            $income      = $txs->where('flow_kind', 'income')->sum('amount');
            $expense     = $txs->where('flow_kind', 'expense')->sum('amount');
            $transferIn  = $txs->where('flow_kind', 'transfer_in')->sum('amount');
            $transferOut = $txs->where('flow_kind', 'transfer_out')->sum('amount');
            $adjustment  = $txs->where('flow_kind', 'adjustment')->sum('amount');

            // Добавляем reconciliation — влияет на баланс, но не на income/expense
$reconciliationPos = $txs->where('flow_kind', 'reconciliation')->where('is_negative', 0)->sum('amount');
$reconciliationNeg = $txs->where('flow_kind', 'reconciliation')->where('is_negative', 1)->sum('amount');

$closing = $opening + $income - $expense + $transferIn - $transferOut 
         + $adjustment + $reconciliationPos - $reconciliationNeg;

            BudMonthTotal::updateOrCreate(
                ['layer_id' => $layerId, 'account_id' => $accountId, 'month_key' => $monthKey],
                [
                    'user_id'            => $userId,
                    'opening_balance'    => $opening,
                    'closing_balance'    => $closing,
                    'income_total'       => $income,
                    'expense_total'      => $expense,
                    'transfer_in_total'  => $transferIn,
                    'transfer_out_total' => $transferOut,
                    'adjustment_total'   => $adjustment,
                    'tx_count'           => $txs->count(),
                    'is_dirty'           => 0,
                    'updated_at'         => now(),
                ]
            );
        }
    }

}