<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tsk_checklist_items')) {
            Schema::create('tsk_checklist_items', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('task_id', 26);
                $table->string('title', 255);
                $table->unsignedSmallInteger('status_id')->default(20);
                $table->integer('sort_order')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'task_id', 'sort_order'], 'tsk_checklist_user_task_sort_idx');
                $table->index(['task_id', 'status_id'], 'tsk_checklist_task_status_idx');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('task_id')->references('id')->on('tsk_tasks')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('tsk_tasks')) {
            DB::transaction(function () {
                DB::statement(<<<'SQL'
                    INSERT IGNORE INTO tsk_checklist_items (
                        id,
                        user_id,
                        task_id,
                        title,
                        status_id,
                        sort_order,
                        meta,
                        created_at,
                        updated_at,
                        deleted_at
                    )
                    SELECT
                        id,
                        user_id,
                        parent_task_id,
                        title,
                        status_id,
                        sort_order,
                        CASE
                            WHEN status_id IN (22, 24) AND JSON_EXTRACT(COALESCE(meta, JSON_OBJECT()), '$.completed_at') IS NULL
                                THEN JSON_SET(COALESCE(meta, JSON_OBJECT()), '$.completed_at', DATE_FORMAT(updated_at, '%Y-%m-%dT%H:%i:%s.000000Z'))
                            ELSE meta
                        END,
                        created_at,
                        updated_at,
                        deleted_at
                    FROM tsk_tasks
                    WHERE parent_task_id IS NOT NULL
                SQL);

                DB::table('tsk_tasks')
                    ->whereNotNull('parent_task_id')
                    ->delete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tsk_checklist_items');
    }
};
