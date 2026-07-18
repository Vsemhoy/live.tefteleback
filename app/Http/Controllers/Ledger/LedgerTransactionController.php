<?php

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Models\LedLayer;
use App\Models\LedTransaction;
use App\Models\StfThing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerTransactionController extends Controller
{
    public function __construct(
        private LedgerClosingController $closing
    ) {}

    private const LINKED_ENTITY_TYPES = [
        'stuffer.thing',
        'exploiter.event',
        'eventor.event',
        'contactor.contact',
    ];

    private function normalizeLinkedEntity(array $data): array
    {
        $hasLinkInput = array_key_exists('linked_entity_type', $data)
            || array_key_exists('linked_entity_id', $data)
            || array_key_exists('exploiter_event_id', $data);

        if (! $hasLinkInput) {
            return $data;
        }

        if (($data['linked_entity_type'] ?? null) === '') {
            $data['linked_entity_type'] = null;
        }
        if (($data['linked_entity_id'] ?? null) === '') {
            $data['linked_entity_id'] = null;
        }
        if (($data['linked_entity_type'] ?? null) === 'exploiter') {
            $data['linked_entity_type'] = 'exploiter.event';
        }
        if (!empty($data['exploiter_event_id']) && empty($data['linked_entity_id'])) {
            $data['linked_entity_type'] = 'exploiter.event';
            $data['linked_entity_id'] = $data['exploiter_event_id'];
        }
        if (empty($data['linked_entity_type']) || empty($data['linked_entity_id'])) {
            $data['linked_entity_type'] = null;
            $data['linked_entity_id'] = null;
        }
        return $data;
    }

    private function attachLinkedEntity($transactions)
    {
        if ($transactions instanceof LedTransaction) {
            $transactions->setAttribute('linked_entity', $this->linkedEntityPayload($transactions));
            return $transactions;
        }

        return $transactions->map(function (LedTransaction $tx) {
            $tx->setAttribute('linked_entity', $this->linkedEntityPayload($tx));
            return $tx;
        });
    }

    private function linkedEntityPayload(LedTransaction $tx): ?array
    {
        $type = $tx->linked_entity_type ?: ($tx->exploiter_event_id ? 'exploiter.event' : null);
        $id = $tx->linked_entity_id ?: ($type === 'exploiter.event' ? $tx->exploiter_event_id : null);

        if (!$type || !$id) {
            return null;
        }

        if ($type === 'stuffer.thing') {
            $thing = StfThing::where('user_id', $tx->user_id)->where('id', $id)->first(['id', 'name', 'entity_type']);
            return [
                'type' => $type,
                'id' => $id,
                'label' => $thing?->name ?? 'Thing',
                'kind' => $thing?->entity_type,
            ];
        }

        if ($type === 'exploiter.event') {
            $event = $tx->relationLoaded('exploiterEvent') ? $tx->exploiterEvent : $tx->exploiterEvent()->first();
            return [
                'type' => $type,
                'id' => $id,
                'label' => $event?->name ?? 'Exploiter event',
                'kind' => $event?->event_kind,
            ];
        }

        return [
            'type' => $type,
            'id' => $id,
            'label' => $type,
            'kind' => null,
        ];
    }

    // â”€â”€â”€ Ð¥ÐµÐ»Ð¿ÐµÑ€: Ð½Ð°Ð¹Ñ‚Ð¸ Ð¿Ð°Ñ€Ð½ÑƒÑŽ Ñ‚Ñ€Ð°Ð½Ð·Ð°ÐºÑ†Ð¸ÑŽ Ð¿ÐµÑ€ÐµÐ²Ð¾Ð´Ð° â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Ð”Ð»Ñ transfer_out: Ð¿Ð°Ñ€Ð½Ð°Ñ = transfer_in Ñ original_transaction_id = $tx->id
    // Ð”Ð»Ñ transfer_in:  Ð¿Ð°Ñ€Ð½Ð°Ñ = transfer_out Ñ id = $tx->original_transaction_id
    private function findPaired(LedTransaction $tx): ?LedTransaction
    {
        if ($tx->flow_kind === 'transfer_out') {
            return LedTransaction::where('original_transaction_id', $tx->id)
                ->whereNull('deleted_at')
                ->first();
        }
        if ($tx->flow_kind === 'transfer_in' && $tx->original_transaction_id) {
            return LedTransaction::where('id', $tx->original_transaction_id)
                ->whereNull('deleted_at')
                ->first();
        }
        return null;
    }

    // â”€â”€â”€ Ð¥ÐµÐ»Ð¿ÐµÑ€: Ð¿ÐµÑ€ÐµÑÑ‡Ð¸Ñ‚Ð°Ñ‚ÑŒ ÑÑ‡Ñ‘Ñ‚ (Ð¸ Ð¿Ð°Ñ€Ð½Ñ‹Ð¹ ÐµÑÐ»Ð¸ ÐµÑÑ‚ÑŒ) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    private function recalcWithPaired(string $userId, LedTransaction $tx, string $fromMonth, ?LedTransaction $paired = null): void
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
            'category_id'       => 'nullable|string|size:26',
            'exploiter_event_id'=> 'nullable|string|size:26',
            'linked_entity_type'=> 'nullable|in:stuffer.thing,exploiter.event,eventor.event,contactor.contact,exploiter',
            'linked_entity_id'  => 'nullable|string|size:26',
            'cost_type'         => 'nullable|in:part,labor,consumption,service,delivery,other',
            'sort_order'        => 'nullable|integer',
            'is_expert'         => 'nullable|boolean',
        ]);

        
        $data = $this->normalizeLinkedEntity($data);

        \Log::info('store input', ['category_id' => $data['category_id'] ?? 'missing']);

        $user     = $request->user();
        $layer    = LedLayer::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'base'],
            ['id' => \Str::ulid(), 'name' => 'Base', 'is_active' => 1]
        );
        $monthKey = Carbon::parse($data['occurred_at'])->format('Y-m');

        DB::transaction(function () use ($data, $user, $layer, $monthKey) {
            $tx = LedTransaction::create(array_merge($data, [
                'user_id'   => $user->id,
                'layer_id'  => $layer->id,
                'month_key' => $monthKey,
            ]));

            \Log::info('created transaction', ['id' => $tx->id, 'category_id' => $tx->category_id]);

            if ($data['flow_kind'] === 'transfer_out' && ($data['target_account_id'] ?? null)) {
                LedTransaction::create([
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
                    'is_expert'               => (bool) ($data['is_expert'] ?? false),
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
            'flow_kind'   => 'nullable|in:expense,income,transfer_out,transfer_in,adjustment,reconciliation',
            'amount'      => 'nullable|integer|min:1',
            'occurred_at' => 'nullable|date',
            'title'       => 'nullable|string|max:255',
            'note'        => 'nullable|string',
            'status'      => 'nullable|in:cleared,pending',
            'group_id'    => 'nullable|string',
            'is_disabled' => 'nullable|boolean',
            'category_id' => 'nullable|string|max:26',
            'exploiter_event_id' => 'nullable|string|size:26',
            'linked_entity_type'=> 'nullable|in:stuffer.thing,exploiter.event,eventor.event,contactor.contact,exploiter',
            'linked_entity_id'  => 'nullable|string|size:26',
            'cost_type'   => 'nullable|in:part,labor,consumption,service,delivery,other',
            'sort_order'  => 'nullable|integer',
            'is_expert'   => 'nullable|boolean',
        ]);

        $data = $this->normalizeLinkedEntity($data);

        \Log::info('update called', ['id' => $id, 'category_id' => $data['category_id'] ?? 'missing']);

        $user        = $request->user();
        $tx          = LedTransaction::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $oldMonthKey = $tx->month_key;
        $layer       = LedLayer::where('user_id', $user->id)->where('type', 'base')->first();
        $paired      = $this->findPaired($tx);

        $tx->update($data);
        \Log::info('updated transaction', ['id' => $tx->id, 'category_id' => $tx->fresh()->category_id]);
        $newMonthKey = $tx->fresh()->month_key;

        // Ð•ÑÐ»Ð¸ Ð¸Ð·Ð¼ÐµÐ½Ð¸Ð»Ð°ÑÑŒ ÑÑƒÐ¼Ð¼Ð° Ð¸Ð»Ð¸ Ð´Ð°Ñ‚Ð° â€” ÑÐ¸Ð½Ñ…Ñ€Ð¾Ð½Ð¸Ð·Ð¸Ñ€ÑƒÐµÐ¼ Ð¿Ð°Ñ€Ð½ÑƒÑŽ Ñ‚Ñ€Ð°Ð½Ð·Ð°ÐºÑ†Ð¸ÑŽ
        if ($paired) {
            $pairedUpdate = [];
            if (isset($data['amount']))      $pairedUpdate['amount']      = $data['amount'];
            if (isset($data['occurred_at'])) $pairedUpdate['occurred_at'] = $data['occurred_at'];
            if (isset($data['occurred_at'])) $pairedUpdate['month_key']   = Carbon::parse($data['occurred_at'])->format('Y-m');
            if (isset($data['title']))       $pairedUpdate['title']       = $data['title'];
            if (isset($data['status']))      $pairedUpdate['status']      = $data['status'];
            if (isset($data['is_expert']))   $pairedUpdate['is_expert']   = $data['is_expert'];
            if (!empty($pairedUpdate))       $paired->update($pairedUpdate);
        }

        $fromMonth = min($oldMonthKey, $newMonthKey);
        $this->recalcWithPaired($user->id, $tx, $fromMonth, $paired);

        return response()->json(['status' => 1, 'content' => $tx->refresh()]);
    }

    public function destroy(Request $request, string $id)
    {
        $user   = $request->user();
        $tx     = LedTransaction::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $layer  = LedLayer::where('user_id', $user->id)->where('type', 'base')->first();
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
        $tx     = LedTransaction::where('user_id', $user->id)->findOrFail($id);
        $layer  = LedLayer::where('user_id', $user->id)->where('type', 'base')->first();
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

        // Ð¡Ð¸Ð½Ñ…Ñ€Ð¾Ð½Ð¸Ð·Ð¸Ñ€ÑƒÐµÐ¼ Ð´Ð°Ñ‚Ñƒ Ð¿Ð°Ñ€Ð½Ð¾Ð¹ Ñ‚Ñ€Ð°Ð½Ð·Ð°ÐºÑ†Ð¸Ð¸
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
        $query = LedTransaction::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereBetween('occurred_at', [$request->get('start'), $request->get('end')])
            ->when(! $request->boolean('include_expert'), function ($query) {
                $query->where('is_expert', false)
                    ->whereHas('account', fn ($account) => $account->where('is_expert', false));
            })
            ->orderBy('occurred_at', 'desc')
            ->orderBy('sort_order');

        if ($request->filled('account_id')) {
            $query->whereIn('account_id', explode(',', $request->get('account_id')));
        }

        return response()->json(['status' => 1, 'content' => $this->attachLinkedEntity($query->with(['category', 'exploiterEvent'])->get())]);
    }

    public function show(Request $request, string $id)
    {
        $tx = LedTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['category', 'exploiterEvent'])
            ->firstOrFail();

        return response()->json([
            'status'  => 1,
            'content' => $this->attachLinkedEntity($tx),
        ]);
    }

    public function toggleDisabled(Request $request, string $id)
    {
        $user  = $request->user();
        $tx    = LedTransaction::where('user_id', $user->id)->findOrFail($id);
        $layer = LedLayer::where('user_id', $user->id)->where('type', 'base')->first();

        $tx->is_disabled = $request->has('is_disabled')
            ? $request->boolean('is_disabled')
            : !$tx->is_disabled;
        $tx->save();

        // Ð¡Ð¸Ð½Ñ…Ñ€Ð¾Ð½Ð¸Ð·Ð¸Ñ€ÑƒÐµÐ¼ Ð¿Ð°Ñ€Ð½ÑƒÑŽ Ñ‚Ñ€Ð°Ð½Ð·Ð°ÐºÑ†Ð¸ÑŽ
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

