<?php

namespace App\Http\Controllers\Staffer;

use App\Http\Controllers\Controller;
use App\Models\StfRegister;
use App\Models\StfThing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StufferRegisterController extends Controller
{
    // GET /stuffer/register?thing_id=&limit=50
    public function index(Request $request)
    {
        $query = StfRegister::where('user_id', Auth::id())
            ->with(['thing', 'fromLocation', 'toLocation', 'expense', 'ledgerTransactions', 'timerEntries', 'eventorEvents'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        if ($request->thing_id) {
            $query->where('thing_id', $request->thing_id);
        }

        $limit = min((int) $request->limit ?: 50, 200);

        return response()->json([
            'status' => 1,
            'content' => $query->limit($limit)->get(),
        ]);
    }

    // POST /stuffer/register - atomic action
    public function store(Request $request)
    {
        $data = $request->validate([
            'thing_id' => 'required|string|max:26',
            'event_type' => 'required|string',
            'from_location_id' => 'nullable|string|max:26',
            'to_location_id' => 'nullable|string|max:26',
            'contact' => 'nullable|string|max:200',
            'return_expected' => 'nullable|date',
            'amount' => 'nullable|integer',
            'note' => 'nullable|string',
            'details' => 'nullable|array',
            'status' => 'nullable|integer',
            'priority' => 'nullable|integer',
            'is_pinned' => 'nullable|boolean',
            'part_cost' => 'nullable|integer',
            'labor_cost' => 'nullable|integer',
            'time_self_min' => 'nullable|integer',
            'time_service_min' => 'nullable|integer',
            'occurred_at' => 'required|date',
        ]);

        $thing = StfThing::where('user_id', Auth::id())
            ->findOrFail($data['thing_id']);

        DB::transaction(function () use ($data, $thing) {
            $reg = StfRegister::create([
                ...$data,
                'user_id' => Auth::id(),
            ]);

            $updates = [
                'current_status' => StfRegister::statusFromEvent($data['event_type']),
            ];

            $locationEvents = ['bought', 'received', 'moved', 'installed', 'returned'];
            if (in_array($data['event_type'], $locationEvents)) {
                $updates['current_location_id'] = $data['to_location_id'] ?? null;
            }

            $clearLocationEvents = ['lent', 'sold', 'lost', 'stolen', 'disposed'];
            if (in_array($data['event_type'], $clearLocationEvents)) {
                $updates['current_location_id'] = null;
            }

            $thing->update($updates);

            if (!empty($data['amount'])) {
                \App\Models\StfExpense::create([
                    'user_id' => Auth::id(),
                    'thing_id' => $thing->id,
                    'register_id' => $reg->id,
                    'amount' => $data['amount'],
                    'occurred_at' => $data['occurred_at'],
                ]);
            }
        });

        return response()->json(['status' => 1]);
    }

    // DELETE /stuffer/register/{id} - rollback of event
    public function destroy(string $id)
    {
        $reg = StfRegister::where('user_id', Auth::id())->findOrFail($id);

        DB::transaction(function () use ($reg) {
            $prev = StfRegister::where('thing_id', $reg->thing_id)
                ->where('id', '!=', $reg->id)
                ->orderByDesc('occurred_at')
                ->orderByDesc('created_at')
                ->first();

            if ($prev) {
                $reg->thing->update([
                    'current_status' => StfRegister::statusFromEvent($prev->event_type),
                    'current_location_id' => $prev->to_location_id,
                ]);
            } else {
                $reg->thing->update([
                    'current_status' => 'active',
                    'current_location_id' => null,
                ]);
            }

            $reg->expense?->delete();
            $reg->delete();
        });

        return response()->json(['status' => 1]);
    }
}
