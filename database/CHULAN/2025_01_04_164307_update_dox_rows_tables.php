<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запуск миграции.
     *
     * @return void
     */
    public function up()
    {
        // Список таблиц, которые нужно обновить
        $tables = [
            'dox_textrow',
            'dox_mdrow',
            'dox_coderow',
            'dox_tablerow',
            'dox_includerow',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_current')->default(false); // Добавляем колонку is_current
                $table->boolean('is_active')->default(true);  // Добавляем колонку is_active
            });
        }

        // Добавляем колонку is_active в dox_books
        Schema::table('dox_books', function (Blueprint $table) {
            $table->boolean('is_active')->default(true); // Добавляем колонку is_active
        });

        // Добавляем колонку is_active в dox_pages
        Schema::table('dox_pages', function (Blueprint $table) {
            $table->boolean('is_active')->default(true); // Добавляем колонку is_active
        });
    }

    /**
     * Откат миграции.
     *
     * @return void
     */
    public function down()
    {
        // Удаляем добавленные колонки при откате миграции
        $tables = [
            'dox_textrow',
            'dox_mdrow',
            'dox_coderow',
            'dox_tablerow',
            'dox_includerow',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['is_current', 'is_active']);
            });
        }

        // Удаляем колонку is_active из dox_books
        Schema::table('dox_books', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // Удаляем колонку is_active из dox_pages
        Schema::table('dox_pages', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
