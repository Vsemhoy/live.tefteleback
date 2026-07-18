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
        Schema::create('led_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('layer_id')->index();
            $table->string('name', 100);
            $table->char('literals', 3)->nullable();
            $table->enum('type', ['cash', 'card', 'credit', 'deposit', 'phantom']);
            $table->char('currency', 3)->default('RUB');
            $table->integer('opening_balance')->default(0);
            $table->string('color', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('is_archived')->default(0);
            $table->boolean('is_expert')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('led_accounts');
    }
};
