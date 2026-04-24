<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stf_expenses', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();

            // ── Ссылка на вещь ────────────────────────────────────────
            $table->char('thing_id', 26)->index();

            // ── Опциональная ссылка на событие Register ───────────────
            // Например: bought → register(bought) + expense(сумма)
            $table->char('register_id', 26)->nullable();

            // ── Опциональная ссылка на транзакцию Badger ─────────────
            // Null если расход записан вручную без Badger-транзакции
            $table->char('transaction_id', 26)->nullable()->index();

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
            // bud_transactions в другой логической группе таблиц,
            // soft delete там не совпадает по механике.
            // Целостность проверяем на уровне приложения.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stf_expenses');
    }
};
