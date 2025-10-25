<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\ChatAssistantController;

Route::post('/chat-assistant', [ChatAssistantController::class, 'sendMessage']);

// Timezone detection route
Route::post('/set-timezone', function (Illuminate\Http\Request $request) {
    $timezone = $request->input('timezone');
    
    if ($timezone && in_array($timezone, timezone_identifiers_list())) {
        session(['user_timezone' => $timezone]);
        
        return response()->json([
            'success' => true,
            'timezone' => $timezone,
            'message' => 'Timezone set successfully'
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Invalid timezone'
    ], 400);
});