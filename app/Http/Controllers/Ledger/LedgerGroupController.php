<?php

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Models\LedTransaction;
use App\Models\LedTransactionGroup;
use Illuminate\Http\Request;

class LedgerGroupController extends Controller
{
    public function index(Request $request)
    {
        return LedTransactionGroup::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_disabled' => 'nullable|boolean',
        ]);

        $user = $request->user();

        $group = LedTransactionGroup::create([
            'user_id' => $user->id,
            ...$data,
        ]);

        return $group;
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_disabled' => 'nullable|boolean',
        ]);

        $group = LedTransactionGroup::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $group->update($data);

        return $group;
    }

    public function toggle(Request $request, string $id)
    {
        $group = LedTransactionGroup::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $group->is_disabled = $request->boolean('is_disabled');
        $group->save();

        // Synchronously update is_disabled on all transactions in group
        LedTransaction::where('group_id', $group->id)
            ->update(['is_disabled' => $group->is_disabled]);

        return $group;
    }

    public function destroy(Request $request, string $id)
    {
        $group = LedTransactionGroup::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $group->delete();

        return response()->json(['message' => 'Group deleted']);
    }
}
