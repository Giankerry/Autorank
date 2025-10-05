<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\User;
use App\Services\AHPService;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Ensure the basic 'user' role exists
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Fetch rank names from the AHPService to ensure consistency
        $facultyRanks = array_keys(AHPService::RANK_THRESHOLDS);

        // Create 50 sample faculty members
        for ($i = 0; $i < 50; $i++) {
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'), // Set a default password for all test users
                'faculty_rank' => $faker->randomElement($facultyRanks),
            ]);

            $user->assignRole($userRole);

            // Create an application for each user
            $this->createApplicationForUser($user, $faker);
        }
    }

    /**
     * Create a sample application with realistic data for a given user.
     *
     * @param User $user
     * @param \Faker\Generator $faker
     * @return void
     */
    private function createApplicationForUser(User $user, $faker): void
    {
        // Randomly decide if the application should be pending or evaluated
        $status = $faker->boolean(75) ? 'evaluated' : 'pending evaluation'; // 75% chance of being evaluated

        $kra1_score = 0;
        $kra2_score = 0;
        $kra3_score = 0;
        $kra4_score = 0;
        $final_score = 0;

        if ($status === 'evaluated') {
            // Generate realistic random scores. These can exceed the caps.
            $kra1_score = $faker->numberBetween(10, 50);  // Cap is 40
            $kra2_score = $faker->numberBetween(30, 120); // Cap is 100
            $kra3_score = $faker->numberBetween(20, 110); // Cap is 100
            $kra4_score = $faker->numberBetween(25, 115); // Cap is 100

            // Calculate the final score based on the official caps, mimicking the real calculation
            $final_score = min($kra1_score, 40) + min($kra2_score, 100) + min($kra3_score, 100) + min($kra4_score, 100);
        }

        Application::create([
            'user_id' => $user->id,
            'status' => $status,
            'kra1_score' => $kra1_score,
            'kra2_score' => $kra2_score,
            'kra3_score' => $kra3_score,
            'kra4_score' => $kra4_score,
            'final_score' => $final_score,
            'evaluation_cycle' => '2025-2026',
            'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }
}
