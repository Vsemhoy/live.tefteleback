<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fct_facts')) {
            Schema::create('fct_facts', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26)->index();
                $table->string('label', 160);
                $table->longText('value');
                $table->string('format', 16)->default('text')->index();
                $table->string('language', 40)->nullable()->index();
                $table->string('unit', 32)->nullable();
                $table->text('context')->nullable();
                $table->json('search_keywords')->nullable();
                $table->string('kind', 32)->default('other')->index();
                $table->string('display_mode', 24)->default('plain')->index();
                $table->boolean('is_sensitive')->default(false)->index();
                $table->boolean('is_expert')->default(false)->index();
                $table->date('valid_from')->nullable()->index();
                $table->date('valid_to')->nullable()->index();
                $table->boolean('is_pinned')->default(false)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'kind', 'is_expert'], 'fct_facts_user_kind_expert_idx');
                $table->index(['user_id', 'is_pinned', 'sort_order'], 'fct_facts_user_pinned_sort_idx');
                $table->index(['user_id', 'format', 'display_mode'], 'fct_facts_user_format_display_idx');
                $table->index(['user_id', 'updated_at'], 'fct_facts_user_updated_idx');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fct_facts');
    }
};