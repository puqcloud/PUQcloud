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
use App\Modules\Plugin;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class puqSamplePlugin extends Plugin
{
    public function __construct()
    {
        parent::__construct();
    }

    public function activate(): string
    {
        try {
            Schema::dropIfExists('puq_sample_plugins');

            Schema::create('puq_sample_plugins', function (Blueprint $table) {
                $table->id()->primary();
                $table->string('name')->unique();
                $table->string('test')->nullable();
                $table->string('test2')->nullable();
                $table->timestamps();
            });
            $this->logInfo('activate', 'Success');

            return 'success';
        } catch (QueryException $e) {
            $this->logError('activate', 'Error activating plugin: '.$e->getMessage());

            return 'Error activating plugin: '.$e->getMessage();
        } catch (\Exception $e) {
            $this->logError('activate', 'Unexpected error activating plugin: '.$e->getMessage());

            return 'Unexpected error activating plugin: '.$e->getMessage();
        }
    }

    public function deactivate(): string
    {
        try {
            Schema::drop('puq_sample_plugins');
            $this->logInfo('deactivate', 'Success');

            return 'success';
        } catch (QueryException $e) {
            $this->logError('deactivate', 'Error deactivating plugin: '.$e->getMessage());

            return 'Error deactivating plugin: '.$e->getMessage();
        } catch (\Exception $e) {
            $this->logError('deactivate', 'Unexpected error deactivating plugin: '.$e->getMessage());

            return 'Unexpected error deactivating plugin: '.$e->getMessage();
        }
    }

    public function update(): string
    {
        $this->activate();

        return 'success';
    }

    public function adminPermissions(): array
    {
        return [
            [
                'name' => 'Info',
                'key' => 'info',
                'description' => 'Info permission',
            ],
            [
                'name' => 'Simple Model Example',
                'key' => 'simple-model-example',
                'description' => 'Example permissions for a simple model',
            ],
            [
                'name' => 'Simple API requests',
                'key' => 'simple-api-requests',
                'description' => 'Example permissions for a simple api requests',
            ],

            [
                'name' => 'Create Simple Model',
                'key' => 'create-simple-model',
                'description' => 'Example permissions for Create Simple Model',
            ],
            [
                'name' => 'Edit Simple Model',
                'key' => 'edit-simple-model',
                'description' => 'Example permissions for Edit Simple Model',
            ],
            [
                'name' => 'Delete Simple Model',
                'key' => 'delete-simple-model',
                'description' => 'Example permissions for Delete Simple Model',
            ],
        ];
    }

    public function adminSidebar(): array
    {
        return [
            [
                'title' => 'Info',
                'link' => 'info',
                'active_links' => ['info'],
                'permission' => 'info',
            ],
            [
                'title' => 'Simple Model Example',
                'link' => 'simple_model_example',
                'active_links' => ['simple_model_example'],
                'permission' => 'simple-model-example',
            ],
            [
                'title' => 'Simple API requests',
                'link' => 'simple_api_requests',
                'active_links' => ['simple_api_requests'],
                'permission' => 'simple-api-requests',
            ],
        ];
    }

    public function adminWebRoutes(): array
    {
        return [
            [
                'method' => 'get',
                'uri' => 'info',
                'permission' => 'info',
                'name' => 'info',
                'controller' => 'puqSamplePluginController@info',
            ],
            [
                'method' => 'get',
                'uri' => 'simple_model_example',
                'permission' => 'simple-model-example',
                'name' => 'simple_model_example',
                'controller' => 'puqSamplePluginController@simpleModelExample',
            ],
            [
                'method' => 'get',
                'uri' => 'simple_api_requests',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_requests',
                'controller' => 'puqSamplePluginController@simpleApiRequests',
            ],
        ];
    }

    public function adminApiRoutes(): array
    {
        return [
            [
                'method' => 'get',
                'uri' => 'simple_api_request',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_request.get',
                'controller' => 'puqSamplePluginController@getApiRequest',
            ],
            [
                'method' => 'put',
                'uri' => 'simple_api_request',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_request.put',
                'controller' => 'puqSamplePluginController@putApiRequest',
            ],
            [
                'method' => 'post',
                'uri' => 'simple_api_request',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_request.post',
                'controller' => 'puqSamplePluginController@postApiRequest',
            ],
            [
                'method' => 'delete',
                'uri' => 'simple_api_request',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_request.delete',
                'controller' => 'puqSamplePluginController@deleteApiRequest',
            ],

            [
                'method' => 'get',
                'uri' => 'simple_models',
                'permission' => 'simple-model-example',
                'name' => 'simple_models.get',
                'controller' => 'puqSamplePluginController@getSimpleModels',
            ],
            [
                'method' => 'get',
                'uri' => 'simple_model/{id}',
                'permission' => 'simple-model-example',
                'name' => 'simple_model.get',
                'controller' => 'puqSamplePluginController@getSimpleModel',
            ],
            [
                'method' => 'post',
                'uri' => 'simple_model',
                'permission' => 'create-simple-model',
                'name' => 'simple_model.post',
                'controller' => 'puqSamplePluginController@postSimpleModel',
            ],
            [
                'method' => 'put',
                'uri' => 'simple_model/{id}',
                'permission' => 'edit-simple-model',
                'name' => 'simple_model.put',
                'controller' => 'puqSamplePluginController@putSimpleModel',
            ],
            [
                'method' => 'delete',
                'uri' => 'simple_model/{id}',
                'permission' => 'delete-simple-model',
                'name' => 'simple_model.delete',
                'controller' => 'puqSamplePluginController@deleteSimpleModel',
            ],
        ];
    }
}
