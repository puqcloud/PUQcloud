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

use App\Models\Service;

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

    public function suspend(): array
    {
        return ['status' => 'success'];
    }

    public function unsuspend(): array
    {
        return ['status' => 'success'];
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

    public function cancellation(): array
    {
        return ['status' => 'success'];
    }

    public function change_package(): array
    {
        return ['status' => 'success'];
    }
}
