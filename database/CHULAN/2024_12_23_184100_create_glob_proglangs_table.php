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



        Schema::create('glob_proglangs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('state')->default('1'); // 1 - active / 0 - disabled
            $table->json('decor')->nullable();
            $table->integer('ordered')->default(0);
            $table->text('icon')->nullable();
            $table->timestamps();
        });

        DB::table('glob_proglangs')->insert([
            ['name' => 'Python', 'state' => 1],
            ['name' => 'JavaScript', 'state' => 1],
            ['name' => 'Java', 'state' => 1],
            ['name' => 'C#', 'state' => 1],
            ['name' => 'C++', 'state' => 1],
            ['name' => 'Ruby', 'state' => 1],
            ['name' => 'PHP', 'state' => 1],
            ['name' => 'Go', 'state' => 1],
            ['name' => 'Swift', 'state' => 1],
            ['name' => 'Kotlin', 'state' => 1],
            ['name' => 'Rust', 'state' => 1],
            ['name' => 'TypeScript', 'state' => 1],
            ['name' => 'Scala', 'state' => 1],
            ['name' => 'Perl', 'state' => 1],
            ['name' => 'Haskell', 'state' => 1],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glob_proglangs');
    }
};
