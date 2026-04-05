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
                'status' => 1,
                'message' => 'Unauthorized'
            ], 401);
        }

        $params = $request->all();

        // Установим дефолтные даты: начало и конец текущего месяца
        $start = Carbon::now()->startOfMonth()->startOfDay();
        $end   = Carbon::now()->endOfMonth()->endOfDay();

        $sections = "ALL";

        // Парсим даты, если переданы
        if (isset($params['start']) && $params['start']) {
            $start = Carbon::parse($params['start'])->startOfDay();
        }

        if (isset($params['end']) && $params['end']) {
            $end = Carbon::parse($params['end'])->endOfDay();
        }

        // Проверяем секции
        if (isset($params['sections']) && is_array($params['sections']) && !empty($params['sections'])) {
            if ($params['sections'][0] !== "NULL"){
                 if ($params['sections'][0] !== 'ALL'){
                     $sections = $params['sections'];
                 }
            } else {
                $sections = null;
            }
        }

        // Строим запрос
        $query = EvtEvent::where('user_id', $user->id)
            ->whereBetween('setdate', [$start, $end]); // ✅ Правильный синтаксис

        if ($sections !== 'ALL') {
            $query->whereIn('section_id', $sections);
        }

        $events = $query->orderBy('setdate', 'DESC')->get();

        return response()->json([
            'status' => 0, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $events // ✅ Laravel автоматически преобразует в JSON
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
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        $rules = [
            'id' => 'required|string|max:26', // включаем id в валидацию
            'section_id' => 'nullable|exists:evt_sections,id',
            'content' => 'required|string',
            'name' => 'sometimes|string|max:128',
            'metadata' => 'nullable|string|max:25',
            'type_id' => 'nullable|exists:evt_types,id',
            'category_id' => 'nullable|exists:evt_categories,id',
            'project_id' => 'nullable|exists:projects,id',
            'location' => 'nullable|string|max:50',
            'client' => 'nullable|string|max:120',
            'format' => 'sometimes|integer|between:1,3',
            'status' => 'sometimes|integer|between:1,3',
            'access' => 'sometimes|integer|between:0,4',
            'setdate' => 'sometimes|date',
        ];

        $messages = [
            'id.required' => 'Event ID is required',
            'content.required' => 'Content is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $eventId = $validated['id'];

        // Log::info('=== START REQUEST ===', [
        //     'request_id' => uniqid(),
        //     'time' => microtime(true),
        //     'session_id' => session()->getId(),
        //     'user_id' => $user->id,
        //     'event_id' => $validated['id']
        // ]);

        try {
            // Определяем, создание это или обновление
            $isTempId = str_starts_with($eventId, 'temp_') || str_starts_with($eventId, 'new_');

            if ($isTempId) {
            //     Log::info('saveEventAction called', [
            //     'user_id' => $user->id,
            //     'event_id' => $validated['id'],
            //     'is_temp' => $isTempId,
            //     'ip' => $request->ip(),
            //     'timestamp' => microtime(true)
            // ]);

                // 🔹 Создание нового события
                $data = [
                    'id' => (string) Ulid::generate(), // настоящий ID
                    'user_id' => $user->id,
                    'section_id' => $validated['section_id'] ?? null,
                    'name' => $validated['name'] ?? 'New event',
                    'content' => $validated['content'],
                    'format' => $validated['format'] ?? 1,
                    'status' => $validated['status'] ?? 2,
                    'access' => $validated['access'] ?? 1,
                    'setdate' => $validated['setdate'] ?? now(),
                ];

                $optional = ['type_id', 'category_id', 'project_id', 'location', 'client', 'metadata'];
                foreach ($optional as $field) {
                    if (isset($validated[$field])) {
                        $data[$field] = $validated[$field];
                    }
                }

                $item = EvtEvent::create($data);

                return response()->json([
                    'status' => 1,
                    'message' => 'Event created successfully',
                    'content' => $item,
                    'tempId' => $eventId, // чтобы фронтенд заменил tempId на настоящий
                    'duration' => round(microtime(true) - LARAVEL_START, 3)
                ], 201);

            } else {
                // 🔹 Обновление существующего события
                $event = EvtEvent::find($eventId);

                if (!$event) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Event not found'
                    ], 404);
                }

                // 🔒 Проверка, что юзер — владелец
                if ($event->user_id !== $user->id) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'You are not the owner of this event'
                    ], 403);
                }

                // Обновляем только разрешённые поля
                $updatable = [
                    'section_id',
                    'name',
                    'content',
                    'type_id',
                    'category_id',
                    'project_id',
                    'location',
                    'client',
                    'metadata',
                    'format',
                    'status',
                    'access',
                    'setdate'
                ];

                $updateData = [];
                foreach ($updatable as $field) {
                    if (isset($validated[$field])) {
                        $updateData[$field] = $validated[$field];
                    }
                }

                $event->update($updateData);

                return response()->json([
                    'status' => 1,
                    'message' => 'Event updated successfully',
                    'content' => $event,
                    'duration' => round(microtime(true) - LARAVEL_START, 3)
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('SaveEventAction failed', [
                'user_id' => $user->id,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

}