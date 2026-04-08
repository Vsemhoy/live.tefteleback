<?php

namespace App\Http\Controllers\Eventor;

use App\Http\Controllers\Controller;
use App\Models\EvtCategory;
use App\Models\EvtEvent;
use App\Models\EvtSection;
use App\Models\EvtType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // ✅ Правильный импорт
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Uid\Ulid;

class EventorApiController extends Controller
{
    public function getMyEventsAction(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $params = $request->all();

        // Дефолт: текущий месяц
        $start = Carbon::now()->startOfMonth()->startOfDay();
        $end = Carbon::now()->endOfMonth()->endOfDay();

        if (! empty($params['start'])) {
            $start = Carbon::parse($params['start'])->startOfDay();
        }

        if (! empty($params['end'])) {
            $end = Carbon::parse($params['end'])->endOfDay();
        }

        $query = EvtEvent::where('user_id', $user->id)
            ->whereBetween('setdate', [$start, $end]);

        // sections filter
        if (! empty($params['sections']) && is_array($params['sections'])) {
            $rawSections = $params['sections'];

            $hasAll = in_array('ALL', $rawSections, true);
            $hasNull = in_array('NULL', $rawSections, true);

            $sectionIds = array_values(array_filter(
                $rawSections,
                fn ($value) => $value !== 'ALL' && $value !== 'NULL' && $value !== null && $value !== ''
            ));

            if (! $hasAll) {
                if ($hasNull && ! empty($sectionIds)) {
                    $query->where(function ($q) use ($sectionIds) {
                        $q->whereNull('section_id')
                            ->orWhereIn('section_id', $sectionIds);
                    });
                } elseif ($hasNull) {
                    $query->whereNull('section_id');
                } elseif (! empty($sectionIds)) {
                    $query->whereIn('section_id', $sectionIds);
                }
            }
        }

        $events = $query->orderBy('setdate', 'DESC')->get();

        return response()->json([
            'status' => 1,
            'message' => 'OK',
            'content' => $events,
        ]);
    }

    public function getMyEventAction(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 1,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Строим запрос
        $query = EvtEvent::where('user_id', $user->id)->where('id', $id)->first();

        return response()->json([
            'status' => 0, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $query, // ✅ Laravel автоматически преобразует в JSON
        ]);
    }

    public function getMySections(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $params = $request->all();

        // Строим запрос
        $query = EvtSection::where('user_id', $user->id);

        $sections = $query->orderBy('sort_order', 'DESC')->get();

        return response()->json([
            'success' => 1, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $sections, // ✅ Laravel автоматически преобразует в JSON
        ]);
    }

    public function getMyCategories(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $params = $request->all();

        // Строим запрос
        $query = EvtCategory::where('user_id', $user->id);

        $categories = $query->orderBy('sort_order', 'DESC')->get();

        return response()->json([
            'success' => 1, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $categories, // ✅ Laravel автоматически преобразует в JSON
        ]);
    }

    public function getMyTypes(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => 0,
                'message' => 'Unauthorized',
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
            'content' => $types, // ✅ Laravel автоматически преобразует в JSON
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

        if (! $user) {
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

            if (! $eventId) {
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

            if (! $event) {
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

    public function updateEventAction(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $event = EvtEvent::find($id);

        if (! $event) {
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

        $request->merge([
            'name' => is_string($request->input('name')) ? trim($request->input('name')) : $request->input('name'),
            'content' => is_string($request->input('content')) ? trim($request->input('content')) : $request->input('content'),
        ]);

        $rules = [
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

        try {
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
            Log::error('UpdateEventAction failed', [
                'user_id' => $user->id,
                'event_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Server error',
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $params = $request->all();

        $validator = Validator::make($params, [
            'q' => 'nullable|string|max:255',
            'types' => 'nullable|array',
            'types.*' => 'string',
            'sections' => 'nullable|array',
            'sections.*' => 'string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $query = EvtEvent::where('user_id', $user->id);

        if (isset($params['q']) && $params['q']) {
            $searchTerm = '%'.$params['q'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhere('content', 'LIKE', $searchTerm);
            });
        }

        if (isset($params['types']) && is_array($params['types']) && ! empty($params['types'])) {
            $query->whereIn('type_id', $params['types']);
        }

        if (isset($params['sections']) && is_array($params['sections']) && ! empty($params['sections'])) {
            $query->whereIn('section_id', $params['sections']);
        }

        if (isset($params['date_from']) && $params['date_from']) {
            $query->whereDate('setdate', '>=', Carbon::parse($params['date_from'])->startOfDay());
        }

        if (isset($params['date_to']) && $params['date_to']) {
            $query->whereDate('setdate', '<=', Carbon::parse($params['date_to'])->endOfDay());
        }

        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 20;

        $total = $query->count();
        $events = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->orderBy('setdate', 'DESC')
            ->get();

        $lastPage = (int) ceil($total / $perPage);

        return response()->json([
            'content' => $events->toArray(),
            'total' => $total,
            'page' => $page,
            'pages' => $lastPage,
        ]);
    }
}

    public function deleteEvent(Request , \): JsonResponse
    {
        \ = \->user();
        if (! \) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        \ = EvtEvent::find(\);

        if (! \) {
            return response()->json(['status' => 0, 'message' => 'Event not found'], 404);
        }

        if (\->user_id !== \->id) {
            return response()->json(['status' => 0, 'message' => 'You are not the owner of this event'], 403);
        }

        \->delete();

        return response()->json(['status' => 1, 'message' => 'Event deleted successfully']);
    }
