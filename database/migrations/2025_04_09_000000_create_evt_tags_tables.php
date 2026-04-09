<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('evt_event_tags');
        Schema::dropIfExists('evt_tags');

        // ── Теги ────────────────────────────────────────────────────────────
        Schema::create('evt_tags', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26)->nullable()->index(); // NULL = системный тег
            $table->string('name', 32);
            $table->string('slug', 32)->index();              // url-friendly, для поиска
            $table->string('color', 9)->nullable();           // HEX foreground
            $table->string('bgcolor', 9)->nullable();         // HEX background
            $table->boolean('is_system')->default(false)->index();
            $table->unsignedBigInteger('sort_order')->default(0)->index();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            // Юзер не может создать два тега с одинаковым slug
            $table->unique(['user_id', 'slug']);

            $table->index(['user_id', 'is_archived']);
            $table->index(['user_id', 'sort_order']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // ── Связка ивент ↔ тег (many-to-many) ──────────────────────────────
        Schema::create('evt_event_tags', function (Blueprint $table) {
            $table->char('event_id', 26);
            $table->char('tag_id', 26);

            $table->primary(['event_id', 'tag_id']);
            $table->index('tag_id');   // для поиска "все ивенты с тегом X"
            $table->index('event_id'); // для поиска "все теги ивента Y"

            $table->foreign('event_id')
                ->references('id')
                ->on('evt_events')
                ->onDelete('cascade'); // удалился ивент — связки тоже

            $table->foreign('tag_id')
                ->references('id')
                ->on('evt_tags')
                ->onDelete('cascade'); // удалился тег — связки тоже
        });

        // ── Системные теги (сидер прямо здесь) ──────────────────────────────
        $systemTags = [
            [
                'id' => 'SYSTAG000000000000URGENT00',
                'user_id' => null,
                'name' => 'Urgent',
                'slug' => 'urgent',
                'color' => '#e03131',
                'bgcolor' => '#ffe3e3',
                'is_system' => true,
                'sort_order' => 1000,
            ],
            [
                'id' => 'SYSTAG00000000000WAITING0',
                'user_id' => null,
                'name' => 'Waiting',
                'slug' => 'waiting',
                'color' => '#e8590c',
                'bgcolor' => '#fff4e6',
                'is_system' => true,
                'sort_order' => 2000,
            ],
            [
                'id' => 'SYSTAG00000000000SOMEDAY0',
                'user_id' => null,
                'name' => 'Someday',
                'slug' => 'someday',
                'color' => '#868e96',
                'bgcolor' => '#f1f3f5',
                'is_system' => true,
                'sort_order' => 3000,
            ],
            [
                'id' => 'SYSTAG000000000000FOCUS00',
                'user_id' => null,
                'name' => 'Focus',
                'slug' => 'focus',
                'color' => '#3b5bdb',
                'bgcolor' => '#edf2ff',
                'is_system' => true,
                'sort_order' => 4000,
            ],
            [
                'id' => 'SYSTAG000000000000QUICK00',
                'user_id' => null,
                'name' => 'Quick win',
                'slug' => 'quick',
                'color' => '#2f9e44',
                'bgcolor' => '#ebfbee',
                'is_system' => true,
                'sort_order' => 5000,
            ],
            [
                'id' => 'SYSTAG000000000000BUY000',
                'user_id' => null,
                'name' => 'Buy',
                'slug' => 'buy',
                'color' => '#0c8599',
                'bgcolor' => '#e3fafc',
                'is_system' => true,
                'sort_order' => 6000,
            ],
            [
                'id' => 'SYSTAG00000000000CALLS000',
                'user_id' => null,
                'name' => 'Calls',
                'slug' => 'calls',
                'color' => '#9c36b5',
                'bgcolor' => '#f8f0fc',
                'is_system' => true,
                'sort_order' => 7000,
            ],
        ];

        DB::table('evt_tags')->insert(array_map(function ($tag) {
            return array_merge($tag, [
                'created_at' => null,
                'updated_at' => null,
            ]);
        }, $systemTags));
    }

    public function down(): void
    {
        Schema::dropIfExists('evt_event_tags');
        Schema::dropIfExists('evt_tags');
    }
};
