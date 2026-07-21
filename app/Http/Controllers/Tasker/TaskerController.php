<?php

namespace App\Http\Controllers\Tasker;

use App\Http\Controllers\Controller;
use App\Models\CtrContact;
use App\Models\PrjProject;
use App\Models\TskBlocker;
use App\Models\TskLog;
use App\Models\TskSpan;
use App\Models\TskTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskerController extends Controller
{
    private const DONE_STATUSES = [22, 24];

    public function index(Request $request)
    {
        $query = TskTask::query()
            ->where('user_id', $request->user()->id)
            ->with(['assigneeContact:id,name,nickname,avatar,avatar_url', 'parent:id,title,status_id', 'project:id,title,code,color,status_id,is_hidden,show_in_tasker'])
            ->withCount(['children', 'logs', 'spans'])
            ->when(! $request->boolean('include_expert'), fn ($q) => $q->where('is_expert', false))
            ->when(! $request->boolean('include_hidden'), fn ($q) => $q->where('is_hidden', false));

        if ($request->filled('status_id')) {
            $query->where('status_id', (int) $request->get('status_id'));
        }

        if ($request->filled('assignee_contact_id')) {
            if ($request->get('assignee_contact_id') === 'me') {
                $query->whereNull('assignee_contact_id');
            } else {
                $query->where('assignee_contact_id', $request->get('assignee_contact_id'));
            }
        }

        if ($request->filled('parent_task_id')) {
            $query->where('parent_task_id', $request->get('parent_task_id'));
        } elseif (! $request->boolean('include_children')) {
            $query->whereNull('parent_task_id');
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->get('project_id'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('result', 'like', "%{$q}%");
            });
        }

        if ($request->boolean('open_only')) {
            $query->whereNotIn('status_id', self::DONE_STATUSES);
        }

        $tasks = $query
            ->orderByDesc('is_pinned')
            ->orderByRaw('CASE WHEN status_id IN (22, 24) THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderBy('priority_id')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->limit(min(max((int) $request->get('limit', 200), 1), 500))
            ->get();

        return response()->json($tasks->map(fn (TskTask $task) => $this->presentTask($task))->values());
    }

    public function show(Request $request, string $id)
    {
        $task = $this->taskForUser($request, $id)->load([
            'assigneeContact:id,name,nickname,avatar,avatar_url',
            'parent:id,title,status_id', 'project:id,title,code,color,status_id,is_hidden,show_in_tasker',
            'children.assigneeContact:id,name,nickname,avatar,avatar_url',
            'logs.blocker:id,title,description,occurrence_count',
            'logs.timerEntry:id,started_at,ended_at,duration_min,note',
            'timerEntries:id,started_at,ended_at,duration_min,entry_type,time_type,note',
            'spans',
        ]);

        return response()->json($this->presentTask($task, true));
    }

    public function store(Request $request)
    {
        $data = $this->validateTask($request);
        $userId = $request->user()->id;
        $this->assertOptionalContact($userId, $data['assignee_contact_id'] ?? null);
        $this->assertOptionalParent($userId, $data['parent_task_id'] ?? null);
        $this->assertOptionalProject($userId, $data['project_id'] ?? null);

        $task = TskTask::create($this->taskPayload($data, $userId));

        return response()->json($this->presentTask($task->load('assigneeContact:id,name,nickname,avatar,avatar_url')), 201);
    }

    public function update(Request $request, string $id)
    {
        $task = $this->taskForUser($request, $id);
        $data = $this->validateTask($request, false);
        $userId = $request->user()->id;
        $this->assertOptionalContact($userId, $data['assignee_contact_id'] ?? null);
        $this->assertOptionalParent($userId, $data['parent_task_id'] ?? null, $task->id);
        $this->assertOptionalProject($userId, $data['project_id'] ?? null);

        DB::transaction(function () use ($task, $data, $userId) {
            $oldStatus = $task->status_id;
            $payload = $this->taskPayload(array_merge($task->toArray(), $data), $userId, false, array_keys($data));

            if (array_key_exists('status_id', $payload)) {
                $payload['closed_at'] = in_array((int) $payload['status_id'], self::DONE_STATUSES, true)
                    ? ($task->closed_at ?: now())
                    : null;
            }

            $task->update($payload);

            if (isset($data['status_id']) && (int) $data['status_id'] !== (int) $oldStatus) {
                TskLog::create([
                    'user_id' => $userId,
                    'task_id' => $task->id,
                    'kind' => 'status_change',
                    'content' => (string) $oldStatus . ' -> ' . (string) $data['status_id'],
                    'occurred_at' => now(),
                    'meta' => ['from' => (int) $oldStatus, 'to' => (int) $data['status_id']],
                ]);
            }
        });

        return response()->json($this->presentTask($task->fresh()->load('assigneeContact:id,name,nickname,avatar,avatar_url')));
    }

    public function destroy(Request $request, string $id)
    {
        $this->taskForUser($request, $id)->delete();

        return response()->json(['id' => $id]);
    }

    public function spans(Request $request)
    {
        $query = TskSpan::query()
            ->where('user_id', $request->user()->id)
            ->with('task:id,title,status_id,priority_id')
            ->orderByDesc('started_at')
            ->orderByDesc('planned_start_at')
            ->orderByDesc('created_at');

        if ($request->filled('task_id')) {
            $query->where('task_id', $request->get('task_id'));
        }

        if ($request->filled('kind') && $request->get('kind') !== 'all') {
            $query->where('kind', $request->get('kind'));
        }

        if ($request->filled('from_date')) {
            $query->where(function ($q) use ($request) {
                $from = $request->get('from_date');
                $q->whereDate('started_at', '>=', $from)
                  ->orWhereDate('planned_start_at', '>=', $from);
            });
        }

        if ($request->filled('to_date')) {
            $query->where(function ($q) use ($request) {
                $to = $request->get('to_date');
                $q->whereDate('started_at', '<=', $to)
                  ->orWhereDate('planned_start_at', '<=', $to);
            });
        }

        if ($request->boolean('live_only')) {
            $query->where('kind', 'fact')->whereNull('ended_at')->whereNotNull('started_at');
        }

        $spans = $query->limit(min(max((int) $request->get('limit', 200), 1), 500))->get();

        return response()->json($spans->map(fn (TskSpan $span) => $this->presentSpan($span))->values());
    }

    public function storeSpan(Request $request)
    {
        $data = $this->validateSpan($request);
        $task = $this->taskForUser($request, $data['task_id']);

        $span = DB::transaction(function () use ($data, $task) {
            $span = TskSpan::create($this->spanPayload($data, $task->user_id));
            $this->recalculateTaskTrackedSeconds($task->user_id, $task->id);

            return $span;
        });

        return response()->json($this->presentSpan($span->load('task:id,title,status_id,priority_id')), 201);
    }

    public function updateSpan(Request $request, string $id)
    {
        $span = TskSpan::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $data = $this->validateSpan($request, false);
        $oldTaskId = $span->task_id;

        if (isset($data['task_id'])) {
            $this->taskForUser($request, $data['task_id']);
        }

        DB::transaction(function () use ($span, $data, $oldTaskId, $request) {
            $span->update($this->spanPayload(array_merge($span->toArray(), $data), $request->user()->id, false, array_keys($data)));
            $fresh = $span->fresh();
            $this->recalculateTaskTrackedSeconds($fresh->user_id, $fresh->task_id);
            if ($oldTaskId !== $fresh->task_id) {
                $this->recalculateTaskTrackedSeconds($fresh->user_id, $oldTaskId);
            }
        });

        return response()->json($this->presentSpan($span->fresh()->load('task:id,title,status_id,priority_id')));
    }

    public function destroySpan(Request $request, string $id)
    {
        $span = TskSpan::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $taskId = $span->task_id;

        DB::transaction(function () use ($span, $taskId) {
            $span->delete();
            $this->recalculateTaskTrackedSeconds($span->user_id, $taskId);
        });

        return response()->json(['id' => $id]);
    }

    public function closeOverdueSpans(Request $request)
    {
        $userId = $request->user()->id;
        $now = now();
        $closedTaskIds = [];

        DB::transaction(function () use ($userId, $now, &$closedTaskIds) {
            TskSpan::query()
                ->where('user_id', $userId)
                ->where('kind', 'fact')
                ->whereNull('ended_at')
                ->whereNotNull('started_at')
                ->whereNotNull('auto_stop_at')
                ->where('auto_stop_at', '<=', $now)
                ->orderBy('auto_stop_at')
                ->lockForUpdate()
                ->get()
                ->each(function (TskSpan $span) use (&$closedTaskIds) {
                    $span->update([
                        'ended_at' => $span->auto_stop_at,
                        'auto_stopped_at' => now(),
                        'auto_stop_reason' => 'limit_reached',
                    ]);
                    $closedTaskIds[$span->task_id] = true;
                });

            foreach (array_keys($closedTaskIds) as $taskId) {
                $this->recalculateTaskTrackedSeconds($userId, $taskId);
            }
        });

        return response()->json([
            'closed_count' => count($closedTaskIds),
            'task_ids' => array_values(array_keys($closedTaskIds)),
        ]);
    }
    public function logs(Request $request)
    {
        $query = TskLog::query()
            ->where('user_id', $request->user()->id)
            ->with(['task:id,title,status_id', 'blocker:id,title,description,occurrence_count', 'timerEntry:id,started_at,ended_at,duration_min,note'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        if ($request->filled('task_id')) {
            $query->where('task_id', $request->get('task_id'));
        }

        if ($request->filled('kind') && $request->get('kind') !== 'all') {
            $query->where('kind', $request->get('kind'));
        }

        $logs = $query->limit(min(max((int) $request->get('limit', 200), 1), 500))->get();

        return response()->json($logs->map(fn (TskLog $log) => $this->presentLog($log))->values());
    }

    public function storeLog(Request $request)
    {
        $data = $this->validateLog($request);
        $task = $this->taskForUser($request, $data['task_id']);
        $this->assertOptionalBlocker($task->user_id, $data['blocker_id'] ?? null);

        $log = DB::transaction(function () use ($data, $task) {
            $log = TskLog::create($this->logPayload($data, $task->user_id));
            $this->touchBlocker($log);

            return $log;
        });

        return response()->json($this->presentLog($log->load(['task:id,title,status_id', 'blocker:id,title,description,occurrence_count', 'timerEntry:id,started_at,ended_at,duration_min,note'])), 201);
    }

    public function updateLog(Request $request, string $id)
    {
        $log = TskLog::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $data = $this->validateLog($request, false);

        if (isset($data['task_id'])) {
            $this->taskForUser($request, $data['task_id']);
        }
        $this->assertOptionalBlocker($request->user()->id, $data['blocker_id'] ?? null);

        $log->update($this->logPayload(array_merge($log->toArray(), $data), $request->user()->id, false, array_keys($data)));

        return response()->json($this->presentLog($log->fresh()->load(['task:id,title,status_id', 'blocker:id,title,description,occurrence_count', 'timerEntry:id,started_at,ended_at,duration_min,note'])));
    }

    public function destroyLog(Request $request, string $id)
    {
        TskLog::query()->where('user_id', $request->user()->id)->findOrFail($id)->delete();

        return response()->json(['id' => $id]);
    }

    public function blockers(Request $request)
    {
        $blockers = TskBlocker::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('occurrence_count')
            ->orderBy('title')
            ->get();

        return response()->json($blockers->map(fn (TskBlocker $blocker) => $this->presentBlocker($blocker))->values());
    }

    public function storeBlocker(Request $request)
    {
        $data = $this->validateBlocker($request);
        $blocker = TskBlocker::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'occurrence_count' => (int) ($data['occurrence_count'] ?? 0),
        ]));

        return response()->json($this->presentBlocker($blocker), 201);
    }

    public function updateBlocker(Request $request, string $id)
    {
        $blocker = TskBlocker::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $blocker->update($this->validateBlocker($request, false));

        return response()->json($this->presentBlocker($blocker->fresh()));
    }

    public function destroyBlocker(Request $request, string $id)
    {
        TskBlocker::query()->where('user_id', $request->user()->id)->findOrFail($id)->delete();

        return response()->json(['id' => $id]);
    }

    private function validateTask(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'result' => ['nullable', 'string'],
            'assignee_contact_id' => ['nullable', 'string', 'size:26'],
            'priority_id' => ['nullable', 'integer', 'between:11,19'],
            'status_id' => ['nullable', 'integer', 'between:20,39'],
            'due_at' => ['nullable', 'date'],
            'eventor_event_id' => ['nullable', 'string', 'size:26'],
            'parent_task_id' => ['nullable', 'string', 'size:26'],
            'project_id' => ['nullable', 'string', 'size:26'],
            'tracked_seconds' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_expert' => ['nullable', 'boolean'],
            'is_hidden' => ['nullable', 'boolean'],
            'closed_at' => ['nullable', 'date'],
        ]);
    }

    private function validateSpan(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'task_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'kind' => ['nullable', 'in:plan,fact'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'auto_stop_at' => ['nullable', 'date'],
            'auto_stopped_at' => ['nullable', 'date'],
            'auto_stop_reason' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }
    private function validateLog(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'task_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'kind' => ['nullable', 'in:note,status_change,report,blocker,clarification'],
            'content' => ['nullable', 'string'],
            'blocker_id' => ['nullable', 'string', 'size:26'],
            'timer_entry_id' => ['nullable', 'string', 'size:26'],
            'occurred_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    private function validateBlocker(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'occurrence_count' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function taskPayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        $payload = [
            'user_id' => $userId,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'result' => $data['result'] ?? null,
            'assignee_contact_id' => $data['assignee_contact_id'] ?? null,
            'priority_id' => (int) ($data['priority_id'] ?? 13),
            'status_id' => (int) ($data['status_id'] ?? 20),
            'due_at' => $data['due_at'] ?? null,
            'eventor_event_id' => $data['eventor_event_id'] ?? null,
            'parent_task_id' => $data['parent_task_id'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'tracked_seconds' => (int) ($data['tracked_seconds'] ?? 0),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'is_expert' => (bool) ($data['is_expert'] ?? false),
            'is_hidden' => (bool) ($data['is_hidden'] ?? false),
            'closed_at' => $data['closed_at'] ?? null,
        ];

        if (! $creating) {
            $allowed = array_fill_keys($keys, true);
            $allowed['user_id'] = true;
            return array_filter($payload, fn ($value, $key) => isset($allowed[$key]), ARRAY_FILTER_USE_BOTH);
        }

        return $payload;
    }

    private function spanPayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        $payload = [
            'user_id' => $userId,
            'task_id' => $data['task_id'],
            'kind' => $data['kind'] ?? 'fact',
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'planned_start_at' => $this->optionalCarbon($data['planned_start_at'] ?? null),
            'planned_end_at' => $this->optionalCarbon($data['planned_end_at'] ?? null),
            'started_at' => $this->optionalCarbon($data['started_at'] ?? null),
            'ended_at' => $this->optionalCarbon($data['ended_at'] ?? null),
            'auto_stop_at' => $this->optionalCarbon($data['auto_stop_at'] ?? null),
            'auto_stopped_at' => $this->optionalCarbon($data['auto_stopped_at'] ?? null),
            'auto_stop_reason' => $data['auto_stop_reason'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        if (! $creating) {
            $allowed = array_fill_keys($keys, true);
            $allowed['user_id'] = true;
            return array_filter($payload, fn ($value, $key) => isset($allowed[$key]), ARRAY_FILTER_USE_BOTH);
        }

        return $payload;
    }
    private function logPayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        $payload = [
            'user_id' => $userId,
            'task_id' => $data['task_id'],
            'kind' => $data['kind'] ?? 'note',
            'content' => $data['content'] ?? null,
            'blocker_id' => $data['blocker_id'] ?? null,
            'timer_entry_id' => $data['timer_entry_id'] ?? null,
            'occurred_at' => Carbon::parse($data['occurred_at'] ?? now()),
            'meta' => $data['meta'] ?? null,
        ];

        if (! $creating) {
            $allowed = array_fill_keys($keys, true);
            $allowed['user_id'] = true;
            return array_filter($payload, fn ($value, $key) => isset($allowed[$key]), ARRAY_FILTER_USE_BOTH);
        }

        return $payload;
    }

    private function optionalCarbon($value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    private function recalculateTaskTrackedSeconds(string $userId, string $taskId): ?TskTask
    {
        $task = TskTask::query()->where('user_id', $userId)->where('id', $taskId)->first();
        if (! $task) {
            return null;
        }

        $seconds = (int) TskSpan::query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('kind', 'fact')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->sum(DB::raw('TIMESTAMPDIFF(SECOND, started_at, ended_at)'));

        $task->update(['tracked_seconds' => max(0, $seconds)]);

        return $task;
    }
    private function taskForUser(Request $request, string $id): TskTask
    {
        return TskTask::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function assertOptionalContact(string $userId, ?string $contactId): void
    {
        if (! $contactId) {
            return;
        }

        if (! CtrContact::query()->where('user_id', $userId)->where('id', $contactId)->exists()) {
            abort(422, 'Assignee contact must belong to current user.');
        }
    }

    private function assertOptionalParent(string $userId, ?string $parentId, ?string $currentId = null): void
    {
        if (! $parentId) {
            return;
        }

        if ($currentId && $parentId === $currentId) {
            abort(422, 'Task cannot be its own parent.');
        }

        if (! TskTask::query()->where('user_id', $userId)->where('id', $parentId)->exists()) {
            abort(422, 'Parent task must belong to current user.');
        }
    }

    private function assertOptionalProject(string $userId, ?string $projectId): void
    {
        if (! $projectId) {
            return;
        }

        if (! PrjProject::query()->where('user_id', $userId)->where('id', $projectId)->exists()) {
            abort(422, 'Project must belong to current user.');
        }
    }
    private function assertOptionalBlocker(string $userId, ?string $blockerId): void
    {
        if (! $blockerId) {
            return;
        }

        if (! TskBlocker::query()->where('user_id', $userId)->where('id', $blockerId)->exists()) {
            abort(422, 'Blocker must belong to current user.');
        }
    }

    private function touchBlocker(TskLog $log): void
    {
        if ($log->kind === 'blocker' && $log->blocker_id) {
            TskBlocker::query()->where('id', $log->blocker_id)->increment('occurrence_count');
        }
    }

    private function presentTask(TskTask $task, bool $full = false): array
    {
        $payload = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'result' => $task->result,
            'assignee_contact_id' => $task->assignee_contact_id,
            'assignee' => $task->assigneeContact ? [
                'id' => $task->assigneeContact->id,
                'name' => $task->assigneeContact->name,
                'nickname' => $task->assigneeContact->nickname,
                'avatar' => $task->assigneeContact->avatar_url ?: $task->assigneeContact->avatar,
                'avatar_url' => $task->assigneeContact->avatar_url ?: $task->assigneeContact->avatar,
            ] : null,
            'priority_id' => $task->priority_id,
            'status_id' => $task->status_id,
            'due_at' => optional($task->due_at)->format('Y-m-d'),
            'eventor_event_id' => $task->eventor_event_id,
            'parent_task_id' => $task->parent_task_id,
            'project_id' => $task->project_id,
            'project' => $task->project ? [
                'id' => $task->project->id,
                'title' => $task->project->title,
                'code' => $task->project->code,
                'color' => $task->project->color,
                'status_id' => $task->project->status_id,
                'is_hidden' => $task->project->is_hidden,
                'show_in_tasker' => $task->project->show_in_tasker,
            ] : null,
            'parent' => $task->parent ? [
                'id' => $task->parent->id,
                'title' => $task->parent->title,
                'status_id' => $task->parent->status_id,
            ] : null,
            'tracked_seconds' => $task->tracked_seconds,
            'sort_order' => $task->sort_order,
            'is_pinned' => $task->is_pinned,
            'is_expert' => $task->is_expert,
            'is_hidden' => $task->is_hidden,
            'closed_at' => optional($task->closed_at)->toISOString(),
            'created_at' => optional($task->created_at)->toISOString(),
            'updated_at' => optional($task->updated_at)->toISOString(),
            'children_count' => $task->children_count ?? null,
            'logs_count' => $task->logs_count ?? null,
            'spans_count' => $task->spans_count ?? null,
        ];

        if ($full) {
            $payload['children'] = $task->children->map(fn (TskTask $child) => $this->presentTask($child))->values();
            $payload['logs'] = $task->logs->map(fn (TskLog $log) => $this->presentLog($log))->values();
            $payload['timer_entries'] = $task->timerEntries->map(fn ($entry) => [
                'id' => $entry->id,
                'started_at' => optional($entry->started_at)->toISOString(),
                'ended_at' => optional($entry->ended_at)->toISOString(),
                'duration_min' => $entry->duration_min,
                'entry_type' => $entry->entry_type,
                'time_type' => $entry->time_type,
                'note' => $entry->note,
            ])->values();
            $payload['spans'] = $task->spans->map(fn (TskSpan $span) => $this->presentSpan($span))->values();
        }

        return $payload;
    }

    private function presentSpan(TskSpan $span): array
    {
        return [
            'id' => $span->id,
            'user_id' => $span->user_id,
            'task_id' => $span->task_id,
            'task' => $span->task ? [
                'id' => $span->task->id,
                'title' => $span->task->title,
                'status_id' => $span->task->status_id,
                'priority_id' => $span->task->priority_id,
            ] : null,
            'kind' => $span->kind,
            'title' => $span->title,
            'content' => $span->content,
            'planned_start_at' => optional($span->planned_start_at)->toISOString(),
            'planned_end_at' => optional($span->planned_end_at)->toISOString(),
            'started_at' => optional($span->started_at)->toISOString(),
            'ended_at' => optional($span->ended_at)->toISOString(),
            'auto_stop_at' => optional($span->auto_stop_at)->toISOString(),
            'auto_stopped_at' => optional($span->auto_stopped_at)->toISOString(),
            'auto_stop_reason' => $span->auto_stop_reason,
            'sort_order' => $span->sort_order,
            'created_at' => optional($span->created_at)->toISOString(),
            'updated_at' => optional($span->updated_at)->toISOString(),
        ];
    }
    private function presentLog(TskLog $log): array
    {
        return [
            'id' => $log->id,
            'task_id' => $log->task_id,
            'task' => $log->task ? [
                'id' => $log->task->id,
                'title' => $log->task->title,
                'status_id' => $log->task->status_id,
            ] : null,
            'kind' => $log->kind,
            'content' => $log->content,
            'blocker_id' => $log->blocker_id,
            'blocker' => $log->blocker ? $this->presentBlocker($log->blocker) : null,
            'timer_entry_id' => $log->timer_entry_id,
            'timer_entry' => $log->timerEntry ? [
                'id' => $log->timerEntry->id,
                'started_at' => optional($log->timerEntry->started_at)->toISOString(),
                'ended_at' => optional($log->timerEntry->ended_at)->toISOString(),
                'duration_min' => $log->timerEntry->duration_min,
                'note' => $log->timerEntry->note,
            ] : null,
            'occurred_at' => optional($log->occurred_at)->toISOString(),
            'meta' => $log->meta,
            'created_at' => optional($log->created_at)->toISOString(),
            'updated_at' => optional($log->updated_at)->toISOString(),
        ];
    }

    private function presentBlocker(TskBlocker $blocker): array
    {
        return [
            'id' => $blocker->id,
            'title' => $blocker->title,
            'description' => $blocker->description,
            'occurrence_count' => $blocker->occurrence_count,
            'created_at' => optional($blocker->created_at)->toISOString(),
            'updated_at' => optional($blocker->updated_at)->toISOString(),
        ];
    }
}



