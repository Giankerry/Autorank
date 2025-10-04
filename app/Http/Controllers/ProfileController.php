<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page, including their evaluation progress and results.
     */
    public function showProfilePage(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $isOwnProfile = true;

            // --- Initialize variables with defaults ---
            $evaluationProgress = 0;
            $evaluationStatus = 'No Application';
            $chartData = null;

            // --- Fetch all applications for the dropdown selector ---
            $allApplications = $user->applications()
                ->whereIn('status', ['pending evaluation', 'evaluated'])
                ->latest()
                ->get();

            // --- Determine which application to display ---
            $application = null;
            if ($request->has('application_id')) {
                // If an ID is in the URL, find that specific application.
                // We use the collection to ensure the requested app belongs to the logged-in user.
                $application = $allApplications->firstWhere('id', $request->input('application_id'));
            } elseif ($allApplications->isNotEmpty()) {
                // Otherwise, if no ID is specified, default to the most recent application.
                $application = $allApplications->first();
            }

            // If an application is selected (either by default or from the URL), prepare its data.
            if ($application) {
                if ($application->status === 'evaluated') {
                    $evaluationProgress = 100;
                    $evaluationStatus = 'Completed';

                    // Prepare Chart Data for the selected application
                    $chartData = [
                        'kra1_capped' => min($application->kra1_score ?? 0, 40),
                        'kra2_capped' => min($application->kra2_score ?? 0, 100),
                        'kra3_capped' => min($application->kra3_score ?? 0, 100),
                        'kra4_capped' => min($application->kra4_score ?? 0, 100),
                    ];
                } else { // Status is 'pending evaluation'
                    $evaluationStatus = 'In Progress';

                    $application->loadCount([
                        'instructions',
                        'researches',
                        'extensions',
                        'professionalDevelopments',
                        'instructions as instructions_scored_count' => fn($q) => $q->whereNotNull('score'),
                        'researches as researches_scored_count' => fn($q) => $q->whereNotNull('score'),
                        'extensions as extensions_scored_count' => fn($q) => $q->whereNotNull('score'),
                        'professionalDevelopments as professional_developments_scored_count' => fn($q) => $q->whereNotNull('score'),
                    ]);

                    $totalSubmissions = $application->instructions_count + $application->researches_count + $application->extensions_count + $application->professional_developments_count;
                    $scoredSubmissions = $application->instructions_scored_count + $application->researches_scored_count + $application->extensions_scored_count + $application->professional_developments_scored_count;

                    if ($totalSubmissions > 0) {
                        $evaluationProgress = round(($scoredSubmissions / $totalSubmissions) * 100);
                    }
                }
            }

            // Fetch theme and color settings
            $primaryColor = Setting::where('key', 'primary_color')->value('value');
            $theme = Auth::check() ? Auth::user()->theme : 'light';

            return view('profile-page', [
                'user' => $user,
                'isOwnProfile' => $isOwnProfile,
                'evaluationProgress' => $evaluationProgress,
                'evaluationStatus' => $evaluationStatus,
                'application' => $application,
                'allApplications' => $allApplications,
                'chartData' => $chartData,
                'primaryColor' => $primaryColor,
                'theme' => $theme,
            ]);
        } else {
            return redirect()->route('signin-page')->with('error', 'You must be logged in to view your profile.');
        }
    }

    /**
     * Fetch data for a single application via API.
     *
     * @param Application $application
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApplicationData(Application $application): JsonResponse
    {
        // 1. Security Check: Ensure the requested application belongs to the authenticated user.
        if (Auth::id() !== $application->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // 2. Prepare Data
        $evaluationProgress = 0;
        $evaluationStatus = 'Not Started';
        $chartData = null;

        if ($application->status === 'evaluated') {
            $evaluationProgress = 100;
            $evaluationStatus = 'Completed';
            $chartData = [
                'kra1_capped' => min($application->kra1_score ?? 0, 40),
                'kra2_capped' => min($application->kra2_score ?? 0, 100),
                'kra3_capped' => min($application->kra3_score ?? 0, 100),
                'kra4_capped' => min($application->kra4_score ?? 0, 100),
            ];
        } else {
            $evaluationStatus = 'In Progress';
            $application->loadCount([
                'instructions',
                'researches',
                'extensions',
                'professionalDevelopments',
                'instructions as instructions_scored_count' => fn($q) => $q->whereNotNull('score'),
                'researches as researches_scored_count' => fn($q) => $q->whereNotNull('score'),
                'extensions as extensions_scored_count' => fn($q) => $q->whereNotNull('score'),
                'professionalDevelopments as professional_developments_scored_count' => fn($q) => $q->whereNotNull('score'),
            ]);
            $totalSubmissions = $application->instructions_count + $application->researches_count + $application->extensions_count + $application->professional_developments_count;
            $scoredSubmissions = $application->instructions_scored_count + $application->researches_scored_count + $application->extensions_scored_count + $application->professional_developments_scored_count;
            if ($totalSubmissions > 0) {
                $evaluationProgress = round(($scoredSubmissions / $totalSubmissions) * 100);
            }
        }

        $data = [
            'evaluationStatus' => $evaluationStatus,
            'evaluationProgress' => $evaluationProgress,
            'highestAttainableRank' => $application->highest_attainable_rank,
            'chartData' => $chartData,
            'status' => $application->status,
        ];

        return response()->json($data);
    }
}
