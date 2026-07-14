<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->index()->after('password');
            }

            if (! Schema::hasColumn('users', 'demo_seeded_at')) {
                $table->timestamp('demo_seeded_at')->nullable()->after('is_demo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'demo_seeded_at')) {
                $table->dropColumn('demo_seeded_at');
            }

            if (Schema::hasColumn('users', 'is_demo')) {
                $table->dropColumn('is_demo');
            }
        });
    }
};
