<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;

class ApplyController extends Controller
{
    /**
     * Performs a pre-check to see if the user's rank is set and if they have submissions for all KRAs.
     * This is intended to be called via AJAX.
     */
    public function checkSubmissions(Request $request)
    {
        $user = Auth::user();

        // Pre-Check: Validate that the user has a rank assigned.
        if (is_null($user->faculty_rank) || trim($user->faculty_rank) === '' || trim($user->faculty_rank) === 'Unset') {
            return response()->json([
                'success' => false,
                'error_type' => 'rank_missing',
                'message' => 'You do not have a faculty rank assigned. Please contact the system administrator to have your rank validated and set before submitting your CCE documents.'
            ]);
        }

        $missing = [];

        // Find the user's current draft application
        $draftApplication = $user->applications()->where('status', 'draft')->first();

        if (!$draftApplication) {
            // If there's no draft application, it means they haven't uploaded anything yet for this cycle.
            $missing = [
                ['name' => 'KRA I: Instruction', 'route' => route('instructor.instructional-page')],
                ['name' => 'KRA II: Research', 'route' => route('instructor.research-page')],
                ['name' => 'KRA III: Extension', 'route' => route('instructor.extension-page')],
                ['name' => 'KRA IV: Professional Development', 'route' => route('instructor.professional-development-page')],
            ];
            return response()->json(['success' => false, 'missing' => $missing]);
        }

        // Check for submissions linked to the specific draft application
        if ($draftApplication->instructions()->count() === 0) {
            $missing[] = ['name' => 'KRA I: Instruction', 'route' => route('instructor.instructional-page')];
        }
        if ($draftApplication->researches()->count() === 0) {
            $missing[] = ['name' => 'KRA II: Research', 'route' => route('instructor.research-page')];
        }
        if ($draftApplication->extensions()->count() === 0) {
            $missing[] = ['name' => 'KRA III: Extension', 'route' => route('instructor.extension-page')];
        }
        if ($draftApplication->professionalDevelopments()->count() === 0) {
            $missing[] = ['name' => 'KRA IV: Professional Development', 'route' => route('instructor.professional-development-page')];
        }

        if (empty($missing)) {
            return response()->json(['success' => true, 'message' => 'Application Submitted!'], 201);
        } else {
            return response()->json(['success' => false, 'missing' => $missing]);
        }
    }

    /**
     * Submits the user's draft application for evaluation.
     */
    public function submitEvaluation(Request $request)
    {
        $user = Auth::user();

        // Server-Side Gate: Final validation to ensure user has a rank.
        if (is_null($user->faculty_rank) || trim($user->faculty_rank) === '' || trim($user->faculty_rank) === 'Unset') {
            return redirect()->route('profile-page')->with('error', 'Submission Denied: You do not have a faculty rank assigned. Please contact the system administrator to have your rank validated and set before submitting your CCE documents.');
        }

        // Determine the current evaluation cycle (e.g., "2025-2026")
        $currentYear = now()->year;
        $evaluationCycle = $currentYear . '-' . ($currentYear + 1);

        // Check for an existing application in the current cycle that is not a draft
        if ($user->applications()->where('evaluation_cycle', $evaluationCycle)->where('status', '!=', 'draft')->exists()) {
            return redirect()->route('profile-page')->with('error', 'You have already submitted an application for the ' . $evaluationCycle . ' evaluation cycle.');
        }

        // Find the user's draft application
        $draftApplication = $user->applications()->where('status', 'draft')->first();

        if (!$draftApplication) {
            return redirect()->route('profile-page')->with('error', 'No draft application found to submit.');
        }

        // Update the status and stamp the application with the current cycle
        $draftApplication->status = 'pending evaluation';
        $draftApplication->evaluation_cycle = $evaluationCycle;
        $draftApplication->save();

        return redirect()->route('profile-page')->with('success', 'Your CCE documents have been successfully submitted for the ' . $evaluationCycle . ' evaluation cycle!');
    }
}
