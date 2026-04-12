<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudLayer;
use App\Models\BudMonthTotal;
use App\Models\BudTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BadgerTransactionController extends Controller
{
    // Инжектим BadgerClosingController в конструктор
    public function __construct(
        private BadgerClosingController $closing
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id'        => 'required|string',
            'target_account_id' => 'nullable|string',
            'flow_kind'         => 'required|in:expense,income,transfer_out,transfer_in,adjustment',
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

        // Пересчёт ПОСЛЕ транзакции — не внутри DB::transaction
        $this->closing->recalcFromMonth($user->id, $layer->id, $data['account_id'], $monthKey);
        if ($data['target_account_id'] ?? null) {
            $this->closing->recalcFromMonth($user->id, $layer->id, $data['target_account_id'], $monthKey);
        }

        return response()->json(['status' => 1, 'message' => 'Transaction created'], 201);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'account_id'        => 'nullable|string',
            'flow_kind'         => 'nullable|in:expense,income,transfer_out,transfer_in,adjustment',
            'amount'            => 'nullable|integer|min:1',
            'occurred_at'       => 'nullable|date',
            'title'             => 'nullable|string|max:255',
            'note'              => 'nullable|string',
            'status'            => 'nullable|in:cleared,pending',
            'group_id'          => 'nullable|string',
        ]);

        $user = $request->user();
        $tx   = BudTransaction::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $oldMonthKey = $tx->month_key;

        $tx->update($data);

        $newMonthKey = $tx->fresh()->month_key;
        $layer = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();

        // Пересчитываем с самого раннего из двух месяцев
        $fromMonth = $oldMonthKey < $newMonthKey ? $oldMonthKey : $newMonthKey;
        $this->closing->recalcFromMonth($user->id, $layer->id, $tx->account_id, $fromMonth);

        return response()->json(['status' => 1, 'content' => $tx->refresh()]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $tx   = BudTransaction::where('user_id', $user->id)->where('id', $id)->firstOrFail();

        $layer    = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();
        $monthKey = $tx->month_key;
        $accId    = $tx->account_id;

        $tx->delete();

        // Пересчёт после удаления
        $this->closing->recalcFromMonth($user->id, $layer->id, $accId, $monthKey);

        return response()->json(['status' => 1, 'message' => 'Transaction deleted']);
    }

    public function move(Request $request, string $id)
    {
        $data = $request->validate([
            'occurred_at' => 'nullable|date',
            'account_id'  => 'nullable|string',
        ]);

        $user  = $request->user();
        $tx    = BudTransaction::where('user_id', $user->id)->findOrFail($id);
        $layer = BudLayer::where('user_id', $user->id)->where('type', 'base')->first();

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

        // Пересчитываем оба счёта от самого раннего месяца
        $fromMonth = min($oldMonthKey, $tx->month_key);
        $this->closing->recalcFromMonth($user->id, $layer->id, $oldAccountId, $fromMonth);
        if ($tx->account_id !== $oldAccountId) {
            $this->closing->recalcFromMonth($user->id, $layer->id, $tx->account_id, $fromMonth);
        }

        return response()->json(['status' => 1, 'content' => $tx]);
    }

    // index и show без изменений
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

        return response()->json(['status' => 1, 'content' => $query->get()]);
    }

    public function show(Request $request, string $id)
    {
        return response()->json([
            'status'  => 1,
            'content' => BudTransaction::where('user_id', $request->user()->id)
                ->where('id', $id)->firstOrFail(),
        ]);
    }
}