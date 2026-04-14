<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bud_accounts', function (Blueprint $table) {
            $table->date('opened_at')->nullable()->after('sort_order');
            $table->date('closed_at')->nullable()->after('opened_at');
            $table->int('interest_rate')->nullable()->after('closed_at');
            $table->date('interest_start')->nullable()->after('interest_rate');
        });

        Schema::table('bud_transactions', function (Blueprint $table) {
            $table->enum('flow_kind', [
                'expense', 'income', 'transfer_out', 'transfer_in', 'adjustment', 'reconciliation',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('bud_accounts', function (Blueprint $table) {
            $table->dropColumn(['opened_at', 'closed_at', 'interest_rate', 'interest_start']);
        });

        Schema::table('bud_transactions', function (Blueprint $table) {
            $table->enum('flow_kind', [
                'expense', 'income', 'transfer_out', 'transfer_in', 'adjustment',
            ])->change();
        });
    }
};
