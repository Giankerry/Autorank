<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FacultyRank;

class FacultyRankSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            ['rank_name' => 'Instructor I', 'level' => 1],
            ['rank_name' => 'Instructor II', 'level' => 2],
            ['rank_name' => 'Instructor III', 'level' => 3],
            ['rank_name' => 'Assistant Professor I', 'level' => 4],
            ['rank_name' => 'Assistant Professor II', 'level' => 5],
            ['rank_name' => 'Assistant Professor III', 'level' => 6],
            ['rank_name' => 'Assistant Professor IV', 'level' => 7],
            ['rank_name' => 'Associate Professor I', 'level' => 8],
            ['rank_name' => 'Associate Professor II', 'level' => 9],
            ['rank_name' => 'Associate Professor III', 'level' => 10],
            ['rank_name' => 'Associate Professor IV', 'level' => 11],
            ['rank_name' => 'Associate Professor V', 'level' => 12],
            ['rank_name' => 'Professor I', 'level' => 13],
            ['rank_name' => 'Professor II', 'level' => 14],
            ['rank_name' => 'Professor III', 'level' => 15],
            ['rank_name' => 'Professor IV', 'level' => 16],
            ['rank_name' => 'Professor V', 'level' => 17],
            ['rank_name' => 'Professor VI', 'level' => 18],
        ];

        foreach ($ranks as $rank) {
            FacultyRank::create($rank);
        }
    }
}
