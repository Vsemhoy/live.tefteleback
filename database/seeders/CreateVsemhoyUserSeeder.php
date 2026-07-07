<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateVsemhoyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('DEV_USER_EMAIL', 'test@example.com')],
            [
                'name' => env('DEV_USER_NAME', 'Test User'),
                'password' => Hash::make(env('DEV_USER_PASSWORD', 'password')),
                'status' => User::STATUS_ACTIVE,
            ]
        );
    }
}
