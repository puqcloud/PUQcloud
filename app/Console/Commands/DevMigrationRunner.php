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

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class DevMigrationRunner extends Command
{
    protected $signature = 'Dev:migration_run {path}';
    protected $description = 'Run a single anonymous migration file manually';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $path = base_path($this->argument('path'));

        if (! file_exists($path)) {
            $this->error("Migration file not found: $path");

            return 1;
        }

        $this->info("Running migration file: $path");

        $migration = include $path;

        if (! is_object($migration)) {
            $this->error('Migration did not return a class instance.');

            return 1;
        }

        if (! method_exists($migration, 'up')) {
            $this->error('Migration does not have an up() method.');

            return 1;
        }

        $migration->down();

        $migration->up();
        $this->info('Migration applied successfully.');

        return 0;
    }
}
