<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $routeMiddleware = [
        // ...
        'auth.custom' => \App\Http\Middleware\AuthCustom::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'nocache' => \App\Http\Middleware\NoCache::class,
        'session.auth' => \App\Http\Middleware\SessionUserAuth::class,
        //'admin.only' => \App\Http\Middleware\AdminOnly::class,

    ];
}
