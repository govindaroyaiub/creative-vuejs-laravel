<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\FileTransfer;
use App\Http\Controllers\FileTransferController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('auth/Login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/file-transfers', function() {
    $fileTransfers = FileTransfer::with('user:id,name') // Load related user with name only
        ->latest()
        ->get()
        ->map(function ($fileTransfer) {
            return [
                'id' => $fileTransfer->id,
                'name' => $fileTransfer->name,
                'client' => $fileTransfer->client,
                'user' => $fileTransfer->user ? $fileTransfer->user->name : 'Unknown',
                'created_at' => $fileTransfer->created_at->format('Y-m-d H:i'),
            ];
        });

    return Inertia::render('FileTransfers', [
        'fileTransfers' => $fileTransfers,
    ]);
})->middleware(['auth', 'verified'])->name('file-transfers');

Route::get('/file-transfers-add', function(){
    return Inertia::render('FileTransferAdd');
})->middleware(['auth', 'verified'])->name('file-transfers-add');
Route::post('/file-transfers-add', [FileTransferController::class, 'storeTransferFiles'])->middleware(['auth', 'verified'])->name('file-transfers-add');

Route::get('/file-transfers/{id}', [FileTransferController::class, 'destroyTransferFiles'])->middleware(['auth', 'verified'])->name('file-transfers-delete');


Route::get('/previews', function(){
    return Inertia::render('Preview');
})->middleware(['auth', 'verified'])->name('previews');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
