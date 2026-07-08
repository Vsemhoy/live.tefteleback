<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cnt_contents')) {
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
            });
        }

        if (! Schema::hasTable('sys_templates')) {
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
            });
        }

        if (Schema::hasTable('evt_events') && Schema::hasColumn('evt_events', 'content')) {
            DB::table('evt_events')
                ->select(['id', 'user_id', 'name', 'content'])
                ->whereNotNull('content')
                ->whereRaw("TRIM(content) <> ''")
                ->orderBy('id')
                ->chunkById(200, function ($events) {
                    $now = now();

                    foreach ($events as $event) {
                        $exists = DB::table('cnt_contents')
                            ->where('source_module', 'eventor')
                            ->where('source_id', $event->id)
                            ->where('field', 'content')
                            ->where('kind', 'markdown')
                            ->where('is_primary', true)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        DB::table('cnt_contents')->insert([
                            'id' => (string) Str::ulid(),
                            'user_id' => $event->user_id,
                            'source_module' => 'eventor',
                            'source_id' => $event->id,
                            'field' => 'content',
                            'kind' => 'markdown',
                            'title' => $event->name,
                            'body_md' => trim($event->content),
                            'body_hash' => hash('sha256', trim($event->content)),
                            'status' => 1,
                            'is_primary' => true,
                            'sort_order' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // The canonical table ownership lives in
        // 2025_03_25_192257_create_contentor_and_templates_tables.php.
        // This bridge only makes already-migrated dev/prod databases catch up.
    }
};
