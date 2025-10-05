<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard with key metrics and recent activity.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Get the total count of applications with 'pending evaluation' status.
        $pendingCount = Application::where('status', 'pending evaluation')->count();

        // Get the total count of applications with 'evaluated' status.
        $evaluatedCount = Application::where('status', 'evaluated')->count();

        // Calculate the distribution of faculty ranks among all users.
        // This fetches all users with a set rank, groups them by that rank,
        // and then counts the number of users in each group.
        $rankDistribution = User::whereNotNull('faculty_rank')
            ->get()
            ->groupBy('faculty_rank')
            ->map->count();

        // Retrieve the 5 most recently completed evaluations.
        // Eager load the associated user data to prevent N+1 query issues in the view.
        $recentEvaluations = Application::where('status', 'evaluated')
            ->with('user')
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Return the admin dashboard view and pass all the fetched data.
        return view('admin.dashboard', compact(
            'pendingCount',
            'evaluatedCount',
            'rankDistribution',
            'recentEvaluations'
        ));
    }
}
