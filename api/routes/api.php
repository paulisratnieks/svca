<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\MediaTokenController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/token', MediaTokenController::class);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::resource('meetings', MeetingController::class)->only('store', 'show');
    Route::resource('meetings/{meetingId}/messages', MessageController::class)->only('store');
});

Route::post('/login', [AuthenticationController::class, 'authenticate']);
Route::post('/register', [AuthenticationController::class, 'register']);
