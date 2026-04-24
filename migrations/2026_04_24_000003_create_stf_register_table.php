<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stf_register', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();
            $table->char('thing_id', 26)->index();

            // ── Тип события ───────────────────────────────────────────
            $table->enum('event_type', [
                'bought',    // куплен (→ stored/active)
                'ordered',   // заказан, ещё не получен
                'received',  // получен (ordered → active/stored)
                'moved',     // перемещён между локациями
                'installed', // установлен в/на asset
                'lent',      // одолжен (→ lent)
                'returned',  // возвращён от одолжившего (→ active/stored)
                'sold',      // продан (→ sold)
                'lost',      // потерян (→ lost)
                'stolen',    // украден (→ lost)
                'disposed',  // выброшен/утилизирован (→ disposed)
                'repaired',  // отдан в ремонт / получен из ремонта
                'noted',     // просто заметка без смены статуса/локации
            ]);

            // ── Перемещение ───────────────────────────────────────────
            // Nullable: bought не имеет from, disposed не имеет to
            $table->char('from_location_id', 26)->nullable();
            $table->char('to_location_id', 26)->nullable();

            // ── Доп. поля для lent ────────────────────────────────────
            $table->string('contact', 200)->nullable();  // кому одолжили
            $table->date('return_expected')->nullable();  // ожидаемый возврат

            // ── Доп. поля для sold/bought ─────────────────────────────
            $table->integer('amount')->nullable(); // сумма в минорных единицах

            // ── Свободная заметка ─────────────────────────────────────
            $table->text('note')->nullable();

            $table->date('occurred_at');   // дата события (выбирает юзер)
            $table->timestamps();

            // FK — intentionally no cascade delete:
            // история регистров должна сохраняться даже если вещь архивирована.
            // При soft delete thing'а — register остаётся.
            // from/to location — nullOnDelete (локация удалена, но событие было)
            $table->foreign('thing_id')
                  ->references('id')->on('stf_things');

            $table->foreign('from_location_id')
                  ->references('id')->on('stf_locations')
                  ->nullOnDelete();

            $table->foreign('to_location_id')
                  ->references('id')->on('stf_locations')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stf_register');
    }
};
