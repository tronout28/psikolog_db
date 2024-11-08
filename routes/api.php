<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OtpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\OrderController;

Route::group(['prefix' => '/auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-admin', [AuthController::class, 'loginForAdmin']);
    Route::post('/login-doctor', [AuthController::class, 'loginFordokter']);

    Route::get('/user-detail', [AuthController::class, 'detailUser'])->middleware('auth:sanctum');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/check-role', [AuthController::class, 'checkRole'])->middleware('auth:sanctum');
});

Route::group(['prefix' => '/accessall'], function () {
    Route::get('/all-user', [UserController::class, 'allUser']);
    Route::get('/all-doctor', [AdminController::class, 'allDoctor']);
    Route::get('/allchat-user', [UserController::class, 'seeOnlyDoctor'])->middleware('auth:sanctum');
});


Route::middleware('auth:sanctum')->group(function () {
   Route::apiResource('chat', ChatController::class)->only(['index', 'store', 'show']);
   Route::apiResource('chat_message', ChatMessageController::class)->only(['index', 'store']);
});

Route::group(['prefix' => '/user','role:user',], function () {
    Route::post('/create-profile', [UserController::class, 'createProfileUser'])->middleware('auth:sanctum');
    Route::post('/send-otp', [OtpController::class, 'sendOtp'])->middleware('auth:sanctum');
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp'])->middleware('auth:sanctum');
    Route::post('/send-otp-phonenumber', [OtpController::class, 'sendOtpwithPhoneNumber']);
});

Route::group(['prefix' => '/admin','role:admin','auth:sanctum'], function () {
    Route::post('/register-dokter', [AdminController::class, 'registerDoctorfromAdmin']);
    Route::post('/update-dokter/{id}', [AdminController::class, 'updateDoctor']);
    Route::get('/detail-dokter/{id}', [AdminController::class, 'showDetailDoctor']);
});

Route::group(['prefix' => '/doctor','role:dokter','auth:sanctum'], function () {

});

Route::group(['prefix' => '/book'], function () {
    Route::post('/insert', [BukuController::class, 'insertBook']);
    Route::post('/update/{id}', [BukuController::class, 'updateBook']);
    Route::post('/is-avaible/{id}', [BukuController::class, 'updateisavaible']);
    Route::get('/all', [BukuController::class, 'index']);
    Route::get('/show/{id}', [BukuController::class, 'show']);
});

Route::group(['prefix' => '/payment','auth:sanctum'], function () {
    Route::post('/checkout', [OrderController::class, 'checkoutbooks']);
    Route::post('/midtrans-callback/{id}', [OrderController::class, 'callback']);
    Route::post('/invoice/{id}', [OrderController::class, 'invoice']);
});


