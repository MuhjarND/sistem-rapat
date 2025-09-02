<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin
        DB::table('users')->insert([
            'name' => 'Admin Sistem',
            'email' => 'admin@sistemrapat.test',
            'password' => Hash::make('password'), // Ubah password untuk production
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Notulis
        DB::table('users')->insert([
            'name' => 'Notulis Satu',
            'email' => 'notulis@sistemrapat.test',
            'password' => Hash::make('password'),
            'role' => 'notulis',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Peserta
        DB::table('users')->insert([
            'name' => 'Peserta Satu',
            'email' => 'peserta@sistemrapat.test',
            'password' => Hash::make('password'),
            'role' => 'peserta',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}

