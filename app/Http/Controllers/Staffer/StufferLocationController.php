<?php

namespace App\Http\Controllers\Staffer;

use App\Http\Controllers\Controller;
use App\Models\StfLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StufferLocationController extends Controller
{
    // GET /stuffer/locations - full tree
    public function index()
    {
        $locations = StfLocation::where('user_id', Auth::id())
            ->orderBy('sort_order')
            ->get();

        return response()->json(['status' => 1, 'content' => $locations]);
    }

    // POST /stuffer/locations
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|string|size:26',
        ]);

        $loc = StfLocation::create([
            ...$data,
            'user_id' => Auth::id(),
            'sort_order' => StfLocation::where('user_id', Auth::id())
                    ->where('parent_id', $data['parent_id'] ?? null)
                    ->max('sort_order') + 1,
        ]);

        return response()->json(['status' => 1, 'content' => $loc], 201);
    }

    // PUT /stuffer/locations/{id}
    public function update(Request $request, string $id)
    {
        $loc = StfLocation::where('user_id', Auth::id())->findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'parent_id' => 'nullable|string|size:26',
            'sort_order' => 'nullable|integer',
            'is_archived' => 'nullable|boolean',
        ]);
        $loc->update($data);

        return response()->json(['status' => 1, 'content' => $loc]);
    }

    // DELETE /stuffer/locations/{id}
    public function destroy(string $id)
    {
        $loc = StfLocation::where('user_id', Auth::id())->findOrFail($id);

        if ($loc->canForceDelete()) {
            $loc->forceDelete();
        } else {
            $loc->delete();
        }

        return response()->json(['status' => 1]);
    }

    // POST /stuffer/locations/reorder
    public function reorder(Request $request)
    {
        $items = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.sort_order' => 'required|integer',
        ])['items'];

        foreach ($items as $item) {
            StfLocation::where('user_id', Auth::id())
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['status' => 1]);
    }
}
