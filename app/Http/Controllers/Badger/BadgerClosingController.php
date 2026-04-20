<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudTransaction;
use App\Services\BadgerBalanceService;
use Carbon\Carbon;

class BadgerClosingController extends Controller
{
    public function __construct(
        private BadgerBalanceService $balanceService
    ) {}

    // recalcFromMonth — пересчитывает цепочку месяцев от fromMonthKey
    // до последнего месяца с транзакциями (или текущего — что позже)
    public function recalcFromMonth(string $userId, string $accountId, string $fromMonthKey): void
    {
        $months = $this->getMonthsFrom($accountId, $fromMonthKey);

        foreach ($months as $monthKey) {
            $this->balanceService->recalcMonth($userId, $accountId, $monthKey);
        }
    }

    private function getMonthsFrom(string $accountId, string $fromMonthKey): array
    {
        // Считаем до последнего месяца с транзакциями ИЛИ до текущего — что позже
        $lastTxMonth = BudTransaction::where('account_id', $accountId)
            ->whereNull('deleted_at')
            ->max('month_key');

        $currentMonthKey = now()->format('Y-m');
        $endMonthKey     = max($lastTxMonth ?? $currentMonthKey, $currentMonthKey);

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
