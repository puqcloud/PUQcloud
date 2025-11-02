<?php

namespace App\Console\Commands;

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class GenerateHorizonConfig extends Command
{
    protected $signature = 'generate:horizon-config';
    protected $description = 'Generate dynamic Horizon config based on active modules';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle(): int
    {
        if (!Schema::hasTable('modules')) {
            $this->error('Table "modules" does not exist.');

            return 1;
        }

        $queues = ['System', 'Cleanup', 'AdminNotification', 'ClientNotification', 'Client', 'Module'];
        $supervisors = [
            'supervisor-Client' => [
                'connection' => 'redis',
                'queue' => ['Client'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 36000,
                'nice' => 0,
            ],
            'supervisor-System' => [
                'connection' => 'redis',
                'queue' => ['System'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 36000,
                'nice' => 0,
            ],
            'supervisor-Cleanup' => [
                'connection' => 'redis',
                'queue' => ['Cleanup'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 36000,
                'nice' => 0,
            ],
            'supervisor-adminNotification' => [
                'connection' => 'redis',
                'queue' => ['AdminNotification'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 36000,
                'nice' => 0,
            ],
            'supervisor-clientNotification' => [
                'connection' => 'redis',
                'queue' => ['ClientNotification'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 36000,
                'nice' => 0,
            ],
            'supervisor-Module' => [
                'connection' => 'redis',
                'queue' => ['Module'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 36000,
                'nice' => 0,
            ],

        ];

        $modules = app('Modules');

        foreach ($modules as $module) {
            if ($module->status !== 'active') {
                continue;
            }

            $module_queues = $module->moduleQueues();
            foreach ($module_queues as $supervisor_name => $supervisor) {
                if (!empty($supervisor['queue'])) {
                    $prefixed_queues = [];
                    foreach ($supervisor['queue'] as $queue_name) {
                        $prefixed_queues[] = $module->name.'-'.$queue_name;
                    }

                    $queues = array_unique(array_merge($queues, $prefixed_queues));

                    $supervisor['queue'] = $prefixed_queues;
                    $supervisors['supervisor-'.$module->name.'-'.$supervisor_name] = $supervisor;
                }
            }

        }

        $config = [
            'queues' => $queues,
            'supervisors' => $supervisors,
        ];

        $this->line(json_encode($config, JSON_PRETTY_PRINT));

        return 0;
    }
}
