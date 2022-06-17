<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
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
            "username" => "adminrs",
            "phone" => "089529909036",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Admin Super');
        $user = User::create([
            "name" => "Marwan Dhiaur Rahman",
            "email" => "marwandhiaurrahman@gmail.com",
            "username" => "marwan",
            "phone" => "089529909036",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Admin Super');
    }
}
