<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BukuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bukus')->insert([
            'title' => 'Panduan Kesehatan dan Gizi Seimbang',
            'description' => 'Buku ini memberikan panduan lengkap tentang kesehatan, nutrisi, dan pola hidup sehat.',
            'price' => 120000,
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
