<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prj_projects')) {
            return;
        }

        Schema::table('prj_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('prj_projects', 'code')) {
                $table->string('code', 3)->nullable()->after('title');
            }

            if (! Schema::hasColumn('prj_projects', 'color')) {
                $table->string('color', 24)->nullable()->after('code');
            }

            if (! Schema::hasColumn('prj_projects', 'show_in_tasker')) {
                $table->boolean('show_in_tasker')->default(true)->after('is_hidden');
                $table->index(['user_id', 'show_in_tasker', 'sort_order'], 'prj_projects_user_tasker_sort_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prj_projects')) {
            return;
        }

        Schema::table('prj_projects', function (Blueprint $table) {
            if (Schema::hasColumn('prj_projects', 'show_in_tasker')) {
                $table->dropIndex('prj_projects_user_tasker_sort_idx');
                $table->dropColumn('show_in_tasker');
            }

            if (Schema::hasColumn('prj_projects', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('prj_projects', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};