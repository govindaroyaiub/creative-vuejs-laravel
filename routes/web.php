<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FileTransferController;
use App\Http\Controllers\BannerSizeController;
use App\Http\Controllers\PreviewController;

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
    Route::get('/file-transfers', [FileTransferController::class, 'index'])->name('file-transfers-index');
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
    Route::delete('/banner-sizes-delete/{id}', [BannerSizeController::class, 'destroy'])->name('banner-sizes-delete');
    //Banner Sizes Routes End
});

Route::get('/file-transfers-view/{id}', [FileTransferController::class, 'view'])->name('file-transfers-view');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
