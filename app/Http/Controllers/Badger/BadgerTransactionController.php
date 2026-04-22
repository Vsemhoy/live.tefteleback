<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudLayer;
use App\Models\BudTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BadgerTransactionController extends Controller
{
    public function __construct(
        private BadgerClosingController $closing
    ) {}

    // ─── Хелпер: найти парную транзакцию перевода ────────────────────
    // Для transfer_out: парная = transfer_in с original_transaction_id = $tx->id
    // Для transfer_in:  парная = transfer_out с id = $tx->original_transaction_id
    private function findPaired(BudTransaction $tx): ?BudTransaction
    {
        if ($tx->flow_kind === 'transfer_out') {
            return BudTransaction::where('original_transaction_id', $tx->id)
                ->whereNull('deleted_at')
                ->first();
        }
        if ($tx->flow_kind === 'transfer_in' && $tx->original_transaction_id) {
            return BudTransaction::where('id', $tx->original_transaction_id)
                ->whereNull('deleted_at')
                ->first();
        }
        return null;
    }

    // ─── Хелпер: пересчитать счёт (и парный если есть) ──────────────
    private function recalcWithPaired(string $userId, BudTransaction $tx, string $fromMonth, ?BudTransaction $paired = null): void
    {
        $this->closing->recalcFromMonth($userId, $tx->account_id, $fromMonth);

        if ($paired) {
            $pairedFrom = min($fromMonth, $paired->month_key);
            $this->closing->recalcFromMonth($userId, $paired->account_id, $pairedFrom);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id'        => 'required|string',
            'target_account_id' => 'nullable|string',
            'flow_kind'         => 'required|in:expense,income,transfer_out,transfer_in,adjustment,reconciliation',
            'amount'            => 'required|integer|min:1',
            'occurred_at'       => 'required|date',
            'title'             => 'nullable|string|max:255',
            'note'              => 'nullable|string',
            'status'            => 'nullable|in:cleared,pending',
            'group_id'          => 'nullable|string',
        ]);

        $user     = $request->user();
        $layer    = BudLayer::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'base'],
            ['id' => \Str::ulid(), 'name' => 'Base', 'is_active' => 1]
        );
        $monthKey = Carbon::parse($data['occurred_at'])->format('Y-m');

        DB::transaction(function () use ($data, $user, $layer, $monthKey) {
            $tx = BudTransaction::create([
                ...$data,
                'user_id'   => $user->id,
                'layer_id'  => $layer->id,
                'month_key' => $monthKey,
            ]);

            if ($data['flow_kind'] === 'transfer_out' && ($data['target_account_id'] ?? null)) {
                BudTransaction::create([
                    'user_id'                 => $user->id,
                    'layer_id'                => $layer->id,
                    'account_id'              => $data['target_account_id'],
                    'target_account_id'       => $data['account_id'],
                    'flow_kind'               => 'transfer_in',
                    'amount'                  => $data['amount'],
                    'occurred_at'             => $data['occurred_at'],
                    'month_key'               => $monthKey,
                    'title'                   => $data['title'] ?? null,
                    'status'                  => $data['status'] ?? 'cleared',
                    'original_transaction_id' => $tx->id,
                ]);
            }
        });

        $this->closing->recalcFromMonth($user->id, $data['account_id'], $monthKey);
        if ($data['target_account_id'] ?? null) {
            $this->closing->recalcFromMonth($user->id, $data['target_account_id'], $monthKey);
        }

        return response()->json(['status' => 1, 'message' => 'Transaction created'], 201);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'account_id'  => 'nullable|string',
            'flow_kind'   => 'nullable|in:expense,income,transfer_out,transfer_in,adjustment',
            'amount'      => 'nullable|integer|min:1',
            'occurred_at' => 'nullable|date',
            'title'       => 'nullable|string|max:255',
            'note'        => 'nullable|string',
            'status'      => 'nullable|in:cleared,pending',
            'group_id'    => 'nullable|string',
            'is_disabled' => 'nullable|boolean',
        ]);

        $user        = $request->user();
        $tx          = BudTransaction::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $oldMonthKey = $tx->month_key;
        $layer       = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();
        $paired      = $this->findPaired($tx);

        $tx->update($data);
        $newMonthKey = $tx->fresh()->month_key;

        // Если изменилась сумма или дата — синхронизируем парную транзакцию
        if ($paired) {
            $pairedUpdate = [];
            if (isset($data['amount']))      $pairedUpdate['amount']      = $data['amount'];
            if (isset($data['occurred_at'])) $pairedUpdate['occurred_at'] = $data['occurred_at'];
            if (isset($data['occurred_at'])) $pairedUpdate['month_key']   = Carbon::parse($data['occurred_at'])->format('Y-m');
            if (isset($data['title']))       $pairedUpdate['title']       = $data['title'];
            if (isset($data['status']))      $pairedUpdate['status']      = $data['status'];
            if (!empty($pairedUpdate))       $paired->update($pairedUpdate);
        }

        $fromMonth = min($oldMonthKey, $newMonthKey);
        $this->recalcWithPaired($user->id, $tx, $fromMonth, $paired);

        return response()->json(['status' => 1, 'content' => $tx->refresh()]);
    }

    public function destroy(Request $request, string $id)
    {
        $user   = $request->user();
        $tx     = BudTransaction::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $layer  = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();
        $paired = $this->findPaired($tx);

        $monthKey = $tx->month_key;
        $accId    = $tx->account_id;

        DB::transaction(function () use ($tx, $paired) {
            $tx->delete();
            if ($paired) $paired->delete();
        });

        $this->closing->recalcFromMonth($user->id, $accId, $monthKey);
        if ($paired) {
            $pairedFrom = min($monthKey, $paired->month_key);
            $this->closing->recalcFromMonth($user->id, $paired->account_id, $pairedFrom);
        }

        return response()->json(['status' => 1, 'message' => 'Transaction deleted']);
    }

    public function move(Request $request, string $id)
    {
        $data = $request->validate([
            'occurred_at' => 'nullable|date',
            'account_id'  => 'nullable|string',
        ]);

        $user   = $request->user();
        $tx     = BudTransaction::where('user_id', $user->id)->findOrFail($id);
        $layer  = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();
        $paired = $this->findPaired($tx);

        $oldAccountId = $tx->account_id;
        $oldMonthKey  = $tx->month_key;

        if ($data['occurred_at'] ?? null) {
            $tx->occurred_at = $data['occurred_at'];
            $tx->month_key   = Carbon::parse($data['occurred_at'])->format('Y-m');
        }
        if ($data['account_id'] ?? null) {
            $tx->account_id = $data['account_id'];
        }
        $tx->save();

        // Синхронизируем дату парной транзакции
        if ($paired && ($data['occurred_at'] ?? null)) {
            $paired->occurred_at = $data['occurred_at'];
            $paired->month_key   = $tx->month_key;
            $paired->save();
        }

        $fromMonth = min($oldMonthKey, $tx->month_key);
        $this->closing->recalcFromMonth($user->id, $oldAccountId, $fromMonth);
        if ($tx->account_id !== $oldAccountId) {
            $this->closing->recalcFromMonth($user->id, $tx->account_id, $fromMonth);
        }
        if ($paired) {
            $this->closing->recalcFromMonth($user->id, $paired->account_id, $fromMonth);
        }

        return response()->json(['status' => 1, 'content' => $tx]);
    }

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = BudTransaction::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereBetween('occurred_at', [$request->get('start'), $request->get('end')])
            ->orderBy('occurred_at', 'desc')
            ->orderBy('sort_order');

        if ($request->filled('account_id')) {
            $query->whereIn('account_id', explode(',', $request->get('account_id')));
        }

        return response()->json(['status' => 1, 'content' => $query->with('category')->get()]);
    }

    public function show(Request $request, string $id)
    {
        return response()->json([
            'status'  => 1,
            'content' => BudTransaction::where('user_id', $request->user()->id)
                ->where('id', $id)->with(['category'])->firstOrFail(),
        ]);
    }

    public function toggleDisabled(Request $request, string $id)
    {
        $user  = $request->user();
        $tx    = BudTransaction::where('user_id', $user->id)->findOrFail($id);
        $layer = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();

        $tx->is_disabled = $request->has('is_disabled')
            ? $request->boolean('is_disabled')
            : !$tx->is_disabled;
        $tx->save();

        // Синхронизируем парную транзакцию
        $paired = $this->findPaired($tx);
        if ($paired) {
            $paired->is_disabled = $tx->is_disabled;
            $paired->save();
        }

        $this->closing->recalcFromMonth($user->id, $tx->account_id, $tx->month_key);
        if ($paired) {
            $this->closing->recalcFromMonth($user->id, $paired->account_id, $paired->month_key);
        }

        return response()->json(['status' => 1, 'content' => $tx->refresh()]);
    }
}
