<?php

use App\Exceptions\UserFacingException;
use App\Http\Middleware\ForceSseHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);
        $middleware->prepend(ForceSseHeaders::class);

        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // User-facing rendering
        $exceptions->render(function (UserFacingException $e, Request $request) {
            if($request->is('api/*')){// only respond to api requests 
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatus());
            }
        });

        // logs all exceptions
        $exceptions->report(function (Throwable $e) {
            // Only log real server errors
            if (!($e instanceof UserFacingException)) {
                Log::error($e);
            }
        });
    })->create();
