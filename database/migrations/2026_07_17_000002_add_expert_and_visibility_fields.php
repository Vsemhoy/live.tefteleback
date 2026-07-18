<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evt_events')) {
            Schema::table('evt_events', function (Blueprint $table) {
                if (! Schema::hasColumn('evt_events', 'is_expert')) {
                    $table->boolean('is_expert')->default(false)->index()->after('is_pinned');
                }
            });
        }

        if (Schema::hasTable('led_accounts')) {
            Schema::table('led_accounts', function (Blueprint $table) {
                if (! Schema::hasColumn('led_accounts', 'is_expert')) {
                    $table->boolean('is_expert')->default(false)->index()->after('is_archived');
                }
            });
        }

        if (Schema::hasTable('led_transactions')) {
            Schema::table('led_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('led_transactions', 'is_expert')) {
                    $table->boolean('is_expert')->default(false)->index()->after('is_pinned');
                }
            });
        }

        if (Schema::hasTable('stf_things')) {
            Schema::table('stf_things', function (Blueprint $table) {
                if (! Schema::hasColumn('stf_things', 'visibility')) {
                    $table->string('visibility', 16)->default('private')->index()->after('track_lifecycle');
                }
                if (! Schema::hasColumn('stf_things', 'is_expert')) {
                    $table->boolean('is_expert')->default(false)->index()->after('visibility');
                }
            });
        }

        if (Schema::hasTable('stf_register')) {
            Schema::table('stf_register', function (Blueprint $table) {
                if (! Schema::hasColumn('stf_register', 'is_expert')) {
                    $table->boolean('is_expert')->default(false)->index()->after('is_pinned');
                }
            });
        }

        if (Schema::hasTable('stf_expenses')) {
            Schema::table('stf_expenses', function (Blueprint $table) {
                if (! Schema::hasColumn('stf_expenses', 'is_expert')) {
                    $table->boolean('is_expert')->default(false)->index()->after('amount');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ([
            ['stf_expenses', 'is_expert'],
            ['stf_register', 'is_expert'],
            ['stf_things', 'is_expert'],
            ['stf_things', 'visibility'],
            ['led_transactions', 'is_expert'],
            ['led_accounts', 'is_expert'],
            ['evt_events', 'is_expert'],
        ] as [$tableName, $columnName]) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $columnName)) {
                Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                    $table->dropColumn($columnName);
                });
            }
        }
    }
};