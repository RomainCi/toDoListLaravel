<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return phpinfo();
});

// Route::get('/email/verify', function () {
//     return view('auth.verify-email');
// })->middleware('auth')->name('verification.notice');



Route::get('/login', function () {
    return ['message' => 'Veuillez vous connecter via votre application.'];
})->name('login');
