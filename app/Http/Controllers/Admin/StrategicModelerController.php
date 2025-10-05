<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FacultyRank;
use App\Services\AHPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;
use Illuminate\Support\Str;

class StrategicModelerController extends Controller
{
    /**
     * Display the strategic modeler page.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        return view('admin.modeler');
    }

    /**
     * Run the in-memory "what-if" simulation based on AHP inputs.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\AHPService $ahpService
     * @return \Illuminate\Http\JsonResponse
     */
    public function runSimulation(Request $request, AHPService $ahpService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rank_category' => 'required|string',
            'comparisons' => 'required|array',
            'comparisons.kra_1_vs_2' => 'required|numeric',
            'comparisons.kra_1_vs_3' => 'required|numeric',
            'comparisons.kra_1_vs_4' => 'required|numeric',
            'comparisons.kra_2_vs_3' => 'required|numeric',
            'comparisons.kra_2_vs_4' => 'required|numeric',
            'comparisons.kra_3_vs_4' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $simulatedWeights = $ahpService->deriveWeightsFromComparisons($request->input('comparisons'));
            $targetRankCategory = $request->input('rank_category');

            $applications = Application::where('status', 'evaluated')
                ->with('user')
                ->whereHas('user', function ($query) use ($targetRankCategory) {
                    $query->where('faculty_rank', 'like', $targetRankCategory . '%');
                })
                ->get();

            if ($applications->isEmpty()) {
                return response()->json(['message' => 'No evaluated applications found for the selected rank category.'], 404);
            }

            // Fetch all faculty ranks and create a lookup map for their levels.
            $rankLevels = FacultyRank::all()->pluck('level', 'rank_name')->all();

            $totalDocumentPoints = 340;
            $simulationResults = [];
            $allScores = [];
            foreach ($applications as $application) {

                $weightsToUse = $simulatedWeights;

                $cappedScores = [
                    'kra1' => min($application->kra1_score ?? 0, 40),
                    'kra2' => min($application->kra2_score ?? 0, 100),
                    'kra3' => min($application->kra3_score ?? 0, 100),
                    'kra4' => min($application->kra4_score ?? 0, 100),
                ];

                $simulatedScore =
                    ($cappedScores['kra1'] * $weightsToUse['kra1']) +
                    ($cappedScores['kra2'] * $weightsToUse['kra2']) +
                    ($cappedScores['kra3'] * $weightsToUse['kra3']) +
                    ($cappedScores['kra4'] * $weightsToUse['kra4']);

                $scaledSimulatedScore = $simulatedScore * ($totalDocumentPoints / 100);
                $allScores[] = $scaledSimulatedScore;

                $currentRank = $application->user->faculty_rank;
                $simulatedRank = $ahpService->getRankFromScore($scaledSimulatedScore);

                // Use the rank levels for accurate comparison.
                $currentRankLevel = $rankLevels[$currentRank] ?? 0;
                $simulatedRankLevel = $rankLevels[$simulatedRank] ?? 0;

                $changeType = 'no_change';
                if ($simulatedRankLevel > $currentRankLevel) {
                    $changeType = 'promoted';
                } elseif ($simulatedRankLevel < $currentRankLevel) {
                    $changeType = 'demoted';
                }

                $simulationResults[] = [
                    'name' => $application->user->name,
                    'current_rank' => $currentRank,
                    'simulated_rank' => $simulatedRank,
                    'score_change' => $scaledSimulatedScore - $application->final_score,
                    'change_type' => $changeType,
                ];
            }

            $response = $this->compileFrontendResponse($simulationResults, $allScores);

            return response()->json($response);
        } catch (InvalidArgumentException $e) {
            Log::error('AHP Calculation Error: ' . $e->getMessage());
            return response()->json(['message' => 'A critical error occurred during calculation. Please check the inputs.'], 400);
        } catch (\Exception $e) {
            Log::error('Simulation Error: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected server error occurred.'], 500);
        }
    }

    /**
     * Compiles the simulation data into the format expected by the frontend.
     *
     * @param array $results
     * @param array $allScores
     * @return array
     */
    private function compileFrontendResponse(array $results, array $allScores): array
    {
        $totalPromoted = count(array_filter($results, fn($r) => $r['change_type'] === 'promoted'));
        $highestImpact = collect($results)->sortByDesc('score_change')->first();

        $kpis = [
            'total_promoted' => $totalPromoted,
            'new_average_score' => count($allScores) > 0 ? array_sum($allScores) / count($allScores) : 0,
            'highest_impact_faculty' => $highestImpact['name'] ?? 'N/A',
        ];

        $rankCategories = array_keys(AHPService::RANK_THRESHOLDS);
        $currentDist = array_fill_keys($rankCategories, 0);
        $simulatedDist = array_fill_keys($rankCategories, 0);

        foreach ($results as $result) {
            if (isset($currentDist[$result['current_rank']])) {
                $currentDist[$result['current_rank']]++;
            }
            if (isset($simulatedDist[$result['simulated_rank']])) {
                $simulatedDist[$result['simulated_rank']]++;
            }
        }

        $chartData = [
            'categories' => $rankCategories,
            'current' => array_values($currentDist),
            'simulated' => array_values($simulatedDist),
        ];

        $tableData = array_map(function ($result) {
            $changeText = 'No Change';
            if ($result['change_type'] === 'promoted') {
                $changeText = 'Promoted';
            } elseif ($result['change_type'] === 'demoted') {
                $changeText = 'Demoted';
            }

            return [
                'name' => $result['name'],
                'current_rank' => $result['current_rank'],
                'simulated_rank' => $result['simulated_rank'],
                'change_type' => $result['change_type'],
                'change_text' => $changeText,
            ];
        }, $results);

        return [
            'kpis' => $kpis,
            'chart_data' => $chartData,
            'table_data' => collect($tableData)->sortBy('name')->values()->all(),
        ];
    }

    /**
     * Helper method to determine the rank category from a specific rank title.
     * e.g., "Associate Professor III" -> "Associate Professor"
     *
     * @param string $rank
     * @return string
     */
    private function getRankCategory(string $rank): string
    {
        if (Str::startsWith($rank, 'Professor')) return 'Professor';
        if (Str::startsWith($rank, 'Associate Professor')) return 'Associate Professor';
        if (Str::startsWith($rank, 'Assistant Professor')) return 'Assistant Professor';
        if (Str::startsWith($rank, 'Instructor')) return 'Instructor';

        // Default fallback
        return 'Instructor';
    }
}
