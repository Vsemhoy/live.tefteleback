<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cnt_contents', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();
            $table->string('source_module', 32)->index();
            $table->char('source_id', 26)->index();
            $table->string('field', 64)->default('content')->index();
            $table->string('kind', 32)->default('markdown')->index();
            $table->string('title', 255)->nullable();
            $table->longText('body_md');
            $table->char('body_hash', 64)->nullable()->index();
            $table->string('locale', 10)->nullable()->index();
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_module', 'source_id', 'field', 'kind'], 'cnt_source_field_kind_idx');
            $table->index(['source_module', 'source_id', 'is_primary', 'sort_order'], 'cnt_source_primary_sort_idx');
            $table->index(['user_id', 'source_module', 'field'], 'cnt_user_module_field_idx');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::create('sys_templates', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index();
            $table->string('module', 32)->index();
            $table->string('name', 128);
            $table->string('icon', 64)->nullable();
            $table->json('payload');
            $table->json('schedule')->nullable();
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'module', 'status', 'sort_order'], 'tpl_user_module_status_sort_idx');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_templates');
        Schema::dropIfExists('cnt_contents');
    }
};
