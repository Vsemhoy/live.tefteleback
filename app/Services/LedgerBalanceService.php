<?php

namespace App\Services;

use App\Models\LedAccount;
use App\Models\LedMonthTotal;
use App\Models\LedTransaction;
use Carbon\Carbon;

class LedgerBalanceService
{
    // ─── Ежедневное начисление процентов (минорные единицы) ───────────
    // Зеркало логики из LedgerUtils.js — держать синхронно!
    // balance  — баланс в копейках (отрицательный = долг)
    // rateInt  — ставка * 100 (2350 = 23.50%)
    // date     — Carbon дата дня начисления
    public function calcDailyInterest(int $balance, int $rateInt, Carbon $date): int
    {
        if ($balance >= 0 || $rateInt <= 0) return 0;
        $daysInYear = $date->isLeapYear() ? 366 : 365;
        // balance отрицательный → результат отрицательный (долг растёт)
        return (int) round($balance * $rateInt / 10000 / $daysInYear);
    }

    // ─── Суммарные проценты за месяц ─────────────────────────────────
    // Считаем пободённо от opening, накапливаем транзакции по датам,
    // затем начисляем проценты на итоговый баланс каждого дня.
    public function calcMonthInterest(LedAccount $account, int $opening, string $monthKey): int
    {
        if (!$account->interest_rate || !$account->interest_start) return 0;

        $interestStart = Carbon::parse($account->interest_start)->startOfDay();
        $monthStart    = Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();
        $monthEnd      = Carbon::createFromFormat('Y-m', $monthKey)->endOfMonth();

        // Транзакции месяца, отсортированные по дате
        $txs = LedTransaction::where('account_id', $account->id)
            ->where('month_key', $monthKey)
            ->where('is_disabled', 0)
            ->whereNull('deleted_at')
            ->orderBy('occurred_at')
            ->get();

        // Строим map дата → delta от транзакций
        $txDeltaByDate = [];
        foreach ($txs as $tx) {
            $dateStr = Carbon::parse($tx->occurred_at)->format('Y-m-d');
            $delta   = $this->txDelta($tx);
            $txDeltaByDate[$dateStr] = ($txDeltaByDate[$dateStr] ?? 0) + $delta;
        }

        $interestTotal = 0;
        $balance       = $opening;
        $cursor        = $monthStart->copy();

        while ($cursor->lte($monthEnd)) {
            $dateStr = $cursor->format('Y-m-d');

            // 1. Применяем транзакции этого дня
            if (isset($txDeltaByDate[$dateStr])) {
                $balance += $txDeltaByDate[$dateStr];
            }

            // 2. Начисляем проценты на итоговый баланс дня
            if ($cursor->gte($interestStart)) {
                $interest       = $this->calcDailyInterest($balance, $account->interest_rate, $cursor);
                $balance       += $interest;
                $interestTotal += $interest;
            }

            $cursor->addDay();
        }

        return $interestTotal; // отрицательное число (долг вырос на столько)
    }

    // ─── Знаковая дельта от транзакции ───────────────────────────────
    public function txDelta(LedTransaction $tx): int
    {
        return match ($tx->flow_kind) {
            'income', 'transfer_in'  => $tx->amount,
            'reconciliation'         => $tx->is_negative ? -$tx->amount : $tx->amount,
            default                  => -$tx->amount, // expense, transfer_out, adjustment
        };
    }

