<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
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
use App\Http\Middleware\CheckUserPermission;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

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
    Route::get('/previews', [PreviewController::class, 'index'])->name('previews-index');
    Route::post('/previews-store', [PreviewController::class, 'store'])->name('previews-store');
    Route::get('/previews-edit/{id}', [PreviewController::class, 'edit'])->name('previews-edit');
    Route::post('/previews-edit/{id}', [PreviewController::class, 'update'])->name('previews-update');
    Route::delete('/previews-delete/{id}', [PreviewController::class, 'destroy'])->name('previews-delete');

    Route::get('/preview/getallversions/{id}', [PreviewApiController::class, 'getAllVersions']);
    Route::get('/preview/updateActiveVersion/{id}', [PreviewApiController::class, 'updateActiveVersion']);
    Route::get('/preview/getVersionType/{id}', [PreviewApiController::class, 'getVersionType']);
    Route::get('/preview/setBannerActiveSubVersion/{id}', [PreviewApiController::class, 'setBannerActiveSubVersion']);
    Route::get('/preview/checkSubVersionCount/{id}', [PreviewApiController::class, 'checkSubVersionCount']);
    Route::get('/preview/getActiveSubVersionBannerData/{id}', [PreviewApiController::class, 'getActiveSubVersionBannerData']);
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

Route::get('/previews/show/{id}', [PreviewController::class, 'show'])->name('previews-show');

Route::get('/file-transfers-view/{id}', [FileTransferController::class, 'show'])->name('file-transfers-view');

Route::prefix('welcome-to-planetnine')->group(function () {
    Route::get('/register', [UserManagementController::class, 'register'])->name('welcome-to-planetnine-register');
    Route::post('/register-post', [UserManagementController::class, 'registerPost'])->name('welcome-to-planetnine-register-post');
});

Route::get('/change-password', [UserManagementController::class, 'changePassword'])->name('change-password');
Route::post('/change-password-post', [UserManagementController::class, 'changePasswordPost'])->name('change-password-post');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
