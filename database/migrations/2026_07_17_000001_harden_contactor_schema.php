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
                if (! Schema::hasColumn('ctr_contacts', 'avatar_url')) {
                    $table->string('avatar_url', 512)->nullable()->after('avatar');
                }
                if (! Schema::hasColumn('ctr_contacts', 'met_precision')) {
                    $table->string('met_precision', 16)->nullable()->after('met_at');
                }
                if (! Schema::hasColumn('ctr_contacts', 'is_pinned')) {
                    $table->boolean('is_pinned')->default(false)->index()->after('details');
                }
                if (! Schema::hasColumn('ctr_contacts', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->index()->after('is_pinned');
                }
            });
        }

        if (! Schema::hasTable('ctr_details')) {
            Schema::create('ctr_details', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26)->index();
                $table->char('contact_id', 26)->index();
                $table->string('kind', 32)->default('custom')->index();
                $table->string('label', 80)->nullable();
                $table->string('value', 1024);
                $table->integer('sort_order')->default(0)->index();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['contact_id', 'sort_order'], 'ctr_details_contact_sort_idx');
                $table->index(['user_id', 'kind'], 'ctr_details_user_kind_idx');

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('contact_id')->references('id')->on('ctr_contacts')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('ctr_contents')) {
            Schema::table('ctr_contents', function (Blueprint $table) {
                if (! Schema::hasColumn('ctr_contents', 'is_expert')) {
                    $table->boolean('is_expert')->default(false)->index()->after('is_pinned');
                }
            });
        }

        if (Schema::hasTable('ctr_contacts') && Schema::hasTable('ctr_details')) {
            $contacts = DB::table('ctr_contacts')
                ->whereNotNull('details')
                ->select(['id', 'user_id', 'details'])
                ->get();

            foreach ($contacts as $contact) {
                $details = json_decode($contact->details, true);
                if (! is_array($details)) {
                    continue;
                }

                foreach (array_values($details) as $index => $detail) {
                    if (! is_array($detail) || empty($detail['value'])) {
                        continue;
                    }

                    $exists = DB::table('ctr_details')
                        ->where('contact_id', $contact->id)
                        ->where('kind', $detail['kind'] ?? 'custom')
                        ->where('value', $detail['value'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('ctr_details')->insert([
                        'id' => (string) str()->ulid(),
                        'user_id' => $contact->user_id,
                        'contact_id' => $contact->id,
                        'kind' => $detail['kind'] ?? 'custom',
                        'label' => $detail['label'] ?? null,
                        'value' => $detail['value'],
                        'sort_order' => $detail['sort_order'] ?? ($index + 1),
                        'meta' => isset($detail['meta']) ? json_encode($detail['meta']) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ctr_contents') && Schema::hasColumn('ctr_contents', 'is_expert')) {
            Schema::table('ctr_contents', function (Blueprint $table) {
                $table->dropColumn('is_expert');
            });
        }

        Schema::dropIfExists('ctr_details');

        if (Schema::hasTable('ctr_contacts')) {
            Schema::table('ctr_contacts', function (Blueprint $table) {
                if (Schema::hasColumn('ctr_contacts', 'met_precision')) {
                    $table->dropColumn('met_precision');
                }
                if (Schema::hasColumn('ctr_contacts', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
                if (Schema::hasColumn('ctr_contacts', 'is_pinned')) {
                    $table->dropColumn('is_pinned');
                }
                if (Schema::hasColumn('ctr_contacts', 'avatar_url')) {
                    $table->dropColumn('avatar_url');
                }
            });
        }
    }
};

