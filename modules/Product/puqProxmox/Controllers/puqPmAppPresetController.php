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

namespace Modules\Product\puqProxmox\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CertificateAuthority;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Product\puqProxmox\Models\PuqPmAppEndpoint;
use Modules\Product\puqProxmox\Models\PuqPmAppEndpointLocation;
use Modules\Product\puqProxmox\Models\PuqPmAppPreset;
use Modules\Product\puqProxmox\Models\PuqPmDnsZone;
use Modules\Product\puqProxmox\Models\PuqPmLxcPreset;
use Yajra\DataTables\DataTables;

class puqPmAppPresetController extends Controller
{
    public function appPresets(Request $request): View
    {
        $title = __('Product.puqProxmox.App Presets');

        return view_admin_module('Product', 'puqProxmox', 'admin_area.app_presets.app_presets', compact('title'));
    }

    public function getAppPresets(Request $request): JsonResponse
    {
        $query = PuqPmAppPreset::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) {
                    $urls = [];
                    $urls['edit'] = route(
                        'admin.web.Product.puqProxmox.app_preset.tab',
                        ['uuid' => $model->uuid, 'tab' => 'general']
                    );

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function appPresetTab(Request $request, $uuid, $tab): View|RedirectResponse
    {

        $app_preset = PuqPmAppPreset::findOrFail($uuid);

        $validTabs = [
            'general',
            'custom_page',
            'app_endpoints',
            'docker_composer',
            'install_script',
            'update_script',
            'status_script',
        ];

        if (!in_array($tab, $validTabs)) {
            return redirect()->route(
                'admin.web.Product.puqProxmox.app_preset.tab',
                ['uuid' => $app_preset->uuid, 'tab' => 'general']
            );
        }

        $title = $app_preset->name;
        $locales = config('locale.client.locales');

        return view_admin_module(
            'Product',
            'puqProxmox',
            'admin_area.app_presets.app_preset_'.$tab,
            compact('title', 'uuid', 'tab', 'app_preset', 'locales')
        );
    }

    public function postAppPreset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmAppPreset();
        $model->name = $request->input('name');

        $puq_pm_lxc_preset = PuqPmLxcPreset::find($request->input('puq_pm_lxc_preset_uuid'));

        if (empty($puq_pm_lxc_preset)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.LXC Preset not found')],
            ], 404);
        }

        $model->puq_pm_lxc_preset_uuid = $puq_pm_lxc_preset->uuid;
        $puq_pm_lxc_os_template = $puq_pm_lxc_preset->puqPmLxcOsTemplates()->first();

        if (empty($puq_pm_lxc_os_template)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.OS Template not found')],
            ], 404);
        }

        $model->puq_pm_lxc_os_template_uuid = $puq_pm_lxc_os_template->uuid;

        $dns_zone = PuqPmDnsZone::find($request->input('puq_pm_dns_zone_uuid'));

        if (empty($dns_zone)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.DNS Zone not found')],
            ], 404);
        }

        $model->puq_pm_dns_zone_uuid = $dns_zone->uuid;


        $certificate_authority = CertificateAuthority::find($request->input('certificate_authority_uuid'));

        if (empty($certificate_authority)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Certificate Authority not found')],
            ], 404);
        }

        $model->certificate_authority_uuid = $certificate_authority->uuid;

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function getAppPreset(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->toArray();
        $puq_pm_lxc_preset = $model->puqPmLxcPreset;

        $data['puq_pm_lxc_preset_data'] = [
            'id' => $puq_pm_lxc_preset->uuid,
            'text' => $puq_pm_lxc_preset->name,
        ];

        $puq_pm_lxc_os_template = $model->puqPmLxcOsTemplate;
        $data['puq_pm_lxc_os_template_data'] = [
            'id' => $puq_pm_lxc_os_template->uuid,
            'text' => $puq_pm_lxc_os_template->name,
        ];

        $puq_pm_lxc_dns_zone = $model->puqPmDnsZone;
        $data['puq_pm_dns_zone_data'] = [
            'id' => $puq_pm_lxc_dns_zone->uuid,
            'text' => $puq_pm_lxc_dns_zone->name,
        ];

        $certificate_authority = $model->CertificateAuthority;
        $data['certificate_authority_data'] = [
            'id' => $certificate_authority->uuid,
            'text' => $certificate_authority->name,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putAppPreset(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'version' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'puq_pm_lxc_preset_uuid' => 'required|exists:puq_pm_lxc_presets,uuid',
            'puq_pm_lxc_os_template_uuid' => 'required|exists:puq_pm_lxc_os_templates,uuid',
            'puq_pm_dns_zone_uuid' => 'required|exists:puq_pm_dns_zones,uuid',
            'certificate_authority_uuid' => 'required|exists:certificate_authorities,uuid',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'name.regex' => __('Product.puqProxmox.Only uppercase letters, digits, dashes and underscores are allowed'),
            'version.string' => __('Product.puqProxmox.Version must be a string'),
            'description.string' => __('Product.puqProxmox.Description must be a string'),
            'puq_pm_lxc_preset_uuid.required' => __('Product.puqProxmox.LXC Preset is required'),
            'puq_pm_lxc_preset_uuid.exists' => __('Product.puqProxmox.Invalid LXC Preset UUID'),
            'puq_pm_lxc_os_template_uuid.required' => __('Product.puqProxmox.LXC OS Template is required'),
            'puq_pm_lxc_os_template_uuid.exists' => __('Product.puqProxmox.Invalid LXC OS Template UUID'),
            'puq_pm_dns_zone_uuid.required' => __('Product.puqProxmox.DNS Zone is required'),
            'puq_pm_dns_zone_uuid.exists' => __('Product.puqProxmox.Invalid DNS Zone UUID'),
            'certificate_authority_uuid.required' => __('Product.puqProxmox.Certificate Authority is required'),
            'certificate_authority_uuid.exists' => __('Product.puqProxmox.Invalid Certificate Authority UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->version = $request->input('version');
        $model->description = $request->input('description');

        $envVariables = $request->input('env_variables');
        $decodedEnv = [];

        if (!empty($envVariables)) {
            $decodedEnv = json_decode($envVariables, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedEnv)) {
                return response()->json([
                    'message' => ['env_variables' => [__('Product.puqProxmox.Environment Variables must be valid JSON array')]],
                ], 422);
            }

            $keys = [];
            foreach ($decodedEnv as $index => $item) {
                $key = $item['key'] ?? null;
                if (empty($key)) {
                    return response()->json([
                        'message' => [
                            'env_variables' => [
                                __('Product.puqProxmox.Environment Variable key is required at index :index',
                                    ['index' => $index]),
                            ],
                        ],
                    ], 422);
                }
                if (in_array($key, $keys)) {
                    return response()->json([
                        'message' => [
                            'env_variables' => [
                                __('Product.puqProxmox.Duplicate Environment Variable key ":key"', ['key' => $key]),
                            ],
                        ],
                    ], 422);
                }
                $keys[] = $key;
            }
        }
        $model->env_variables = $decodedEnv;

        $model->puq_pm_lxc_preset_uuid = $request->input('puq_pm_lxc_preset_uuid');
        $model->puq_pm_lxc_os_template_uuid = $request->input('puq_pm_lxc_os_template_uuid');
        $model->puq_pm_dns_zone_uuid = $request->input('puq_pm_dns_zone_uuid');
        $model->certificate_authority_uuid = $request->input('certificate_authority_uuid');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function getAppPresetCustomPage(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if (!empty($request->input('locale'))) {
            $model->setLocale($request->input('locale'));
        }

        $data['custom_page'] = $model->custom_page;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putAppPresetCustomPage(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        if (!empty($request->input('locale'))) {
            $model->setLocale($request->input('locale'));
        }

        $model->custom_page = $request->input('custom_page');

        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
        ]);
    }


    public function deleteAppPreset(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'redirect' => route('admin.web.Product.puqProxmox.app_presets'),
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    public function getAppPresetScript(Request $request, $uuid, $type): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $data = $model->puqPmScripts()->where('type', $type)->first();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function putAppPresetScript(Request $request, $uuid, $type): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $scriptModel = $model->puqPmScripts()->where('type', $type)->first();

        if ($scriptModel) {
            $scriptModel->script = $request->input('script');
            $scriptModel->save();
        } else {
            $scriptModel = $model->puqPmScripts()->create([
                'type' => $type,
                'script' => $request->input('script'),
                'model' => PuqPmAppPreset::class,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $scriptModel,
        ]);
    }

    public function getAppPresetsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (!empty($search)) {
            $models = PuqPmAppPreset::query()->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $models = PuqPmAppPreset::query()->get();
        }

        $results = [];
        foreach ($models->toArray() ?? [] as $model) {

            $text = $model['name'];
            if (!empty($model['version'])) {
                $text .= ' ('.$model['version'].')';
            }

            $results[] = [
                'id' => $model['uuid'],
                'text' => $text,
            ];
        }

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function getAppPresetExportJson(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $json_data = $model->exportJson();

        if ($json_data['status'] === 'error') {
            return response()->json([
                'errors' => $json_data['errors'],
            ], $json_data['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $json_data['data'],
        ]);
    }

    public function postAppPresetImportJson(Request $request, $uuid): JsonResponse
    {
        $raw = $request->input('import');

        if (empty($raw)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.No import data')],
            ], 422);
        }

        $import = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Invalid JSON'), json_last_error_msg()],
            ], 422);
        }

        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $result = $model->importJson($import);

        if (!empty($result['status']) && $result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $result['message'] ?? [__('Product.puqProxmox.Import failed')],
            ]);
        }

        return response()->json([
            'status' => 'success',
        ]);
    }


    // App Endpoints
    public function getAppPresetAppEndpoints(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppPreset::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmAppEndpoints();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('uuid', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) use ($uuid) {
                    $urls = [];

                    $urls['edit'] = route('admin.web.Product.puqProxmox.app_preset.tab',
                        ['uuid' => $uuid, 'tab' => 'app_endpoints', 'edit' => $model->uuid]);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.app_endpoint.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getAppEndpoint(Request $request, $uuid): JsonResponse
    {

        $model = PuqPmAppEndpoint::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    public function postAppEndpoint(Request $request): JsonResponse
    {
        $model = new PuqPmAppEndpoint;

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'puq_pm_app_preset_uuid' => 'required|exists:puq_pm_app_presets,uuid',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
            'puq_pm_app_preset_uuid.required' => __('Product.puqProxmox.App Preset is required'),
            'puq_pm_app_preset_uuid.exists' => __('Product.puqProxmox.App Preset not found'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->puq_pm_app_preset_uuid = $request->input('puq_pm_app_preset_uuid');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function putAppEndpoint(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppEndpoint::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => __('Product.puqProxmox.The Name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->subdomain = $request->input('subdomain');
        $model->server_custom_config_before = $request->input('server_custom_config_before');
        $model->server_custom_config = $request->input('server_custom_config');
        $model->server_custom_config_after = $request->input('server_custom_config_after');
        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteAppEndpoint(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppEndpoint::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('Product.puqProxmox.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

    // App Endpoint Locations

    public function getAppEndpointAppEndpointLocations(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppEndpoint::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $query = $model->puqPmAppEndpointLocations();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('uuid', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($model) use ($uuid) {
                    $urls = [];

                    $urls['get'] = route('admin.api.Product.puqProxmox.app_endpoint_location.get', $model->uuid);
                    $urls['put'] = route('admin.api.Product.puqProxmox.app_endpoint_location.put', $model->uuid);
                    $urls['delete'] = route('admin.api.Product.puqProxmox.app_endpoint_location.delete', $model->uuid);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getAppEndpointLocation(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppEndpointLocation::find($uuid);

        if (!$model) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $model,
        ]);
    }

    public function postAppEndpointLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => [
                'required',
                'string',
                'max:255',
                Rule::unique('puq_pm_app_endpoint_locations')
                    ->where('puq_pm_app_endpoint_uuid', $request->input('puq_pm_app_endpoint_uuid')),
            ],
            'puq_pm_app_endpoint_uuid' => 'required|exists:puq_pm_app_endpoints,uuid',
            'proxy_protocol' => 'required|string|in:http,https',
            'proxy_port' => 'required|integer',
            'proxy_path' => 'nullable|string|max:255',
        ], [
            'path.required' => __('Product.puqProxmox.Path is required'),
            'path.string' => __('Product.puqProxmox.Path must be a string'),
            'path.max' => __('Product.puqProxmox.Path too long'),
            'path.unique' => __('Product.puqProxmox.Path already used for this endpoint'),

            'proxy_protocol.required' => __('Product.puqProxmox.Proxy protocol is required'),
            'proxy_protocol.string' => __('Product.puqProxmox.Proxy protocol must be a string'),
            'proxy_protocol.in' => __('Product.puqProxmox.Proxy protocol must be http or https'),

            'proxy_port.required' => __('Product.puqProxmox.Proxy port is required'),
            'proxy_port.integer' => __('Product.puqProxmox.Proxy port must be an integer'),
            'proxy_port.min' => __('Product.puqProxmox.Proxy port must be at least 1'),
            'proxy_port.max' => __('Product.puqProxmox.Proxy port cannot be more than 65535'),

            'proxy_path.required' => __('Product.puqProxmox.Proxy path is required'),
            'proxy_path.string' => __('Product.puqProxmox.Proxy path must be a string'),
            'proxy_path.max' => __('Product.puqProxmox.Proxy path too long'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model = new PuqPmAppEndpointLocation;

        $path = ltrim($request->input('path'), '/');
        $model->path = '/'.$path;

        $model->proxy_path = null;
        if (!empty($request->input('proxy_path'))) {
            $proxyPath = ltrim($request->input('proxy_path'), '/');
            $model->proxy_path = '/'.$proxyPath;
        }

        $model->puq_pm_app_endpoint_uuid = $request->input('puq_pm_app_endpoint_uuid');
        $model->show_to_client = $request->input('show_to_client') === 'yes';
        $model->proxy_protocol = $request->input('proxy_protocol');
        $model->proxy_port = $request->input('proxy_port');
        $model->custom_config = $request->input('custom_config');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Created successfully'),
            'data' => $model,
        ]);
    }

    public function putAppEndpointLocation(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppEndpointLocation::find($uuid);

        if (!$model) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'path' => [
                'required',
                'string',
                'max:255',
                Rule::unique('puq_pm_app_endpoint_locations')
                    ->ignore($model->uuid, 'uuid')
                    ->where('puq_pm_app_endpoint_uuid', $model->puq_pm_app_endpoint_uuid),
            ],
            'proxy_protocol' => 'required|string|in:http,https',
            'proxy_port' => 'required|integer',
            'proxy_path' => 'nullable|string|max:255',
        ], [
            'path.required' => __('Product.puqProxmox.Path is required'),
            'path.string' => __('Product.puqProxmox.Path must be a string'),
            'path.max' => __('Product.puqProxmox.Path too long'),
            'path.unique' => __('Product.puqProxmox.Path already used for this endpoint'),

            'proxy_protocol.required' => __('Product.puqProxmox.Proxy protocol is required'),
            'proxy_protocol.string' => __('Product.puqProxmox.Proxy protocol must be a string'),
            'proxy_protocol.in' => __('Product.puqProxmox.Proxy protocol must be http or https'),

            'proxy_port.required' => __('Product.puqProxmox.Proxy port is required'),
            'proxy_port.integer' => __('Product.puqProxmox.Proxy port must be an integer'),
            'proxy_port.min' => __('Product.puqProxmox.Proxy port must be at least 1'),
            'proxy_port.max' => __('Product.puqProxmox.Proxy port cannot be more than 65535'),

            'proxy_path.required' => __('Product.puqProxmox.Proxy path is required'),
            'proxy_path.string' => __('Product.puqProxmox.Proxy path must be a string'),
            'proxy_path.max' => __('Product.puqProxmox.Proxy path too long'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $path = ltrim($request->input('path'), '/');
        $model->path = '/'.$path;

        $model->proxy_path = null;
        if (!empty($request->input('proxy_path'))) {
            $proxyPath = ltrim($request->input('proxy_path'), '/');
            $model->proxy_path = '/'.$proxyPath;
        }

        $model->show_to_client = $request->input('show_to_client') === 'yes';
        $model->proxy_protocol = $request->input('proxy_protocol');
        $model->proxy_port = $request->input('proxy_port');
        $model->custom_config = $request->input('custom_config');

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteAppEndpointLocation(Request $request, $uuid): JsonResponse
    {
        $model = PuqPmAppEndpointLocation::find($uuid);

        if (!$model) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Not found')],
            ], 404);
        }

        try {
            $model->delete();
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('Product.puqProxmox.Deletion failed').': '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product.puqProxmox.Deleted successfully'),
        ]);
    }

}
