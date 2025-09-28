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

namespace Modules\Plugin\puqSamplePlugin\Commands;

use Illuminate\Console\Command;

class puqSamplePluginTest extends Command
{
    protected $signature = 'puqSamplePlugin:test';
    protected $description = 'It is test PUQ Sample Plugin command';

    public function handle()
    {
        $logo = <<<EOT

██████╗ ██╗   ██╗ ██████╗  ██████╗██╗      ██████╗ ██╗   ██╗██████╗
██╔══██╗██║   ██║██╔═══██╗██╔════╝██║     ██╔═══██╗██║   ██║██╔══██╗
██████╔╝██║   ██║██║   ██║██║     ██║     ██║   ██║██║   ██║██║  ██║
██╔═══╝ ██║   ██║██║▄▄ ██║██║     ██║     ██║   ██║██║   ██║██║  ██║
██║     ╚██████╔╝╚██████╔╝╚██████╗███████╗╚██████╔╝╚██████╔╝██████╔╝
╚═╝      ╚═════╝  ╚══▀▀═╝  ╚═════╝╚══════╝ ╚═════╝  ╚═════╝ ╚═════╝

EOT;

        $this->line($logo);
        $this->info(str_repeat('-', 70));
        $this->info(' Welcome to PUQcloud Test Command ');
        $this->comment(' This is a sample module command for demonstration purposes.');
        $this->info(str_repeat('-', 70));
    }
}
