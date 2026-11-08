<?php

namespace Database\Seeders;

use App\Models\Pump;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PumpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $petrol = Product::where('name', 'like', '%Super Petrol%')->first();
        $diesel = Product::where('name', 'like', '%Diesel%')->first();
        $octane = Product::where('name', 'like', '%Octane%')->first();
        $cng = Product::where('name', 'like', '%CNG%')->first();

        $pumps = [
            [
                'name' => 'Pump Machine 1 (Super Petrol)',
                'code' => 'PUMP-01',
                'nozzle_number' => 'Nozzle 1 & 2',
                'meter_type' => 'electronic',
                'product_id' => $petrol?->id,
                'tank_no' => 'Underground Tank #1',
                'initial_reading' => 10500.00,
                'last_reading' => 10500.00,
                'status' => 'active',
                'description' => 'Dual nozzle dispenser for Super Petrol (RON 92).',
            ],
            [
                'name' => 'Pump Machine 2 (High Speed Diesel)',
                'code' => 'PUMP-02',
                'nozzle_number' => 'Nozzle 3 & 4',
                'meter_type' => 'electronic',
                'product_id' => $diesel?->id,
                'tank_no' => 'Underground Tank #2',
                'initial_reading' => 18200.00,
                'last_reading' => 18200.00,
                'status' => 'active',
                'description' => 'High speed commercial dispenser for HSD trucks and buses.',
            ],
            [
                'name' => 'Pump Machine 3 (Hi-Octane 97)',
                'code' => 'PUMP-03',
                'nozzle_number' => 'Nozzle 5',
                'meter_type' => 'electronic',
                'product_id' => $octane?->id,
                'tank_no' => 'Underground Tank #3',
                'initial_reading' => 4500.00,
                'last_reading' => 4500.00,
                'status' => 'active',
                'description' => 'High Octane HOBC dispenser for performance cars.',
            ],
            [
                'name' => 'Pump Machine 4 (CNG Dispenser)',
                'code' => 'PUMP-04',
                'nozzle_number' => 'High-Pressure Nozzle 1',
                'meter_type' => 'electronic',
                'product_id' => $cng?->id,
                'tank_no' => 'Compressor Station #1',
                'initial_reading' => 3100.00,
                'last_reading' => 3100.00,
                'status' => 'active',
                'description' => 'Electronic high-pressure CNG dispenser.',
            ],
        ];

        foreach ($pumps as $pump) {
            Pump::updateOrCreate(
                ['name' => $pump['name']],
                $pump
            );
        }
    }
}
