<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function signin()
    {
        return view('signin-page');
    }

    public function showDashboard()
    {
        return view('dashboard');
    }

    public function showApplicationsPage()
    {
        return view('application-page');
    }

    public function showProfilePage()
    {
        $user = Auth::user();

        if ($user) {
            /** @var \App\Models\User $user */ // For IDE
            $user->load('credentials');
        } else {
            return redirect('/signin')->with('error', 'You must be logged in to view your profile.');
        }

        return view('profile-page', compact('user'));
    }

    public function showResearchDocumentsPage()
    {
        return view('research-documents-page');
    }

    public function showReviewDocumentsPage()
    {
        return view('review-documents-page');
    }

    public function showEvaluationsPage()
    {
        return view('evaluations-page');
    }

    public function showEventParticipationsPage()
    {
        return view('event-participations-page');
    }

    public function showAllUsersPage()
    {
        $users = User::with('roles.permissions')->get();

        return view('manage-users', compact('users'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/signin');
    }
}
