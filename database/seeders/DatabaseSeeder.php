<?php

namespace Database\Seeders;

use App\Models\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('users')->insert([[
            'id' => 1,
            'name' => "松井英之",
            'email' => "atagohan@yahoo.co.jp",
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ],[
            'id' => 2,
            'name' => "関孝和",
            'email' => "seki@yahoo.co.jp",
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ],[
            'id' => 3,
            'name' => "渋川春海",
            'email' => "haruumi@yahoo.co.jp",
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]]);
    }
}
