<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            "name" => "Admin",
            "email" => "admin@gmail.com",
            // "username" => "adminrs",
            'password' => bcrypt('qweqwe'),
            // 'email_verified_at' => Carbon::now()
        ]);
    }
}
