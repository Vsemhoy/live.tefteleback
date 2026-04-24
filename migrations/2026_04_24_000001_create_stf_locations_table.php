<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stf_locations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();

            $table->string('name', 100);
            $table->char('parent_id', 26)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_archived')->default(false);

            $table->timestamps();
            $table->softDeletes(); // мягкое удаление — история регистров сохраняется

            $table->foreign('parent_id')
                  ->references('id')->on('stf_locations')
                  ->nullOnDelete(); // если родитель удалён — ребёнок становится корневым
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stf_locations');
    }
};
