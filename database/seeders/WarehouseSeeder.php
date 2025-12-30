<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Main Warehouse',
                'address' => 'Main Street',
                'city' => 'Manila',
                'state' => 'Metro Manila',
                'postal_code' => '1000',
                'phone' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Warehouse 1',
                'address' => 'Warehouse District 1',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1100',
                'phone' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Warehouse 2',
                'address' => 'Warehouse District 2',
                'city' => 'Makati',
                'state' => 'Metro Manila',
                'postal_code' => '1200',
                'phone' => null,
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
