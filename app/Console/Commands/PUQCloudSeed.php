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

namespace App\Console\Commands;

use App\Models\Module;
use App\Services\AdminPermissionService;
use App\Services\TranslationService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\GroupsSeeder;
use Database\Seeders\HomeCompanySeeder;
use Database\Seeders\ModulesSeeder;
use Database\Seeders\NotificationLayoutSeeder;
use Database\Seeders\NotificationSenderSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\TicketSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class PUQCloudSeed extends Command
{

    protected $signature = 'puqcloud:seed {--email=} {--password=} {--name=}';
    protected $description = 'Run Post Install Seed';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        $this->info('=== Seeder process started ===');

        $this->runSeeder('ModulesSeeder', function () {
            (new ModulesSeeder)->run();
        });

        $Modules = Module::all();
        app()->instance('Modules', $Modules);
        $AdminPermission = new AdminPermissionService;
        app()->instance('AdminPermission', $AdminPermission);

        $this->runSeeder('NotificationSenderSeeder', function () use ($email, $name) {
            (new NotificationSenderSeeder($email ?: 'noreply@example.com', $name ?: 'System'))->run();
        });

        $this->runSeeder('NotificationLayoutSeeder', function () {
            (new NotificationLayoutSeeder)->run();
        });

        $this->runSeeder('NotificationTemplateSeeder', function () {
            (new NotificationTemplateSeeder)->run();
        });

        $this->runSeeder('GroupsSeeder', function () {
            (new GroupsSeeder)->run();
        });

        $this->runSeeder('CountrySeeder', function () {
            (new CountrySeeder)->run();
        });

        $this->runSeeder('CurrencySeeder', function () {
            (new CurrencySeeder)->run();
        });

        $this->runSeeder('HomeCompanySeeder', function () {
            (new HomeCompanySeeder)->run();
        });

        $this->runSeeder('TicketSeeder', function () {
            (new TicketSeeder)->run();
        });

        // Admin creation: only if all 3 params provided
        if ($email && $password && $name) {
            $this->runSeeder('AdminSeeder', function () use ($email, $password, $name) {
                (new AdminSeeder($email, $password, $name))->run();
            });
        } else {
            $this->info('AdminSeeder skipped (use --email --password --name to create admin)');
        }

        $this->info('=== Seeder process finished ===');
    }

    private function runSeeder(string $name, callable $callback): void
    {
        $this->info("Running {$name}...");
        try {
            $callback();
            $this->info("{$name} completed");
        } catch (\Throwable $e) {
            $this->error("{$name} failed: " . $e->getMessage());
        }
    }
}
