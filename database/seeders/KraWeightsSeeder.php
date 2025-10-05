<?php

namespace Database\Seeders;

use App\Models\KraWeight;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KraWeightsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Task 5.2: Seed the kra_weights table with the initial official weights using the Eloquent model.

        $initialWeights = [
            [
                'rank_category' => 'Instructor',
                'kra1_weight' => 0.80,
                'kra2_weight' => 0.10,
                'kra3_weight' => 0.05,
                'kra4_weight' => 0.05,
            ],
            [
                'rank_category' => 'Assistant Professor',
                'kra1_weight' => 0.60,
                'kra2_weight' => 0.20,
                'kra3_weight' => 0.10,
                'kra4_weight' => 0.10,
            ],
            [
                'rank_category' => 'Associate Professor',
                'kra1_weight' => 0.40,
                'kra2_weight' => 0.30,
                'kra3_weight' => 0.15,
                'kra4_weight' => 0.15,
            ],
            [
                'rank_category' => 'Professor',
                'kra1_weight' => 0.30,
                'kra2_weight' => 0.40,
                'kra3_weight' => 0.15,
                'kra4_weight' => 0.15,
            ],
        ];

        // Ensure the table is empty before seeding. Using the model is best practice.
        KraWeight::truncate();

        // Loop through the defined weights and insert them using the KraWeight model.
        foreach ($initialWeights as $data) {
            KraWeight::create(array_merge($data, ['is_active' => true]));
        }
    }
}
