<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tsk_spans')) {
            Schema::create('tsk_spans', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('task_id', 26);
                $table->enum('kind', ['plan', 'fact'])->default('fact')->index();
                $table->string('title', 255)->nullable();
                $table->longText('content')->nullable();
                $table->timestamp('planned_start_at')->nullable();
                $table->timestamp('planned_end_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamp('auto_stop_at')->nullable();
                $table->timestamp('auto_stopped_at')->nullable();
                $table->string('auto_stop_reason', 32)->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'kind', 'started_at'], 'tsk_spans_user_kind_started_idx');
                $table->index(['user_id', 'kind', 'planned_start_at'], 'tsk_spans_user_kind_planned_idx');
                $table->index(['user_id', 'ended_at', 'auto_stop_at'], 'tsk_spans_user_auto_stop_idx');
                $table->index(['task_id', 'kind', 'started_at'], 'tsk_spans_task_kind_started_idx');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('task_id')->references('id')->on('tsk_tasks')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tsk_spans');
    }
};
