<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dox_current_rows', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('row_id', 26)->index();
            $table->tinyInteger('type')->default('1');
            $table->timestamps();
        });


        Schema::create('dox_row_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 26)->index();
            $table->boolean('is_active')->default(1);
            $table->string('title', 26);
        });


        DB::table('dox_row_types')->insert([
            [ 'name' => 'dox_textrow'   ],
            [ 'name' => 'dox_mdrow'     ],
            [ 'name' => 'dox_coderow'   ],
            [ 'name' => 'dox_tablerow'  ],
            [ 'name' => 'dox_includerow'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dox_current_rows');
        Schema::dropIfExists('dox_row_types');
    }
};
