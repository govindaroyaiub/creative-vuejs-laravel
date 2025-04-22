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

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('auth/Login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

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
    //Preview Routes End

    //Banner Sizes Routes Start
    Route::get('/banner-sizes', [BannerSizeController::class, 'index'])->name('banner-sizes-index');
    Route::get('/banner-sizes-create', [BannerSizeController::class, 'create'])->name('banner-sizes-create');
    Route::post('/banner-sizes-create-post', [BannerSizeController::class, 'store'])->name('banner-sizes-create-post');
    Route::get('/banner-sizes-edit/{id}', [BannerSizeController::class, 'edit'])->name('banner-sizes-edit');
    Route::put('/banner-sizes-edit/{id}', [BannerSizeController::class, 'update'])->name('banner-sizes-update');
    Route::delete('/banner-sizes-delete/{id}', [BannerSizeController::class, 'destroy'])->name('banner-sizes-delete');
    //Banner Sizes Routes End

    //Video Sizes Routes Start
    Route::get('/video-sizes', [VideoSizeController::class, 'index'])->name('video-sizes-index');
    Route::get('/video-sizes-create', [VideoSizeController::class, 'create'])->name('video-sizes-create');
    Route::post('/video-sizes-create-post', [VideoSizeController::class, 'store'])->name('video-sizes-create-post');
    Route::get('/video-sizes-edit/{id}', [VideoSizeController::class, 'edit'])->name('video-sizes-edit');
    Route::put('/video-sizes-edit/{id}', [VideoSizeController::class, 'update'])->name('video-sizes-update');
    Route::delete('/video-sizes-delete/{id}', [VideoSizeController::class, 'destroy'])->name('video-sizes-delete');
    //Video Sizes Routes End

    //Social Routes Start
    Route::get('/socials', [SocialController::class, 'index'])->name('socials');
    Route::get('/socials-create', [SocialController::class, 'create'])->name('socials-create');
    Route::post('/socials-create-post', [SocialController::class, 'store'])->name('socials-create-post');
    Route::get('/socials-edit/{id}', [SocialController::class, 'edit'])->name('socials-edit');
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
});

Route::get('/file-transfers-view/{id}', [FileTransferController::class, 'show'])->name('file-transfers-view');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
