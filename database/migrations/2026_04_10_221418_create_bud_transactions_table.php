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
        Schema::create('bud_transactions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();
            $table->char('layer_id', 26)->index();
            $table->char('account_id', 26)->index();
            $table->char('target_account_id', 26)->nullable();
            $table->char('group_id', 26)->nullable()->index();
            $table->char('original_transaction_id', 26)->nullable();

            $table->enum('flow_kind', [
                'expense', 'income', 'transfer_out', 'transfer_in', 'adjustment',
            ]);
            $table->integer('amount'); // копейки, всегда > 0
            $table->date('occurred_at')->index();
            $table->char('month_key', 7)->index(); // '2026-04'

            $table->string('title', 255)->nullable();
            $table->text('note')->nullable();

            $table->enum('status', ['cleared', 'pending'])->default('cleared');
            $table->tinyInteger('is_disabled')->default(0);
            $table->tinyInteger('is_pinned')->default(0);
            $table->integer('sort_order')->default(0);

            // Polymorphic связь с другими модулями
            $table->string('linked_entity_type', 50)->nullable();
            $table->char('linked_entity_id', 26)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'month_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bud_transactions');
    }
};
