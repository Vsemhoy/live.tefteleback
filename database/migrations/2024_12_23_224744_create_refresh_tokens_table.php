<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefreshTokensTable extends Migration
{
    public function up()
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->char('user_id', 26)->index();
            $table->string('user_agent', 512)->nullable(); // Добавлено поле user_agent
            $table->string('ip_address', 45)->nullable();   // Добавлено поле ip_address
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refresh_tokens');
    }
}

