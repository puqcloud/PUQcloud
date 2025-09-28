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
require_once __DIR__.'/../Middleware/WebMiddleware.php';
require_once __DIR__.'/../Middleware/WebCheckAuthenticated.php';
require_once __DIR__.'/../Middleware/WebLoginMiddleware.php';
require_once __DIR__.'/../Middleware/WebPanelMiddleware.php';
require_once __DIR__.'/../Middleware/WebSessionTracker.php';
require_once __DIR__.'/../Middleware/WebSessionTracker.php';

require_once __DIR__.'/../Middleware/ApiCheckAuthenticated.php';
require_once __DIR__.'/../Middleware/ApiClient.php';
require_once __DIR__.'/../Middleware/ApiLoginMiddleware.php';
require_once __DIR__.'/../Middleware/ApiSessionTracker.php';
require_once __DIR__.'/../Middleware/ApiMiddleware.php';

use Illuminate\Foundation\Configuration\Middleware;
use Middleware\ApiCheckAuthenticated;
use Middleware\ApiClient;
use Middleware\ApiLoginMiddleware;
use Middleware\ApiMiddleware;
use Middleware\ApiSessionTracker;
use Middleware\WebCheckAuthenticated;
use Middleware\WebLoginMiddleware;
use Middleware\WebMiddleware;
use Middleware\WebPanelMiddleware;
use Middleware\WebSessionTracker;

/** @var Middleware $middleware */
if (! isset($middleware)) {
    throw new \RuntimeException('Middleware config object not provided.');
}

$middleware->group('client_web', [
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    WebCheckAuthenticated::class,
    WebMiddleware::class,
]);

$middleware->group('panel_web', [
    WebPanelMiddleware::class,
    WebSessionTracker::class,
]);

$middleware->group('client_api', [
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ApiCheckAuthenticated::class,
    ApiClient::class,
    ApiMiddleware::class,
    ApiSessionTracker::class,
]);

$middleware->group('panel_login_web', [
    WebLoginMiddleware::class,
]);

$middleware->group('panel_login_api', [
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ApiLoginMiddleware::class,
    ApiMiddleware::class,
]);

$middleware->group('client_static', [
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
]);
