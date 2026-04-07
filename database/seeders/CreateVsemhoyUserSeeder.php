<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateVsemhoyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Vsemhoy',
            'email' => 'vsemhoy@live.ru',
            'password' => Hash::make('xxxxxxxxx'), // Используем Hash::make (без скобок в конце!)
            'status' => 1,
        ]);
    }
}
