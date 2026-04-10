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
        Schema::create('bud_accounts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();
            $table->char('layer_id', 26)->index();
            $table->string('name', 100);
            $table->enum('type', ['cash', 'card', 'credit', 'deposit', 'phantom']);
            $table->char('currency', 3)->default('RUB');
            $table->integer('opening_balance')->default(0); // копейки
            $table->string('color', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('is_archived')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bud_accounts');
    }
};
