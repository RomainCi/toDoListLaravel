<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectUserController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProfilController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum','verified']);


Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'E-mail vérifié avec succès.'. $request->user()]);
})->middleware(['auth:sanctum','signed'])->name('verification.verify');

Route::controller(AuthController::class)->name('auth.')->prefix('auth')->group(function () {
    Route::post('/register', 'register')->name('register')->middleware([HandlePrecognitiveRequests::class]);
    Route::post('/register/{token}', 'registerWithToken')->name('register.token')->middleware([HandlePrecognitiveRequests::class]);
    Route::post('/login', 'login')->name('login');
    Route::get('/login','loginIndex')->name('login.index');
    Route::post('/logout','logout')->name('logout')->middleware('auth:sanctum');
    Route::post('/email/verification-notification', 'sendVerificationEmail')->name('verification.notice')->middleware('auth:sanctum');
    Route::post('/forget-password','forgetPassword')->name('password.email')->middleware('guest');
    Route::get('/reset-password/{token}','resetPassword')->name('password.reset')->middleware('guest');
    Route::patch('/reset-password','updatePassword')->name('store.password.reset')->middleware('guest');

});

Route::controller(UserController::class)->name('user.')->prefix('user')->middleware(['auth:sanctum','verified'])->group(function(){
    Route::get('/show','show')->name('show');
    Route::put('/update','update')->name('update');
    Route::delete('/delete','destroy')->name('destroy');
    Route::put('/update/password','updatePassword')->name('update.password');
    Route::patch('/update/email','updateEmail')->name('update.email');
    Route::get('/email-change-verify/{user}/{email}','verifyEmailChange')->middleware('signed')->name('email.change.verify');
});

Route::controller(UserProfilController::class)->name('profil.')->prefix('profil')->middleware(['auth:sanctum','verified'])->group(function(){
    Route::post("/", "store")->name('store');
    Route::get('/',"index")->name('index');
    Route::delete('/','destroy')->name('destroy');
    Route::patch('/','update')->name('update');
});

Route::controller(ProjectController::class)->name('project.')->prefix('project')->middleware(['auth:sanctum','verified'])->group(function(){
    Route::post('/',"store")->name('store');
    Route::get('/',"index")->name('index');
    Route::patch('/{project}','update')->name('update');
    Route::delete('/{project}','destroy')->name('destroy');
});

Route::controller(ProjectUserController::class)->name('project.user.')->prefix('project/user')->middleware(['auth:sanctum','verified'])->group(function(){
    Route::put('/{project}','update')->name('update');
    Route::delete('/{project}','destroy')->name('destroy');
    Route::get('/{project}','show')->name('show');
});

Route::controller(InvitationController::class)->name('invitation.')->prefix('invitation')->group(function(){
   Route::post('/{project}','store')->middleware(['auth:sanctum','verified'])->name('store');
   Route::get('/accept/{token}','accept')->name('accept');
});

// Ajouter middleware(['throttle:60,1']) pour limiter les requesters
// NE PAS OUBLIER LE CRON POUR LES TOKEN RESET PASSWORD
// NE PAS OUBLIER SUPERVISOR POUR LES JOBS COMPRENDRE PK JAI DES ERREURS DANS WORKER.LOG
