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

        Schema::create('evt_types', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index(); // Добавлено недостающее поле
            $table->string('name', 32)->default('Default type');
            $table->string('description', 256)->nullable();
            $table->string('color', 9)->nullable();
            $table->string('bgcolor', 9)->nullable();
            $table->unsignedBigInteger('sort_order')->default(0)->index();
            $table->string('icon', 64)->nullable();
            $table->boolean('is_archived')->default(false); // Исправлена опечатка
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_archived']);
            $table->index('is_default');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });



        Schema::create('evt_sections', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index(); // Добавлено недостающее поле
            $table->string('name', 32)->default('New section');
            $table->string('literals', 3)->nullable();
            $table->string('description', 256)->nullable();
            $table->unsignedBigInteger('sort_order')->default(0);
            $table->tinyInteger('access')->default(1)->index();
            $table->string('color', 9)->nullable();
            $table->string('bgcolor', 9)->nullable();
            $table->string('icon', 64)->nullable();
            $table->json('decor')->nullable();
            $table->json('seo')->nullable();
            $table->boolean('is_archived')->default(false); // Исправлена опечатка
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'access']);
            $table->index(['user_id', 'is_archived']);
            $table->index(['user_id', 'sort_order']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });



        Schema::create('evt_categories', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->index(); // Добавлено недостающее поле
            $table->string('name', 32)->default('New category');
            $table->string('description', 256)->nullable();
            $table->string('color', 9)->nullable();
            $table->string('bgcolor', 9)->nullable();
            $table->unsignedBigInteger('sort_order')->default(0);
            $table->boolean('is_archived')->default(false); // Исправлена опечатка
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_archived']);
            $table->index(['user_id', 'sort_order']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        


        Schema::create('evt_events', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 128)->nullable();
            $table->char('user_id', 26)->index();
            $table->char('type_id', 26)->index()->nullable();
            $table->tinyInteger('format')->index()->default(1); // 1 - md, 2 - text, 3 - code
            $table->string('metadata', 25)->nullable(); // Browser language or something else
            $table->string('language', 10)->nullable()->index(); // 'en', 'ru', 'zh'
            $table->string('code_language', 20)->nullable();     // 'javascript', 'python', 'csharp'
            $table->char('section_id', 26)->index()->nullable();
            $table->char('category_id', 26)->index()->nullable();
            $table->char('project_id', 26)->index()->nullable();
            $table->string('location', 50)->nullable(); // GPS
            $table->string('client', 120)->nullable(); // Who posted client info
            $table->text('content')->nullable(); // md text or something else

            $table->tinyInteger('status')->default(1); // 1 - await / 2 - published / 3 - archieved

            $table->unsignedBigInteger('sort_order')->default(1);
            $table->tinyInteger('access')->default(1); // 0 - private / 1 - friends / 2 - group / 3 - public registered / 4 - public global
            $table->tinyInteger('comment_access')->default(2); // 2 - by section / 1 - forbid / 3 - allow 

            $table->char('parent_id', 26)->index()->nullable(); // ид родительского поста или оригинала. Его нет, если это авто-пост
            $table->char('root_id', 26)->index()->nullable(); // ид исходного поста
            $table->tinyInteger('relation_type')->default(0);
            // 0 - none, 1 - reply, 2 - quote, 3 - fork, 4 - reaction

            $table->boolean('is_blurred')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->datetime('setdate')->nullable()->useCurrent(); // Date when we place this event on the calendar
            $table->timestamps();





            // Составные индексы
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['root_id', 'created_at']);
            $table->index('setdate');

            // Для ленты: посты в секции
            $table->index(['section_id', 'status', 'setdate']);

            // Для ветки: все ответы на пост
            $table->index(['root_id', 'setdate']);

            // Для профиля: посты пользователя с сортировкой
            $table->index(['user_id', 'setdate']);

            // Для комментариев: кто может комментировать
            $table->index('comment_access');

            // Для сортировки
            $table->index('sort_order');
            

            // Внешние ключи с учетом nullable
            $table->foreign('type_id')
                ->references('id')
                ->on('evt_types')
                ->nullOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('evt_categories')
                ->nullOnDelete();

            $table->foreign('section_id')
                ->references('id')
                ->on('evt_sections')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });


        Schema::create('evt_starred', function (Blueprint $table) {
            $table->char('user_id', 26)->index();
            $table->char('event_id', 26)->index();
            $table->timestamps();

            $table->unique(['user_id', 'event_id']);
        });

        Schema::create('evt_media', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('event_id', 26)->index(); // Ссылка на пост
            $table->char('user_id', 26)->index(); // Владелец
            $table->string('url'); // Полный URL в Яндекс.Облаке
            $table->string('path'); // Путь в бакете (например 'uploads/events/abc123.jpg')
            $table->string('mime_type')->nullable(); // image/jpeg, image/png и т.д.
            $table->integer('size')->nullable(); // Размер в байтах
            $table->integer('width')->nullable(); // Для изображений
            $table->integer('height')->nullable(); // Для изображений
            $table->unsignedBigInteger('sort_order')->default(0); // Порядок сортировки
            $table->json('meta')->nullable(); // Доп. метаданные (EXIF и т.п.)
            $table->timestamps();
        });


        Schema::create('evt_embeds', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('event_id', 26)->index();     // Связь с постом
            $table->char('user_id', 26)->index();      // Кто добавил
            $table->string('url', 500);                // Оригинальный URL (https://youtu.be/abc )
            $table->string('provider', 20)->index();   // 'youtube', 'twitter', 'vimeo', 'spotify', 'figma'
            $table->string('type', 20)->index();       // 'video', 'tweet', 'audio', 'iframe', 'code', 'document'
            $table->string('title', 255)->nullable();  // Заголовок (из oEmbed или парсинга)
            $table->string('author', 100)->nullable(); // Автор/канал
            $table->string('thumbnail_url', 500)->nullable(); // Превью
            $table->string('duration', 20)->nullable(); // Для видео: "4:22"
            $table->json('meta')->nullable();          // Доп. данные: embed_url, html, provider_id и т.д.
            $table->integer('order')->default(0);      // Порядок в посте
            $table->timestamps();

            // Уникальность: чтобы не дублировать одну и ту же ссылку в одном посте
            $table->unique(['event_id', 'url']);
        });

        // Schema::create('evt_pin_algorithms', function (Blueprint $table) {
        //     $table->foreignId('event_id')->constrained('evt_events');
        //     $table->foreignId('algorithm_id')->constrained('pin_algorithms');
        //     $table->unsignedInteger('params')->default(0); // Saves day or month numbers, timestamp or another bool values
            
        //     // Добавляем индексы для часто используемых фильтров
        //     $table->index(['algorithm_id', 'params']);
        //     $table->index(['event_id', 'order']);
        // });

        // Schema::create('evt_pin_algorithms', function (Blueprint $table) {
        //     $table->char('event_id', 26)->index();
        //     $table->char('algorithm_id', 26)->index();
        //     $table->unsignedInteger('params')->default(0);

        //     $table->primary(['event_id', 'algorithm_id']);
        //     $table->index(['algorithm_id', 'params']);
        //     $table->index(['event_id']);

        //     $table->foreign('event_id')
        //         ->references('id')
        //         ->on('evt_events')
        //         ->onDelete('cascade');

        //     $table->foreign('algorithm_id')
        //         ->references('id')
        //         ->on('pin_algorithms')
        //         ->onDelete('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evt_starred');
        Schema::dropIfExists('evt_media');
        Schema::dropIfExists('evt_embeds');
        Schema::dropIfExists('evt_events');
        Schema::dropIfExists('evt_categories');
        Schema::dropIfExists('evt_sections');
        Schema::dropIfExists('evt_types');
        // Schema::dropIfExists('evt_pin_algorithms');
    }
};
