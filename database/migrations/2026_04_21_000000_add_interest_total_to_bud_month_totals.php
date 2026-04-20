<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bud_month_totals', function (Blueprint $table) {
            $table->integer('interest_total')->default(0)->after('adjustment_total');
        });
    }

    public function down(): void
    {
        Schema::table('bud_month_totals', function (Blueprint $table) {
            $table->dropColumn('interest_total');
        });
    }
};
