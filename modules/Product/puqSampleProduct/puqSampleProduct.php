<?php

use App\Models\Service;
use App\Models\Task;
use App\Modules\Product;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Product\puqSampleProduct\Controllers\puqSampleProductController;

class puqSampleProduct extends Product
{
    public $product_data;

    public $product_uuid;

    public $service_data;

    public $service_uuid;

    public function __construct()
    {
        parent::__construct();
    }

    public function activate(): string
    {
        try {
            Schema::dropIfExists('puq_sample_products');

            Schema::create('puq_sample_products', function (Blueprint $table) {
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
            Schema::drop('puq_sample_products');
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

    public function getProductData(array $data = []): array
    {
        $this->product_data = [
            'test1' => $data['test1'] ?? '',
            'test2' => $data['test2'] ?? '',
            'test3' => $data['test3'] ?? '',
            'test4' => $data['test4'] ?? '',
        ];

        return $this->product_data;
    }

    public function getProductPage(): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['config'] = $this->config;

        return $this->view('admin_area.product', $data);
    }

    public function saveProductData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'test1' => 'required',
            'test2' => 'required',
            'test3' => 'required',
            'test4' => 'required',
        ], [
            'test1.required' => __('Product.puqSampleProduct.The test1 field is required'),
            'test2.required' => __('Product.puqSampleProduct.The test2 field is required'),
            'test3.required' => __('Product.puqSampleProduct.The test3 field is required'),
            'test4.required' => __('Product.puqSampleProduct.The test4 field is required'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
            'code' => 200,
        ];

    }

    public function getServiceData(array $data = []): array
    {
        $this->service_data = [
            'login' => $data['login'] ?? '',
            'password' => $data['password'] ?? '',
            'domain' => $data['domain'] ?? '',
            'ip' => $data['ip'] ?? '',
        ];

        return $this->service_data;
    }

    public function getServicePage(): string
    {
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['product_uuid'] = $this->product_uuid;
        $data['product_data'] = $this->product_data;
        $data['service_uuid'] = $this->service_uuid;
        $data['service_data'] = $this->service_data;
        $data['config'] = $this->config;

        return $this->view('admin_area.service', $data);
    }

    public function saveServiceData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'login' => 'required',
            'password' => 'required',
            'domain' => 'required',
            'ip' => 'required',
        ], [
            'login.required' => __('Product.puqSampleProduct.The Login field is required'),
            'password.required' => __('Product.puqSampleProduct.The Password field is required'),
            'domain.required' => __('Product.puqSampleProduct.The Domain field is required'),
            'ip.required' => __('Product.puqSampleProduct.The IP field is required'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
            'code' => 200,
        ];

    }

    public function create(): array
    {
        $data = [
            'uuid' => $this->service_uuid,  // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'createJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'create',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function createJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
    }

    public function suspend(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'suspendJob',       // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'suspend',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function suspendJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('pause');

        return ['status' => 'success'];
    }

    public function unsuspend(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'unsuspendJob',     // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'unsuspend',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function unsuspendJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
    }

    public function idle(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'idleJob',          // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'idle',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function idleJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('pause');

        return ['status' => 'success'];
    }

    public function unidle(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'unidleJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'unidle',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function unidleJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
    }

    public function termination(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'terminateJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'terminate',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function terminationJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('not_found');

        return ['status' => 'success'];
    }

    public function cancellation(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'cancellationJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'cancellation',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function cancellationJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('not_found');

        return ['status' => 'success'];
    }

    public function change_package(): array
    {
        $data = [
            'uuid' => $this->service_uuid,                // Unique identifier for the service
            'module' => $this,              // Reference to the current module class (the one that contains the method to run)
            'method' => 'change_packageJob',        // The method name that should be executed inside the job
            'tries' => 1,                   // Number of retry attempts if the job fails
            'backoff' => 60,                // Delay in seconds between retries
            'timeout' => 600,               // Max execution time for the job in seconds
            'maxExceptions' => 1,           // Max number of unhandled exceptions before marking the job as failed
        ];

        $tags = [
            'change_package',
        ];
        Task::add('ModuleJob', 'Module', $data, $tags);

        return ['status' => 'success'];
    }

    public function change_packageJob(): array
    {
        $service = Service::find($this->service_uuid);
        $service->setProvisionStatus('processing');
        sleep(120);          // artificial delay
        $service->setProvisionStatus('completed');

        return ['status' => 'success'];
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
                'controller' => 'puqSampleProductController@info',
            ],
            [
                'method' => 'get',
                'uri' => 'simple_model_example',
                'permission' => 'simple-model-example',
                'name' => 'simple_model_example',
                'controller' => 'puqSampleProductController@simpleModelExample',
            ],
            [
                'method' => 'get',
                'uri' => 'simple_api_requests',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_requests',
                'controller' => 'puqSampleProductController@simpleApiRequests',
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
                'controller' => 'puqSampleProductController@getApiRequest',
            ],
            [
                'method' => 'put',
                'uri' => 'simple_api_request',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_request.put',
                'controller' => 'puqSampleProductController@putApiRequest',
            ],
            [
                'method' => 'post',
                'uri' => 'simple_api_request',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_request.post',
                'controller' => 'puqSampleProductController@postApiRequest',
            ],
            [
                'method' => 'delete',
                'uri' => 'simple_api_request',
                'permission' => 'simple-api-requests',
                'name' => 'simple_api_request.delete',
                'controller' => 'puqSampleProductController@deleteApiRequest',
            ],

            [
                'method' => 'get',
                'uri' => 'simple_models',
                'permission' => 'simple-model-example',
                'name' => 'simple_models.get',
                'controller' => 'puqSampleProductController@getSimpleModels',
            ],
            [
                'method' => 'get',
                'uri' => 'simple_model/{id}',
                'permission' => 'simple-model-example',
                'name' => 'simple_model.get',
                'controller' => 'puqSampleProductController@getSimpleModel',
            ],
            [
                'method' => 'post',
                'uri' => 'simple_model',
                'permission' => 'create-simple-model',
                'name' => 'simple_model.post',
                'controller' => 'puqSampleProductController@postSimpleModel',
            ],
            [
                'method' => 'put',
                'uri' => 'simple_model/{id}',
                'permission' => 'edit-simple-model',
                'name' => 'simple_model.put',
                'controller' => 'puqSampleProductController@putSimpleModel',
            ],
            [
                'method' => 'delete',
                'uri' => 'simple_model/{id}',
                'permission' => 'delete-simple-model',
                'name' => 'simple_model.delete',
                'controller' => 'puqSampleProductController@deleteSimpleModel',
            ],
        ];
    }

    public function scheduler(): array
    {
        return [
            [
                'artisan' => 'test',
                'cron' => '* * * * *',
                'disable' => true,
            ],
        ];
    }

    public function getClientAreaMenuConfig(): array
    {
        return [
            'general' => [
                'name' => 'General',
                'template' => 'client_area.general',
            ],
            'test' => [
                'name' => 'TEST',
                'template' => 'client_area.test',
            ],
        ];
    }

    public function variables_general(): array
    {
        return ['config' => $this->config];
    }

    public function variables_test(): array
    {
        return ['uuid' => $this->service->uuid ?? ''];
    }

    public function controllerClient_testGet(Request $request): JsonResponse
    {
        $controller = new puqSampleProductController;

        return $controller->getApiRequest($request);
    }

    public function controllerClient_testPost(Request $request): JsonResponse
    {
        $controller = new puqSampleProductController;

        return $controller->postApiRequest($request);
    }

    public function controllerClient_testPut(Request $request): JsonResponse
    {
        $controller = new puqSampleProductController;

        return $controller->putApiRequest($request);
    }

    public function controllerClient_testDelete(Request $request): JsonResponse
    {
        $controller = new puqSampleProductController;

        return $controller->deleteApiRequest($request);
    }
}
