<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stf_things', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();

            // ── Тип сущности ──────────────────────────────────────────
            $table->enum('entity_type', ['asset', 'item'])->default('item');

            // ── Основные поля ─────────────────────────────────────────
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('vendor', 200)->nullable();
            $table->text('url')->nullable();

            // ── Иерархия (Asset вложен в Asset, Item относится к Asset) ─
            $table->ulid('parent_id')->nullable();

            // ── Категория (общая таблица bud_categories) ─────────────
            $table->ulid('category_id')->nullable()->index();

            // ── Текущее состояние (денормализация для скорости) ───────
            $table->ulid('current_location_id')->nullable()->index();
            $table->enum('current_status', [
                'active',     // в использовании
                'stored',     // на хранении
                'ordered',    // заказан, в пути
                'installed',  // установлен в другой asset
                'lent',       // одолжен
                'sold',       // продан
                'lost',       // потерян/украден
                'disposed',   // выброшен/утилизирован
            ])->default('active');

            // ── Поля только для Asset ─────────────────────────────────
            $table->string('serial_no', 100)->nullable();

            // ── Поля только для Item ──────────────────────────────────
            $table->decimal('qty', 10, 2)->nullable();   // кол-во (3.5 литра, 2 штуки)
            $table->string('unit', 20)->nullable();      // шт, л, м, кг...

            // ── Финансы ───────────────────────────────────────────────
            $table->integer('purchase_price')->nullable(); // минорные единицы (копейки)
            $table->date('purchase_date')->nullable();

            // ── Для сортировки по частоте использования ───────────────
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamp('last_opened_at')->nullable();

            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                  ->references('id')->on('stf_things')
                  ->nullOnDelete();

            $table->foreign('current_location_id')
                  ->references('id')->on('stf_locations')
                  ->nullOnDelete(); // локация удалена → location_id = null
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stf_things');
    }
};
