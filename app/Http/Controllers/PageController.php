<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function signin()
    {
        return view('auth.signin-page');
    }

    public function showDashboard()
    {
        return view('dashboard');
    }

    public function showAllUsersPage()
    {
        $users = User::with('roles.permissions')->get();

        return view('admin.manage-users', compact('users'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin-page');
    }
}
