<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\CheckUserPermission;
use App\Http\Controllers\FileTransferController;
use App\Http\Controllers\BannerSizeController;
use App\Http\Controllers\VideoSizeController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ColorPaletteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PreviewApiController;
use App\Http\Controllers\PreviewTrackerController;
use App\Http\Controllers\newPreviewController;
use App\Http\Controllers\newCategoryController;
use App\Http\Controllers\newFeedbackController;
use App\Http\Controllers\newFeedbackSetController;
use App\Http\Controllers\newVersionController;
use App\Http\Controllers\newBannerController;
use App\Http\Controllers\NewGifController;
use App\Http\Controllers\newPreviewApiController;
use App\Http\Controllers\NewSocialController;
use App\Http\Controllers\NewVideoController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('auth/Login');
})->name('home');

Route::middleware(['auth', 'verified', CheckUserPermission::class])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    //File Transfer Routes Start
    Route::get('/file-transfers', [FileTransferController::class, 'index'])->name('file-transfers');
    Route::get('/file-transfers-add', [FileTransferController::class, 'create'])->name('file-transfers-add');
    Route::post('/file-transfers-add', [FileTransferController::class, 'store'])->name('file-transfers-add-post');
    Route::get('/file-transfers-edit/{id}', [FileTransferController::class, 'edit'])->name('file-transfers-edit');
    Route::post('/file-transfers-edit/{id}', [FileTransferController::class, 'update'])->name('file-transfers-update');
    Route::delete('/file-transfers-delete/{id}', [FileTransferController::class, 'destroy'])->name('file-transfers-delete');
    //File Transfer Routes End

    //Preview Routes Start
    Route::get('/previews', [newPreviewController::class, 'index'])->name('previews-index');
    Route::post('/previews-store', [newPreviewController::class, 'store'])->name('previews-store');
    Route::get('/previews-edit/{newPreview}', [newPreviewController::class, 'edit'])->name('previews-edit');
    Route::put('/previews-edit/{newPreview}', [newPreviewController::class, 'update'])->name('previews-update');
    Route::delete('/previews-delete/{newPreview}', [newPreviewController::class, 'destroy'])->name('previews-delete');
    Route::get('/previews/update/{id}', [newPreviewController::class, 'updatePreview'])->name('previews.update.all');
    Route::post('/previews/{id}/bulk-edit', [newPreviewController::class, 'bulkEdit'])->name('previews.bulkEdit');

    Route::delete('/previews/category/delete/{id}', [newCategoryController::class, 'destroy'])->name('previews.category.delete');
    Route::delete('/previews/feedback/delete/{id}', [newFeedbackController::class, 'destroy'])->name('previews.feedback.delete');
    Route::delete('/previews/feedbackSet/delete/{id}', [newFeedbackSetController::class, 'destroy'])->name('previews.feedback.set.delete');
    Route::delete('/previews/version/delete/{id}', [newVersionController::class, 'destroy'])->name('previews.version.delete');
    Route::post('/previews/banner/edit/{id}', [newBannerController::class, 'update'])->name('previews.banner.edit');
    Route::delete('/previews/banner/delete/{id}', [newBannerController::class, 'destroy'])->name('previews.banner.delete');
    Route::post('/previews/social/edit/{id}', [NewSocialController::class, 'update'])->name('previews.social.edit');
    Route::delete('/previews/social/delete/{id}', [NewSocialController::class, 'destroy'])->name('previews.social.delete');
    Route::post('/previews/gif/edit/{id}', [NewGifController::class, 'update'])->name('previews.gif.edit');
    Route::delete('/previews/gif/delete/{id}', [NewGifController::class, 'destroy'])->name('previews.gif.delete');
    Route::post('/previews/video/edit/{id}', [NewVideoController::class, 'update'])->name('previews.video.edit');
    Route::delete('/previews/video/delete/{id}', [NewVideoController::class, 'destroy'])->name('previews.video.delete');
    Route::delete('/previews/companion-banner/delete/{id}', [NewVideoController::class, 'deleteCompanionBanner'])->name('previews.companion.banner.delete');
    //Preview Routes End

    //Banner Sizes Routes Start
    Route::get('/banner-sizes', [BannerSizeController::class, 'index'])->name('banner-sizes-index');
    Route::post('/banner-sizes-create-post', [BannerSizeController::class, 'store'])->name('banner-sizes-create-post');
    Route::put('/banner-sizes-edit/{id}', [BannerSizeController::class, 'update'])->name('banner-sizes-update');
    Route::delete('/banner-sizes-delete/{id}', [BannerSizeController::class, 'destroy'])->name('banner-sizes-delete');
    //Banner Sizes Routes End

    //Video Sizes Routes Start
    Route::get('/video-sizes', [VideoSizeController::class, 'index'])->name('video-sizes-index');
    Route::post('/video-sizes-create-post', [VideoSizeController::class, 'store'])->name('video-sizes-create-post');
    Route::put('/video-sizes-edit/{id}', [VideoSizeController::class, 'update'])->name('video-sizes-update');
    Route::delete('/video-sizes-delete/{id}', [VideoSizeController::class, 'destroy'])->name('video-sizes-delete');
    //Video Sizes Routes End

    //Social Routes Start
    Route::get('/socials', [SocialController::class, 'index'])->name('socials');
    Route::post('/socials-create-post', [SocialController::class, 'store'])->name('socials-create-post');
    Route::put('/socials-edit/{id}', [SocialController::class, 'update'])->name('socials-update');
    Route::delete('/socials-delete/{id}', [SocialController::class, 'destroy'])->name('socials-delete');
    //Social Routes End

    //Bills Routes Start
    Route::get('/bills', [BillController::class, 'index'])->name('bills');
    Route::get('/bills/{id}', [BillController::class, 'show'])->name('bills-show');
    Route::get('/bills-create', [BillController::class, 'create'])->name('bills-create');
    Route::post('/bills-create-post', [BillController::class, 'store'])->name('bills-create-post');
    Route::get('/bills-edit/{id}', [BillController::class, 'edit'])->name('bills-edit');
    Route::put('/bills-edit/{id}', [BillController::class, 'update'])->name('bills-update');
    Route::delete('/bills-delete/{id}', [BillController::class, 'destroy'])->name('bills-delete');
    Route::get('/bills-download/{id}', [BillController::class, 'download'])->name('bills-download');
    //Bills Routes End

    //Client Routes Start
    Route::get('/clients', [ClientController::class, 'index'])->name('clients');
    Route::get('/clients-create', [ClientController::class, 'create'])->name('clients-create');
    Route::post('/clients-store', [ClientController::class, 'store'])->name('clients-store');
    Route::get('/clients-edit/{id}', [ClientController::class, 'edit'])->name('clients-edit');
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

    // Color palette routes start
    Route::get('/color-palettes', [ColorPaletteController::class, 'index'])->name('color-palettes');
    Route::post('/color-palettes-create', [ColorPaletteController::class, 'store'])->name('color-palettes-store');
    Route::put('/color-palettes-update/{id}', [ColorPaletteController::class, 'update'])->name('color-palettes-update');
    Route::delete('/color-palettes-delete/{id}', [ColorPaletteController::class, 'destroy'])->name('color-palettes-delete');
    // Color Palette Routes

    //Media Routes Start
    Route::get('/medias', [MediaController::class, 'index'])->name('medias');
    Route::post('/medias-store', [MediaController::class, 'store'])->name('medias-store');
    Route::delete('/medias-delete/{id}', [MediaController::class, 'destroy'])->name('medias-delete');
    Route::get('/medias-download/{id}', [MediaController::class, 'download'])->name('medias-download');
    //Media Routes End
});

