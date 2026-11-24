<?php

namespace Database\Seeders;

use App\Models\Courier;
use Illuminate\Database\Seeder;

class CourierSeeder extends Seeder
{
    /**
     * Isi kurir contoh yang tetap (dipakai di contoh README dan Postman) ditambah kurir acak.
     */
    public function run(): void
    {
        $demoCouriers = [
            ['name' => 'Budiono Hadi Agung', 'phone' => '081200000001', 'email' => 'budiono@example.com', 'level' => 3, 'registered_at' => '2025-03-10'],
            ['name' => 'Budi Santoso', 'phone' => '081200000002', 'email' => 'budi.santoso@example.com', 'level' => 2, 'registered_at' => '2024-11-02'],
            ['name' => 'Agung Prasetyo', 'phone' => '081200000003', 'email' => 'agung@example.com', 'level' => 5, 'registered_at' => '2025-07-21'],
            ['name' => 'Siti Rahmawati', 'phone' => '081200000004', 'email' => null, 'level' => 1, 'registered_at' => '2026-01-15'],
        ];

        foreach ($demoCouriers as $demoCourier) {
            Courier::factory()->create($demoCourier);
        }

        Courier::factory()->count(20)->create();
    }
}
