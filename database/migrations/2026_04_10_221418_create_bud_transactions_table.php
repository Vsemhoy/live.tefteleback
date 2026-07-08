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
        Schema::create('led_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('layer_id')->index()->nullable();
            $table->ulid('account_id')->index();
            $table->ulid('target_account_id')->nullable();
            $table->ulid('group_id')->nullable()->index();
            $table->ulid('original_transaction_id')->nullable();

            $table->enum('flow_kind', [
                'expense', 'income', 'transfer_out', 'transfer_in', 'adjustment',
            ]);
            $table->integer('amount');
            $table->date('occurred_at')->index();
            $table->char('month_key', 7)->index();

            $table->string('title', 255)->nullable();
            $table->text('note')->nullable();

            $table->enum('status', ['cleared', 'pending'])->default('cleared');
            $table->tinyInteger('is_disabled')->default(0);
            $table->tinyInteger('is_pinned')->default(0);
            $table->integer('sort_order')->default(0);

            $table->ulid('exploiter_event_id')->nullable()->index();
            $table->enum('cost_type', ['part', 'labor', 'delivery', 'other'])->nullable()->index();

            $table->string('linked_entity_type', 50)->nullable();
            $table->ulid('linked_entity_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'month_key']);
            $table->index(['exploiter_event_id', 'cost_type', 'sort_order'], 'led_tx_exp_event_cost_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('led_transactions');
    }
};
