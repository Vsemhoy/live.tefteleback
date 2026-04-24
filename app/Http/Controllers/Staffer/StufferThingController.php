<?php

namespace App\Http\Controllers\Staffer;

use App\Http\Controllers\Controller;
use App\Models\StfThing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StufferThingController extends Controller
{
    // GET /stuffer/things
    public function index(Request $request)
    {
        $query = StfThing::where('user_id', Auth::id())
            ->active()
            ->byRelevance()
            ->with(['location', 'category']);

        if ($request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->location_id) {
            $query->where('current_location_id', $request->location_id);
        }
        if ($request->status) {
            $query->where('current_status', $request->status);
        }
        if ($request->q) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                    ->orWhere('description', 'like', "%{$request->q}%")
                    ->orWhere('serial_no', 'like', "%{$request->q}%")
                    ->orWhere('vendor', 'like', "%{$request->q}%");
            });
        }

        return response()->json([
            'status' => 1,
            'content' => $query->get(),
        ]);
    }

    // GET /stuffer/things/{id}
    public function show(string $id)
    {
        $thing = StfThing::where('user_id', Auth::id())
            ->with([
                'location',
                'category',
                'children.location',
                'parent',
                'register.fromLocation',
                'register.toLocation',
                'expenses.transaction',
            ])
            ->findOrFail($id);

        $thing->recordOpen();

        return response()->json(['status' => 1, 'content' => $thing]);
    }

    // POST /stuffer/things
    public function store(Request $request)
    {
        $data = $request->validate([
            'entity_type' => 'required|in:asset,item',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'vendor' => 'nullable|string|max:200',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|string|size:26',
            'category_id' => 'nullable|string|size:26',
            'current_location_id' => 'nullable|string|size:26',
            'current_status' => 'nullable|string',
            'serial_no' => 'nullable|string|max:100',
            'qty' => 'nullable|numeric',
            'unit' => 'nullable|string|max:20',
            'purchase_price' => 'nullable|integer',
            'purchase_date' => 'nullable|date',
        ]);

        $thing = StfThing::create([
            ...$data,
            'user_id' => Auth::id(),
            'current_status' => $data['current_status'] ?? 'active',
        ]);

        return response()->json(['status' => 1, 'content' => $thing], 201);
    }

    // PUT /stuffer/things/{id}
    public function update(Request $request, string $id)
    {
        $thing = StfThing::where('user_id', Auth::id())->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'vendor' => 'nullable|string|max:200',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|string|size:26',
            'category_id' => 'nullable|string|size:26',
            'serial_no' => 'nullable|string|max:100',
            'qty' => 'nullable|numeric',
            'unit' => 'nullable|string|max:20',
            'purchase_price' => 'nullable|integer',
            'purchase_date' => 'nullable|date',
        ]);

        $thing->update($data);

        return response()->json(['status' => 1, 'content' => $thing]);
    }

    // DELETE /stuffer/things/{id} - soft delete (archive)
    public function destroy(string $id)
    {
        $thing = StfThing::where('user_id', Auth::id())->findOrFail($id);
        $thing->update(['is_archived' => true]);
        $thing->delete();

        return response()->json(['status' => 1]);
    }

    // POST /stuffer/things/{id}/open - increment open counter
    public function open(string $id)
    {
        $thing = StfThing::where('user_id', Auth::id())->findOrFail($id);
        $thing->recordOpen();

        return response()->json(['status' => 1]);
    }
}
