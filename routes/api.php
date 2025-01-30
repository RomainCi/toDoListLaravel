<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user(); 
})->middleware(['auth:sanctum','verified']);


Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'E-mail vérifié avec succès.'. $request->user()]);
})->middleware(['auth:sanctum','signed'])->name('verification.verify');

Route::controller(AuthController::class)->name('auth.')->prefix('auth')->group(function () {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::get('/login','loginIndex')->name('login.index');
    Route::post('/logout','logout')->name('logout')->middleware('auth:sanctum');
    Route::post('/email/verification-notification', 'sendVerificationEmail')->name('verification.notice')->middleware('auth:sanctum');
    Route::post('/forget-password','forgetPassword')->name('password.email')->middleware('guest');
    Route::get('/reset-password/{token}','resetPassword')->name('password.reset')->middleware('guest');
    Route::post('/reset-password','updatePassword')->name('store.password.reset')->middleware('guest');
});

Route::controller(UserController::class)->name('user.')->prefix('user')->middleware('auth:sanctum')->group(function(){
    Route::get('/show','show')->name('show');
    Route::post('/update','update')->name('update');
    Route::delete('/delete','destroy')->name('destroy');
});