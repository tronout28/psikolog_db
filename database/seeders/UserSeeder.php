<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert one admin
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'phone_number' => '081234567890',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // List of doctor names
        $doctorNames = [
            'Dr. Rizky Pramata',
            'Dr. Linda Pratiwi',
            'Dr. Sismo Amin Kusumo',
            'Dr. Agus Salim',
            'Dr. Putra Hamid',
        ];

        // Insert doctors
        $doctors = [];
        foreach ($doctorNames as $index => $name) {
            $doctors[] = [
                'name' => $name,
                'email' => 'doctor' . ($index + 1) . '@gmail.com',
                'password' => Hash::make('dokter123'),
                'role' => 'dokter',
                'phone_number' => '08123456789' . ($index + 1),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('users')->insert($doctors);
    }
}
