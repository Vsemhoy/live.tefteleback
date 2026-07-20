<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tsk_blockers')) {
            Schema::create('tsk_blockers', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->string('title', 255);
                $table->longText('description')->nullable();
                $table->unsignedInteger('occurrence_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'title'], 'tsk_blockers_user_title_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('tsk_tasks')) {
            Schema::create('tsk_tasks', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->string('title', 255);
                $table->longText('description')->nullable();
                $table->longText('result')->nullable();
                $table->char('assignee_contact_id', 26)->nullable();
                $table->unsignedSmallInteger('priority_id')->default(13);
                $table->unsignedSmallInteger('status_id')->default(20);
                $table->date('due_at')->nullable();
                $table->char('eventor_event_id', 26)->nullable();
                $table->char('parent_task_id', 26)->nullable();
                $table->unsignedInteger('tracked_seconds')->default(0);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_expert')->default(false);
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'status_id', 'due_at'], 'tsk_tasks_user_status_due_idx');
                $table->index(['user_id', 'assignee_contact_id', 'status_id'], 'tsk_tasks_user_assignee_status_idx');
                $table->index(['user_id', 'parent_task_id', 'sort_order'], 'tsk_tasks_user_parent_sort_idx');
                $table->index(['user_id', 'is_expert', 'updated_at'], 'tsk_tasks_user_expert_updated_idx');
                $table->index(['user_id', 'is_pinned', 'sort_order'], 'tsk_tasks_user_pin_sort_idx');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('assignee_contact_id')->references('id')->on('ctr_contacts')->nullOnDelete();
                $table->foreign('eventor_event_id')->references('id')->on('evt_events')->nullOnDelete();
                $table->foreign('parent_task_id')->references('id')->on('tsk_tasks')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('tsk_logs')) {
            Schema::create('tsk_logs', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('task_id', 26);
                $table->string('kind', 32)->default('note');
                $table->longText('content')->nullable();
                $table->char('blocker_id', 26)->nullable();
                $table->char('timer_entry_id', 26)->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'occurred_at'], 'tsk_logs_user_time_idx');
                $table->index(['task_id', 'occurred_at'], 'tsk_logs_task_time_idx');
                $table->index(['user_id', 'kind'], 'tsk_logs_user_kind_idx');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('task_id')->references('id')->on('tsk_tasks')->cascadeOnDelete();
                $table->foreign('blocker_id')->references('id')->on('tsk_blockers')->nullOnDelete();
                $table->foreign('timer_entry_id')->references('id')->on('sys_timer_entries')->nullOnDelete();
            });
        }

        if (Schema::hasTable('ctr_contents') && ! Schema::hasColumn('ctr_contents', 'tasker_task_id')) {
            Schema::table('ctr_contents', function (Blueprint $table) {
                $table->char('tasker_task_id', 26)->nullable()->after('exploiter_event_id');
                $table->index('tasker_task_id', 'ctr_contents_tasker_task_idx');
                $table->foreign('tasker_task_id')->references('id')->on('tsk_tasks')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ctr_contents') && Schema::hasColumn('ctr_contents', 'tasker_task_id')) {
            Schema::table('ctr_contents', function (Blueprint $table) {
                $table->dropForeign(['tasker_task_id']);
                $table->dropIndex('ctr_contents_tasker_task_idx');
                $table->dropColumn('tasker_task_id');
            });
        }

        Schema::dropIfExists('tsk_logs');
        Schema::dropIfExists('tsk_tasks');
        Schema::dropIfExists('tsk_blockers');
    }
};