<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlamatUserController;
use App\Http\Controllers\OtpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaketController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ArtikelController;

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
    Route::get('/all-user', [AdminController::class, 'allUser']);
    Route::get('/all-doctor', [AdminController::class, 'allDoctor']);
    Route::get('/all-admin', [UserController::class, 'seeOnlyAdmin']);
    Route::get('/allchat-user', [UserController::class, 'seeOnlyDoctor'])->middleware('auth:sanctum');
    Route::get('/all-orders', [OrderController::class, 'getOrders']);
    Route::get('/total-dokter', [AdminController::class, 'getTotalDoctors']);
    Route::get('/revenue', [OrderController::class, 'getMonthlyRevenue']);
    Route::get('/total-paket', [OrderController::class, 'getTotalPurchasedPaket']);
    Route::get('/total-book', [OrderController::class, 'getTotalPurchasedBooks']);
    Route::get('/total-konsult', [PaketController::class, 'getTotalActivePaket']);
});

Route::group(['prefix' => '/order','middleware' => ['auth:sanctum']], function () {
    Route::get('/histori', [OrderController::class, 'histories']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('chat', ChatController::class)->only(['index', 'store', 'show']);
    Route::apiResource('chat_message', ChatMessageController::class)->only(['index', 'store']);
});

Route::group(['prefix' => '/user','role:user',], function () {
    Route::post('/create-profile', [UserController::class, 'createProfileUser'])->middleware('auth:sanctum');
    Route::post('/send-otp', [OtpController::class, 'sendOtp'])->middleware('auth:sanctum');
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp'])->middleware('auth:sanctum');
    Route::post('/update-profile', [UserController::class, 'updateimage'])->middleware('auth:sanctum');
    Route::post('/send-otp-phonenumber', [OtpController::class, 'sendOtpwithPhoneNumber']);
});

Route::group(['prefix' => '/admin','role:admin','auth:sanctum'], function () {
    Route::post('/register-dokter', [AdminController::class, 'registerDoctorfromAdmin']);
    Route::post('/update-dokter/{id}', [AdminController::class, 'updateDoctor']);
    Route::post('/update-active-user/{id}', [AdminController::class, 'updateactiveDoctor']);
    Route::get('/detail-dokter/{id}', [AdminController::class, 'showDetailDoctor']);
});

Route::group(['prefix' => '/doctor','role:dokter','middleware' => ['auth:sanctum']], function () {

});

Route::group(['prefix' => '/book'], function () {
    Route::post('/insert', [BukuController::class, 'insertBook']);
    Route::post('/update/{id}', [BukuController::class, 'updateBook']);
    Route::post('/is-avaible/{id}', [BukuController::class, 'updateisavaible']);
    Route::get('/all', [BukuController::class, 'index']);
    Route::get('/show/{id}', [BukuController::class, 'show']);
});

Route::group(['prefix' => '/payment','middleware' => ['auth:sanctum']], function () {
    Route::post('/checkout', [OrderController::class, 'checkoutbooks']);
    Route::post('/checkout-paket', [OrderController::class, 'checkoutpaket']);
    Route::get('/history', [OrderController::class, 'histories']);
});

Route::post('/midtrans-callback', [OrderController::class, 'callback']);
Route::get('/payment/invoice/{id}', [OrderController::class, 'invoiceView']);
Route::get('/all-orders', [OrderController::class, 'getOrders']);

Route::group(['prefix' => '/paket',], function () {
    Route::post('/add-paket', [PaketController::class, 'store']);
    Route::delete('/delete-paket/{id}', [PaketController::class, 'destroy']);
    Route::put('/update-paket/{id}', [PaketController::class, 'update']);
    Route::get('/all-paket', [PaketController::class, 'index']);
    Route::get('/paket-dokter/{id}', [PaketController::class, 'showpaketuser']);
    Route::get('/get-paketype', [PaketController::class, 'filterbytype']);
});

Route::group(['prefix' => '/voucher',], function () {
    Route::post('/add-voucher', [VoucherController::class, 'store']);
    Route::delete('/delete', [VoucherController::class, 'destroy']);
    Route::get('/all-voucher', [VoucherController::class, 'index']);
    Route::get('/valid-voucher', [VoucherController::class, 'validateVoucher']);
});

Route::group(['prefix' => '/banner'], function () {
    Route::post('/add-banner', [BannerController::class, 'inputBanner']);
    Route::get('/all-banner', [BannerController::class, 'index']);
    Route::delete('/delete-banner/{id}', [BannerController::class, 'deleteBanner']);
    Route::get('/show-banner/{id}', [BannerController::class, 'detailBanner']);
});

Route::prefix('/artikel')->group(function () {
    Route::get('/all-artikel', [ArtikelController::class, 'index']);
    Route::get('/show-artikel/{id}', [ArtikelController::class, 'show']);
    Route::post('/add-artikel', [ArtikelController::class, 'store']);
    Route::put('/update-artikel/{id}', [ArtikelController::class, 'update']);
    Route::delete('/delete-artikel/{id}', [ArtikelController::class, 'destroy']);
});

Route::group(['prefix' => '/rating'], function () {
    Route::post('/rate-user', [RatingController::class, 'store']);
});

Route::group(['prefix' => '/alamat','middleware' => ['auth:sanctum']], function () {
    Route::post('/add-alamat', [AlamatUserController::class, 'store']);
    Route::get('/all-alamat', [AlamatUserController::class, 'index']);
    Route::put('/update-alamat/{id}', [AlamatUserController::class, 'update']);
    Route::delete('/delete-alamat/{id}', [AlamatUserController::class, 'destroy']);
    Route::put('/select-alamat/{id}', [AlamatUserController::class, 'selectAlamat']);
    Route::get('/show-alamat/{id}', [AlamatUserController::class, 'showdetailalamat']);
});