    // ─── Пересчёт одного месяца ──────────────────────────────────────
    public function recalcMonth(string $userId, string $accountId, string $monthKey): void
    {
        $account = LedAccount::find($accountId);

        $prev      = Carbon::createFromFormat('Y-m', $monthKey)->subMonth()->format('Y-m');
        $prevTotal = LedMonthTotal::where([
            'account_id' => $accountId,
            'month_key'  => $prev,
        ])->first();

        $opening = $prevTotal?->closing_balance ?? 0;

        $txs = LedTransaction::where('account_id', $accountId)
            ->where('month_key', $monthKey)
            ->where('is_disabled', 0)
            ->whereNull('deleted_at')
            ->get();

        $income      = $txs->where('flow_kind', 'income')->sum('amount');
        $expense     = $txs->where('flow_kind', 'expense')->sum('amount');
        $transferIn  = $txs->where('flow_kind', 'transfer_in')->sum('amount');
        $transferOut = $txs->where('flow_kind', 'transfer_out')->sum('amount');
        $adjustment  = $txs->whereIn('flow_kind', ['adjustment'])->sum('amount');

        $reconciliationPos = $txs->where('flow_kind', 'reconciliation')->where('is_negative', 0)->sum('amount');
        $reconciliationNeg = $txs->where('flow_kind', 'reconciliation')->where('is_negative', 1)->sum('amount');

        // Проценты за месяц (только для кредитных счетов)
        $interestTotal = $account ? $this->calcMonthInterest($account, $opening, $monthKey) : 0;

        // closing = все движения + проценты
        $closing = $opening
            + $income - $expense
            + $transferIn - $transferOut
            + $adjustment
            + $reconciliationPos - $reconciliationNeg
            + $interestTotal; // отрицательное — долг вырос

        LedMonthTotal::updateOrCreate(
            ['account_id' => $accountId, 'month_key' => $monthKey],
            [
                'user_id'            => $userId,
                'opening_balance'    => $opening,
                'closing_balance'    => $closing,
                'income_total'       => $income,
                'expense_total'      => $expense,
                'transfer_in_total'  => $transferIn,
                'transfer_out_total' => $transferOut,
                'adjustment_total'   => $adjustment,
                'interest_total'     => $interestTotal,
                'tx_count'           => $txs->count(),
                'is_dirty'           => 0,
                'updated_at'         => now(),
            ]
        );
    }

    // ─── markDirty ────────────────────────────────────────────────────
    public function markDirty(string $userId, string $accountId, string $monthKey): void
    {
        LedMonthTotal::updateOrCreate(
            ['account_id' => $accountId, 'month_key' => $monthKey],
            ['user_id' => $userId, 'is_dirty' => 1, 'updated_at' => now()]
        );
    }

    // ─── calcBalanceToday ─────────────────────────────────────────────
    // Используется в LedgerAccountController для balance_today в сайдбаре.
    // Opening = closing предыдущего месяца (уже включает проценты).
    // Затем накатываем транзакции текущего месяца + проценты за каждый день.
    public function calcBalanceToday(LedAccount $account): int
    {
        $today    = now()->startOfDay();
        $monthKey = $today->format('Y-m');
        $prevKey  = $today->copy()->subMonth()->format('Y-m');

        $prevTotal = LedMonthTotal::where([
            'account_id' => $account->id,
            'month_key'  => $prevKey,
        ])->first();

        $opening = $prevTotal?->closing_balance ?? 0;

        // Транзакции с начала месяца по сегодня включительно
        $txs = LedTransaction::where('account_id', $account->id)
            ->where('is_disabled', 0)
            ->whereNull('deleted_at')
            ->where('month_key', $monthKey)
            ->where('occurred_at', '<=', $today->format('Y-m-d'))
            ->orderBy('occurred_at')
            ->get();

        $txDeltaByDate = [];
        foreach ($txs as $tx) {
            $dateStr = Carbon::parse($tx->occurred_at)->format('Y-m-d');
            $txDeltaByDate[$dateStr] = ($txDeltaByDate[$dateStr] ?? 0) + $this->txDelta($tx);
        }

        $interestStart = $account->interest_rate && $account->interest_start
            ? Carbon::parse($account->interest_start)->startOfDay()
            : null;

        $balance = $opening;
        $cursor  = Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();

        while ($cursor->lte($today)) {
            $dateStr = $cursor->format('Y-m-d');

            if (isset($txDeltaByDate[$dateStr])) {
                $balance += $txDeltaByDate[$dateStr];
            }

            if ($interestStart && $cursor->gte($interestStart)) {
                $balance += $this->calcDailyInterest($balance, $account->interest_rate, $cursor);
            }

            $cursor->addDay();
        }

        return $balance;
    }
}
