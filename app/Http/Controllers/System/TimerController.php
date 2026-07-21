<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\StfRegister;
use App\Models\SysTimerEntry;
use App\Models\TskLog;
use App\Models\TskSpan;
use App\Models\TskTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimerController extends Controller
{
    private const TASKER_MODULE = 'tasker';
    private const TASKER_IN_PROGRESS = 21;
    private const TASKER_DONE_STATUSES = [22, 24];

    public function active(Request $request)
    {
        $userId = $request->user()->id;
        $span = $this->activeTaskSpan($userId);
        if ($span) {
            return response()->json($this->presentTaskSpanTimer($span));
        }

        $entry = $this->activeEntry($userId);

        return response()->json($entry ? $this->presentTimer($entry) : null);
    }

    public function entries(Request $request)
    {
        if ($request->get('source_module') === self::TASKER_MODULE) {
            $query = TskSpan::query()
                ->where('user_id', $request->user()->id)
                ->where('kind', 'fact')
                ->with('task:id,title,status_id,priority_id,tracked_seconds,due_at')
                ->orderByDesc('started_at')
                ->orderByDesc('created_at');

            if ($request->filled('source_id')) {
                $query->where('task_id', $request->get('source_id'));
            }

            if ($request->boolean('completed_only')) {
                $query->whereNotNull('ended_at');
            }

            $spans = $query->limit(min(max((int) $request->get('limit', 200), 1), 500))->get();

            return response()->json($spans->map(fn (TskSpan $span) => $this->presentTaskSpanTimer($span))->values());
        }

        $query = SysTimerEntry::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('started_at')
            ->orderByDesc('created_at');

        if ($request->filled('source_module')) {
            $query->where('source_module', $request->get('source_module'));
        }

        if ($request->filled('source_id')) {
            $query->where('source_id', $request->get('source_id'));
        }

        if ($request->boolean('completed_only')) {
            $query->whereNotNull('ended_at');
        }

        $entries = $query->limit(min(max((int) $request->get('limit', 200), 1), 500))->get();

        return response()->json($entries->map(fn (SysTimerEntry $entry) => $this->presentTimer($entry))->values());
    }

    public function storeEntry(Request $request)
    {
        $data = $this->validateEntry($request);
        $userId = $request->user()->id;
        $this->assertSource($userId, $data['source_module'], $data['source_id']);

        if ($data['source_module'] === self::TASKER_MODULE) {
            $result = DB::transaction(function () use ($data, $userId) {
                $startedAt = Carbon::parse($data['started_at']);
                $endedAt = Carbon::parse($data['ended_at']);

                $span = TskSpan::create([
                    'user_id' => $userId,
                    'task_id' => $data['source_id'],
                    'kind' => 'fact',
                    'title' => $data['note'] ?? null,
                    'content' => $data['content'] ?? null,
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                    'sort_order' => $startedAt->timestamp,
                ]);

                $task = $this->recalculateTaskTrackedSeconds($userId, $data['source_id']);

                return [$span->fresh(), $task?->fresh()];
            });

            [$span, $task] = $result;

            return response()->json([
                'timer' => $this->presentTaskSpanTimer($span),
                'task' => $task ? $this->presentTaskLite($task) : null,
                'log' => null,
            ], 201);
        }

        $result = DB::transaction(function () use ($data, $userId) {
            $startedAt = Carbon::parse($data['started_at']);
            $endedAt = Carbon::parse($data['ended_at']);
            $seconds = $this->secondsBetween($startedAt, $endedAt);

            $entry = SysTimerEntry::create([
                'user_id' => $userId,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_min' => (int) ceil($seconds / 60),
                'entry_type' => $data['entry_type'] ?? 'manual',
                'time_type' => $data['time_type'] ?? 'self',
                'source_module' => $data['source_module'],
                'source_id' => $data['source_id'],
                'sort_order' => $startedAt->timestamp,
                'note' => $data['note'] ?? null,
            ]);

            return [$entry->fresh(), null, null];
        });

        [$entry, $task, $log] = $result;

        return response()->json([
            'timer' => $this->presentTimer($entry),
            'task' => $task ? $this->presentTaskLite($task) : null,
            'log' => $log ? $this->presentLogLite($log) : null,
        ], 201);
    }

    public function updateEntry(Request $request, string $id)
    {
        $userId = $request->user()->id;
        $span = TskSpan::query()->where('user_id', $userId)->find($id);
        if ($span) {
            if (! $span->ended_at) {
                return response()->json(['message' => 'Active timers must be stopped before manual editing.'], 422);
            }

            $data = $this->validateEntry($request, false);
            $oldTaskId = $span->task_id;
            $taskId = $data['source_id'] ?? $span->task_id;
            $this->assertSource($userId, self::TASKER_MODULE, $taskId);

            DB::transaction(function () use ($span, $data, $taskId, $oldTaskId, $userId) {
                $startedAt = Carbon::parse($data['started_at'] ?? $span->started_at);
                $endedAt = Carbon::parse($data['ended_at'] ?? $span->ended_at);

                $span->update([
                    'task_id' => $taskId,
                    'title' => array_key_exists('note', $data) ? $data['note'] : $span->title,
                    'content' => array_key_exists('content', $data) ? $data['content'] : $span->content,
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                    'sort_order' => $startedAt->timestamp,
                ]);

                $this->recalculateTaskTrackedSeconds($userId, $taskId);
                if ($oldTaskId !== $taskId) {
                    $this->recalculateTaskTrackedSeconds($userId, $oldTaskId);
                }
            });

            $fresh = $span->fresh()->load('task:id,title,status_id,priority_id,tracked_seconds,due_at');

            return response()->json([
                'timer' => $this->presentTaskSpanTimer($fresh),
                'task' => $fresh->task ? $this->presentTaskLite($fresh->task) : null,
                'log' => null,
            ]);
        }

        $entry = SysTimerEntry::query()->where('user_id', $userId)->findOrFail($id);
        if (! $entry->ended_at) {
            return response()->json(['message' => 'Active timers must be stopped before manual editing.'], 422);
        }

        $data = $this->validateEntry($request, false);
        $module = $data['source_module'] ?? $entry->source_module;
        $sourceId = $data['source_id'] ?? $entry->source_id;
        $this->assertSource($userId, $module, $sourceId);

        $entry = DB::transaction(function () use ($entry, $data, $module, $sourceId) {
            $startedAt = Carbon::parse($data['started_at'] ?? $entry->started_at);
            $endedAt = Carbon::parse($data['ended_at'] ?? $entry->ended_at);
            $seconds = $this->secondsBetween($startedAt, $endedAt);

            $payload = [
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_min' => (int) ceil($seconds / 60),
                'source_module' => $module,
                'source_id' => $sourceId,
                'sort_order' => $startedAt->timestamp,
            ];

            foreach (['entry_type', 'time_type', 'note'] as $key) {
                if (array_key_exists($key, $data)) {
                    $payload[$key] = $data[$key];
                }
            }

            $entry->update($payload);

            return $entry->fresh();
        });

        return response()->json([
            'timer' => $this->presentTimer($entry),
            'task' => null,
            'log' => null,
        ]);
    }

    public function destroyEntry(Request $request, string $id)
    {
        $userId = $request->user()->id;
        $span = TskSpan::query()->where('user_id', $userId)->find($id);
        if ($span) {
            $taskId = $span->task_id;
            DB::transaction(function () use ($span, $taskId, $userId) {
                $span->delete();
                $this->recalculateTaskTrackedSeconds($userId, $taskId);
            });

            return response()->json(['id' => $id]);
        }

        $entry = SysTimerEntry::query()->where('user_id', $userId)->findOrFail($id);

        DB::transaction(function () use ($entry) {
            TskLog::query()
                ->where('timer_entry_id', $entry->id)
                ->update(['timer_entry_id' => null]);

            $entry->delete();
        });

        return response()->json(['id' => $id]);
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'source_module' => ['required', 'in:tasker,exploiter'],
            'source_id' => ['required', 'string', 'size:26'],
            'time_type' => ['nullable', 'in:self,service'],
            'note' => ['nullable', 'string'],
            'started_at' => ['nullable', 'date'],
            'auto_stop_at' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;
        $this->assertSource($userId, $data['source_module'], $data['source_id']);

        if ($data['source_module'] === self::TASKER_MODULE) {
            $span = DB::transaction(function () use ($data, $userId) {
                $this->closeOpenTimers($userId);
                $startedAt = Carbon::parse($data['started_at'] ?? now());

                $span = TskSpan::create([
                    'user_id' => $userId,
                    'task_id' => $data['source_id'],
                    'kind' => 'fact',
                    'title' => $data['note'] ?? null,
                    'started_at' => $startedAt,
                    'ended_at' => null,
                    'auto_stop_at' => isset($data['auto_stop_at']) ? Carbon::parse($data['auto_stop_at']) : null,
                    'sort_order' => $startedAt->timestamp,
                ]);

                TskTask::query()
                    ->where('user_id', $userId)
                    ->where('id', $data['source_id'])
                    ->whereNotIn('status_id', self::TASKER_DONE_STATUSES)
                    ->update(['status_id' => self::TASKER_IN_PROGRESS]);

                return $span;
            });

            return response()->json($this->presentTaskSpanTimer($span->fresh()), 201);
        }

        $entry = DB::transaction(function () use ($data, $userId) {
            $this->closeOpenTimers($userId);
            $startedAt = Carbon::parse($data['started_at'] ?? now());

            return SysTimerEntry::create([
                'user_id' => $userId,
                'started_at' => $startedAt,
                'ended_at' => null,
                'duration_min' => 0,
                'entry_type' => 'timer',
                'time_type' => $data['time_type'] ?? 'self',
                'source_module' => $data['source_module'],
                'source_id' => $data['source_id'],
                'sort_order' => $startedAt->timestamp,
                'note' => $data['note'] ?? null,
            ]);
        });

        return response()->json($this->presentTimer($entry->fresh()), 201);
    }

    public function stop(Request $request)
    {
        $data = $request->validate([
            'timer_entry_id' => ['nullable', 'string', 'size:26'],
            'content' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', 'between:20,39'],
            'ended_at' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;
        $span = $this->taskSpanForStop($userId, $data['timer_entry_id'] ?? null);
        if ($span) {
            $result = DB::transaction(function () use ($span, $data, $userId) {
                $endedAt = Carbon::parse($data['ended_at'] ?? now());

                $span->update([
                    'ended_at' => $endedAt,
                    'title' => array_key_exists('note', $data) ? $data['note'] : $span->title,
                    'content' => array_key_exists('content', $data) ? $data['content'] : $span->content,
                ]);

                $task = TskTask::query()->where('user_id', $userId)->where('id', $span->task_id)->first();
                if ($task) {
                    $payload = ['tracked_seconds' => $this->taskTrackedSeconds($userId, $task->id)];
                    if (isset($data['status_id'])) {
                        $payload['status_id'] = (int) $data['status_id'];
                        $payload['closed_at'] = in_array((int) $data['status_id'], self::TASKER_DONE_STATUSES, true) ? now() : null;
                    }
                    $task->update($payload);
                }

                return [$span->fresh()->load('task:id,title,status_id,priority_id,tracked_seconds,due_at'), $task?->fresh()];
            });

            [$span, $task] = $result;

            return response()->json([
                'timer' => $this->presentTaskSpanTimer($span),
                'task' => $task ? $this->presentTaskLite($task) : null,
                'log' => null,
            ]);
        }

        $entry = $this->timerForStop($userId, $data['timer_entry_id'] ?? null);
        if (! $entry) {
            return response()->json(['message' => 'No active timer found.'], 404);
        }

        $entry = DB::transaction(function () use ($entry, $data) {
            $endedAt = Carbon::parse($data['ended_at'] ?? now());
            $seconds = $this->secondsBetween($entry->started_at, $endedAt);

            $entry->update([
                'ended_at' => $endedAt,
                'duration_min' => (int) ceil($seconds / 60),
                'note' => array_key_exists('note', $data) ? $data['note'] : $entry->note,
            ]);

            return $entry->fresh();
        });

        return response()->json([
            'timer' => $this->presentTimer($entry),
            'task' => null,
            'log' => null,
        ]);
    }

    public function report(Request $request)
    {
        $data = $request->validate([
            'timer_entry_id' => ['nullable', 'string', 'size:26'],
            'content' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $userId = $request->user()->id;
        $span = $data['timer_entry_id'] ?? null
            ? TskSpan::query()->where('user_id', $userId)->find($data['timer_entry_id'])
            : $this->activeTaskSpan($userId);

        if ($span) {
            $span->update([
                'content' => $data['content'],
                'title' => array_key_exists('note', $data) ? $data['note'] : $span->title,
            ]);

            return response()->json([
                'timer' => $this->presentTaskSpanTimer($span->fresh()),
                'task' => $span->task ? $this->presentTaskLite($span->task) : null,
                'log' => null,
            ], 201);
        }

        $entry = $data['timer_entry_id'] ?? null
            ? SysTimerEntry::query()->where('user_id', $userId)->findOrFail($data['timer_entry_id'])
            : $this->activeEntry($userId);

        if (! $entry) {
            return response()->json(['message' => 'No timer found.'], 404);
        }

        return response()->json(['message' => 'Timer reports are currently supported for Tasker timers only.'], 422);
    }

    private function validateEntry(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'source_module' => [$creating ? 'required' : 'sometimes', 'in:tasker,exploiter'],
            'source_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'started_at' => [$creating ? 'required' : 'sometimes', 'date'],
            'ended_at' => [$creating ? 'required' : 'sometimes', 'date'],
            'entry_type' => ['nullable', 'in:manual,timer,interval'],
            'time_type' => ['nullable', 'in:self,service'],
            'note' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
        ]);
    }

    private function activeEntry(string $userId): ?SysTimerEntry
    {
        return SysTimerEntry::query()
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();
    }

    private function activeTaskSpan(string $userId): ?TskSpan
    {
        return TskSpan::query()
            ->where('user_id', $userId)
            ->where('kind', 'fact')
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->with('task:id,title,status_id,priority_id,tracked_seconds,due_at')
            ->orderByDesc('started_at')
            ->first();
    }

    private function timerForStop(string $userId, ?string $id): ?SysTimerEntry
    {
        $query = SysTimerEntry::query()->where('user_id', $userId)->whereNull('ended_at');

        if ($id) {
            $query->where('id', $id);
        }

        return $query->orderByDesc('started_at')->first();
    }

    private function taskSpanForStop(string $userId, ?string $id): ?TskSpan
    {
        $query = TskSpan::query()
            ->where('user_id', $userId)
            ->where('kind', 'fact')
            ->whereNull('ended_at')
            ->whereNotNull('started_at');

        if ($id) {
            $query->where('id', $id);
        }

        return $query->orderByDesc('started_at')->first();
    }

    private function closeOpenTimers(string $userId): void
    {
        TskSpan::query()
            ->where('user_id', $userId)
            ->where('kind', 'fact')
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->get()
            ->each(function (TskSpan $span) use ($userId) {
                $span->update(['ended_at' => now()]);
                $this->recalculateTaskTrackedSeconds($userId, $span->task_id);
            });

        SysTimerEntry::query()
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->get()
            ->each(function (SysTimerEntry $entry) {
                $endedAt = now();
                $seconds = $this->secondsBetween($entry->started_at, $endedAt);
                $entry->update([
                    'ended_at' => $endedAt,
                    'duration_min' => (int) ceil($seconds / 60),
                ]);
            });
    }

    private function assertSource(string $userId, string $module, string $sourceId): void
    {
        if ($module === self::TASKER_MODULE) {
            if (! TskTask::query()->where('user_id', $userId)->where('id', $sourceId)->exists()) {
                abort(422, 'Task must belong to current user.');
            }
            return;
        }

        if ($module === 'exploiter') {
            if (! StfRegister::query()->where('user_id', $userId)->where('id', $sourceId)->exists()) {
                abort(422, 'Exploiter event must belong to current user.');
            }
            return;
        }

        abort(422, 'Unsupported timer source module.');
    }

    private function recalculateTaskTrackedSeconds(string $userId, string $taskId): ?TskTask
    {
        $task = TskTask::query()->where('user_id', $userId)->where('id', $taskId)->first();
        if (! $task) {
            return null;
        }

        $task->update(['tracked_seconds' => $this->taskTrackedSeconds($userId, $taskId)]);

        return $task;
    }

    private function taskTrackedSeconds(string $userId, string $taskId): int
    {
        return (int) TskSpan::query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('kind', 'fact')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->sum(DB::raw('TIMESTAMPDIFF(SECOND, started_at, ended_at)'));
    }

    private function secondsBetween($startedAt, $endedAt): int
    {
        if (! $startedAt || ! $endedAt) {
            return 0;
        }

        return max(0, Carbon::parse($startedAt)->diffInSeconds(Carbon::parse($endedAt)));
    }

    private function presentTaskSpanTimer(TskSpan $span): array
    {
        $task = $span->relationLoaded('task') ? $span->task : TskTask::query()->where('user_id', $span->user_id)->where('id', $span->task_id)->first();
        $elapsedSeconds = $span->ended_at
            ? $this->secondsBetween($span->started_at, $span->ended_at)
            : $this->secondsBetween($span->started_at, now());

        return [
            'id' => $span->id,
            'user_id' => $span->user_id,
            'started_at' => optional($span->started_at)->toISOString(),
            'ended_at' => optional($span->ended_at)->toISOString(),
            'duration_min' => (int) ceil($elapsedSeconds / 60),
            'elapsed_seconds' => $elapsedSeconds,
            'entry_type' => $span->ended_at ? 'manual' : 'timer',
            'time_type' => 'self',
            'source_module' => self::TASKER_MODULE,
            'source_id' => $span->task_id,
            'task_id' => $span->task_id,
            'source' => $task ? $this->presentTaskLite($task) : null,
            'note' => $span->title,
            'content' => $span->content,
            'log_id' => null,
            'span_id' => $span->id,
            'kind' => $span->kind,
            'planned_start_at' => optional($span->planned_start_at)->toISOString(),
            'planned_end_at' => optional($span->planned_end_at)->toISOString(),
            'auto_stop_at' => optional($span->auto_stop_at)->toISOString(),
            'auto_stopped_at' => optional($span->auto_stopped_at)->toISOString(),
            'auto_stop_reason' => $span->auto_stop_reason,
        ];
    }

    private function presentTimer(SysTimerEntry $entry): array
    {
        $source = null;
        if ($entry->source_module === 'exploiter') {
            $event = StfRegister::query()->where('user_id', $entry->user_id)->where('id', $entry->source_id)->first();
            $source = $event ? [
                'id' => $event->id,
                'title' => $event->title ?? $event->name ?? $event->content ?? 'Exploiter event',
                'event_kind' => $event->event_kind ?? null,
                'placed_at' => optional($event->placed_at ?? $event->created_at)->toISOString(),
            ] : null;
        }

        $elapsedSeconds = $entry->ended_at
            ? $this->secondsBetween($entry->started_at, $entry->ended_at)
            : $this->secondsBetween($entry->started_at, now());

        return [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'started_at' => optional($entry->started_at)->toISOString(),
            'ended_at' => optional($entry->ended_at)->toISOString(),
            'duration_min' => $entry->duration_min,
            'elapsed_seconds' => $elapsedSeconds,
            'entry_type' => $entry->entry_type,
            'time_type' => $entry->time_type,
            'source_module' => $entry->source_module,
            'source_id' => $entry->source_id,
            'source' => $source,
            'note' => $entry->note,
            'content' => null,
            'log_id' => null,
        ];
    }

    private function presentTaskLite(TskTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status_id' => $task->status_id,
            'priority_id' => $task->priority_id,
            'tracked_seconds' => $task->tracked_seconds,
            'due_at' => optional($task->due_at)->format('Y-m-d'),
        ];
    }

    private function presentLogLite(TskLog $log): array
    {
        return [
            'id' => $log->id,
            'task_id' => $log->task_id,
            'kind' => $log->kind,
            'content' => $log->content,
            'timer_entry_id' => $log->timer_entry_id,
            'occurred_at' => optional($log->occurred_at)->toISOString(),
            'meta' => $log->meta,
        ];
    }
}