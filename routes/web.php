<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Controller Imports
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\SocialiteLoginController;
use App\Http\Controllers\Evaluator\EvaluationController;
use App\Http\Controllers\Instructor\ApplyController;
use App\Http\Controllers\Instructor\ExtensionController;
use App\Http\Controllers\Instructor\InstructionController;
use App\Http\Controllers\Instructor\ProfessionalDevelopmentController;
use App\Http\Controllers\Instructor\ResearchController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserPreferenceController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| These routes are accessible to everyone, including guests.
*/

Route::get('/', [PageController::class, 'signin'])->name('signin-page');

// Google OAuth Routes
Route::prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('redirect', [SocialiteLoginController::class, 'redirectGoogleAuth'])->name('redirect');
    Route::get('callback', [SocialiteLoginController::class, 'handleGoogleCallback'])->name('callback');
});

// Dynamic CSS route for global theme color
Route::get('/css/dynamic-styles.css', [SystemSettingsController::class, 'generateDynamicCss'])->name('dynamic.css');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| These routes require a user to be logged in.
*/
Route::middleware(['auth'])->group(function () {
    // General Authenticated Routes
    Route::get('/dashboard', [PageController::class, 'showDashboard'])->name('dashboard');
    Route::get('/profile', [PageController::class, 'showProfilePage'])->name('profile-page');
    Route::get('/settings', [SystemSettingsController::class, 'showSystemSettings'])->name('system-settings');
    Route::post('/user/preference/theme', [UserPreferenceController::class, 'updateTheme'])->name('user.preference.theme.update');

    // Google Drive Access Management
    Route::post('/settings/google-drive/revoke', [SocialiteLoginController::class, 'revokeGoogleToken'])->name('settings.google.revoke');
    Route::get('/settings/google-drive/reconnect', [SocialiteLoginController::class, 'reconnectGoogle'])->name('settings.google.reconnect');

    // Logout Route
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('signin-page');
    })->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role_or_permission:admin|access applications page'])->group(function () {
        // Application Management
        Route::get('/applications', [ApplicationController::class, 'index'])->name('application-page');
        Route::get('/review-documents', [PageController::class, 'showReviewDocumentsPage'])->name('review-documents-page');
        Route::patch('/positions/{position}/toggle', [PositionController::class, 'toggle'])->name('admin.positions.toggle');

        // User & Rank Management
        Route::get('/manage-users', [UserController::class, 'index'])->name('manage-users');
        Route::put('/manage-users/{user}/update-roles', [UserController::class, 'updateRoles'])->name('manage-users.updateRoles');
        Route::get('/manage-faculty-ranks', [UserController::class, 'manageFacultyRanks'])->name('manage-faculty-ranks');
        Route::put('/users/{user}/update-faculty-rank', [UserController::class, 'updateFacultyRank'])->name('users.update-faculty-rank');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('user.profile');

        // System Settings & Theme Management
        Route::post('/settings/logo', [SystemSettingsController::class, 'updateLogo'])->middleware('can:manage users')->name('settings.logo.update');
        Route::post('/system-settings/primary-color', [SystemSettingsController::class, 'updatePrimaryColor'])->middleware('can:manage users')->name('system.primary-color.update');
        Route::post('/system-settings/theme/reset', [SystemSettingsController::class, 'resetThemeColor'])->name('system.theme.reset');
        Route::get('/system-settings/color', [SystemSettingsController::class, 'getColorSetting'])->name('system.color.get');
    });

    /*
    |--------------------------------------------------------------------------
    | Evaluator Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['permission:access evaluate applications page'])->prefix('evaluator')->name('evaluator.')->group(function () {
        Route::get('/applications', [EvaluationController::class, 'index'])->name('applications.dashboard');
        Route::get('/application/{application}', [EvaluationController::class, 'showApplication'])->name('application.details');
        Route::get('/application/{application}/kra/{kra_slug}', [EvaluationController::class, 'showApplicationKra'])->name('application.kra');
        Route::post('/application/score/{kra_slug}/{submission_id}', [EvaluationController::class, 'scoreSubmission'])->name('submission.score');
        Route::post('/application/{application}/calculate-score', [EvaluationController::class, 'calculateFinalScore'])->name('application.calculate-score');
    });

    /*
    |--------------------------------------------------------------------------
    | Instructor Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin|user'])->group(function () {
        // Application Submission
        Route::get('/application/check-completeness', [ApplyController::class, 'checkCompleteness'])->name('application.check-completeness');
        Route::post('/application/submit/{position}', [ApplyController::class, 'submit'])->name('application.submit');

        Route::prefix('instructor')->name('instructor.')->group(function () {
            // KRA I: Instruction
            Route::get('/instruction', [InstructionController::class, 'index'])->name('instructional-page');
            Route::post('/instruction', [InstructionController::class, 'store'])->name('instruction.store');
            Route::delete('/instruction/{instruction}', [InstructionController::class, 'destroy'])->name('instruction.destroy');
            Route::get('/instruction/{id}/file-info', [InstructionController::class, 'getFileInfo'])->name('instruction.file-info');
            Route::get('/instruction/{id}/view-file', [InstructionController::class, 'viewFile'])->name('instruction.view-file');

            // KRA II: Research
            Route::get('/research', [ResearchController::class, 'index'])->name('research-page');
            Route::post('/research', [ResearchController::class, 'store'])->name('research.store');
            Route::delete('/research/{research}', [ResearchController::class, 'destroy'])->name('research.destroy');
            Route::get('/research/{id}/file-info', [ResearchController::class, 'getFileInfo'])->name('research.file-info');
            Route::get('/research/{id}/view-file', [ResearchController::class, 'viewFile'])->name('research.view-file');

            // KRA III: Extension
            Route::get('/extension', [ExtensionController::class, 'index'])->name('extension-page');
            Route::post('/extension', [ExtensionController::class, 'store'])->name('extension.store');
            Route::delete('/extension/{extension}', [ExtensionController::class, 'destroy'])->name('extension.destroy');
            Route::get('/extension/{id}/file-info', [ExtensionController::class, 'getFileInfo'])->name('extension.file-info');
            Route::get('/extension/{id}/view-file', [ExtensionController::class, 'viewFile'])->name('extension.view-file');

            // KRA IV: Professional Development
            Route::get('/professional-development', [ProfessionalDevelopmentController::class, 'index'])->name('professional-development-page');
            Route::post('/professional-development', [ProfessionalDevelopmentController::class, 'store'])->name('professional-development.store');
            Route::delete('/professional-development/{professionalDevelopment}', [ProfessionalDevelopmentController::class, 'destroy'])->name('professional-development.destroy');
            Route::get('/professional-development/{id}/file-info', [ProfessionalDevelopmentController::class, 'getFileInfo'])->name('professional-development.file-info');
            Route::get('/professional-development/{id}/view-file', [ProfessionalDevelopmentController::class, 'viewFile'])->name('professional-development.view-file');
        });
    });
});
