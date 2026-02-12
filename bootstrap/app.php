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
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            // 🔐 Autenticación por sesión personalizada
            'auth.custom' => \App\Http\Middleware\AuthCustom::class,

            // 👮 Roles (si lo usas)
            'role'        => \App\Http\Middleware\RoleMiddleware::class,

            // 🚫 Evitar cache (botón atrás)
            'nocache'     => \App\Http\Middleware\NoCache::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
