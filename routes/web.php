<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\CheckUserPermission;
use App\Http\Controllers\FileTransferController;
use App\Http\Controllers\CreativeSizeController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ColorPaletteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PreviewTrackerController;
use App\Http\Controllers\NewPreviewController;
use App\Http\Controllers\NewCategoryController;
use App\Http\Controllers\NewFeedbackController;
use App\Http\Controllers\NewFeedbackSetController;
use App\Http\Controllers\NewVersionController;
use App\Http\Controllers\NewBannerController;
use App\Http\Controllers\NewGifController;
use App\Http\Controllers\newPreviewApiController;
use App\Http\Controllers\NewSocialController;
use App\Http\Controllers\NewVideoController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TetrisController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CacheManagementController;
use App\Http\Controllers\PreviewTourGuideController;
use App\Http\Controllers\OrbitController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('auth/Login');
})->name('home');

Route::middleware(['auth', 'verified', CheckUserPermission::class])->group(function () {

    Route::get('/documentations', function () {
        return Inertia::render('LazyDoc');
    })->name('documentations');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    //File Transfer Routes Start
    Route::get('/file-transfers', [FileTransferController::class, 'index'])->name('file-transfers');
    Route::post('/file-transfers-add', [FileTransferController::class, 'store'])->name('file-transfers-add-post');
    Route::post('/file-transfers-edit/{id}', [FileTransferController::class, 'update'])->name('file-transfers-update');
    Route::delete('/file-transfers-delete/{id}', [FileTransferController::class, 'destroy'])->name('file-transfers-delete');
    Route::post('/file-transfers/bulk-delete', [FileTransferController::class, 'bulkDestroy'])->name('file-transfers-bulk-delete');
    //File Transfer Routes End

    //Preview Routes Start
    Route::get('/previews', [NewPreviewController::class, 'index'])->name('previews-index');
    Route::post('/previews-store', [NewPreviewController::class, 'store'])->name('previews-store');
    Route::get('/previews-edit/{newPreview}', [NewPreviewController::class, 'edit'])->name('previews-edit');
    Route::put('/previews-edit/{newPreview}', [NewPreviewController::class, 'update'])->name('previews-update');
    Route::delete('/previews-delete/{newPreview}', [NewPreviewController::class, 'destroy'])->name('previews-delete');
    Route::get('/previews/update/{id}', [NewPreviewController::class, 'updatePreview'])->name('previews.update.all');
    Route::get('/previews/update2/{id}', [NewPreviewController::class, 'updatePreview2'])->name('previews.update2.all');
    Route::post('/previews/{id}/bulk-edit', [NewPreviewController::class, 'bulkEdit'])->name('previews.bulkEdit');
    Route::get('/previews/{id}/activity', [NewPreviewController::class, 'activityLog'])->name('previews.activity-log');

    Route::delete('/previews/category/delete/{id}', [NewCategoryController::class, 'destroy'])->name('previews.category.delete');
    Route::put('/previews/feedback/approve/{id}', [NewFeedbackController::class, 'approve'])->name('previews.feedback.approve');
    // Also accept POST for approve to ensure multipart file uploads work reliably
    Route::post('/previews/feedback/approve/{id}', [NewFeedbackController::class, 'approve']);
    Route::put('/previews/feedback/disapprove/{id}', [NewFeedbackController::class, 'disapprove'])->name('previews.feedback.disapprove');
    Route::delete('/previews/feedback/delete/{id}', [NewFeedbackController::class, 'destroy'])->name('previews.feedback.delete');
    Route::delete('/previews/feedbackSet/delete/{id}', [NewFeedbackSetController::class, 'destroy'])->name('previews.feedback.set.delete');
    Route::delete('/previews/version/delete/{id}', [NewVersionController::class, 'destroy'])->name('previews.version.delete');
    Route::post('/previews/banner/edit/{id}', [NewBannerController::class, 'update'])->name('previews.banner.edit');
    Route::delete('/previews/banner/delete/{id}', [NewBannerController::class, 'destroy'])->name('previews.banner.delete');
    Route::get('/previews/banner/download/{id}', [NewBannerController::class, 'download'])->name('preview.banner.download');
    Route::post('/previews/social/edit/{id}', [NewSocialController::class, 'update'])->name('previews.social.edit');
    Route::delete('/previews/social/delete/{id}', [NewSocialController::class, 'destroy'])->name('previews.social.delete');
    Route::post('/previews/gif/edit/{id}', [NewGifController::class, 'update'])->name('previews.gif.edit');
    Route::delete('/previews/gif/delete/{id}', [NewGifController::class, 'destroy'])->name('previews.gif.delete');
    Route::post('/previews/video/edit/{id}', [NewVideoController::class, 'update'])->name('previews.video.edit');
    Route::delete('/previews/video/delete/{id}', [NewVideoController::class, 'destroy'])->name('previews.video.delete');
    Route::delete('/previews/companion-banner/delete/{id}', [NewVideoController::class, 'deleteCompanionBanner'])->name('previews.companion.banner.delete');
    //Preview Routes End

    //Creative Sizes Routes (unified Banner + Video sizes management)
    Route::get('/creative-sizes', [CreativeSizeController::class, 'index'])->name('creative-sizes-index');
    Route::post('/creative-sizes', [CreativeSizeController::class, 'store'])->name('creative-sizes-store');
    Route::put('/creative-sizes/{id}', [CreativeSizeController::class, 'update'])->name('creative-sizes-update');
    Route::delete('/creative-sizes/{id}', [CreativeSizeController::class, 'destroy'])->name('creative-sizes-delete');

    // Legacy redirects — old URLs and named routes forward to the unified
    // page so external bookmarks and stale `route()` calls keep working.
    Route::get('/banner-sizes', fn() => redirect()->route('creative-sizes-index'))
        ->name('banner-sizes-index');
    Route::get('/video-sizes', fn() => redirect()->route('creative-sizes-index', ['type' => 'video']))
        ->name('video-sizes-index');

    //Social Routes Start
    Route::get('/socials', [SocialController::class, 'index'])->name('socials');
    Route::post('/socials-create-post', [SocialController::class, 'store'])->name('socials-create-post');
    Route::put('/socials-edit/{id}', [SocialController::class, 'update'])->name('socials-update');
    Route::delete('/socials-delete/{id}', [SocialController::class, 'destroy'])->name('socials-delete');
    //Social Routes End

    //Reporting Routes Start
    Route::get('/reporting', [ReportingController::class, 'index'])->name('reporting');
    Route::post('/reporting/process', [ReportingController::class, 'process'])->name('reporting-process');
    Route::post('/reporting/save-adhese', [ReportingController::class, 'saveAdhese'])->name('reporting-save-adhese');
    Route::post('/reporting/save-adhese-batch', [ReportingController::class, 'saveAdheseBatch'])->name('reporting-save-adhese-batch');
    Route::post('/reporting/verify', [ReportingController::class, 'verify'])->name('reporting-verify');
    Route::post('/reporting/verify-weekly', [ReportingController::class, 'verifyWeekly'])->name('reporting-verify-weekly');
    Route::post('/reporting/config', [ReportingController::class, 'config'])->name('reporting-config');
    Route::post('/reporting/links', [ReportingController::class, 'links'])->name('reporting-links');
    Route::post('/reporting/sync', [ReportingController::class, 'sync'])->name('reporting-sync');
    Route::get('/reporting/upload-files', [ReportingController::class, 'uploadFiles'])->name('reporting-upload-files');
    Route::get('/reporting/download', [ReportingController::class, 'download'])->name('reporting-download');
    Route::delete('/reporting/{siteId}/{dateKey}', [ReportingController::class, 'destroy'])->name('reporting-destroy');
    //Reporting Routes End

    //Bills Routes Start
    Route::get('/bills', [BillController::class, 'index'])->name('bills');
    Route::get('/bills/{id}', [BillController::class, 'show'])->name('bills-show');
    Route::get('/bills-create', [BillController::class, 'create'])->name('bills-create');
    Route::post('/bills-create-post', [BillController::class, 'store'])->name('bills-create-post');
    Route::get('/bills-edit/{id}', [BillController::class, 'edit'])->name('bills-edit');
    Route::put('/bills-edit/{id}', [BillController::class, 'update'])->name('bills-update');
    Route::delete('/bills-delete/{id}', [BillController::class, 'destroy'])->name('bills-delete');
    Route::get('/bills-download/{id}', [BillController::class, 'download'])->name('bills-download');
    Route::delete('/bills/{billId}/documents/{documentId}', [BillController::class, 'deleteDocument'])->name('bills-document-delete');
    Route::get('/bills/{billId}/documents/{documentId}/download', [BillController::class, 'downloadDocument'])->name('bills-document-download');
    //Bills Routes End

    //Client Routes Start
    Route::get('/clients', [ClientController::class, 'index'])->name('clients');
    Route::post('/clients-store', [ClientController::class, 'store'])->name('clients-store');
    Route::post('/clients-update/{id}', [ClientController::class, 'update'])->name('clients-update');
    Route::delete('/clients-delete/{id}', [ClientController::class, 'destroy'])->name('clients-delete');
    //Client Routes End

    //user management routes start
    Route::get('/user-managements', function () {
        return redirect()->route('user-managements');
    });
    Route::get('/user-managements/designations', [UserManagementController::class, 'designationIndex'])->name('user-managements');
    Route::post('/user-managements/designations-create-post', [UserManagementController::class, 'designationStore'])->name('user-managements-designations-create-post');
    Route::put('/user-managements/designations-edit/{id}', [UserManagementController::class, 'designationUpdate'])->name('user-managements-designations-update');
    Route::delete('/user-managements/designations-delete/{id}', [UserManagementController::class, 'designationDestroy'])->name('user-managements-designations-delete');

    Route::get('/user-managements/users', [UserManagementController::class, 'userIndex'])->name('user-managements-users');
    Route::post('/user-managements/users-create', [UserManagementController::class, 'userCreate'])->name('user-managements-users-create');
    Route::delete('/user-managements/users-delete/{id}', [UserManagementController::class, 'userDelete'])->name('user-managements-users-delete');

    Route::put('/user-managaments/users/update/permissions/{id}', [UserManagementController::class, 'userPermissionsUpdate'])->name('user-managements-users-update-permissions');
    Route::put('/user-managements/users/{id}/update-role', [UserManagementController::class, 'updateRole'])->name('user-managements-users-update-role');
    Route::put('/user-managements/users/{user}/client', [UserManagementController::class, 'updateClient'])->name('user-managements-users-update-client');
    Route::post('/user-managements/users/update-password/{id}', [UserManagementController::class, 'userPasswordUpdate'])->name('user-managements-users-update-password');

    Route::get('/user-managements/routes', [UserManagementController::class, 'routesIndex'])->name('user-managements-routes');
    Route::post('/user-managements/routes-create-post', [UserManagementController::class, 'routesStore'])->name('user-managements-routes-create-post');
    Route::put('/user-managements/routes-edit/{id}', [UserManagementController::class, 'routesUpdate'])->name('user-managements-routes-update');
    Route::delete('/user-managements/routes-delete/{id}', [UserManagementController::class, 'routesDestroy'])->name('user-managements-routes-delete');
    //user-management routes end

    //preview tour guide code for active/inactive starts
    Route::get('/preview-tour-guide', [PreviewTourGuideController::class, 'index'])->name('preview-tour-guide.index');
    Route::put('/preview-tour-guide/update', [PreviewTourGuideController::class, 'update'])->name('preview-tour-guide.update');
    //preview tour guide routes ends

    // Color palette routes start
    Route::get('/color-palettes', [ColorPaletteController::class, 'index'])->name('color-palettes');
    Route::post('/color-palettes/store', [ColorPaletteController::class, 'store'])->name('color-palettes-store');
    Route::put('/color-palettes-update/{id}', [ColorPaletteController::class, 'update'])->name('color-palettes-update');
    Route::put('/color-palettes-toggle-status/{id}', [ColorPaletteController::class, 'toggleStatus'])->name('color-palettes-toggle-status');
    Route::delete('/color-palettes-delete/{id}', [ColorPaletteController::class, 'destroy'])->name('color-palettes-delete');
    // Color Palette Routes

    //Media Routes Start
    Route::get('/medias', [MediaController::class, 'index'])->name('medias');
    Route::post('/medias-store', [MediaController::class, 'store'])->name('medias-store');
    Route::delete('/medias-delete/{id}', [MediaController::class, 'destroy'])->name('medias-delete');
    Route::post('/medias/bulk-delete', [MediaController::class, 'bulkDestroy'])->name('medias-bulk-delete');
    Route::get('/medias-download/{id}', [MediaController::class, 'download'])->name('medias-download');
    //Media Routes End

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::post('/activity-logs/bulk-delete', [ActivityLogController::class, 'bulkDestroy'])->name('activity-logs.bulk-delete');
    Route::post('/activity-logs/empty', [ActivityLogController::class, 'empty'])->name('activity-logs.empty');
    Route::get('/play/tetris', [TetrisController::class, 'index'])->name('tetris.index');
    Route::post('/tetris/submit/score', [TetrisController::class, 'submitScore'])->name('tetris.submit.score');

    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates-store', [TemplateController::class, 'store'])->name('templates.store');
    Route::post('/templates-update/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates-delete/{template}', [TemplateController::class, 'destroy'])->name('templates.delete');
    Route::get('/templates-download/{template}', [TemplateController::class, 'download'])->name('templates.download');

    //Cache Management Routes Start
    Route::get('/cache-management', [CacheManagementController::class, 'index'])->name('cache-management');
    Route::post('/cache-management/run-cleanup', [CacheManagementController::class, 'runCleanup'])->name('cache-management.run-cleanup');
    Route::post('/cache-management/blank-logs', [CacheManagementController::class, 'blankLogFiles'])->name('cache-management.blank-logs');
    Route::post('/cache-management/run-artisan-clear', [CacheManagementController::class, 'runArtisanClear'])->name('cache-management.run-artisan-clear');
    //Cache Management Routes End

    //Log Viewer Routes Start
    Route::get('/logs', [App\Http\Controllers\LogViewerController::class, 'index'])->name('logs.index');
    Route::get('/logs/data', [App\Http\Controllers\LogViewerController::class, 'getLogData'])->name('logs.data');
    Route::get('/logs/download', [App\Http\Controllers\LogViewerController::class, 'downloadLog'])->name('logs.download');
    Route::post('/logs/clear', [App\Http\Controllers\LogViewerController::class, 'clearLog'])->name('logs.clear');
    //Log Viewer Routes End

    //Support Ticket Routes Start
    Route::get('/support-tickets', [App\Http\Controllers\SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('/support-tickets/create', [App\Http\Controllers\SupportTicketController::class, 'create'])->name('support-tickets.create');
    Route::post('/support-tickets', [App\Http\Controllers\SupportTicketController::class, 'store'])->name('support-tickets.store');
    Route::get('/support-tickets/{ticket}', [App\Http\Controllers\SupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::put('/support-tickets/{ticket}/status', [App\Http\Controllers\SupportTicketController::class, 'updateStatus'])->name('support-tickets.update-status');
    Route::put('/support-tickets/{ticket}/priority', [App\Http\Controllers\SupportTicketController::class, 'updatePriority'])->name('support-tickets.update-priority');
    Route::delete('/support-tickets/{ticket}', [App\Http\Controllers\SupportTicketController::class, 'destroy'])->name('support-tickets.destroy');
    //Support Ticket Routes End

    //Orbit Routes Start
    Route::get('/orbit', [OrbitController::class, 'index'])->name('orbit.index');
    Route::get('/orbit/available-previews', [OrbitController::class, 'availablePreviews'])->name('orbit.available-previews');
    Route::get('/orbit/available-banners/{preview}', [OrbitController::class, 'availableBannersForPreview'])
        ->where('preview', '[0-9]+')
        ->name('orbit.available-banners');
    Route::post('/orbit', [OrbitController::class, 'store'])->name('orbit.store');
    Route::post('/orbit/{id}/toggle', [OrbitController::class, 'toggle'])->name('orbit.toggle');
    Route::delete('/orbit/{id}', [OrbitController::class, 'destroy'])->name('orbit.destroy');
    //Orbit Routes End

});

// Public banner tag — embedded on third-party sites / ad platforms.
// Throttled to keep the endpoint from being trivially abused as a
// reflective amplifier; banner asset URLs are already publicly served.
Route::get('/tag/banner/{id}.js', [NewBannerController::class, 'tag'])
    ->where('id', '[0-9]+')
    ->middleware('throttle:120,1')
    ->name('orbit.banner-tag');

// Public view-tracking beacon. CSRF-exempt (see bootstrap/app.php)
// because it's hit cross-origin from sendBeacon on third-party pages.
Route::post('/track/orbit/banner/{id}/view', [OrbitController::class, 'trackView'])
    ->where('id', '[0-9]+')
    ->middleware('throttle:600,1')
    ->name('orbit.track.view');

Route::post('/track/orbit/banner/{id}/click', [OrbitController::class, 'trackClick'])
    ->where('id', '[0-9]+')
    ->middleware('throttle:600,1')
    ->name('orbit.track.click');

// Iframe wrapper for the Orbit tag — serves index.html with an
// injected <base> + click listener. The preview page is unaffected.
Route::get('/orbit/serve/banner/{id}', [OrbitController::class, 'serveBanner'])
    ->where('id', '[0-9]+')
    ->middleware('throttle:300,1')
    ->name('orbit.serve-banner');

Route::get('/previews/show/{slug}', [newPreviewController::class, 'show'])->name('previews-show');
Route::get('/previews/show2/{slug}', [newPreviewController::class, 'show2'])->name('previews-show-2');

// Preview-tree XHR endpoints used by Show2/Show. Each method gates
// access by the preview's own `requires_login` flag, mirroring the
// page-route check. Reads stay GET; state changes are POST so the
// CSRF middleware applies (a previous version was all-GET, which
// allowed cross-site triggering via image/iframe tags).

Route::get('/preview/renderCategories/{id}', [newPreviewApiController::class, 'renderCategories']);
Route::get('/preview/renderVersions/{feedbackSet_id}', [newPreviewApiController::class, 'renderVersions']);
Route::get('/preview/renderBanners/{version_id}', [newPreviewApiController::class, 'renderBanners']);
Route::get('/preview/renderGifs/{version_id}', [newPreviewApiController::class, 'renderGifs']);
Route::get('/preview/renderSocials/{version_id}', [newPreviewApiController::class, 'renderSocials']);
Route::get('/preview/renderVideos/{version_id}', [newPreviewApiController::class, 'renderVideos']);

Route::post('/preview/updateActiveCategory/{id}', [newPreviewApiController::class, 'updateActiveCategory']);
Route::post('/preview/updateActiveFeedback/{feedback_id}', [newPreviewApiController::class, 'updateActiveFeedback']);
Route::post('/preview/{preview_id}/change/theme/{color_id}', [newPreviewApiController::class, 'changeTheme']);

//preview axios requests end

// Public file-transfer viewer. Slug is a UUID (high entropy), but the
// throttle keeps brute-force enumeration bounded if a slug ever leaks
// the lower bits of its randomness.
Route::get('/file-transfers-view/{slug}', [FileTransferController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('file-transfers-view');

// First-registration flow. The GET form is reachable via the welcome
// link; the POST is throttled (5 attempts / 5 minutes / IP) and the
// controller re-verifies the target user still holds the
// `/welcome-to-planetnine/register` permission before applying the
// password — once that permission is consumed, repeat submissions
// are no-ops, which closes the prior takeover-by-id-enumeration hole.
Route::prefix('welcome-to-planetnine')->group(function () {
    Route::get('/register', [UserManagementController::class, 'register'])->name('welcome-to-planetnine-register');
    Route::post('/register-post', [UserManagementController::class, 'registerPost'])
        ->middleware('throttle:5,5')
        ->name('welcome-to-planetnine-register-post');
});

// Forced-reset flow. Both endpoints require an authenticated session;
// changePasswordPost uses Auth::id() server-side rather than trusting
// the request body's user_id, so a logged-in user can only change
// their own password.
Route::middleware('auth')->group(function () {
    Route::get('/change-password', [UserManagementController::class, 'changePassword'])->name('change-password');
    Route::post('/change-password-post', [UserManagementController::class, 'changePasswordPost'])->name('change-password-post');
});

// Preview-viewer presence pings. Both endpoints are throttled (the
// trackViewers endpoint formerly accepted unbounded `guest_name`
// values that an attacker could spam into the live-viewers list)
// and the controller gates each call by the preview's requires_login
// flag.
Route::get('/get-viewers/{page_id}', [PreviewTrackerController::class, 'getViewers'])
    ->middleware('throttle:120,1');
Route::post('/track-viewer', [PreviewTrackerController::class, 'trackViewers'])
    ->middleware('throttle:60,1');

Route::post('/preview-login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    // Throttle by `email|ip` — the previous `throttle:5,1` middleware
    // was keyed by route+IP, which let an attacker credential-stuff
    // an unlimited number of emails from a single IP. This mirrors
    // Laravel's standard LoginRequest behavior. 5 attempts / minute
    // per (email, IP) pair, with a Lockout event raised on exceed.
    $key = \Illuminate\Support\Str::transliterate(
        \Illuminate\Support\Str::lower((string) $request->input('email')).'|'.$request->ip()
    );

    if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
        event(new \Illuminate\Auth\Events\Lockout($request));
        return response()->json([
            'message' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ], 429);
    }

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        \Illuminate\Support\Facades\RateLimiter::clear($key);
        $request->session()->regenerate();
        return response()->json([
            'redirect_to' => session()->pull('preview_redirect_after_login', '/'),
        ]);
    }

    \Illuminate\Support\Facades\RateLimiter::hit($key, 60);
    return response()->json([
        'message' => 'Invalid credentials.',
    ], 422);
})->name('preview-login');

Route::post('/logout-preview', function (Request $request) {
    $previewId = $request->input('preview_id');

    $slug = \App\Models\newPreview::where('id', $previewId)->value('slug');

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->to("/previews/show/{$slug}");
})->name('preview.logout');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
