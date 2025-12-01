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

use App\Models\Module;
use App\Services\HookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        URL::forceRootUrl(config('app.url'));

        $this->app->singleton(HookService::class, function ($app) {
            return new HookService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // Hook AdminAreaFooterOutput
            $AdminAreaFooterOutput = '';
            app()->instance('AdminAreaFooterOutput', $AdminAreaFooterOutput);
        } catch (Throwable $e) {
            Log::error('Error initializing AdminAreaFooterOutput', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return;
        }

        try {
            $Modules = Module::all();
            app()->instance('Modules', $Modules);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::warning('Table "modules" not found or query failed. Using empty collection.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            app()->instance('Modules', collect());
        } catch (\Throwable $e) {
            Log::error('Error booting modules', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            app()->instance('Modules', collect());
        }

        try {
            // Load Modules Namespaces
            loadModulesNamespaces();
        } catch (Throwable $e) {
            Log::error('Error loading module namespaces', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return;
        }

        try {
            // Load Admin Template Namespaces
            loadAdminTemplateNamespaces();
        } catch (Throwable $e) {
            Log::error('Error loading admin template namespaces namespaces', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return;
        }

        try {
            // Load Client Template Namespaces
            loadClientTemplateNamespaces();
        } catch (Throwable $e) {
            Log::error('Error loading client template namespaces namespaces', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return;
        }

        try {
            // Load Hooks
            HookService::loadHooks();
        } catch (Throwable $e) {
            Log::error('Error loading hooks', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return;
        }
    }
}
