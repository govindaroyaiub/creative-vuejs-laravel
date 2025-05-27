<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\ChatAssistantController;

Route::post('/chat-assistant', [ChatAssistantController::class, 'sendMessage']);