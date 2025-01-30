<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn (Request $request) => route('auth.login.index'));
        $middleware->redirectUsersTo(fn(Request $request) => url(env('FRONTEND_URL', 'http://localhost:3000')));
        $middleware->trustHosts(at: fn () => explode(',', config('app.trusted_hosts')));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
