<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssistantController;

Route::post('/assistant', [AssistantController::class, 'handle'])->name('assistant');