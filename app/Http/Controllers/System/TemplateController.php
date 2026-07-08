<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SysTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SysTemplate::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('module')) {
            $query->where('module', $request->string('module')->toString());
        }

        return response()->json([
            'status' => 1,
            'content' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => 'required|string|max:32',
            'name' => 'required|string|max:128',
            'icon' => 'nullable|string|max:64',
            'payload' => 'required|array',
            'schedule' => 'nullable|array',
            'status' => 'nullable|integer|min:0|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $template = SysTemplate::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? 1,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'status' => 1,
            'content' => $template,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $template = SysTemplate::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'module' => 'sometimes|string|max:32',
            'name' => 'sometimes|string|max:128',
            'icon' => 'nullable|string|max:64',
            'payload' => 'sometimes|array',
            'schedule' => 'nullable|array',
            'status' => 'nullable|integer|min:0|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $template->update($validated);

        return response()->json([
            'status' => 1,
            'content' => $template->refresh(),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $template = SysTemplate::where('user_id', $request->user()->id)->findOrFail($id);
        $template->delete();

        return response()->json(['status' => 1]);
    }
}
