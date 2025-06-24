<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Ini adalah tempat Anda mendaftarkan middleware
        $middleware->alias([
            'access.code' => \App\Http\Middleware\AccessCodeMiddleware::class,
        ]);


        // Anda juga bisa menambahkan middleware ke grup lain atau sebagai alias di sini
        // $middleware->alias([
        //     'is.admin' => \App\Http\Middleware\IsAdminMiddleware::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();