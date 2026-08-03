<?php

use App\Http\Middleware\AdminPanelMiddleware;
use App\Http\Middleware\IsTeacher;
use App\Http\Middleware\SetRobotsHeader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SetRobotsHeader::class);

        $middleware->alias([
            'admin' => AdminPanelMiddleware::class,
            'teacher' => IsTeacher::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'monopay/webhook',
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->is('logout')) {
                return redirect()
                    ->route('login')
                    ->with('social_auth_error', 'Сесія завершилася. Увійдіть, будь ласка, ще раз.');
            }

            return null;
        });
    })->create();
