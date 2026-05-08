<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureTumomitoUser;
use App\Http\Middleware\EnsureTumomitoAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'tumomito.auth' => EnsureTumomitoUser::class,
            'tumomito.admin' => EnsureTumomitoAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(static function (\Throwable $e): void {
            error_log(sprintf(
                '[tumomito] %s @ %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        });
    })->create();
