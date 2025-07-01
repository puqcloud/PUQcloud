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

namespace App\Jobs;

use App\Models\Admin;
use App\Models\Task;
use App\Services\AdminPermissionService;
use App\Services\HookService;
use App\Services\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class CheckingAdminPermissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 10;

    public $timeout = 600;

    public $maxExceptions = 2;

    public $data = [];

    public $tags = [];

    public function __construct($data, $tags)
    {
        $this->data = $data;
        $this->tags = $tags;
    }

    public function handle()
    {
        $jobId = $this->job->getJobId();

        $admins = Admin::all();
        foreach ($admins as $admin) {

            if (empty($admin->language)) {
                $admin->language = config('locale.admin.default');
            }
            session(['locale' => $admin->language]);
            App::setLocale($admin->language);
            TranslationService::init('admin');

            $AdminPermission = new AdminPermissionService;
            app()->instance('AdminPermission', $AdminPermission);

            $permissions = $admin->getAllPermissions();
            $old_permissions = json_decode($admin->old_permissions, true);
            if (empty($old_permissions)) {
                $old_permissions = [];
            }
            sort($permissions);
            sort($old_permissions);
            if ($permissions != $old_permissions) {
                $vars = [
                    'admin' => $admin,
                    'old_permissions' => $old_permissions,
                    'new_permissions' => $permissions,
                ];
                app(HookService::class)->callHooks('AdminChangePermissions', $vars);
                $admin->old_permissions = json_encode($permissions);
                $admin->save();
            }
        }

        $task = Task::where('job_id', $jobId)->firstOrFail();
        $msg = [
            'jobId' => $jobId,
        ];
        $task->output_data = json_encode($msg);
        $task->save();
    }

    public function tags(): array
    {
        return $this->tags;
    }
}
