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

namespace App\Modules;

class Product extends Module
{
    public $product_data;

    public $product_uuid;

    public $service_data;

    public $service_uuid;

    public function __construct()
    {
        $this->module_type = 'Product';
        parent::__construct();
    }

    // Product
    public function setProductUuid(string $product_uuid): void
    {
        $this->product_uuid = $product_uuid;
    }

    public function getProductData(array $data = []): array
    {
        $this->product_data = $data;

        return $this->product_data;
    }

    public function getProductPage(): string
    {
        return __('message.No data to display');
    }

    public function saveProductData(array $data = []): array
    {
        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    // Service
    public function setServiceUuid(string $service_uuid): void
    {
        $this->service_uuid = $service_uuid;
    }

    public function getServiceData(array $data = []): array
    {
        $this->service_data = $data;

        return $this->service_data;
    }

    public function getServicePage(): string
    {
        return __('message.No data to display');
    }

    public function saveServiceData(array $data = []): array
    {
        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    public function getClientAreaMenuConfig(): array
    {
        return [];
    }

    public function view(string $template, array $data = []): string
    {
        $templatePath = 'modules.Product.'.$this->module_name.'.views.'.$template;

        if (view()->exists($templatePath)) {
            try {
                return view($templatePath, $data)->render();
            } catch (\Throwable $e) {
                return 'Error rendering template: '.$e->getMessage();
            }
        }

        return "Template '{$template}' not found.";
    }

    // Actions
    public function create(): array
    {
        return ['status' => 'success'];
    }

    public function retry_deploy(): array
    {
        return ['status' => 'success'];
    }

    public function createCallback($result, $jobId = null): void
    {
        $service = \App\Models\Service::find($this->service_uuid);

        if ($result['status'] == 'error') {
            $create_error = '';
            foreach ($result['errors'] as $index => $error) {
                if ($index > 0) {
                    $create_error .= ', ';
                }
                $create_error .= $error;
            }
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            $service->setProvisionStatus('error');
            $service->create_error = $create_error;
            $service->save();

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity('info', 'Service:'.$service->uuid.' '."Create Successful. JobId: $jobId",
            'Create', null, null, $service->user_uuid);
        $service->setProvisionStatus('completed');
        $service->create_error = '';
        $service->activated_date = now();
        $service->save();
    }

    public function suspend(): array
    {
        return ['status' => 'success'];
    }

    public function suspendCallback($result, $jobId = null): void
    {
        $service = \App\Models\Service::find($this->service_uuid);

        if ($result['status'] == 'error') {
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            $service->setProvisionStatus('error');
            $service->save();

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity('info', 'Service:'.$service->uuid.' '."Suspend Successful. JobId: $jobId",
            'Suspend', null, null, $service->user_uuid);
        $service->setProvisionStatus('suspended');
        $service->suspended_date = now();
        $service->save();
    }

    public function unsuspend(): array
    {
        return ['status' => 'success'];
    }

    public function unsuspendCallback($result, $jobId = null): void
    {
        $service = \App\Models\Service::find($this->service_uuid);

        if ($result['status'] == 'error') {
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            $service->setProvisionStatus('error');
            $service->save();

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity('info', 'Service:'.$service->uuid.' '."Unsuspend Successful. JobId: $jobId",
            'Unsuspend', null, null, $service->user_uuid);
        $service->setProvisionStatus('completed');
        $service->suspended_date = null;
        $service->save();
    }

    public function idle(): array
    {
        return ['status' => 'success'];
    }

    public function unidle(): array
    {
        return ['status' => 'success'];
    }

    public function termination(): array
    {
        return ['status' => 'success'];
    }

    public function terminationCallback($result, $jobId = null): void
    {
        $service = \App\Models\Service::find($this->service_uuid);

        if ($result['status'] == 'error') {
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            $service->setProvisionStatus('error');
            $service->save();

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity('info', 'Service:'.$service->uuid.' '."Termination Successful. JobId: $jobId",
            'Termination', null, null, $service->user_uuid);
        $service->setProvisionStatus('terminated');
        $service->terminated_date = now();
        $service->save();
    }

    public function cancellation(): array
    {
        return ['status' => 'success'];
    }

    public function cancellationCallback($result, $jobId = null): void
    {
        $service = \App\Models\Service::find($this->service_uuid);

        if ($result['status'] == 'error') {
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            $service->setProvisionStatus('error');
            $service->save();

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity('info', 'Service:'.$service->uuid.' '."Cancellation Successful. JobId: $jobId",
            'Cancellation', null, null, $service->user_uuid);
        $service->setProvisionStatus('cancellated');
        $service->cancelled_date = now();
        $service->save();
    }

    public function change_package(): array
    {
        return ['status' => 'success'];
    }

    public function change_packageCallback($result, $jobId = null): void
    {
        $service = \App\Models\Service::find($this->service_uuid);

        if ($result['status'] == 'error') {
            $this->logError(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
            $service->setProvisionStatus('error');
            $service->save();

            return;
        }

        $this->logInfo(__FUNCTION__, $service, ['result' => $result, 'jobId' => $jobId]);
        logActivity('info', 'Service:'.$service->uuid.' '."Change Package Successful. JobId: $jobId",
            'Change Package', null, null, $service->user_uuid);
        $service->setProvisionStatus('completed');
        $service->cancelled_date = now();
        $service->save();
    }
}
