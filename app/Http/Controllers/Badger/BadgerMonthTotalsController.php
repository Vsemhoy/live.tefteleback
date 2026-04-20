<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudAccount;
use App\Models\BudLayer;
use App\Models\BudMonthTotal;
use App\Services\BadgerBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BadgerMonthTotalsController extends Controller
{
    public function __construct(
        private BadgerBalanceService $balanceService
    ) {}

    public function index(Request $request)
    {
        $user      = $request->user();
        $monthKey  = $request->query('month_key');
        $accountId = $request->query('account_id');

        // Перед отдачей данных — залатываем дыры до запрошенного месяца
        if ($monthKey && $accountId) {
            $layer = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();
            if ($layer) {
                $accountIds = explode(',', $accountId);
                foreach ($accountIds as $accId) {
                    $this->fillGapsUntil($user->id, $layer->id, trim($accId), $monthKey);
                }
            }
        }

        $query = BudMonthTotal::where('user_id', $user->id);

        if ($monthKey) {
            $query->where('month_key', $monthKey);
        }

        if ($accountId) {
            $query->whereIn('account_id', explode(',', $accountId));
        }

        return response()->json([
            'status'  => 1,
            'content' => $query->orderBy('month_key')->get(),
        ]);
    }

    // ─── fillGapsUntil ────────────────────────────────────────────────
    // Находит последний посчитанный месяц для счёта.
    // Если он раньше targetMonth — досчитывает все промежуточные месяцы.
    // Месяцы без транзакций тоже создаются — closing = opening (баланс протягивается).
    private function fillGapsUntil(string $userId, string $layerId, string $accountId, string $targetMonth): void
    {
        // Последний посчитанный месяц
        $lastCalculated = BudMonthTotal::where('layer_id', $layerId)
            ->where('account_id', $accountId)
            ->where('is_dirty', 0)
            ->orderBy('month_key', 'desc')
            ->value('month_key');

        // Если уже посчитано до targetMonth или дальше — ничего не делаем
        if ($lastCalculated && $lastCalculated >= $targetMonth) return;

        // Стартуем с месяца после последнего посчитанного,
        // или с самого раннего месяца транзакций счёта если тоталов нет вообще
        if ($lastCalculated) {
            $fromMonth = Carbon::createFromFormat('Y-m', $lastCalculated)
                ->addMonth()->format('Y-m');
        } else {
            // Ищем самую раннюю транзакцию счёта
            $earliestTx = \App\Models\BudTransaction::where('account_id', $accountId)
                ->where('layer_id', $layerId)
                ->whereNull('deleted_at')
                ->orderBy('occurred_at')
                ->value('month_key');

            $fromMonth = $earliestTx ?? $targetMonth;
        }

        // Генерируем полный диапазон от fromMonth до targetMonth
        $cursor = Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth();
        $limit  = Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth();

        while ($cursor->lte($limit)) {
            $this->balanceService->recalcMonth($userId, $layerId, $accountId, $cursor->format('Y-m'));
            $cursor->addMonth();
        }
    }
}
