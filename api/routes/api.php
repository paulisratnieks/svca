<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('meetings', MeetingController::class)->only('store');

Route::resource('meetings/{meetingId}/messages', MessageController::class)->only('store');

Route::post('/login', [LoginController::class, 'authenticate']);
