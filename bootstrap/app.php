<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CandidateMiddleware;
use App\Http\Middleware\ThrottleLoginAttempts;
use App\Http\Middleware\TrackPublicVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'candidate' => CandidateMiddleware::class,
            'track.public' => TrackPublicVisit::class,
            'login.throttle' => ThrottleLoginAttempts::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
