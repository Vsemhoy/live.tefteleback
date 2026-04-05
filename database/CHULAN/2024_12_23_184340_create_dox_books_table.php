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
        Schema::create('dox_books', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 128)->default('new document');
            $table->string('description', 2048)->nullable();
            $table->char('user_id', 26)->index();
            $table->json('content'); // structure of the book
            $table->json('decor')->nullable();
            $table->json('seo')->nullable();
            $table->integer('ordered')->default(1);
            $table->tinyInteger('access')->default(1); // 0 - private / 1 - friends / 2 - group / 3 - public registered / 4 - public global
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });


        Schema::create('dox_extlinks', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('book_id', 26)->index();
            $table->string('key', 128); // hash key of reference
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('dox_books')->onDelete('cascade');
        });


        Schema::create('dox_pages', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->tinyInteger('type')->default(11); // 1 - page / 2 - folder / 3 - group
            $table->string('name', 128)->default('new page');
            $table->string('description', 2048)->nullable();
            $table->char('book_id', 26)->index();
            $table->char('user_id', 26)->index();
            $table->json('decor')->nullable();
            $table->json('seo')->nullable();
            $table->json('content'); // structure of pages
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('dox_books')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });


        Schema::create('dox_textrow', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('page_id', 26)->index();
            $table->char('user_id', 26)->index();
            $table->json('decor')->nullable();
            $table->text('content'); // content of page
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('dox_pages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });


        Schema::create('dox_mdrow', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('page_id', 26)->index();
            $table->char('user_id', 26)->index();
            $table->json('decor')->nullable();
            $table->text('content'); // content of page
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('dox_pages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('dox_coderow', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('page_id', 26)->index();
            $table->char('user_id', 26)->index();
            $table->foreignId('lang_id')->nullable()->constrained('glob_proglangs')->onDelete('set null');
            $table->json('decor')->nullable();
            $table->text('content'); // content of page
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('dox_pages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('lang_id')->references('id')->on('glob_proglangs')->onDelete('set null');
        });

        Schema::create('dox_tablerow', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('page_id', 26)->index();
            $table->char('user_id', 26)->index();
            $table->json('decor')->nullable();
            $table->json('content');
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('dox_pages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });


        // This table alow user to insert content ROW to his own document from Another user's document
        Schema::create('dox_includerow', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('page_id', 26)->index();
            $table->char('user_id', 26)->index();
            $table->char('item_id', 26)->index();
            $table->string('item_type', 15)->index(); // name of table
            $table->string('extlink_key', 128)->nullable(); // if linked item is not public, use external access-link to access
            $table->json('decor')->nullable();
            $table->string('title', 1024);
            $table->boolean('hidden')->default(false);
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('dox_pages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dox_extlinks');
        Schema::dropIfExists('dox_pages');
        Schema::dropIfExists('dox_textrow');
        Schema::dropIfExists('dox_mdrow');
        Schema::dropIfExists('dox_coderow');
        Schema::dropIfExists('dox_tablerow');
        Schema::dropIfExists('dox_includerow');
        Schema::dropIfExists('dox_books');
    }
};
