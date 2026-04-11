<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bud_transaction_tags', function (Blueprint $table) {
            $table->char('transaction_id', 26);
            $table->char('tag_id', 26);

            $table->primary(['transaction_id', 'tag_id']);
            $table->index('tag_id');
            $table->index('transaction_id');

            $table->foreign('transaction_id')
                ->references('id')
                ->on('bud_transactions')
                ->onDelete('cascade');

            $table->foreign('tag_id')
                ->references('id')
                ->on('evt_tags')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bud_transaction_tags');
    }
};
