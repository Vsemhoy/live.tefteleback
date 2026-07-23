<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tsk_tasks') && ! Schema::hasColumn('tsk_tasks', 'meta')) {
            Schema::table('tsk_tasks', function (Blueprint $table) {
                $table->json('meta')->nullable()->after('closed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tsk_tasks') && Schema::hasColumn('tsk_tasks', 'meta')) {
            Schema::table('tsk_tasks', function (Blueprint $table) {
                $table->dropColumn('meta');
            });
        }
    }
};
