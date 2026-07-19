<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fct_facts') && Schema::hasColumn('fct_facts', 'search_aliases') && ! Schema::hasColumn('fct_facts', 'search_keywords')) {
            Schema::table('fct_facts', function (Blueprint $table) {
                $table->renameColumn('search_aliases', 'search_keywords');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fct_facts') && Schema::hasColumn('fct_facts', 'search_keywords') && ! Schema::hasColumn('fct_facts', 'search_aliases')) {
            Schema::table('fct_facts', function (Blueprint $table) {
                $table->renameColumn('search_keywords', 'search_aliases');
            });
        }
    }
};