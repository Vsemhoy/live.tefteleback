<?php

namespace App\Http\Controllers\Eventor;

use App\Http\Controllers\Controller;
use App\Models\EvtCategory;
use App\Models\EvtEvent;
use App\Models\EvtSection;
use App\Models\EvtType;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request; // ✅ Правильный импорт
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Uid\Ulid;

class EventorApiController extends Controller
{
public function getMyEventsAction(Request $request): JsonResponse
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'status' => 0,
            'message' => 'Unauthorized'
        ], 401);
    }

    $params = $request->all();

    // Дефолт: текущий месяц
    $start = Carbon::now()->startOfMonth()->startOfDay();
    $end   = Carbon::now()->endOfMonth()->endOfDay();

    if (!empty($params['start'])) {
        $start = Carbon::parse($params['start'])->startOfDay();
    }

    if (!empty($params['end'])) {
        $end = Carbon::parse($params['end'])->endOfDay();
    }

    $query = EvtEvent::where('user_id', $user->id)
        ->whereBetween('setdate', [$start, $end]);

    // sections filter
    if (!empty($params['sections']) && is_array($params['sections'])) {
        $rawSections = $params['sections'];

        $hasAll = in_array('ALL', $rawSections, true);
        $hasNull = in_array('NULL', $rawSections, true);

        $sectionIds = array_values(array_filter(
            $rawSections,
            fn ($value) => $value !== 'ALL' && $value !== 'NULL' && $value !== null && $value !== ''
        ));

        if (!$hasAll) {
            if ($hasNull && !empty($sectionIds)) {
                $query->where(function ($q) use ($sectionIds) {
                    $q->whereNull('section_id')
                      ->orWhereIn('section_id', $sectionIds);
                });
            } elseif ($hasNull) {
                $query->whereNull('section_id');
            } elseif (!empty($sectionIds)) {
                $query->whereIn('section_id', $sectionIds);
            }
        }
    }

    $events = $query->orderBy('setdate', 'DESC')->get();

    return response()->json([
        'status' => 1,
        'message' => 'OK',
        'content' => $events
    ]);
}



     public function getMyEventAction(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 1,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Строим запрос
        $query = EvtEvent::where('user_id', $user->id)->where('id', $id)->first();

        return response()->json([
            'status' => 0, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $query // ✅ Laravel автоматически преобразует в JSON
        ]);
    }



    public function getMySections(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'Unauthorized'
            ], 401);
        }

        $params = $request->all();



        // Строим запрос
        $query = EvtSection::where('user_id', $user->id);
            

        $sections = $query->orderBy('sort_order', 'DESC')->get();

        return response()->json([
            'success' => 1, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $sections // ✅ Laravel автоматически преобразует в JSON
        ]);
    }


    public function getMyCategories(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'Unauthorized'
            ], 401);
        }

        $params = $request->all();



        // Строим запрос
        $query = EvtCategory::where('user_id', $user->id);
            

        $categories = $query->orderBy('sort_order', 'DESC')->get();

        return response()->json([
            'success' => 1, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $categories // ✅ Laravel автоматически преобразует в JSON
        ]);
    }


    public function getMyTypes(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Строим запрос
        $types = EvtType::where('is_default', 1)
            ->orWhere('user_id', $user->id)
            ->orderBy('sort_order', 'ASC')
            ->distinct()
            ->get(['id', 'name', 'color', 'bgcolor', 'icon', 'sort_order']);
            

        return response()->json([
            'success' => 1, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $types // ✅ Laravel автоматически преобразует в JSON
        ]);
    }


    // public function saveEventAction(Request $request)
    // {
    //     $user = $request->user(); // Лучше через $request->user(), чем Auth::user()
    //     if (!$user) {
    //         return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
    //     }

    //     $rules = [
    //         'section_id' => 'nullable|exists:evt_sections,id',
    //         'content' => 'required|string',
    //         'name' => 'sometimes|string|max:128',
    //         'metadata' => 'nullable|string|max:25',
    //         'type_id' => 'nullable|exists:evt_types,id',
    //         'category_id' => 'nullable|exists:evt_categories,id',
    //         'project_id' => 'nullable|exists:projects,id',
    //         'location' => 'nullable|string|max:50',
    //         'client' => 'nullable|string|max:120',
    //         'format' => 'sometimes|integer|between:1,3', // 1-md, 2-text, 3-code
    //         'status' => 'sometimes|integer|between:1,3', // 1-published, 2-archived
    //         'access' => 'sometimes|integer|between:0,4',
    //         'setdate' => 'sometimes|date',
    //     ];

    //     $messages = [
    //         'section_id.required' => 'Section ID is required',
    //         'section_id.exists' => 'Specified section does not exist',
    //         'content.required' => 'Content is required'
    //     ];

    //     $validator = Validator::make($request->all(), $rules, $messages);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 0, // ❌ Ошибка → status: 0
    //             'message' => $validator->errors()->first(),
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         $validated = $validator->validated();

    //         $data = [
    //             'user_id' =>    $user->id,
    //             'section_id' => $validated['section_id'],
    //             'name' =>       $validated['name'] ?? 'New event',
    //             'content' =>    $validated['content'],
    //             'format' =>     $validated['format'] ?? 1,
    //             'status' =>     $validated['status'] ?? 2, // default: published
    //             'access' =>     $validated['access'] ?? 1,
    //             'setdate' =>    $validated['setdate'] ?? now(),
    //         ];

    //         // Добавляем только существующие опциональные поля
    //         $optional = ['type_id', 'category_id', 'project_id', 'location', 'client', 'metadata'];
    //         foreach ($optional as $field) {
    //             if (isset($validated[$field])) {
    //                 $data[$field] = $validated[$field];
    //             }
    //         }

    //         $item = EvtEvent::create($data);

    //         return response()->json([
    //             'status' => 1, // ✅ Успех → status: 1
    //             'message' => 'Event created successfully',
    //             'content' => $item,
    //             'duration' => round(microtime(true) - LARAVEL_START, 3)
    //         ], 201); // 201 Created

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 0, // ✅ Ошибка → status: 0
    //             'message' => 'Server error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

