<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\InterviewController::class);
Route::post('/chat', \App\Http\Controllers\ChatController::class);
