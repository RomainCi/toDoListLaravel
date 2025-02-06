<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return phpinfo();
});



Route::get('/login', function () {
    return ['message' => 'Veuillez vous connecter via votre application.'];
})->name('login');
