<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Adrian Cecilia Ogalla',
            'email' => 'adrian.cecilia4@gmail.com',
            'password' => Hash::make(config('app.admin_password')),
        ]);
    }
}
