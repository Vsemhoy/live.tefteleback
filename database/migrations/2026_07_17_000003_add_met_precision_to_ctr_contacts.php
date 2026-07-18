<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ctr_contacts') && ! Schema::hasColumn('ctr_contacts', 'met_precision')) {
            Schema::table('ctr_contacts', function (Blueprint $table) {
                $table->string('met_precision', 16)->nullable()->after('met_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ctr_contacts') && Schema::hasColumn('ctr_contacts', 'met_precision')) {
            Schema::table('ctr_contacts', function (Blueprint $table) {
                $table->dropColumn('met_precision');
            });
        }
    }
};
