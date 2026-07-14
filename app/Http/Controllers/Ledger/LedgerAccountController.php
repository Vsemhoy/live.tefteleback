<?php

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Models\LedAccount;
use App\Models\LedLayer;
use App\Models\LedTransaction;
use App\Services\LedgerBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LedgerAccountController extends Controller
{
    public function __construct(
        private LedgerBalanceService $balanceService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $layer = LedLayer::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'base'],
            ['id' => (string) Str::ulid(), 'name' => 'Base', 'is_active' => 1]
        );

        $accounts = LedAccount::where('user_id', $user->id)
            ->where('layer_id', $layer->id)
            ->where('is_archived', 0)
            ->withCount(['transactions' => fn($q) => $q->whereNull('deleted_at')])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($account) use ($layer) {
                $account->balance_today    = $this->balanceService->calcBalanceToday($account);
                $account->has_transactions = $account->transactions_count > 0;
                return $account;
            });

        return response()->json(['status' => 1, 'content' => $accounts]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'literals'        => 'nullable|string|max:3',
            'type'            => 'required|in:cash,card,credit,deposit,phantom',
            'currency'        => 'nullable|string|size:3',
            'color'           => 'nullable|string|max:20',
            'sort_order'      => 'nullable|integer',
            'opening_balance' => 'nullable|integer',
            'opened_at'       => 'nullable|date',
            'closed_at'       => 'nullable|date',
            'interest_rate'   => 'nullable|integer',
            'interest_start'  => 'nullable|date',
        ]);

        $data['name'] = trim($data['name']);
        if ($data['name'] === '') {
            return response()->json(['message' => 'Account name is required'], 422);
        }

        $layer = LedLayer::where('user_id', $user->id)
            ->where('type', 'base')
            ->firstOrFail();

        $account = LedAccount::create([
            'id'       => (string) Str::ulid(),
            'user_id'  => $user->id,
            'layer_id' => $layer->id,
            ...$data,
        ]);

        return response()->json(['status' => 1, 'content' => $account], 201);
    }

    public function update(Request $request, string $id)
    {
        $account = LedAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate([
            'name'            => 'nullable|string|max:100',
            'literals'        => 'nullable|string|max:3',
            'type'            => 'nullable|in:cash,card,credit,deposit,phantom',
            'currency'        => 'nullable|string|size:3',
            'color'           => 'nullable|string|max:20',
            'sort_order'      => 'nullable|integer',
            'opening_balance' => 'nullable|integer',
            'is_archived'     => 'nullable|boolean',
            'opened_at'       => 'nullable|date',
            'closed_at'       => 'nullable|date',
            'interest_rate'   => 'nullable|integer',
            'interest_start'  => 'nullable|date',
        ]);

        if (array_key_exists('name', $data)) {
            $data['name'] = trim((string) $data['name']);
            if ($data['name'] === '') {
                return response()->json(['message' => 'Account name is required'], 422);
            }
        }

        // Защита: не менять ставку если уже есть транзакции
        $hasTx = LedTransaction::where('account_id', $id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasTx) {
            unset($data['interest_rate'], $data['interest_start']);
        }

        $account->update($data);

        return response()->json(['status' => 1, 'content' => $account->fresh()]);
    }

    public function destroy(Request $request, string $id)
    {
        $account = LedAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $account->delete();

        return response()->json(['status' => 1, 'message' => 'Account deleted']);
    }
}
