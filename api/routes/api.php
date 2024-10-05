<?php

use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::resource('meetings', MeetingController::class)->only('store');

Route::resource('meetings/{meetingId}/messages', MessageController::class)->only('store');
