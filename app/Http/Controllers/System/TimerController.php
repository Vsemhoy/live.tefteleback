<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\StfRegister;
use App\Models\SysTimerEntry;
use App\Models\TskLog;
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
        $entry = $this->activeEntry($request->user()->id);

        return response()->json($entry ? $this->presentTimer($entry) : null);
    }

    public function entries(Request $request)
    {
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

            $task = $this->syncTaskAfterEntryChange($entry);
            $log = $this->syncEntryReport($entry, $data['content'] ?? null, $endedAt, $seconds);

            return [$entry->fresh(), $task?->fresh(), $log?->fresh()];
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
        $entry = SysTimerEntry::query()->where('user_id', $request->user()->id)->findOrFail($id);
        if (! $entry->ended_at) {
            return response()->json(['message' => 'Active timers must be stopped before manual editing.'], 422);
        }

        $data = $this->validateEntry($request, false);
        $userId = $request->user()->id;
        $oldTaskId = $entry->source_module === self::TASKER_MODULE ? $entry->source_id : null;
        $module = $data['source_module'] ?? $entry->source_module;
        $sourceId = $data['source_id'] ?? $entry->source_id;
        $this->assertSource($userId, $module, $sourceId);

        $result = DB::transaction(function () use ($entry, $data, $module, $sourceId, $oldTaskId) {
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
            $task = $this->syncTaskAfterEntryChange($entry->fresh(), $oldTaskId);
            $log = array_key_exists('content', $data)
                ? $this->syncEntryReport($entry->fresh(), $data['content'], $endedAt, $seconds)
                : null;

            return [$entry->fresh(), $task?->fresh(), $log?->fresh()];
        });

        [$entry, $task, $log] = $result;

        return response()->json([
            'timer' => $this->presentTimer($entry),
            'task' => $task ? $this->presentTaskLite($task) : null,
            'log' => $log ? $this->presentLogLite($log) : null,
        ]);
    }

    public function destroyEntry(Request $request, string $id)
    {
        $entry = SysTimerEntry::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $oldTaskId = $entry->source_module === self::TASKER_MODULE ? $entry->source_id : null;

        DB::transaction(function () use ($entry, $oldTaskId) {
            TskLog::query()
                ->where('timer_entry_id', $entry->id)
                ->update(['timer_entry_id' => null]);

            $entry->delete();
            if ($oldTaskId) {
                $this->recalculateTaskTrackedSeconds($entry->user_id, $oldTaskId);
            }
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
        ]);

        $userId = $request->user()->id;
        $this->assertSource($userId, $data['source_module'], $data['source_id']);

        $entry = DB::transaction(function () use ($data, $userId) {
            $this->closeOpenTimers($userId);

            $entry = SysTimerEntry::create([
                'user_id' => $userId,
                'started_at' => Carbon::parse($data['started_at'] ?? now()),
                'ended_at' => null,
                'duration_min' => 0,
                'entry_type' => 'timer',
                'time_type' => $data['time_type'] ?? 'self',
                'source_module' => $data['source_module'],
                'source_id' => $data['source_id'],
                'sort_order' => now()->timestamp,
                'note' => $data['note'] ?? null,
            ]);

            if ($data['source_module'] === self::TASKER_MODULE) {
                TskTask::query()
                    ->where('user_id', $userId)
                    ->where('id', $data['source_id'])
                    ->whereNotIn('status_id', self::TASKER_DONE_STATUSES)
                    ->update(['status_id' => self::TASKER_IN_PROGRESS]);
            }

            return $entry;
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
        $entry = $this->timerForStop($userId, $data['timer_entry_id'] ?? null);
        if (! $entry) {
            return response()->json(['message' => 'No active timer found.'], 404);
        }

        $result = DB::transaction(function () use ($entry, $data, $userId) {
            $endedAt = Carbon::parse($data['ended_at'] ?? now());
            $seconds = $this->secondsBetween($entry->started_at, $endedAt);

            $entry->update([
                'ended_at' => $endedAt,
                'duration_min' => (int) ceil($seconds / 60),
                'note' => array_key_exists('note', $data) ? $data['note'] : $entry->note,
            ]);

            $task = null;
            $log = null;

            if ($entry->source_module === self::TASKER_MODULE) {
                $task = TskTask::query()->where('user_id', $userId)->where('id', $entry->source_id)->first();
                if ($task) {
                    $payload = ['tracked_seconds' => $this->taskTrackedSeconds($userId, $task->id)];
                    if (isset($data['status_id'])) {
                        $payload['status_id'] = (int) $data['status_id'];
                        $payload['closed_at'] = in_array((int) $data['status_id'], self::TASKER_DONE_STATUSES, true) ? now() : null;
                    }
                    $task->update($payload);
                    $log = $this->syncEntryReport($entry->fresh(), $data['content'] ?? null, $endedAt, $seconds);
                }
            }

            return [$entry->fresh(), $task?->fresh(), $log?->fresh()];
        });

        [$entry, $task, $log] = $result;

        return response()->json([
            'timer' => $this->presentTimer($entry),
            'task' => $task ? $this->presentTaskLite($task) : null,
            'log' => $log ? $this->presentLogLite($log) : null,
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
        $entry = $data['timer_entry_id'] ?? null
            ? SysTimerEntry::query()->where('user_id', $userId)->findOrFail($data['timer_entry_id'])
            : $this->activeEntry($userId);

        if (! $entry) {
            return response()->json(['message' => 'No timer found.'], 404);
        }

        if ($entry->source_module !== self::TASKER_MODULE) {
            return response()->json(['message' => 'Timer reports are currently supported for Tasker timers only.'], 422);
        }

        $task = TskTask::query()->where('user_id', $userId)->where('id', $entry->source_id)->firstOrFail();

        $log = DB::transaction(function () use ($entry, $task, $data, $userId) {
            if (array_key_exists('note', $data)) {
                $entry->update(['note' => $data['note']]);
            }

            return TskLog::create([
                'user_id' => $userId,
                'task_id' => $task->id,
                'kind' => 'report',
                'content' => $data['content'],
                'timer_entry_id' => $entry->id,
                'occurred_at' => now(),
            ]);
        });

        return response()->json([
            'timer' => $this->presentTimer($entry->fresh()),
            'task' => $this->presentTaskLite($task->fresh()),
            'log' => $this->presentLogLite($log),
        ], 201);
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

    private function timerForStop(string $userId, ?string $id): ?SysTimerEntry
    {
        $query = SysTimerEntry::query()->where('user_id', $userId)->whereNull('ended_at');

        if ($id) {
            $query->where('id', $id);
        }

        return $query->orderByDesc('started_at')->first();
    }

    private function closeOpenTimers(string $userId): void
    {
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

                if ($entry->source_module === self::TASKER_MODULE) {
                    $this->recalculateTaskTrackedSeconds($entry->user_id, $entry->source_id);
                }
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

    private function syncTaskAfterEntryChange(SysTimerEntry $entry, ?string $oldTaskId = null): ?TskTask
    {
        if ($oldTaskId && ($entry->source_module !== self::TASKER_MODULE || $entry->source_id !== $oldTaskId)) {
            $this->recalculateTaskTrackedSeconds($entry->user_id, $oldTaskId);
        }

        if ($entry->source_module !== self::TASKER_MODULE) {
            return null;
        }

        return $this->recalculateTaskTrackedSeconds($entry->user_id, $entry->source_id);
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
        return (int) SysTimerEntry::query()
            ->where('user_id', $userId)
            ->where('source_module', self::TASKER_MODULE)
            ->where('source_id', $taskId)
            ->whereNotNull('ended_at')
            ->sum(DB::raw('duration_min * 60'));
    }

    private function syncEntryReport(SysTimerEntry $entry, ?string $content, Carbon $occurredAt, int $seconds): ?TskLog
    {
        if ($entry->source_module !== self::TASKER_MODULE || trim((string) $content) === '') {
            return null;
        }

        $task = TskTask::query()->where('user_id', $entry->user_id)->where('id', $entry->source_id)->first();
        if (! $task) {
            return null;
        }

        $log = TskLog::query()
            ->where('user_id', $entry->user_id)
            ->where('timer_entry_id', $entry->id)
            ->where('kind', 'report')
            ->first() ?: new TskLog();

        $log->fill([
            'user_id' => $entry->user_id,
            'task_id' => $task->id,
            'kind' => 'report',
            'content' => $content,
            'timer_entry_id' => $entry->id,
            'occurred_at' => $occurredAt,
            'meta' => ['duration_seconds' => $seconds],
        ]);
        $log->save();

        return $log;
    }

    private function secondsBetween($startedAt, $endedAt): int
    {
        if (! $startedAt || ! $endedAt) {
            return 0;
        }

        return max(0, Carbon::parse($startedAt)->diffInSeconds(Carbon::parse($endedAt)));
    }

    private function presentTimer(SysTimerEntry $entry): array
    {
        $source = null;
        if ($entry->source_module === self::TASKER_MODULE) {
            $task = TskTask::query()->where('user_id', $entry->user_id)->where('id', $entry->source_id)->first();
            $source = $task ? $this->presentTaskLite($task) : null;
        } elseif ($entry->source_module === 'exploiter') {
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

        $report = $entry->source_module === self::TASKER_MODULE
            ? TskLog::query()
                ->where('user_id', $entry->user_id)
                ->where('timer_entry_id', $entry->id)
                ->where('kind', 'report')
                ->latest('occurred_at')
                ->first()
            : null;

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
            'content' => $report?->content,
            'log_id' => $report?->id,
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