<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{

    protected static function generate_type_id(string $key, int $length = 26): string
    {
        do {
            $item_id = Str::random($length); // Генерация случайной строки
            $exists = DB::table('evt_types')
                ->where($key, '=', $item_id)
                ->exists(); // Проверка на существование
        } while ($exists); // Продолжаем генерировать до тех пор, пока не получим уникальный ID
        return $item_id; //Return the generated id as it does not exist in the DB
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {

        if (!DB::table('users')->where('id', 'SYSTEM00000000000000000000')->exists()) {
        DB::table('users')->insert([
            'id'=>'SYSTEM00000000000000000000',
            'name' => 'SYSTEM',
            'email' => 'system@system.sy',
            'password' => '$2y$12$LRF4b/HvRAlbJWc/NJvewOqFnA28WTjT1CqhSREHjynJYMalkfYTK'        
        ]);
    };

    // Добавляем данные
    $newtypes = [
        [
            'id' => "SYSLWI7GB9DA6DIQKRNOGN843Y",
            'user_id' => 'SYSTEM00000000000000000000',
            'name' => 'Event',
            'color' => "#666",
            'bgcolor' => "#76c38530",
            'sort_order' => 1000000000000,
            'icon' => "calendar-event",
            'is_default'=> true
        ],
        [
            'id' => "SYSGZUK78N5JMADYM5RZJTYABM",
            'user_id' => 'SYSTEM00000000000000000000',
            'name' => 'Action',
            'color' => "#666",
            'bgcolor' => "#d1b50030",
            'sort_order' => 1000000000000,
            'icon' => "bolt",
            'is_default'=> true
        ],
        [
            'id' => "SYS3THGLH8SI1XX5R3JV4FLUU2",
            'user_id' => 'SYSTEM00000000000000000000',
            'name' => 'Note',
            'color' => "#666",
            'bgcolor' => "#9c27b029",
            'sort_order' => 3000000000000,
            'icon' => "pencil",
            'is_default'=> true
        ],
        [
            'id' => "SYSKERK4XVKQ04CNS93331JUNH",
            'user_id' => 'SYSTEM00000000000000000000',
            'name' => 'Task',
            'color' => "#666",
            'bgcolor' => "#ff960036",
            'sort_order' => 4000000000000,
            'icon' => "checklist",
            'is_default'=> true
        ],
        [
            'id' => "SYSC571A0QCBW9VUNQKDIGQWLU",
            'user_id' => 'SYSTEM00000000000000000000',
            'name' => 'Synopsis',
            'color' => "#666",
            'bgcolor' => "#00abff3b",
            'sort_order' => 5000000000000,
            'icon' => "book-2",
            'is_default'=> true
        ],
        [
            'id' => "SYSUMZRE5C7FD2LKLF8H09P460",
            'user_id' => 'SYSTEM00000000000000000000',
            'name' => 'Info',
            'color' => "#666",
            'bgcolor' => "#00d9cd54",
            'sort_order' => 6000000000000,
            'icon' => "info-circle",
            'is_default'=> true
        ],
        [
            'id' => "SYSLWI7GB9DA6DIQKRNOGSTATE",
            'user_id' => 'SYSTEM00000000000000000000',
            'name' => 'State',
            'color' => "#4a90a4",
            'bgcolor' => "#7eb8d430",
            'sort_order' => 1000000000004,
            'icon' => "dashboard",
            'is_default' => true
        ],

    ];

    DB::table('evt_types')->insert($newtypes);

    // DB::table('evt_sections')->insert([
    // [
    //     'id' => 'SYSTEM0000000000000000000',
    //     'user_id'=>'SYSTEM0000000000000000000',
    //     'name' => 'Default Section',
    //     'description' => 'Default section for all users',
    //     'literals' => 'DEF',
    //     'is_default'=> true
    //     ]
    // ]);
        

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DB::table('evt_sections')->where('id', 'SYSTEM0000000000000000000')->delete();
        DB::table('evt_types')->where('is_default', true)->delete();
        DB::table('users')->where('id', 'SYSTEM0000000000000000000')->delete();
    }
};
