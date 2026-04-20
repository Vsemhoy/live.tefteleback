<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudMonthTotal;
use App\Services\BadgerBalanceService;
use Carbon\Carbon;

class BadgerClosingController extends Controller
{
    public function __construct(
        private BadgerBalanceService $balanceService
    ) {}

    // recalcFromMonth — пересчитывает цепочку месяцев от fromMonthKey до текущего
    public function recalcFromMonth(string $userId, string $layerId, string $accountId, string $fromMonthKey): void
    {
        $months = $this->getMonthsFrom($layerId, $accountId, $fromMonthKey);

        foreach ($months as $monthKey) {
            $this->balanceService->recalcMonth($userId, $layerId, $accountId, $monthKey);
        }
    }

    // getMonthsFrom — возвращает массив month_key от fromMonthKey до текущего
    // Если в БД есть записи — берём их список (могут быть дыры → fillGaps дополнит)
    // Если нет — генерируем диапазон
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

        // Всегда генерируем полный диапазон без дыр
        $fullRange = [];
        $cursor    = Carbon::createFromFormat('Y-m', $fromMonthKey)->startOfMonth();
        $limit     = Carbon::createFromFormat('Y-m', $currentMonthKey)->startOfMonth();
        while ($cursor->lte($limit)) {
            $fullRange[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        // Объединяем — берём полный диапазон (он покрывает и дыры)
        return $fullRange;
    }
}
