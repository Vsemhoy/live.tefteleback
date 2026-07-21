<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tsk_spans') || ! Schema::hasTable('sys_timer_entries')) {
            return;
        }

        DB::table('sys_timer_entries')
            ->where('source_module', 'tasker')
            ->orderBy('started_at')
            ->get()
            ->each(function ($entry) {
                $exists = DB::table('tsk_spans')
                    ->where('user_id', $entry->user_id)
                    ->where('task_id', $entry->source_id)
                    ->where('started_at', $entry->started_at)
                    ->where('ended_at', $entry->ended_at)
                    ->exists();

                if ($exists) {
                    return;
                }

                $report = Schema::hasTable('tsk_logs')
                    ? DB::table('tsk_logs')
                        ->where('timer_entry_id', $entry->id)
                        ->where('kind', 'report')
                        ->orderByDesc('occurred_at')
                        ->first()
                    : null;

                DB::table('tsk_spans')->insert([
                    'id' => (string) Str::ulid(),
                    'user_id' => $entry->user_id,
                    'task_id' => $entry->source_id,
                    'kind' => 'fact',
                    'title' => $entry->note,
                    'content' => $report?->content,
                    'planned_start_at' => null,
                    'planned_end_at' => null,
                    'started_at' => $entry->started_at,
                    'ended_at' => $entry->ended_at,
                    'auto_stop_at' => null,
                    'auto_stopped_at' => null,
                    'auto_stop_reason' => null,
                    'sort_order' => $entry->sort_order ?? 0,
                    'created_at' => $entry->created_at ?? now(),
                    'updated_at' => $entry->updated_at ?? now(),
                    'deleted_at' => $entry->deleted_at ?? null,
                ]);
            });

        DB::table('tsk_tasks')
            ->select('id', 'user_id')
            ->orderBy('id')
            ->get()
            ->each(function ($task) {
                $seconds = (int) DB::table('tsk_spans')
                    ->where('user_id', $task->user_id)
                    ->where('task_id', $task->id)
                    ->where('kind', 'fact')
                    ->whereNotNull('started_at')
                    ->whereNotNull('ended_at')
                    ->sum(DB::raw('TIMESTAMPDIFF(SECOND, started_at, ended_at)'));

                DB::table('tsk_tasks')
                    ->where('id', $task->id)
                    ->update(['tracked_seconds' => max(0, $seconds)]);
            });
    }

    public function down(): void
    {
        // Historical timer entries are intentionally preserved in sys_timer_entries.
    }
};