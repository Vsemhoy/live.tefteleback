<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prj_projects')) {
            Schema::create('prj_projects', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->string('title', 255);
                $table->longText('description')->nullable();
                $table->longText('result')->nullable();
                $table->unsignedSmallInteger('priority_id')->default(13);
                $table->unsignedSmallInteger('status_id')->default(20);
                $table->date('started_on')->nullable();
                $table->date('due_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_expert')->default(false);
                $table->boolean('is_hidden')->default(false);
                $table->integer('sort_order')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'status_id', 'due_at'], 'prj_projects_user_status_due_idx');
                $table->index(['user_id', 'is_hidden', 'is_expert'], 'prj_projects_user_visibility_idx');
                $table->index(['user_id', 'is_pinned', 'sort_order'], 'prj_projects_user_pin_sort_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('tsk_tasks')) {
            Schema::table('tsk_tasks', function (Blueprint $table) {
                if (! Schema::hasColumn('tsk_tasks', 'project_id')) {
                    $table->char('project_id', 26)->nullable()->after('parent_task_id');
                    $table->index(['user_id', 'project_id', 'status_id'], 'tsk_tasks_user_project_status_idx');
                    $table->foreign('project_id')->references('id')->on('prj_projects')->nullOnDelete();
                }

                if (! Schema::hasColumn('tsk_tasks', 'is_hidden')) {
                    $table->boolean('is_hidden')->default(false)->after('is_expert');
                    $table->index(['user_id', 'is_hidden', 'is_expert'], 'tsk_tasks_user_visibility_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tsk_tasks')) {
            Schema::table('tsk_tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tsk_tasks', 'project_id')) {
                    $table->dropForeign(['project_id']);
                    $table->dropIndex('tsk_tasks_user_project_status_idx');
                    $table->dropColumn('project_id');
                }

                if (Schema::hasColumn('tsk_tasks', 'is_hidden')) {
                    $table->dropIndex('tsk_tasks_user_visibility_idx');
                    $table->dropColumn('is_hidden');
                }
            });
        }

        Schema::dropIfExists('prj_projects');
    }
};