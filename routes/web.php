<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\FileTransfer;
use App\Models\BannerSize;
use App\Http\Controllers\FileTransferController;
use App\Http\Controllers\BannerSizeController;


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

    Route::get('/file-transfers', function () {
        $fileTransfers = FileTransfer::paginate(10);
        return Inertia::render('FileTransfers/FileTransfers', [
            'fileTransfers' => $fileTransfers,
        ]);
    })->name('file-transfers');

    Route::get('/file-transfers-add', function () {
        return Inertia::render('FileTransfers/FileTransferAdd');
    })->name('file-transfers-add');

    Route::post('/file-transfers-add', [FileTransferController::class, 'storeTransferFiles'])->name('file-transfers-add-post');

    Route::get('/file-transfers-edit/{id}', function ($id) {
        $fileTransfer = FileTransfer::with('user:id,name')->findOrFail($id);

        // Remove 'Transfer Files' from each file path, then split by commas
        $filePaths = array_map(function ($file) {
            return str_replace('Transfer Files/', '', $file);
        }, explode(',', $fileTransfer->file_path)); // Split the file paths into an array

        return Inertia::render('FileTransfers/FileTransfersEdit', [
            'fileTransfer' => [
                'id' => $fileTransfer->id,
                'name' => $fileTransfer->name,
                'client' => $fileTransfer->client,
                'user' => $fileTransfer->user ? $fileTransfer->user->name : 'Unknown',
                'created_at' => $fileTransfer->created_at->format('Y-m-d H:i'),
                'file_paths' => $filePaths, // Send as an array
            ]
        ]);
    })->name('file-transfers-edit');

    Route::post('/file-transfers-edit/{id}', [FileTransferController::class, 'updateTransferFiles'])->name('file-transfers-update');

    Route::delete('/file-transfers-delete/{id}', [FileTransferController::class, 'destroyTransferFiles'])->name('file-transfers-delete');

    Route::get('/previews', function () {
        return Inertia::render('Preview');
    })->name('previews');
    
    Route::get('/banner-sizes', function () {
        $bannerSizes = BannerSize::orderBy('width', 'asc')->paginate(10);

        return Inertia::render('BannerSizes/Index', [
            'bannerSizes' => $bannerSizes,
        ]);
    })->name('banner-sizes-index');

    Route::get('/banner-sizes-create', function () {
        return Inertia::render('BannerSizes/Create', [
            'flash' => session('success'), // Pass flash message explicitly
        ]);
    })->name('banner-sizes-create');

    Route::post('/banner-sizes-create-post', [BannerSizeController::class, 'create'])->name('banner-sizes-create-post');

    Route::delete('/banner-sizes-delete/{id}', [BannerSizeController::class, 'destroy'])->name('banner-sizes-delete');

});

Route::get('/file-transfers-view/{id}', function ($id) {
    $fileTransfer = FileTransfer::with('user:id,name')->findOrFail($id);

    // Remove 'Transfer Files' from each file path, then split by commas
    $filePaths = array_map(function ($file) {
        return str_replace('Transfer Files/', '', $file);
    }, explode(',', $fileTransfer->file_path)); // Split the file paths into an array

    return Inertia::render('FileTransfersView', [
        'fileTransfer' => [
            'id' => $fileTransfer->id,
            'name' => $fileTransfer->name,
            'client' => $fileTransfer->client,
            'user' => $fileTransfer->user ? $fileTransfer->user->name : 'Unknown',
            'created_at' => $fileTransfer->created_at->format('Y-m-d H:i'),
            'file_paths' => $filePaths, // Send as an array
        ]
    ]);
})->name('file-transfers-view');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
