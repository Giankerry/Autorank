<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google_Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteLoginController extends Controller
{
    /**
     * Redirects the user to the Google authentication page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectGoogleAuth(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/drive.file',
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email'
            ])
            ->with(["access_type" => "offline", "prompt" => "consent select_account"])
            ->redirect();
    }

    /**
     * Handles the callback from Google after the user has authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google authentication failed: ' . $e->getMessage());
            return redirect()->route('signin-page')->with('error', 'Google authentication failed. Please try again.');
        }

        // Find an existing user by their email.
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // If the user exists, update their information.
            $user->update([
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => preg_replace('/=s\d+(-c)?$/', '=s400-c', $googleUser->getAvatar()),
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken ?? $user->google_refresh_token, // Keep old refresh token if a new one isn't provided
            ]);
        } else {
            // If the user does not exist, create a new one.
            $user = User::create([
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => preg_replace('/=s\d+(-c)?$/', '=s400-c', $googleUser->getAvatar()),
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
                'password' => bcrypt(Str::random(40)),
            ]);
        }

        $user->assignDefaultRoleByEmail();

        Auth::login($user, true);

        // Check for the session flag and redirect to settings if present. This takes priority.
        if ($request->session()->pull('redirect_to_settings')) {
            return redirect()->route('system-settings')->with('success', 'Google Drive has been reconnected successfully!');
        }

        /** @var \App\Models\User $loggedInUser */
        $loggedInUser = Auth::user();

        // Redirect Admins to the Admin Dashboard.
        if ($loggedInUser->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        // Redirect Evaluators to their applications queue.
        if ($loggedInUser->hasRole('evaluator')) {
            return redirect()->route('evaluator.applications.dashboard');
        }

        // Default redirect for regular users (instructors).
        return redirect()->route('profile-page');
    }

    /**
     * Revoke the Google token for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeGoogleToken(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->google_refresh_token) {
            try {
                $this->getGoogleClient()->revokeToken($user->google_refresh_token);
            } catch (\Exception $e) {
                Log::warning('Failed to revoke Google token, possibly already invalid: ' . $e->getMessage());
            } finally {
                $user->forceFill([
                    'google_id' => null,
                    'google_token' => null,
                    'google_refresh_token' => null,
                ])->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Google Drive access has been successfully revoked.']);
    }

    /**
     * Redirect the user to Google to re-authenticate for Drive access.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reconnectGoogle(): RedirectResponse
    {
        session(['redirect_to_settings' => true]);
        return $this->redirectGoogleAuth();
    }

    /**
     * Helper method to get an authenticated Google API client.
     *
     * @return \Google_Client
     */
    private function getGoogleClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        return $client;
    }
}
