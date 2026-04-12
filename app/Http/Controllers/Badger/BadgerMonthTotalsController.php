<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudMonthTotal;
use Illuminate\Http\Request;

class BadgerMonthTotalsController extends Controller
{

    public function index(Request $request)
    {
        $user     = $request->user();
        $monthKey = $request->query('month_key');
        $accountId = $request->query('account_id');

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
    // public function index(Request $request)
    // {
    //     $user = $request->user();
    //     $layerId = $request->query('layer_id');
    //     $accountId = $request->query('account_id');
    //     $monthKey = $request->query('month_key');

    //     $query = BudMonthTotal::where('user_id', $user->id);

    //     if ($layerId) {
    //         $query->where('layer_id', $layerId);
    //     }

    //     if ($accountId) {
    //         $query->where('account_id', $accountId);
    //     }

    //     if ($monthKey) {
    //         $query->where('month_key', $monthKey);
    //     }

    //     return $query->orderBy('month_key')->get();
    // }
}
