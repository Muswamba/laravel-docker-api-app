<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Feed\FeedController;
use App\Http\Controllers\Auth\AuthApiController;
use App\Http\Controllers\Auth\APIAuthResetPasswordController;
Route::get('/', function() {
    return ['Hello world from api, this is a test'];
});
// Authentiticated user routes

Route::post('/register', [AuthApiController::class, 'register']);

Route::post('/login', [AuthApiController::class, 'login']);

Route::post('/forgot-password', [APIAuthResetPasswordController::class, 'sendTokenCode']);
// Validate the verification code
Route::post('/reset-password/validate-code',
[APIAuthResetPasswordController::class, 'validateTokenCode']);
Route::post('/reset-password',
[APIAuthResetPasswordController::class, 'resetPassword']);


// Refresh token
Route::post('/auth/refresh', [AuthApiController::class, 'refreshToken']);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Home feed routes
 */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/feed', [FeedController::class, 'index']);
    Route::get('/explore', [FeedController::class, 'explore']);
});
