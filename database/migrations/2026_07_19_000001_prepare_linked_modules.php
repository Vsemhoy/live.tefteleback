<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stf_things', function (Blueprint $table) {
            $table->char('last_category_id', 26)->nullable()->after('category_id')->index();
        });

        Schema::table('stf_register', function (Blueprint $table) {
            $table->char('performer_contact_id', 26)->nullable()->after('contact')->index();
        });

        Schema::table('evt_events', function (Blueprint $table) {
            $table->char('thing_id', 26)->nullable()->after('exploiter_event_id')->index();
        });

        Schema::create('ctr_contact_origins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('contact_id')->index();
            $table->string('origin_type', 32);
            $table->char('origin_id', 26)->nullable();
            $table->timestamps();
            $table->unique(['contact_id', 'origin_type', 'origin_id'], 'ctr_contact_origins_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ctr_contact_origins');
        Schema::table('evt_events', fn (Blueprint $table) => $table->dropColumn('thing_id'));
        Schema::table('stf_register', fn (Blueprint $table) => $table->dropColumn('performer_contact_id'));
        Schema::table('stf_things', fn (Blueprint $table) => $table->dropColumn('last_category_id'));
    }
};
