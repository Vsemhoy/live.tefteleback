<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkr_spaces')) {
            Schema::create('bkr_spaces', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->string('title', 255);
                $table->string('slug', 160)->nullable();
                $table->string('visibility', 16)->default('private')->index();
                $table->integer('sort_order')->default(0)->index();
                $table->boolean('is_archived')->default(false)->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'is_archived', 'sort_order'], 'bkr_spaces_user_archive_sort_idx');
                $table->index(['user_id', 'slug'], 'bkr_spaces_user_slug_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('bkr_books')) {
            Schema::create('bkr_books', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('space_id', 26)->nullable();
                $table->string('title', 255);
                $table->string('slug', 160)->nullable();
                $table->longText('description')->nullable();
                $table->string('structure_mode', 16)->default('tree')->index();
                $table->string('visibility', 16)->default('private')->index();
                $table->string('cover_color', 24)->nullable();
                $table->json('export_settings')->nullable();
                $table->integer('sort_order')->default(0)->index();
                $table->boolean('is_archived')->default(false)->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'space_id', 'is_archived'], 'bkr_books_user_space_archive_idx');
                $table->index(['user_id', 'slug'], 'bkr_books_user_slug_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('space_id')->references('id')->on('bkr_spaces')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('bkr_pages')) {
            Schema::create('bkr_pages', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('book_id', 26);
                $table->char('parent_id', 26)->nullable();
                $table->string('title', 255);
                $table->string('slug', 160)->nullable();
                $table->string('visibility', 16)->default('private')->index();
                $table->integer('sort_order')->default(0)->index();
                $table->boolean('is_archived')->default(false)->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'book_id', 'parent_id', 'sort_order'], 'bkr_pages_user_book_parent_sort_idx');
                $table->index(['book_id', 'slug'], 'bkr_pages_book_slug_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('book_id')->references('id')->on('bkr_books')->cascadeOnDelete();
                $table->foreign('parent_id')->references('id')->on('bkr_pages')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('bkr_block_groups')) {
            Schema::create('bkr_block_groups', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('page_id', 26);
                $table->char('master_block_id', 26)->nullable();
                $table->string('type', 32)->default('markdown')->index();
                $table->string('role', 32)->default('content')->index();
                $table->string('visibility', 16)->default('private')->index();
                $table->boolean('is_hidden_by_default')->default(false)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'page_id', 'sort_order'], 'bkr_groups_user_page_sort_idx');
                $table->index(['page_id', 'role', 'is_hidden_by_default'], 'bkr_groups_page_role_hidden_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('page_id')->references('id')->on('bkr_pages')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('bkr_blocks')) {
            Schema::create('bkr_blocks', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('group_id', 26);
                $table->unsignedInteger('version_number')->default(1);
                $table->text('title')->nullable();
                $table->longText('content')->nullable();
                $table->json('payload')->nullable();
                $table->string('status', 24)->default('draft')->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['group_id', 'version_number'], 'bkr_blocks_group_version_unique');
                $table->index(['user_id', 'group_id', 'status'], 'bkr_blocks_user_group_status_idx');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('group_id')->references('id')->on('bkr_block_groups')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('bkr_block_groups') && Schema::hasTable('bkr_blocks')) {
            Schema::table('bkr_block_groups', function (Blueprint $table) {
                $table->foreign('master_block_id')->references('id')->on('bkr_blocks')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkr_block_groups')) {
            Schema::table('bkr_block_groups', function (Blueprint $table) {
                $table->dropForeign(['master_block_id']);
            });
        }

        Schema::dropIfExists('bkr_blocks');
        Schema::dropIfExists('bkr_block_groups');
        Schema::dropIfExists('bkr_pages');
        Schema::dropIfExists('bkr_books');
        Schema::dropIfExists('bkr_spaces');
    }
};
