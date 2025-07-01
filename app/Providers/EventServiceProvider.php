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

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [

        \Laravel\Horizon\Events\JobPushed::class => [
            \App\Listeners\Horizon\HorizonJobPushedListener::class,
        ],
        \Laravel\Horizon\Events\JobReserved::class => [
            \App\Listeners\Horizon\HorizonJobReservedListener::class,
        ],
        \Laravel\Horizon\Events\JobReleased::class => [
            \App\Listeners\Horizon\HorizonJobReleasedListener::class,
        ],
        \Laravel\Horizon\Events\JobFailed::class => [
            \App\Listeners\Horizon\HorizonJobFailedListener::class,
        ],
        \Laravel\Horizon\Events\JobDeleted::class => [
            \App\Listeners\Horizon\HorizonJobDeletedListener::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
