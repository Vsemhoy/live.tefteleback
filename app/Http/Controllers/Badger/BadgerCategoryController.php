<?php

namespace App\Http\Controllers\Badger;

use App\Http\Controllers\Controller;
use App\Models\BudCategory;
use App\Models\BudTransaction;
use Illuminate\Http\Request;

class BadgerCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = BudCategory::where('user_id', $request->user()->id)
            ->orderBy('path')
            ->get();

        return response()->json(['status' => 1, 'content' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|string|size:26',
            'depth'     => 'integer|min:0|max:4',
            'sort_order'=> 'integer',
        ]);

        if (($data['depth'] ?? 0) > 4) {
            return response()->json(['message' => 'Max depth is 5 levels'], 422);
        }

        $id = (string) \Ulid\Ulid::generate();

        $path = $id;
        if (!empty($data['parent_id'])) {
            $parent = BudCategory::where('id', $data['parent_id'])
                                  ->where('user_id', $request->user()->id)
                                  ->firstOrFail();
            $path = $parent->path . '.' . $id;
        }

        $category = BudCategory::create([
            'id'         => $id,
            'user_id'    => $request->user()->id,
            'parent_id'  => $data['parent_id'] ?? null,
            'name'       => $data['name'],
            'depth'      => $data['depth'] ?? 0,
            'path'       => $path,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json(['status' => 1, 'content' => $category]);
    }

    public function update(Request $request, string $id)
    {
        $category = BudCategory::where('id', $id)
                                ->where('user_id', $request->user()->id)
                                ->firstOrFail();

        $data = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'sort_order' => 'sometimes|integer',
            'is_archived'=> 'sometimes|boolean',
        ]);

        $category->update($data);

        return response()->json(['status' => 1, 'content' => $category]);
    }

    public function destroy(Request $request, string $id)
    {
        $category = BudCategory::where('id', $id)
                                ->where('user_id', $request->user()->id)
                                ->firstOrFail();

        if ($category->children()->exists()) {
            return response()->json(['message' => 'Remove child categories first'], 422);
        }

        BudTransaction::where('category_id', $id)->update(['category_id' => null]);

        $category->delete();

        return response()->json(['status' => 1]);
    }

    public function reorder(Request $request)
    {
        $items = $request->validate([
            'items'            => 'required|array',
            'items.*.id'       => 'required|string',
            'items.*.sort_order'=> 'required|integer',
        ])['items'];

        foreach ($items as $item) {
            BudCategory::where('id', $item['id'])
                       ->where('user_id', $request->user()->id)
                       ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['status' => 1]);
    }
}
