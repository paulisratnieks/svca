<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\MediaTokenController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\RecordingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/token', MediaTokenController::class);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::resource('meetings', MeetingController::class)->only('store');
    Route::resource('recordings', RecordingController::class)->only('store', 'update');
    Route::patch('recordings/{recordingId}/stop', [RecordingController::class, 'stop']);
});

Route::post('/login', [AuthenticationController::class, 'authenticate']);
Route::post('/register', [AuthenticationController::class, 'register']);
