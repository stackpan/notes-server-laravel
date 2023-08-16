<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'username' => 'johndoe',
            'email' => 'johndoe@gmail.com',
            'password' => Hash::make('Secret123!'),
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }
}
