<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bud_month_totals', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();
            $table->char('layer_id', 26);
            $table->char('account_id', 26);
            $table->char('month_key', 7); // '2026-04'

            $table->integer('opening_balance')->default(0);
            $table->integer('closing_balance')->default(0);
            $table->integer('income_total')->default(0);
            $table->integer('expense_total')->default(0);
            $table->integer('transfer_in_total')->default(0);
            $table->integer('transfer_out_total')->default(0);
            $table->integer('adjustment_total')->default(0);
            $table->integer('tx_count')->default(0);

            $table->tinyInteger('is_dirty')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['layer_id', 'account_id', 'month_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bud_month_totals');
    }
};
