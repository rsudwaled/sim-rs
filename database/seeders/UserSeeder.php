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
            "username" => "admin",
            "phone" => "089529909036",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Admin');

        $user = User::create([
            "name" => "Admin Super",
            "email" => "adminrs@gmail.com",
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

        $user = User::create([
            "name" => "Admin Pendaftaran",
            "email" => "adminpendaftaran@gmail.com",
            "username" => "adminpendaftaran",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Pendaftaran');

        $user = User::create([
            "name" => "Admin Antrian BPJS",
            "email" => "antrianbpjs@gmail.com",
            "username" => "antrianbpjs",
            'password' => bcrypt('antrianbpjs'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Pendaftaran');

        $user = User::create([
            "name" => "Admin Kasir",
            "email" => "adminkasir@gmail.com",
            "username" => "adminkasir",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Kasir');

        $user = User::create([
            "name" => "Admin Poliklinik",
            "email" => "adminpoliklinik@gmail.com",
            "username" => "adminpoliklinik",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Poliklinik');

        $user = User::create([
            "name" => "Admin Farmasi",
            "email" => "adminfarmasi@gmail.com",
            "username" => "adminfarmasi",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Farmasi');

        $user = User::create([
            "name" => "Admin Pelayanan Medis",
            "email" => "adminyanmed@gmail.com",
            "username" => "adminyanmed",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Pelayanan Medis');

        $user = User::create([
            "name" => "Admin Rekam Medis",
            "email" => "adminrekammedis@gmail.com",
            "username" => "adminrekammedis",
            'password' => bcrypt('qweqwe'),
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Rekam Medis');
    }
}
