<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stf_register', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('thing_id')->nullable()->index();

            // â”€â”€ Ð¢Ð¸Ð¿ ÑÐ¾Ð±Ñ‹Ñ‚Ð¸Ñ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $table->enum('event_type', [
                'bought',    // ÐºÑƒÐ¿Ð»ÐµÐ½ (â†’ stored/active)
                'ordered',   // Ð·Ð°ÐºÐ°Ð·Ð°Ð½, ÐµÑ‰Ñ‘ Ð½Ðµ Ð¿Ð¾Ð»ÑƒÑ‡ÐµÐ½
                'received',  // Ð¿Ð¾Ð»ÑƒÑ‡ÐµÐ½ (ordered â†’ active/stored)
                'moved',     // Ð¿ÐµÑ€ÐµÐ¼ÐµÑ‰Ñ‘Ð½ Ð¼ÐµÐ¶Ð´Ñƒ Ð»Ð¾ÐºÐ°Ñ†Ð¸ÑÐ¼Ð¸
                'installed', // ÑƒÑÑ‚Ð°Ð½Ð¾Ð²Ð»ÐµÐ½ Ð²/Ð½Ð° asset
                'lent',      // Ð¾Ð´Ð¾Ð»Ð¶ÐµÐ½ (â†’ lent)
                'returned',  // Ð²Ð¾Ð·Ð²Ñ€Ð°Ñ‰Ñ‘Ð½ Ð¾Ñ‚ Ð¾Ð´Ð¾Ð»Ð¶Ð¸Ð²ÑˆÐµÐ³Ð¾ (â†’ active/stored)
                'sold',      // Ð¿Ñ€Ð¾Ð´Ð°Ð½ (â†’ sold)
                'lost',      // Ð¿Ð¾Ñ‚ÐµÑ€ÑÐ½ (â†’ lost)
                'stolen',    // ÑƒÐºÑ€Ð°Ð´ÐµÐ½ (â†’ lost)
                'disposed',  // Ð²Ñ‹Ð±Ñ€Ð¾ÑˆÐµÐ½/ÑƒÑ‚Ð¸Ð»Ð¸Ð·Ð¸Ñ€Ð¾Ð²Ð°Ð½ (â†’ disposed)
                'repaired',  // Ð¾Ñ‚Ð´Ð°Ð½ Ð² Ñ€ÐµÐ¼Ð¾Ð½Ñ‚ / Ð¿Ð¾Ð»ÑƒÑ‡ÐµÐ½ Ð¸Ð· Ñ€ÐµÐ¼Ð¾Ð½Ñ‚Ð°
                'noted',     // Ð¿Ñ€Ð¾ÑÑ‚Ð¾ Ð·Ð°Ð¼ÐµÑ‚ÐºÐ° Ð±ÐµÐ· ÑÐ¼ÐµÐ½Ñ‹ ÑÑ‚Ð°Ñ‚ÑƒÑÐ°/Ð»Ð¾ÐºÐ°Ñ†Ð¸Ð¸
            ]);
            $table->string('event_kind', 16)->default('work')->index();

            // â”€â”€ ÐŸÐµÑ€ÐµÐ¼ÐµÑ‰ÐµÐ½Ð¸Ðµ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // Nullable: bought Ð½Ðµ Ð¸Ð¼ÐµÐµÑ‚ from, disposed Ð½Ðµ Ð¸Ð¼ÐµÐµÑ‚ to
            $table->ulid('from_location_id')->nullable();
            $table->ulid('to_location_id')->nullable();

            // â”€â”€ Ð”Ð¾Ð¿. Ð¿Ð¾Ð»Ñ Ð´Ð»Ñ lent â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $table->string('contact', 200)->nullable();  // ÐºÐ¾Ð¼Ñƒ Ð¾Ð´Ð¾Ð»Ð¶Ð¸Ð»Ð¸
            $table->date('return_expected')->nullable();  // Ð¾Ð¶Ð¸Ð´Ð°ÐµÐ¼Ñ‹Ð¹ Ð²Ð¾Ð·Ð²Ñ€Ð°Ñ‚

            // â”€â”€ Ð”Ð¾Ð¿. Ð¿Ð¾Ð»Ñ Ð´Ð»Ñ sold/bought â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $table->integer('amount')->nullable(); // ÑÑƒÐ¼Ð¼Ð° Ð² Ð¼Ð¸Ð½Ð¾Ñ€Ð½Ñ‹Ñ… ÐµÐ´Ð¸Ð½Ð¸Ñ†Ð°Ñ…

            // â”€â”€ Ð¡Ð²Ð¾Ð±Ð¾Ð´Ð½Ð°Ñ Ð·Ð°Ð¼ÐµÑ‚ÐºÐ° â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $table->text('note')->nullable();
            $table->json('details')->nullable();

            // Exploiter workflow/priority and rollup cache.
            // Authoritative money lives in led_transactions; authoritative time lives in sys_timer_entries.
            $table->unsignedTinyInteger('status')->nullable()->index();
            $table->unsignedTinyInteger('priority')->nullable()->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->boolean('is_expert')->default(false)->index();
            $table->integer('part_cost')->default(0);
            $table->integer('labor_cost')->default(0);
            $table->integer('time_self_min')->default(0);
            $table->integer('time_service_min')->default(0);

            $table->date('occurred_at');   // Ð´Ð°Ñ‚Ð° ÑÐ¾Ð±Ñ‹Ñ‚Ð¸Ñ (Ð²Ñ‹Ð±Ð¸Ñ€Ð°ÐµÑ‚ ÑŽÐ·ÐµÑ€)
            $table->timestamps();

            // FK â€” intentionally no cascade delete:
            // Ð¸ÑÑ‚Ð¾Ñ€Ð¸Ñ Ñ€ÐµÐ³Ð¸ÑÑ‚Ñ€Ð¾Ð² Ð´Ð¾Ð»Ð¶Ð½Ð° ÑÐ¾Ñ…Ñ€Ð°Ð½ÑÑ‚ÑŒÑÑ Ð´Ð°Ð¶Ðµ ÐµÑÐ»Ð¸ Ð²ÐµÑ‰ÑŒ Ð°Ñ€Ñ…Ð¸Ð²Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð°.
            // ÐŸÑ€Ð¸ soft delete thing'Ð° â€” register Ð¾ÑÑ‚Ð°Ñ‘Ñ‚ÑÑ.
            // from/to location â€” nullOnDelete (Ð»Ð¾ÐºÐ°Ñ†Ð¸Ñ ÑƒÐ´Ð°Ð»ÐµÐ½Ð°, Ð½Ð¾ ÑÐ¾Ð±Ñ‹Ñ‚Ð¸Ðµ Ð±Ñ‹Ð»Ð¾)
            $table->foreign('thing_id')
                  ->references('id')->on('stf_things')
                  ->nullOnDelete();

            $table->foreign('from_location_id')
                  ->references('id')->on('stf_locations')
                  ->nullOnDelete();

            $table->foreign('to_location_id')
                  ->references('id')->on('stf_locations')
                  ->nullOnDelete();

            $table->index(['thing_id', 'occurred_at']);
            $table->index(['status', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stf_register');
    }
};