Route::get('/previews/show/{slug}', [NewPreviewController::class, 'show'])->name('previews-show');
//preview axios get requests start

Route::get('/preview/renderCategories/{id}', [newPreviewApiController::class, 'renderCategories']);
Route::get('/preview/updateActiveCategory/{id}', [newPreviewApiController::class, 'updateActiveCategory']);
Route::get('/preview/updateActiveFeedback/{feedback_id}', [newPreviewApiController::class, 'updateActiveFeedback']);
Route::get('/preview/renderVersions/{feedbackSet_id}', [newPreviewApiController::class, 'renderVersions']);
Route::get('/preview/renderBanners/{version_id}', [newPreviewApiController::class, 'renderBanners']);
Route::get('/preview/renderGifs/{version_id}', [newPreviewApiController::class, 'renderGifs']);
Route::get('/preview/renderSocials/{version_id}', [newPreviewApiController::class, 'renderSocials']);
Route::get('/preview/renderVideos/{version_id}', [newPreviewApiController::class, 'renderVideos']);
Route::get('/preview/{preview_id}/change/theme/{color_id}', [newPreviewApiController::class, 'changeTheme']);

//preview axios get requests end

Route::get('/file-transfers-view/{id}', [FileTransferController::class, 'show'])->name('file-transfers-view');

Route::prefix('welcome-to-planetnine')->group(function () {
    Route::get('/register', [UserManagementController::class, 'register'])->name('welcome-to-planetnine-register');
    Route::post('/register-post', [UserManagementController::class, 'registerPost'])->name('welcome-to-planetnine-register-post');
});

Route::get('/change-password', [UserManagementController::class, 'changePassword'])->name('change-password');
Route::post('/change-password-post', [UserManagementController::class, 'changePasswordPost'])->name('change-password-post');

//Preview tracker routes start
Route::get('/get-viewers/{page_id}', [PreviewTrackerController::class, 'getViewers']);
Route::post('/track-viewer', [PreviewTrackerController::class, 'trackViewers']);
//Preview tracker routes end

Route::post('/preview-login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return response()->json([
            'redirect_to' => session()->pull('preview_redirect_after_login', '/'),
        ]);
    }

    return response()->json([
        'message' => 'Invalid credentials.',
    ], 422);
})->name('preview-login');

Route::post('/logout-preview', function (Request $request) {
    $previewId = $request->input('preview_id');

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // ✅ Send them directly to the show route — Blade handles the rest
    return redirect()->to("/previews/show/{$previewId}");
})->name('preview.logout');


Route::get('/preview/{preview_id}/get/data/type', [PreviewApiController::class, 'getDataType']);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
