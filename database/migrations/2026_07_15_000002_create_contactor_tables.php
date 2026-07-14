<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ctr_contacts')) {
            Schema::create('ctr_contacts', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26)->index();
                $table->string('name', 160);
                $table->string('nickname', 120)->nullable();
                $table->string('group', 32)->default('friends')->index();
                $table->string('role', 160)->nullable();
                $table->string('company', 160)->nullable();
                $table->string('avatar', 512)->nullable();
                $table->date('met_at')->nullable()->index();
                $table->string('met_context', 255)->nullable();
                $table->timestamp('last_contact_at')->nullable()->index();
                $table->json('details')->nullable();
                $table->boolean('is_archived')->default(false)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'is_archived', 'group'], 'ctr_contacts_user_group_idx');
                $table->index(['user_id', 'last_contact_at'], 'ctr_contacts_user_last_idx');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('ctr_contents')) {
            Schema::create('ctr_contents', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26)->index();
                $table->char('contact_id', 26)->index();
                $table->string('kind', 32)->default('note')->index();
                $table->timestamp('occurred_at')->nullable()->index();
                $table->string('title', 255)->nullable();
                $table->longText('body_md')->nullable();
                $table->boolean('is_pinned')->default(false)->index();
                $table->char('eventor_event_id', 26)->nullable()->index();
                $table->char('stuffer_register_id', 26)->nullable()->index();
                $table->char('exploiter_event_id', 26)->nullable()->index();
                $table->json('meta')->nullable();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'occurred_at'], 'ctr_contents_user_time_idx');
                $table->index(['contact_id', 'occurred_at'], 'ctr_contents_contact_time_idx');
                $table->index(['user_id', 'kind'], 'ctr_contents_user_kind_idx');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
                $table->foreign('contact_id')
                    ->references('id')
                    ->on('ctr_contacts')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('ctr_relations')) {
            Schema::create('ctr_relations', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('user_id', 26)->index();
                $table->char('contact_a_id', 26)->index();
                $table->char('contact_b_id', 26)->index();
                $table->string('kind', 32)->default('friend')->index();
                $table->string('context', 255)->nullable();
                $table->date('valid_from')->nullable()->index();
                $table->date('valid_to')->nullable()->index();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'kind'], 'ctr_relations_user_kind_idx');
                $table->index(['contact_a_id', 'contact_b_id'], 'ctr_relations_pair_idx');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
                $table->foreign('contact_a_id')
                    ->references('id')
                    ->on('ctr_contacts')
                    ->onDelete('cascade');
                $table->foreign('contact_b_id')
                    ->references('id')
                    ->on('ctr_contacts')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ctr_relations');
        Schema::dropIfExists('ctr_contents');
        Schema::dropIfExists('ctr_contacts');
    }
};