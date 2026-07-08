<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_timer_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();

            $table->dateTime('started_at')->nullable()->index();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_min')->default(0);

            $table->enum('entry_type', ['manual', 'timer', 'interval'])->default('manual')->index();
            $table->enum('time_type', ['self', 'service'])->nullable()->index();

            $table->string('source_module', 32)->index();
            $table->ulid('source_id')->index();
            $table->integer('sort_order')->default(0);

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_module', 'source_id', 'sort_order'], 'timer_source_sort_idx');
            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_timer_entries');
    }
};