public function saveEventAction(Request $request): JsonResponse
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'status' => 0,
            'message' => 'Unauthorized',
        ], 401);
    }

    $request->merge([
        'name' => is_string($request->input('name')) ? trim($request->input('name')) : $request->input('name'),
        'content' => is_string($request->input('content')) ? trim($request->input('content')) : $request->input('content'),
    ]);

    $rules = [
        'id' => 'nullable|string|max:26',
        'section_id' => 'nullable|exists:evt_sections,id',
        'name' => 'nullable|string|max:128|required_without:content',
        'content' => 'nullable|string|required_without:name',
        'metadata' => 'nullable|string|max:25',
        'type_id' => 'nullable|exists:evt_types,id',
        'category_id' => 'nullable|exists:evt_categories,id',
        'project_id' => 'nullable|exists:projects,id',
        'location' => 'nullable|string|max:50',
        'client' => 'nullable|string|max:120',
        'format' => 'nullable|integer|between:1,3',
        'status' => 'nullable|integer|between:1,3',
        'access' => 'nullable|integer|between:0,4',
        'setdate' => 'nullable|date',
    ];

    $messages = [
        'name.required_without' => 'Either title or content is required',
        'content.required_without' => 'Either title or content is required',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
        return response()->json([
            'status' => 0,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);
    }

    $validated = $validator->validated();
    $eventId = $validated['id'] ?? null;

    try {
        $fillableFields = [
            'section_id',
            'name',
            'content',
            'metadata',
            'type_id',
            'category_id',
            'project_id',
            'location',
            'client',
            'format',
            'status',
            'access',
            'setdate',
        ];

        if (!$eventId) {
            // CREATE
            $data = [
                'id' => (string) Ulid::generate(),
                'user_id' => $user->id,
                'name' => $validated['name'] ?? null,
                'content' => $validated['content'],
                'format' => $validated['format'] ?? 1,
                'status' => $validated['status'] ?? 2,
                'access' => $validated['access'] ?? 1,
                'setdate' => $validated['setdate'] ?? now(),
            ];

            foreach ($fillableFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $data[$field] = $validated[$field];
                }
            }

            $item = EvtEvent::create($data);

            return response()->json([
                'status' => 1,
                'message' => 'Event created successfully',
                'content' => $item,
                'duration' => round(microtime(true) - LARAVEL_START, 3),
            ], 201);
        }

        // UPDATE
        $event = EvtEvent::find($eventId);

        if (!$event) {
            return response()->json([
                'status' => 0,
                'message' => 'Event not found',
            ], 404);
        }

        if ($event->user_id !== $user->id) {
            return response()->json([
                'status' => 0,
                'message' => 'You are not the owner of this event',
            ], 403);
        }

        $updateData = [];

        foreach ($fillableFields as $field) {
            if (array_key_exists($field, $validated)) {
                $updateData[$field] = $validated[$field];
            }
        }

        $event->update($updateData);
        $event->refresh();

        return response()->json([
            'status' => 1,
            'message' => 'Event updated successfully',
            'content' => $event,
            'duration' => round(microtime(true) - LARAVEL_START, 3),
        ], 200);

    } catch (\Throwable $e) {
        Log::error('SaveEventAction failed', [
            'user_id' => $user->id,
            'event_id' => $eventId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => 0,
            'message' => 'Server error',
        ], 500);
    }
}

}