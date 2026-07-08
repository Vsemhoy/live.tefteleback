<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stf_expenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();

            // ── Ссылка на вещь ────────────────────────────────────────
            $table->ulid('thing_id')->index();

            // ── Опциональная ссылка на событие Register ───────────────
            // Например: bought → register(bought) + expense(сумма)
            $table->ulid('register_id')->nullable();

            // ── Опциональная ссылка на транзакцию Ledger ─────────────
            // Null если расход записан вручную без Ledger-транзакции
            $table->ulid('transaction_id')->nullable()->index();

            // ── Сумма (если нет транзакции — пишем вручную) ──────────
            $table->integer('amount')->nullable(); // минорные единицы

            $table->text('note')->nullable();
            $table->date('occurred_at');
            $table->timestamps();

            $table->foreign('thing_id')
                  ->references('id')->on('stf_things');

            $table->foreign('register_id')
                  ->references('id')->on('stf_register')
                  ->nullOnDelete();

            // transaction_id — намеренно без FK constraint:
            // led_transactions в другой логической группе таблиц,
            // soft delete там не совпадает по механике.
            // Целостность проверяем на уровне приложения.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stf_expenses');
    }
};
