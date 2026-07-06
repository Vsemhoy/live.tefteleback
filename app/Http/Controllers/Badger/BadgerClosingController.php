<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
use App\Services\BadgerBalanceService;
use Carbon\Carbon;

class BadgerClosingController extends Controller
{
    public function __construct(
        private BadgerBalanceService $balanceService
    ) {}

    public function recalcFromMonth(string $userId, string $accountId, string $fromMonthKey): void
    {
        $months = $this->getMonthsFrom($accountId, $fromMonthKey);

        foreach ($months as $monthKey) {
            $this->balanceService->recalcMonth($userId, $accountId, $monthKey);
        }
    }

    private function getMonthsFrom(string $accountId, string $fromMonthKey): array
    {
        // Кандидаты на конечный месяц:
        // 1. Последний месяц с транзакциями
        $lastTxMonth = BudTransaction::where('account_id', $accountId)
            ->whereNull('deleted_at')
            ->max('month_key');

        // 2. Последний уже существующий тотал — он тоже должен пересчитаться
        //    т.к. его opening мог устареть из-за изменений в предыдущих месяцах
        $lastTotalMonth = BudMonthTotal::where('account_id', $accountId)
            ->max('month_key');

        // 3. Текущий месяц — минимальная граница
        $currentMonthKey = now()->format('Y-m');

        // Берём максимум из всех трёх
        $endMonthKey = max(
            $lastTxMonth    ?? $currentMonthKey,
            $lastTotalMonth ?? $currentMonthKey,
            $currentMonthKey
        );

        $fullRange = [];
        $cursor    = Carbon::createFromFormat('Y-m', $fromMonthKey)->startOfMonth();
        $limit     = Carbon::createFromFormat('Y-m', $endMonthKey)->startOfMonth();

        while ($cursor->lte($limit)) {
            $fullRange[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $fullRange;
    }
}
