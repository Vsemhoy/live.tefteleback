<?php

namespace App\Http\Controllers\Eventor;

use App\Http\Controllers\Controller;
use App\Models\EvtCategory;
use App\Models\EvtEvent;
use App\Models\EvtSection;
use App\Models\EvtTag;
use App\Models\EvtType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

        $query = EvtEvent::with([
            'evt_type',
            'parent:id,name,setdate',
            'children:id,name,parent_id,setdate',
            'section:id,name,color,bgcolor,icon',
            'primaryContent',
            'tags',
        ])
            ->where('user_id', $user->id)
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


    public function getMyPinnedAction(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }


        $query = EvtEvent::with([
            'evt_type',
            'parent:id,name,setdate',
            'children:id,name,parent_id,setdate',
            'section:id,name,color,bgcolor,icon',
            'primaryContent',
            'tags',
        ])
            ->where('user_id', $user->id)
            ->where('is_pinned', 1);

        $events = $query->orderBy('setdate', 'DESC')->orderBy('sort_order', 'DESC')->get();

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
        $query = EvtEvent::with([
            'parent:id,name,setdate',
            'children:id,name,parent_id,setdate',
            'primaryContent',
        ])->where('user_id', $user->id)->where('id', $id)->first();

        return response()->json([
            'status' => 0, // ✅ 0 = успех
            'message' => 'OK',
            'content' => $query, // ✅ Laravel автоматически преобразует в JSON
        ]);
    }

    public function getEventPublicAction(Request $request, $id): JsonResponse
    {
        $event = EvtEvent::with([
            'parent:id,name,setdate',
            'children:id,name,parent_id,setdate',
            'user:id,name',
            'type',
            'section:id,name,color,bgcolor,icon',
            'primaryContent',
            'tags',
        ])
            ->where('id', $id)
            ->first();

        if (! $event) {
            return response()->json(['status' => 0, 'message' => 'Event not found'], 404);
        }

        if ($event->access !== 3) {
            $authUser = $this->tryGetUser($request);

            if (! $authUser || $authUser->id !== $event->user_id) {
                return response()->json(['status' => 0, 'message' => 'Access denied'], 403);
            }
        }

        return response()->json(['status' => 1, 'message' => 'OK', 'content' => $event]);
    }

    // Мягкая проверка — не бросает исключение, просто null если нет токена
    private function tryGetUser(Request $request): ?User
    {
        try {
            $token = $request->cookie('access_token');
            if (! $token) return null;

            $decoded = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key(config('jwt.secret'), config('jwt.algo'))
            );

            return User::find($decoded->sub);
        } catch (\Exception $e) {
            return null;
        }
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
            'parent_id' => 'nullable|string|max:26',
            'section_id' => 'nullable|exists:evt_sections,id',
            'name' => 'nullable|string|max:128|required_without:content',
            'content' => 'nullable|string|required_without:name',
            'metadata' => 'nullable|string|max:25',
            'type_id' => 'nullable|exists:evt_types,id',
            'category_id' => 'nullable|exists:evt_categories,id',
            'project_id' => 'nullable|exists:projects,id',
            'exploiter_event_id' => 'nullable|string|size:26',
            'location' => 'nullable|string|max:50',
            'client' => 'nullable|string|max:120',
            'format' => 'nullable|integer|between:1,3',
            'status' => 'nullable|integer|between:1,3',
            'access' => 'nullable|integer|between:0,6',
            'is_locked' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',
            'is_blurred' => 'nullable|boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'string|max:26|exists:evt_tags,id',
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
                'exploiter_event_id',
                'location',
                'client',
                'format',
                'status',
                'access',
                'is_locked',
                'is_pinned',
                'is_blurred',
                'setdate',
                'parent_id',
                'access',
            ];

            if (! $eventId) {
                // CREATE
                $data = [
                    'id' => (string) Ulid::generate(),
                    'user_id' => $user->id,
                    'name' => $validated['name'] ?? null,
                    'content' => $validated['content'] ?? null,
                    'format' => $validated['format'] ?? 1,
                    'status' => $validated['status'] ?? 2,
                    'access' => 1,
                    'setdate' => $validated['setdate'] ?? now(),
                    'parent_id' => $validated['parent_id'] ?? null,
                ];

                foreach ($fillableFields as $field) {
                    if (array_key_exists($field, $validated)) {
                        $data[$field] = $validated[$field];
                    }
                }

                $item = EvtEvent::create($data);
                $item->syncPrimaryContent($validated['content'] ?? null);

                if (array_key_exists('tag_ids', $validated)) {
                    $requestedTagIds = array_values(array_unique($validated['tag_ids'] ?? []));
                    $allowedTagIds = EvtTag::whereIn('id', $requestedTagIds)
                        ->where(function ($q) use ($user) {
                            $q->where('user_id', $user->id)
                                ->orWhere('is_system', true);
                        })
                        ->pluck('id')
                        ->all();

                    if (count($allowedTagIds) !== count($requestedTagIds)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'One or more tags are invalid or not accessible',
                        ], 422);
                    }

                    $item->tags()->sync($allowedTagIds);
                }

                return response()->json([
                    'status' => 1,
                    'message' => 'Event created successfully',
                    'content' => $item->fresh(['primaryContent', 'tags']),
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

            if ($event->is_locked) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Event is locked and cannot be updated',
                ], 423);
            }

            $updateData = [];

            foreach ($fillableFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $updateData[$field] = $validated[$field];
                }
            }

            $event->update($updateData);

            if (array_key_exists('content', $validated)) {
                $event->syncPrimaryContent($validated['content']);
            }

            if (array_key_exists('tag_ids', $validated)) {
                $requestedTagIds = array_values(array_unique($validated['tag_ids'] ?? []));
                $allowedTagIds = EvtTag::whereIn('id', $requestedTagIds)
                    ->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->orWhere('is_system', true);
                    })
                    ->pluck('id')
                    ->all();

                if (count($allowedTagIds) !== count($requestedTagIds)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'One or more tags are invalid or not accessible',
                    ], 422);
                }

                $event->tags()->sync($allowedTagIds);
            }

            $event->refresh();
            $event->load('primaryContent');

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

        if ($event->is_locked) {
            return response()->json([
                'status' => 0,
                'message' => 'Event is locked and cannot be updated',
            ], 423);
        }

        $request->merge([
            'name' => is_string($request->input('name')) ? trim($request->input('name')) : $request->input('name'),
            'content' => is_string($request->input('content')) ? trim($request->input('content')) : $request->input('content'),
        ]);

        $rules = [
            'section_id' => 'nullable|exists:evt_sections,id',
            'parent_id' => 'nullable|string|max:26',
            'name' => 'nullable|string|max:128|required_without:content',
            'content' => 'nullable|string|required_without:name',
            'metadata' => 'nullable|string|max:25',
            'type_id' => 'nullable|exists:evt_types,id',
            'category_id' => 'nullable|exists:evt_categories,id',
            'project_id' => 'nullable|exists:projects,id',
            'exploiter_event_id' => 'nullable|string|size:26',
            'location' => 'nullable|string|max:50',
            'client' => 'nullable|string|max:120',
            'format' => 'nullable|integer|between:1,3',
            'status' => 'nullable|integer|between:1,3',
            'access' => 'nullable|integer|between:0,6',
            'is_locked' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',
            'is_blurred' => 'nullable|boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'string|max:26|exists:evt_tags,id',
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
            'exploiter_event_id',
            'location',
            'client',
            'format',
            'status',
            'is_locked',
            'is_pinned',
            'is_blurred',
            'setdate',
            'parent_id',
            'access',
        ];

        try {
            $updateData = [];

            foreach ($fillableFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $updateData[$field] = $validated[$field];
                }
            }

            $event->update($updateData);

            if (array_key_exists('content', $validated)) {
                $event->syncPrimaryContent($validated['content']);
            }

            if (array_key_exists('tag_ids', $validated)) {
                $requestedTagIds = array_values(array_unique($validated['tag_ids'] ?? []));
                $allowedTagIds = EvtTag::whereIn('id', $requestedTagIds)
                    ->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->orWhere('is_system', true);
                    })
                    ->pluck('id')
                    ->all();

                if (count($allowedTagIds) !== count($requestedTagIds)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'One or more tags are invalid or not accessible',
                    ], 422);
                }

                $event->tags()->sync($allowedTagIds);
            }

            $event->refresh();
            $event->load('primaryContent');

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

    public function saveSectionAction(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $rules = [
            'name' => 'nullable|string|max:32',
            'literals' => 'nullable|string|max:3',
            'bgcolor' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/i',
            'sort_order' => 'nullable|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $data = [
                'id' => (string) Ulid::generate(),
                'user_id' => $user->id,
                'name' => $validated['name'] ?? null,
                'literals' => $validated['literals'] ?? null,
                'bgcolor' => $validated['bgcolor'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_archived' => false,
                'is_default' => false,
            ];

            $section = EvtSection::create($data);

            return response()->json([
                'status' => 1,
                'message' => 'Section created successfully',
                'content' => $section,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('SaveSectionAction failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Server error',
            ], 500);
        }
    }

    public function updateSectionAction(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $section = EvtSection::find($id);

        if (! $section) {
            return response()->json([
                'status' => 0,
                'message' => 'Section not found',
            ], 404);
        }

        if ($section->user_id !== $user->id) {
            return response()->json([
                'status' => 0,
                'message' => 'You are not the owner of this section',
            ], 403);
        }

        $rules = [
            'name' => 'nullable|string|max:32',
            'literals' => 'nullable|string|max:3',
            'bgcolor' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/i',
            'sort_order' => 'nullable|integer',
            'is_archived' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $updateData = [];

            if (isset($validated['name'])) {
                $updateData['name'] = trim($validated['name']);
            }
            if (isset($validated['literals'])) {
                $updateData['literals'] = $validated['literals'];
            }
            if (isset($validated['bgcolor'])) {
                $updateData['bgcolor'] = $validated['bgcolor'];
            }
            if (isset($validated['sort_order'])) {
                $updateData['sort_order'] = $validated['sort_order'];
            }
            if (isset($validated['is_archived'])) {
                $updateData['is_archived'] = $validated['is_archived'];
            }

            $section->update($updateData);
            $section->refresh();

            return response()->json([
                'status' => 1,
                'message' => 'Section updated successfully',
                'content' => $section,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('UpdateSectionAction failed', [
                'user_id' => $user->id,
                'section_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Server error',
            ], 500);
        }
    }

    public function deleteSectionAction(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $section = EvtSection::find($id);

        if (! $section) {
            return response()->json([
                'status' => 0,
                'message' => 'Section not found',
            ], 404);
        }

        if ($section->user_id !== $user->id) {
            return response()->json([
                'status' => 0,
                'message' => 'You are not the owner of this section',
            ], 403);
        }

        if ($section->is_default) {
            return response()->json([
                'status' => 0,
                'message' => 'Cannot delete: default section',
            ], 422);
        }

        $hasEvents = EvtEvent::where('section_id', $id)->exists();

        if ($hasEvents) {
            return response()->json([
                'status' => 0,
                'message' => 'Cannot delete: section has linked events',
            ], 422);
        }

        try {
            $section->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Section deleted successfully',
            ], 200);

        } catch (\Throwable $e) {
            Log::error('DeleteSectionAction failed', [
                'user_id' => $user->id,
                'section_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Server error',
            ], 500);
        }
    }

    public function reorderSectionsAction(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized',
            ], 401);
        }

        $rules = [
            'sections' => 'required|array|min:1',
            'sections.*.id' => 'required|string|max:26',
            'sections.*.sort_order' => 'required|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            DB::transaction(function () use ($user, $validated) {
                foreach ($validated['sections'] as $sectionData) {
                    EvtSection::where('id', $sectionData['id'])
                        ->where('user_id', $user->id)
                        ->update(['sort_order' => $sectionData['sort_order']]);
                }
            });

            return response()->json([
                'status' => 1,
                'message' => 'Sections reordered successfully',
            ], 200);

        } catch (\Throwable $e) {
            Log::error('ReorderSectionsAction failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
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

    public function deleteEvent(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        $event = EvtEvent::find($id);

        if (! $event) {
            return response()->json(['status' => 0, 'message' => 'Event not found'], 404);
        }

        if ($event->user_id !== $user->id) {
            return response()->json(['status' => 0, 'message' => 'You are not the owner of this event'], 403);
        }

        $event->delete();

        return response()->json(['status' => 1, 'message' => 'Event deleted successfully']);
    }

    public function getMyTagsAction(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        $tags = EvtTag::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere('is_system', true);
        })
            ->where('is_archived', false)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'color', 'bgcolor', 'is_system', 'sort_order']);

        return response()->json(['status' => 1, 'message' => 'OK', 'content' => $tags]);
    }

    public function saveTagAction(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        $rules = [
            'name' => 'required|string|max:32',
            'color' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/i',
            'bgcolor' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/i',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();

        try {
            $tag = EvtTag::create([
                'id' => (string) Ulid::generate(),
                'user_id' => $user->id,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'color' => $validated['color'] ?? null,
                'bgcolor' => $validated['bgcolor'] ?? null,
                'is_system' => false,
                'sort_order' => 0,
                'is_archived' => false,
            ]);

            return response()->json(['status' => 1, 'message' => 'Tag created successfully', 'content' => $tag], 201);
        } catch (\Throwable $e) {
            Log::error('SaveTagAction failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['status' => 0, 'message' => 'Server error'], 500);
        }
    }

    public function updateTagAction(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        $tag = EvtTag::find($id);

        if (! $tag) {
            return response()->json(['status' => 0, 'message' => 'Tag not found'], 404);
        }

        if ($tag->is_system) {
            return response()->json(['status' => 0, 'message' => 'Cannot modify system tag'], 403);
        }

        if ($tag->user_id !== $user->id) {
            return response()->json(['status' => 0, 'message' => 'You are not the owner of this tag'], 403);
        }

        $rules = [
            'name' => 'required|string|max:32',
            'color' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/i',
            'bgcolor' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/i',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();

        try {
            $tag->name = $validated['name'];
            $tag->slug = Str::slug($validated['name']);
            if (isset($validated['color'])) {
                $tag->color = $validated['color'];
            }
            if (isset($validated['bgcolor'])) {
                $tag->bgcolor = $validated['bgcolor'];
            }

            $tag->save();

            return response()->json(['status' => 1, 'message' => 'Tag updated successfully', 'content' => $tag]);
        } catch (\Throwable $e) {
            Log::error('UpdateTagAction failed', ['user_id' => $user->id, 'tag_id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['status' => 0, 'message' => 'Server error'], 500);
        }
    }

    public function deleteTagAction(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        $tag = EvtTag::find($id);

        if (! $tag) {
            return response()->json(['status' => 0, 'message' => 'Tag not found'], 404);
        }

        if ($tag->is_system) {
            return response()->json(['status' => 0, 'message' => 'Cannot delete system tag'], 403);
        }

        if ($tag->user_id !== $user->id) {
            return response()->json(['status' => 0, 'message' => 'You are not the owner of this tag'], 403);
        }

        try {
            $tag->delete();

            return response()->json(['status' => 1, 'message' => 'Tag deleted successfully']);
        } catch (\Throwable $e) {
            Log::error('DeleteTagAction failed', ['user_id' => $user->id, 'tag_id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['status' => 0, 'message' => 'Server error'], 500);
        }
    }


    public function togglePinnedEventAction(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized'], 401);
        }

        $event = EvtEvent::find($id);

        if (! $event) {
            return response()->json(['status' => 0, 'message' => 'Event not found'], 404);
        }

        // ← вот она, проверка владельца
        if ($event->user_id !== $user->id) {
            return response()->json(['status' => 0, 'message' => 'Forbidden'], 403);
        }

        $event->is_pinned = ! $event->is_pinned;
        $event->save();

        return response()->json([
            'status'    => 1,
            'message'   => $event->is_pinned ? 'Pinned' : 'Unpinned',
            'is_pinned' => $event->is_pinned,
        ]);
    }
}
