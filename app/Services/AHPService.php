<?php

namespace App\Services;

use App\Models\Application;
use App\Models\KraWeight;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AHPService
{
    // DBM-CHED (2022) KRA Category Point Caps
    protected const KRA_CAPS = [
        'kra1' => [ // Instruction
            'total' => 40, // Excludes teaching effectiveness (QCE)
            'sub_caps' => [
                'instructional_materials' => 30,  // Textbooks, modules, curriculum dev
                'mentorship_services' => 10,      // Thesis advising, student coaching
            ],
            // Note: Teaching effectiveness (student/peer eval) is NOT included here (QCE)
        ],

        'kra2' => [ // Research, Invention, Creative Work
            'total' => 100,
            'sub_caps' => [
                // These are sources of outputs; total capped at 100
                'research_outputs' => 100,        // Publications, papers
                'inventions' => 100,              // Patents, inventions
                'creative_works' => 100,          // Artistic/creative outputs
            ],
            // Note: The combined total of these must NOT exceed 100 points
        ],

        'kra3' => [ // Extension, Service, Outreach
            'total' => 100,
            'sub_caps' => [
                'service_to_institution' => 30,   // Committee work, admin tasks
                'service_to_community' => 50,     // Community outreach, trainings
                'extension_involvement' => 20,    // Extension projects, programs
            ],
            'bonus' => 20, // Optional bonus for admin/leadership roles (added on top of 100)
        ],

        'kra4' => [ // Professional Development, Achievements
            'total' => 100,
            'sub_caps' => [
                'professional_organizations' => 20,   // Memberships, offices held
                'continuing_development' => 60,       // Trainings, seminars, capacity building
                'awards_and_recognitions' => 20,      // Honors, citations
            ],
            'new_hire_bonus' => 20, // Bonus for newly hired faculty (e.g. relevant industry exp)
        ],
    ];

    public const RANK_THRESHOLDS = [
        'Professor V' => 595,
        'Professor IV' => 562,
        'Professor III' => 529,
        'Professor II' => 496,
        'Professor I' => 463,
        'Associate Professor V' => 430,
        'Associate Professor IV' => 397,
        'Associate Professor III' => 364,
        'Associate Professor II' => 331,
        'Associate Professor I' => 298,
        'Assistant Professor IV' => 265,
        'Assistant Professor III' => 232,
        'Assistant Professor II' => 199,
        'Assistant Professor I' => 166,
        'Instructor III' => 133,
        'Instructor II' => 100,
        'Instructor I' => 66,
    ];

    /**
     * Retrieves the official, active KRA weights for a given rank category from the database.
     *
     * @param string $rankCategory The rank category to fetch weights for (e.g., 'Instructor').
     * @return array|null An associative array of the weights or null if not found.
     */
    public function getOfficialWeights(string $rankCategory): ?array
    {
        $weights = KraWeight::where('rank_category', $rankCategory)
            ->where('is_active', true)
            ->first();

        if (!$weights) {
            Log::warning("No active KRA weights found in the database for rank category: {$rankCategory}");
            return null;
        }

        return [
            'kra1' => $weights->kra1_weight,
            'kra2' => $weights->kra2_weight,
            'kra3' => $weights->kra3_weight,
            'kra4' => $weights->kra4_weight,
        ];
    }

    /**
     * Derives the final KRA percentage weights from pairwise comparison values using the Analytic Hierarchy Process (AHP).
     *
     * @param array $comparisons An associative array of the 6 pairwise comparison values from the frontend.
     * @return array The final derived weights for each KRA (e.g., ['kra1' => 0.43, ...]).
     * @throws InvalidArgumentException
     */
    public function deriveWeightsFromComparisons(array $comparisons): array
    {
        // Step 1: Construct the 4x4 pairwise comparison matrix.
        // The matrix represents the relative importance of each KRA against every other KRA.
        $matrix = [
            [1, $comparisons['kra_1_vs_2'], $comparisons['kra_1_vs_3'], $comparisons['kra_1_vs_4']],
            [1 / $comparisons['kra_1_vs_2'], 1, $comparisons['kra_2_vs_3'], $comparisons['kra_2_vs_4']],
            [1 / $comparisons['kra_1_vs_3'], 1 / $comparisons['kra_2_vs_3'], 1, $comparisons['kra_3_vs_4']],
            [1 / $comparisons['kra_1_vs_4'], 1 / $comparisons['kra_2_vs_4'], 1 / $comparisons['kra_3_vs_4'], 1],
        ];

        // Step 2: Calculate the sum of each column in the matrix.
        $columnSums = [0, 0, 0, 0];
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $columnSums[$j] += $matrix[$i][$j];
            }
        }

        // Basic validation to prevent division by zero.
        foreach ($columnSums as $sum) {
            if ($sum == 0) {
                throw new InvalidArgumentException("AHP matrix column sum is zero, cannot normalize.");
            }
        }

        // Step 3: Normalize the matrix by dividing each element by its column sum.
        $normalizedMatrix = [];
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $normalizedMatrix[$i][$j] = $matrix[$i][$j] / $columnSums[$j];
            }
        }

        // Step 4: Calculate the final weights by averaging the values in each row of the normalized matrix.
        $weights = [];
        for ($i = 0; $i < 4; $i++) {
            $weights[$i] = array_sum($normalizedMatrix[$i]) / 4;
        }

        // Return the weights in a clearly labeled associative array.
        return [
            'kra1' => $weights[0],
            'kra2' => $weights[1],
            'kra3' => $weights[2],
            'kra4' => $weights[3],
        ];
    }

    /**
     * Calculates the final CCE Document Score for an application.
     * This is the sum of the capped scores from the four KRAs.
     *
     * @param Application $application The application with pre-aggregated KRA scores.
     * @return float The final CCE Document Score.
     */
    public function calculateCceDocumentScore(Application $application): float
    {
        // Step 1: Get the raw KRA scores from the application model.
        $rawScores = [
            'kra1' => $application->kra1_score ?? 0,
            'kra2' => $application->kra2_score ?? 0,
            'kra3' => $application->kra3_score ?? 0,
            'kra4' => $application->kra4_score ?? 0,
        ];

        // Step 2: Apply the DBM-CHED caps to each KRA total score.
        $cappedScores = [
            'kra1' => min($rawScores['kra1'], self::KRA_CAPS['kra1']['total']),
            'kra2' => min($rawScores['kra2'], self::KRA_CAPS['kra2']['total']),
            'kra3' => min($rawScores['kra3'], self::KRA_CAPS['kra3']['total']),
            'kra4' => min($rawScores['kra4'], self::KRA_CAPS['kra4']['total']),
        ];

        // Step 3: Calculate the final score by summing the capped scores.
        // This is the correct procedure according to NBC 461.
        $finalScore = array_sum($cappedScores);

        return (float) $finalScore;
    }

    /**
     * Determines the highest attainable rank based on a given score.
     * NOTE: This method compares the CCE Document Score (max 340) against the
     * official RANK_THRESHOLDS which are based on the TOTAL score (CCE + other criteria).
     * The result is a preliminary determination and must be contextualized in the UI.
     *
     * @param float $score The CCE Document Score.
     * @return string The name of the highest rank achieved based on the CCE document score alone.
     */
    public function getRankFromScore(float $score): string
    {
        foreach (self::RANK_THRESHOLDS as $rank => $threshold) {
            if ($score >= $threshold) {
                return $rank;
            }
        }

        return "Below 'Instructor I' Threshold";
    }
}
