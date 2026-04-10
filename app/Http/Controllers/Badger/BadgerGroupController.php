<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudTransaction;
use App\Models\BudTransactionGroup;
use Illuminate\Http\Request;

class BadgerGroupController extends Controller
{
    public function index(Request $request)
    {
        return BudTransactionGroup::where('user_id', $request->user()->id)
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

        $group = BudTransactionGroup::create([
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

        $group = BudTransactionGroup::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $group->update($data);

        return $group;
    }

    public function toggle(Request $request, string $id)
    {
        $group = BudTransactionGroup::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $group->is_disabled = $request->boolean('is_disabled');
        $group->save();

        // Synchronously update is_disabled on all transactions in group
        BudTransaction::where('group_id', $group->id)
            ->update(['is_disabled' => $group->is_disabled]);

        return $group;
    }

    public function destroy(Request $request, string $id)
    {
        $group = BudTransactionGroup::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $group->delete();

        return response()->json(['message' => 'Group deleted']);
    }
}
