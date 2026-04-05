<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{

    protected static function generate_id(string $key, int $length = 25): string
    {
        do {
            $item_id = Str::random($length); // Генерация случайной строки
            $exists = DB::table('pin_algorithms')
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

        Schema::create('pin_algorithms', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 32)->default('Default type');
            $table->string('description', 256)->nullable();
            $table->integer('ordered')->default(0);
            $table->boolean('is_archieved')->default(false);
            $table->enum('type', [
                'ALWAYS',
                'MONTHDATE',
                'DATE',
                'ORDER',
                'WEEKDAY',
                'MONTH',
                'DAYTYPE'
            ])->default('ALWAYS');
        });


    // Добавляем данные
    $algorithms = [
        [
            'id' => self::generate_id('id'),
            'name' => 'Always',
            'type' => 'ALWAYS'
        ],
        [
            'id' => self::generate_id('id'),
            'name' => 'Odd or even',
            'type' => 'ORDER'
        ],
        [
            'id' => self::generate_id('id'),
            'name' => 'Week day',
            'type' => 'WEEKDAY'
        ],
        [
            'id' => self::generate_id('id'),
            'name' => 'Specific date',
            'type' => 'DATE'
        ],
        [
            'id' => self::generate_id('id'),
            'name' => 'Date of the month',
            'type' => 'MONTHDATE'
        ],
        [
            'id' => self::generate_id('id'),
            'name' => 'Month',
            'type' => 'MONTH'
        ],
        [
            'id' => self::generate_id('id'),
            'name' => 'Day type',
            'type' => 'DAYTYPE'
        ]
    ];

    DB::table('pin_algorithms')->insert($algorithms);

        // $data = 
        // [
        //     ['name'=>"Always",'description'=>"", "is_always"=>true],
        //     ['name'=>"Odd or even",'description'=>"","is_order"=>true],

        //     ['name'=>"Week day",'description'=>"","is_dweek"=>true],

        //     ['name'=>"A single date",'description'=>"" ,"is_date"=>true],

        //     ['name'=>"Month",'description'=>"","is_month"=>true],

        // ];     
        

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pin_algorithms');
    }
};

