<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\MediaTokenController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\RecordingController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'auth:web'])->group(function (): void {
    Route::get('token', MediaTokenController::class);
    Route::get('user', fn(Request $request): ?User => $request->user());
    Route::get('logout', [AuthenticationController::class, 'logout']);
    Route::resource('meetings', MeetingController::class)->only('store', 'show');
    Route::resource('recordings', RecordingController::class)->only('index', 'show', 'store', 'update');
    Route::delete('recordings', [RecordingController::class, 'destroy']);
    Route::patch('recordings/{recording}/stop', [RecordingController::class, 'stop']);
    Route::get('recordings/{recording}/download', [RecordingController::class, 'download']);
});

Route::post('login', [AuthenticationController::class, 'authenticate']);
Route::post('register', [AuthenticationController::class, 'register']);
