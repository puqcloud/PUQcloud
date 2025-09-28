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
use Database\Seeders\AdminSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\GroupsSeeder;
use Database\Seeders\HomeCompanySeeder;
use Database\Seeders\ModulesSeeder;
use Database\Seeders\NotificationLayoutSeeder;
use Database\Seeders\NotificationSenderSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Console\Command;

class PUQCloudSeed extends Command
{
    protected $signature = 'puqcloud:seed {--email=} {--password=} {--name=}';

    protected $description = 'Run Post Install Seed';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        $this->info('=== Seeder process started ===');

        $this->info('Running ModulesSeeder...');
        $seeder = new ModulesSeeder;
        $seeder->run();
        $this->info('ModulesSeeder completed');

        $Modules = Module::all();
        app()->instance('Modules', $Modules);
        $AdminPermission = new AdminPermissionService;
        app()->instance('AdminPermission', $AdminPermission);

        $this->info('Running NotificationSenderSeeder...');
        $seeder = new NotificationSenderSeeder($email, $name);
        $seeder->run();
        $this->info('NotificationSenderSeeder completed');

        $this->info('Running NotificationLayoutSeeder...');
        $seeder = new NotificationLayoutSeeder;
        $seeder->run();
        $this->info('NotificationLayoutSeeder completed');

        $this->info('Running NotificationTemplateSeeder...');
        $seeder = new NotificationTemplateSeeder;
        $seeder->run();
        $this->info('NotificationTemplateSeeder completed');

        $this->info('Running GroupsSeeder...');
        $seeder = new GroupsSeeder;
        $seeder->run();
        $this->info('GroupsSeeder completed');

        $this->info('Running CountrySeeder...');
        $seeder = new CountrySeeder;
        $seeder->run();
        $this->info('CountrySeeder completed');

        $this->info('Running CurrencySeeder...');
        $seeder = new CurrencySeeder;
        $seeder->run();
        $this->info('CurrencySeeder completed');

        $this->info('Running HomeCompanySeeder...');
        $seeder = new HomeCompanySeeder;
        $seeder->run();
        $this->info('HomeCompanySeeder completed');

        $this->info('Running AdminSeeder...');
        $seeder = new AdminSeeder($email, $password, $name);
        $seeder->run();
        $this->info('AdminSeeder completed');

        $this->info('=== Seeder process finished ===');
    }
}
