<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Добавляем is_negative
        Schema::table('led_transactions', function (Blueprint $table) {
            $table->tinyInteger('is_negative')->default(0)->after('amount');
        });

        // 2. Расширяем enum flow_kind — добавляем reconciliation
        // MySQL не поддерживает ALTER COLUMN для enum напрямую — меняем через MODIFY
        DB::statement("ALTER TABLE led_transactions MODIFY COLUMN flow_kind ENUM('expense','income','transfer_out','transfer_in','adjustment','reconciliation') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('led_transactions', function (Blueprint $table) {
            $table->dropColumn('is_negative');
        });

        DB::statement("ALTER TABLE led_transactions MODIFY COLUMN flow_kind ENUM('expense','income','transfer_out','transfer_in','adjustment') NOT NULL");
    }
};
