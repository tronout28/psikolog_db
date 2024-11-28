<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class PaketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users with role 'dokter'
        $doctors = DB::table('users')->where('role', 'dokter')->get();

        // Define paket types and their prices
        $paketTypes = [
            ['paket_type' => '3day', 'price' => 50000],
            ['paket_type' => '7day', 'price' => 100000],
            ['paket_type' => '30day', 'price' => 300000],
            ['paket_type' => 'realtime', 'price' => 1000000],
        ];

        // Insert pakets for each doctor
        foreach ($doctors as $doctor) {
            foreach ($paketTypes as $paket) {
                DB::table('pakets')->insert([
                    'user_id' => $doctor->id,
                    'title' => $paket['paket_type'] . ' package for ' . $doctor->name,
                    'paket_type' => $paket['paket_type'],
                    'price' => $paket['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
