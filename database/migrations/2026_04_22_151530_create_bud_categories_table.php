<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bud_categories', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            $table->char('user_id', 26);
            $table->char('parent_id', 26)->nullable();

            $table->string('name', 100);
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('path', 500)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_archived')->default(false);

            $table->timestamps();

            $table->index('user_id');
            $table->index('parent_id');
            $table->index(['user_id', 'parent_id']);
            $table->index(['user_id', 'is_archived']);
            $table->index(['user_id', 'parent_id', 'sort_order']);

            // если нужны ограничения:
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('bud_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bud_categories');
    }
};