<?php

namespace App\Http\Controllers\Projector;

use App\Http\Controllers\Controller;
use App\Models\PrjProject;
use App\Models\TskTask;
use Illuminate\Http\Request;

class ProjectorController extends Controller
{
    private const DONE_STATUSES = [22, 24];

    public function index(Request $request)
    {
        $query = PrjProject::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['tasks' => function ($tasks) use ($request) {
                if (! $request->boolean('include_expert')) {
                    $tasks->where('is_expert', false);
                }
                if (! $request->boolean('include_hidden')) {
                    $tasks->where('is_hidden', false);
                }
            }])
            ->when(! $request->boolean('include_expert'), fn ($q) => $q->where('is_expert', false))
            ->when(! $request->boolean('include_hidden'), fn ($q) => $q->where('is_hidden', false));

        if ($request->filled('status_id')) {
            $query->where('status_id', (int) $request->get('status_id'));
        }

        if ($request->boolean('tasker_visible')) {
            $query->where('show_in_tasker', true);
        }

        if ($request->boolean('open_only')) {
            $query->whereNotIn('status_id', self::DONE_STATUSES);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('result', 'like', "%{$q}%");
            });
        }

        $projects = $query
            ->orderByDesc('is_pinned')
            ->orderByRaw('CASE WHEN status_id IN (22, 24) THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderBy('priority_id')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->limit(min(max((int) $request->get('limit', 200), 1), 500))
            ->get();

        return response()->json($projects->map(fn (PrjProject $project) => $this->presentProject($project))->values());
    }

    public function show(Request $request, string $id)
    {
        $project = $this->projectForUser($request, $id)->load(['tasks' => function ($tasks) use ($request) {
            if (! $request->boolean('include_expert')) {
                $tasks->where('is_expert', false);
            }
            if (! $request->boolean('include_hidden')) {
                $tasks->where('is_hidden', false);
            }
            $tasks->with('assigneeContact:id,name,nickname,avatar,avatar_url');
        }]);

        return response()->json($this->presentProject($project, true));
    }

    public function store(Request $request)
    {
        $data = $this->validateProject($request);
        $project = PrjProject::create($this->payload($data, $request->user()->id));

        return response()->json($this->presentProject($project), 201);
    }

    public function update(Request $request, string $id)
    {
        $project = $this->projectForUser($request, $id);
        $data = $this->validateProject($request, false);
        $payload = $this->payload(array_merge($project->toArray(), $data), $request->user()->id, false, array_keys($data));

        if (array_key_exists('status_id', $payload)) {
            $payload['closed_at'] = in_array((int) $payload['status_id'], self::DONE_STATUSES, true)
                ? ($project->closed_at ?: now())
                : null;
        }

        $project->update($payload);

        return response()->json($this->presentProject($project->fresh()));
    }

    public function destroy(Request $request, string $id)
    {
        $this->projectForUser($request, $id)->delete();

        return response()->json(['id' => $id]);
    }

    private function validateProject(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:3'],
            'color' => ['nullable', 'string', 'max:24'],
            'description' => ['nullable', 'string'],
            'result' => ['nullable', 'string'],
            'priority_id' => ['nullable', 'integer', 'between:11,19'],
            'status_id' => ['nullable', 'integer', 'between:20,39'],
            'started_on' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_expert' => ['nullable', 'boolean'],
            'is_hidden' => ['nullable', 'boolean'],
            'show_in_tasker' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    private function payload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        $payload = [
            'user_id' => $userId,
            'title' => $data['title'] ?? null,
            'code' => isset($data['code']) ? strtoupper(substr(trim((string) $data['code']), 0, 3)) : null,
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'result' => $data['result'] ?? null,
            'priority_id' => (int) ($data['priority_id'] ?? 13),
            'status_id' => (int) ($data['status_id'] ?? 20),
            'started_on' => $data['started_on'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'closed_at' => $data['closed_at'] ?? null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'is_expert' => (bool) ($data['is_expert'] ?? false),
            'is_hidden' => (bool) ($data['is_hidden'] ?? false),
            'show_in_tasker' => (bool) ($data['show_in_tasker'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'meta' => $data['meta'] ?? null,
        ];

        if (! $creating) {
            $allowed = array_fill_keys($keys, true);
            $allowed['user_id'] = true;
            return array_filter($payload, fn ($value, $key) => isset($allowed[$key]), ARRAY_FILTER_USE_BOTH);
        }

        return $payload;
    }

    private function projectForUser(Request $request, string $id): PrjProject
    {
        return PrjProject::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function presentProject(PrjProject $project, bool $full = false): array
    {
        $payload = [
            'id' => $project->id,
            'title' => $project->title,
            'code' => $project->code,
            'color' => $project->color,
            'description' => $project->description,
            'result' => $project->result,
            'priority_id' => $project->priority_id,
            'status_id' => $project->status_id,
            'started_on' => optional($project->started_on)->format('Y-m-d'),
            'due_at' => optional($project->due_at)->format('Y-m-d'),
            'closed_at' => optional($project->closed_at)->toISOString(),
            'is_pinned' => $project->is_pinned,
            'is_expert' => $project->is_expert,
            'is_hidden' => $project->is_hidden,
            'show_in_tasker' => $project->show_in_tasker,
            'sort_order' => $project->sort_order,
            'meta' => $project->meta,
            'tasks_count' => $project->tasks_count ?? null,
            'created_at' => optional($project->created_at)->toISOString(),
            'updated_at' => optional($project->updated_at)->toISOString(),
        ];

        if ($full) {
            $payload['tasks'] = $project->tasks->map(fn (TskTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'priority_id' => $task->priority_id,
                'status_id' => $task->status_id,
                'due_at' => optional($task->due_at)->format('Y-m-d'),
                'tracked_seconds' => $task->tracked_seconds,
                'is_pinned' => $task->is_pinned,
                'is_expert' => $task->is_expert,
                'is_hidden' => $task->is_hidden,
                'assignee' => $task->assigneeContact ? [
                    'id' => $task->assigneeContact->id,
                    'name' => $task->assigneeContact->name,
                    'nickname' => $task->assigneeContact->nickname,
                    'avatar' => $task->assigneeContact->avatar_url ?: $task->assigneeContact->avatar,
                    'avatar_url' => $task->assigneeContact->avatar_url ?: $task->assigneeContact->avatar,
                ] : null,
            ])->values();
        }

        return $payload;
    }
}