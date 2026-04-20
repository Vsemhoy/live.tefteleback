<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudLayer;
use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
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
        $accountId = $request->query('account_id');

        // Поддерживаем два режима:
        // 1. start + end (диапазон месяцев) — основной
        // 2. month_key (один месяц) — обратная совместимость
        $start = $request->query('start');
        $end   = $request->query('end');
        $monthKey = $request->query('month_key');

        if ($monthKey && !$start) {
            $start = $monthKey;
            $end   = $monthKey;
        }

        // Залатываем дыры до конца запрошенного диапазона
        if ($end && $accountId) {
            foreach (explode(',', $accountId) as $accId) {
                    $this->fillGapsUntil($user->id, trim($accId), $end);
                }
        }

        $query = BudMonthTotal::where('user_id', $user->id);

        if ($start && $end) {
            $query->whereBetween('month_key', [$start, $end]);
        } elseif ($start) {
            $query->where('month_key', $start);
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
    private function fillGapsUntil(string $userId, string $accountId, string $targetMonth): void
    {
        $lastCalculated = BudMonthTotal::where('account_id', $accountId)
            ->where('is_dirty', 0)
            ->orderBy('month_key', 'desc')
            ->value('month_key');

        if ($lastCalculated && $lastCalculated >= $targetMonth) return;

        if ($lastCalculated) {
            $fromMonth = Carbon::createFromFormat('Y-m', $lastCalculated)
                ->addMonth()->format('Y-m');
        } else {
            $earliestTx = BudTransaction::where('account_id', $accountId)
                ->whereNull('deleted_at')
                ->orderBy('occurred_at')
                ->value('month_key');

            $fromMonth = $earliestTx ?? $targetMonth;
        }

        $cursor = Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth();
        $limit  = Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth();

        while ($cursor->lte($limit)) {
            $this->balanceService->recalcMonth($userId, $accountId, $cursor->format('Y-m'));
            $cursor->addMonth();
        }
    }
}
