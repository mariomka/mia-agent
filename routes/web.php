<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return '';
})->name('home');

Route::get('/interviews/{interview}', InterviewController::class)
    ->middleware(['throttle:20,1']) // 20 requests per minute
    ->name('interview');

Route::post('/chat', ChatController::class)
    ->middleware(['throttle:60,1']) // 60 requests per minute
    ->name('chat');
