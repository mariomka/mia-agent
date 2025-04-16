<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return '';
})->name('home');

Route::get('/interviews/{interview}', InterviewController::class)->name('interview');

Route::post('/chat', ChatController::class);
