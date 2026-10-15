<?php

use App\Http\Controllers\Admin\GeminiSettingsController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\SourceAdminController;
use App\Http\Controllers\Admin\TopicAdminController;
use App\Http\Controllers\Admin\UsageAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GlossaryController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/glossary', [GlossaryController::class, 'index'])->name('glossary.index');
Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
Route::get('/videos/{video}/stream', [VideoController::class, 'stream'])->name('videos.stream');

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Email verification
    Route::get('/email/verify', [AuthController::class, 'showVerifyNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->middleware('throttle:6,1')->name('verification.send');

    // Dashboard & Library
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/library', [VideoController::class, 'index'])->name('library.index');
    Route::get('/videos/{video}/download', [VideoController::class, 'download'])->name('videos.download.default');
    Route::get('/videos/{video}/download/{type}', [VideoController::class, 'download'])->name('videos.download');
    Route::post('/videos/{video}/bookmark', [VideoController::class, 'toggleBookmark'])->name('videos.bookmark');
    Route::post('/videos/{video}/quiz/submit', [VideoController::class, 'submitQuiz'])->name('videos.quiz.submit');
    Route::post('/videos/{video}/toggle-public', [VideoController::class, 'togglePublic'])->name('videos.toggle-public');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');

    // Community gallery reporting
    Route::post('/gallery/{video}/report', [GalleryController::class, 'report'])->name('gallery.report');

    // Series
    Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
    Route::post('/series', [SeriesController::class, 'store'])->name('series.store');
    Route::get('/series/{series}', [SeriesController::class, 'show'])->name('series.show');

    // Schedules
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::delete('/schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

    // Studio & Video Generation (Requires Verified Email)
    Route::middleware('verified')->group(function () {
        Route::get('/studio', [StudioController::class, 'index'])->name('studio.index');
        Route::post('/studio/generate', [StudioController::class, 'generate'])->name('studio.generate');
        Route::post('/studio/{video}/approve-script', [StudioController::class, 'approveScript'])->name('studio.approve-script');
        Route::put('/studio/{video}/scenes/{scene}', [StudioController::class, 'updateScene'])->name('studio.scenes.update');
        Route::post('/studio/{video}/scenes/{scene}/regenerate-visual', [StudioController::class, 'regenerateSceneVisual'])->name('studio.scenes.regenerate-visual');
        Route::post('/studio/{video}/regenerate-thumbnail', [StudioController::class, 'regenerateThumbnail'])->name('studio.regenerate-thumbnail');
        Route::post('/studio/{video}/retry', [StudioController::class, 'retry'])->name('studio.retry');
        Route::post('/studio/{video}/cancel', [StudioController::class, 'cancel'])->name('studio.cancel');
        Route::get('/api/videos/{video}/status', [StudioController::class, 'getStatus'])->name('studio.status');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // AI Settings (Google Gemini)
        Route::get('/settings/gemini', [GeminiSettingsController::class, 'index'])->name('settings.gemini');
        Route::post('/settings/gemini/key', [GeminiSettingsController::class, 'saveApiKey'])->name('settings.gemini.key');
        Route::delete('/settings/gemini/key', [GeminiSettingsController::class, 'removeApiKey'])->name('settings.gemini.key.remove');
        Route::post('/settings/gemini/models', [GeminiSettingsController::class, 'updateModels'])->name('settings.gemini.models');
        Route::post('/settings/gemini/test-connection', [GeminiSettingsController::class, 'testConnection'])->name('settings.gemini.test.connection');
        Route::post('/settings/gemini/test-text', [GeminiSettingsController::class, 'testText'])->name('settings.gemini.test.text');
        Route::post('/settings/gemini/test-image', [GeminiSettingsController::class, 'testImage'])->name('settings.gemini.test.image');
        Route::post('/settings/gemini/test-video', [GeminiSettingsController::class, 'testVideo'])->name('settings.gemini.test.video');

        // Topics Management
        Route::get('/topics', [TopicAdminController::class, 'index'])->name('topics.index');
        Route::post('/topics', [TopicAdminController::class, 'store'])->name('topics.store');
        Route::put('/topics/{topic}', [TopicAdminController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [TopicAdminController::class, 'destroy'])->name('topics.destroy');

        // Sources Management
        Route::get('/sources', [SourceAdminController::class, 'index'])->name('sources.index');
        Route::post('/sources', [SourceAdminController::class, 'store'])->name('sources.store');
        Route::put('/sources/{source}', [SourceAdminController::class, 'update'])->name('sources.update');
        Route::delete('/sources/{source}', [SourceAdminController::class, 'destroy'])->name('sources.destroy');

        // Users & Quotas
        Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
        Route::match(['post', 'put'], '/users/{user}/quota', [UserAdminController::class, 'updateQuota'])->name('users.quota');

        // Moderation
        Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
        Route::post('/moderation/videos/{video}/approve', [ModerationController::class, 'approveVideo'])->name('moderation.videos.approve');
        Route::post('/moderation/videos/{video}/reject', [ModerationController::class, 'rejectVideo'])->name('moderation.videos.reject');
        Route::post('/moderation/reports/{report}/resolve', [ModerationController::class, 'resolveReport'])->name('moderation.reports.resolve');

        // AI Usage Analytics
        Route::get('/usage', [UsageAdminController::class, 'index'])->name('usage.index');
    });
});
