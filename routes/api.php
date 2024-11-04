<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatMessageController;

Route::group(['prefix' => '/auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/check-role', [AuthController::class, 'checkRole'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
   Route::apiResource('chat', ChatController::class)->only(['index', 'store', 'show']);
   Route::apiResource('chat_message', ChatMessageController::class)->only(['index', 'store']);
});
