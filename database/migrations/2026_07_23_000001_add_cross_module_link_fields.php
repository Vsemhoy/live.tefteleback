<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ctr_contacts')) {
            Schema::table('ctr_contacts', function (Blueprint $table) {
                if (! Schema::hasColumn('ctr_contacts', 'birthday_on')) {
                    $table->date('birthday_on')->nullable()->after('met_context');
                }

                if (! Schema::hasColumn('ctr_contacts', 'birthday_precision')) {
                    $table->string('birthday_precision', 8)->nullable()->after('birthday_on');
                }

                if (! $this->indexExists('ctr_contacts', 'ctr_contacts_user_birthday_idx')) {
                    $table->index(['user_id', 'birthday_on'], 'ctr_contacts_user_birthday_idx');
                }
            });
        }

        if (Schema::hasTable('evt_events') && Schema::hasTable('ctr_contacts') && ! Schema::hasTable('evt_event_contacts')) {
            Schema::create('evt_event_contacts', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('event_id', 26);
                $table->char('contact_id', 26);
                $table->string('role', 32)->nullable();
                $table->text('note')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'event_id'], 'evt_event_contacts_user_event_idx');
                $table->index(['user_id', 'contact_id'], 'evt_event_contacts_user_contact_idx');
                $table->unique(['event_id', 'contact_id', 'role'], 'evt_event_contacts_unique');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('event_id')->references('id')->on('evt_events')->cascadeOnDelete();
                $table->foreign('contact_id')->references('id')->on('ctr_contacts')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('ctr_contents') && Schema::hasTable('ctr_contacts') && ! Schema::hasTable('ctr_content_mentions')) {
            Schema::create('ctr_content_mentions', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26);
                $table->char('content_id', 26);
                $table->char('contact_id', 26);
                $table->string('role', 32)->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'content_id'], 'ctr_content_mentions_user_content_idx');
                $table->index(['user_id', 'contact_id'], 'ctr_content_mentions_user_contact_idx');
                $table->unique(['content_id', 'contact_id', 'role'], 'ctr_content_mentions_unique');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('content_id')->references('id')->on('ctr_contents')->cascadeOnDelete();
                $table->foreign('contact_id')->references('id')->on('ctr_contacts')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('ctr_contents') && Schema::hasTable('evt_tags') && ! Schema::hasTable('ctr_content_tags')) {
            Schema::create('ctr_content_tags', function (Blueprint $table) {
                $table->char('content_id', 26);
                $table->char('tag_id', 26);
                $table->timestamps();

                $table->primary(['content_id', 'tag_id']);
                $table->index('tag_id', 'ctr_content_tags_tag_idx');

                $table->foreign('content_id')->references('id')->on('ctr_contents')->cascadeOnDelete();
                $table->foreign('tag_id')->references('id')->on('evt_tags')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('stf_register')) {
            Schema::table('stf_register', function (Blueprint $table) {
                if (! Schema::hasColumn('stf_register', 'content_md')) {
                    $table->longText('content_md')->nullable()->after('note');
                }
            });

            if (Schema::hasColumn('stf_register', 'note') && Schema::hasColumn('stf_register', 'content_md')) {
                DB::table('stf_register')
                    ->whereNull('content_md')
                    ->whereNotNull('note')
                    ->update(['content_md' => DB::raw('note')]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ctr_content_tags');
        Schema::dropIfExists('ctr_content_mentions');
        Schema::dropIfExists('evt_event_contacts');

        if (Schema::hasTable('stf_register') && Schema::hasColumn('stf_register', 'content_md')) {
            Schema::table('stf_register', function (Blueprint $table) {
                $table->dropColumn('content_md');
            });
        }

        if (Schema::hasTable('ctr_contacts')) {
            Schema::table('ctr_contacts', function (Blueprint $table) {
                if ($this->indexExists('ctr_contacts', 'ctr_contacts_user_birthday_idx')) {
                    $table->dropIndex('ctr_contacts_user_birthday_idx');
                }

                if (Schema::hasColumn('ctr_contacts', 'birthday_precision')) {
                    $table->dropColumn('birthday_precision');
                }

                if (Schema::hasColumn('ctr_contacts', 'birthday_on')) {
                    $table->dropColumn('birthday_on');
                }
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schema = $connection->getDatabaseName();

        return (bool) $connection->table('information_schema.statistics')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
