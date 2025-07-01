<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\FileController;
use App\Http\Middleware\Api\ApiCheckPermissionMiddleware;
use App\Http\Middleware\Web\WebCheckPermissionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        then: function () {

            // Admin area WEB
            Route::middleware('admin_web')
                ->prefix(config('app.admin_path'))
                ->name('admin.web.')
                ->group(base_path('routes/admin_web.php'));

            Route::middleware('admin_web_login')
                ->get(config('app.admin_path').'/login', [AdminAuthController::class, 'loginForm'])
                ->name('admin.web.login');

            // Admin area API
            Route::middleware('admin_api')
                ->prefix(config('app.admin_path').'/api')
                ->name('admin.api.')
                ->group(base_path('routes/admin_api.php'));

            Route::middleware('admin_api_login')
                ->post(config('app.admin_path').'/api/login', [AdminAuthController::class, 'login'])
                ->name('admin.api.login');

            // Admin area static
            Route::prefix(config('app.admin_path').'/static')
                ->name('admin.web.static.')
                ->group(base_path('routes/admin_static.php'));

            // Static
            Route::get('static/file/{uuid}/{name}', [FileController::class, 'download'])->name('static.file');

            // Client area routes (from template)
            $template = env('TEMPLATE_CLIENT', 'puqcloud');
            $clientRoutesBootstrap = base_path("templates/client/{$template}/bootstrap/routes.php");

            if (file_exists($clientRoutesBootstrap)) {
                require $clientRoutesBootstrap;
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'WebPermission' => WebCheckPermissionMiddleware::class,
            'ApiPermission' => ApiCheckPermissionMiddleware::class,
        ]);

        $middleware->group('admin_web', [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Web\WebCheckAuthenticated::class,
            \App\Http\Middleware\Web\WebSessionTracker::class,
            \App\Http\Middleware\Web\WebMiddleware::class,
        ]);

        $middleware->group('admin_api', [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Api\ApiCheckAuthenticated::class,
            \App\Http\Middleware\Api\ApiSessionTracker::class,
            \App\Http\Middleware\Api\ApiMiddleware::class,
        ]);

        $middleware->group('admin_api_login', [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Api\ApiLoginMiddleware::class,
            \App\Http\Middleware\Api\ApiMiddleware::class,
        ]);

        $middleware->group('admin_web_login', [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Web\WebLoginMiddleware::class,
        ]);

        // Client area middleware (from template)
        $template = env('TEMPLATE_CLIENT', 'puqcloud');
        $clientMiddlewareFile = base_path("templates/client/{$template}/bootstrap/middleware.php");

        if (file_exists($clientMiddlewareFile)) {
            require $clientMiddlewareFile;
        }

    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
