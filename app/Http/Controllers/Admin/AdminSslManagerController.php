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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateAuthority;
use App\Models\Module;
use App\Models\SslCertificate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminSslManagerController extends Controller
{
    public function certificateAuthorities(): View
    {
        $title = __('main.Certificate Authorities');

        return view_admin('certificate_authorities.certificate_authorities', compact('title'));
    }

    public function certificateAuthority(Request $request, $uuid): View
    {
        $title = __('main.Certificate Authority');

        return view_admin('certificate_authorities.certificate_authority', compact('title', 'uuid'));
    }

    public function getCertificateAuthorities(Request $request): JsonResponse
    {
        $query = CertificateAuthority::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");

                        });
                    }
                })
                ->addColumn('module_data', function ($model) {
                    return $model->getModuleConfig();
                })
                ->addColumn('urls', function ($model) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('dns-manager-dns-servers')) {
                        $urls['edit'] = route('admin.web.certificate_authority', $model->uuid);
                        $urls['delete'] = route('admin.api.certificate_authority.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postCertificateAuthority(Request $request): JsonResponse
    {
        $model = new CertificateAuthority();
        $modules = Module::query()->where('type', 'CertificateAuthority')->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:dns_servers,name',
            'module' => 'required|in:'.implode(',', $modules),
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
            'module.in' => __('error.The selected Module is invalid'),
            'module.required' => __('error.The Module field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name') && !empty($request->input('name'))) {
            $model->name = $request->input('name');
        }
        if ($request->has('module') && !empty($request->input('module'))) {
            $model->module_uuid = $request->input('module');
        }

        $model->configuration = json_encode([]);
        $model->description = $request->input('description') ?? '';

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
            'redirect' => route('admin.web.certificate_authority', $model->uuid),
        ]);
    }

    public function getCertificateAuthority(Request $request, $uuid): JsonResponse
    {
        $model = CertificateAuthority::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $module_html = $model->getSettingsPage();
        $responseData = $model->toArray();
        $responseData['module_html'] = $module_html;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

    public function putCertificateAuthority(Request $request, $uuid): JsonResponse
    {

        $model = CertificateAuthority::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'unique:certificate_authorities,name,'.$model->uuid.',uuid',
        ], [
            'name.unique' => __('error.The name is already in taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->name = $request->input('name');
        $model->description = $request->input('description') ?? '';

        $save_module_data = $model->saveModuleData($request->all());

        if ($save_module_data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $save_module_data['message'],
            ], $save_module_data['code']);
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function deleteCertificateAuthority(Request $request, $uuid): JsonResponse
    {
        $model = CertificateAuthority::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);

    }

    public function getCertificateAuthorityModulesSelect(Request $request): JsonResponse
    {
        $Module_models = Module::all();
        $modules = [];
        foreach ($Module_models as $module) {
            if ($module->type != 'CertificateAuthority') {
                continue;
            }
            $module_name = !empty($module->module_data['name']) ? $module->module_data['name'] : $module->name;
            $modules[] = [
                'id' => $module->uuid,
                'text' => $module_name.' ('.$module->status.')',
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($modules, function ($module) use ($searchTerm) {
            return empty($searchTerm) || stripos($module['text'], $searchTerm) !== false;
        });

        return response()->json([
            'data' => [
                'results' => array_values($filteredModules),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
    }

    public function getCertificateAuthorityTestConnection(Request $request, $uuid): JsonResponse
    {
        $model = CertificateAuthority::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $test_connection = $model->testConnection();

        if ($test_connection['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $test_connection['errors'],
            ], $test_connection['code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $test_connection['data'] ?? '',
        ], 200);
    }


    public function sslCertificates(): View
    {
        $title = __('main.SSL Certificates');

        return view_admin('ssl_certificates.ssl_certificates', compact('title'));
    }

    public function sslCertificate(Request $request, $uuid): View
    {
        $title = __('main.SSL Certificate');

        return view_admin('ssl_certificates.ssl_certificate', compact('title', 'uuid'));
    }

    public function getSslCertificates(Request $request): JsonResponse
    {
        $columns = Schema::getColumnListing('ssl_certificates');
        $exclude = ['private_key_pem', 'csr_pem', 'certificate_pem', 'configuration'];
        $columns = array_diff($columns, $exclude);

        $query = SslCertificate::select($columns);

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('domain', 'like', "%{$search}%")
                                ->orWhere('aliases', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('certificate_authority_uuid', 'like', "%{$search}%")
                                ->orWhere('issuer', 'like', "%{$search}%")
                                ->orWhere('serial_number_dec', 'like', "%{$search}%")
                                ->orWhere('signature_algorithm', 'like', "%{$search}%");

                            $cleanSearch = str_replace(':', '', $search);
                            $q->orWhereRaw("REPLACE(certificate_fingerprint_md5, ':', '') LIKE ?", ["%{$cleanSearch}%"])
                                ->orWhereRaw("REPLACE(certificate_fingerprint_sha1, ':', '') LIKE ?",
                                    ["%{$cleanSearch}%"])
                                ->orWhereRaw("REPLACE(certificate_fingerprint_sha256, ':', '') LIKE ?",
                                    ["%{$cleanSearch}%"]);
                        });
                    }
                })
                ->addColumn('certificate_authority_data', function ($model) {
                    $certificate_authority = $model->certificateAuthority;

                    return [
                        'name' => $certificate_authority->name,
                        'module_name' => $certificate_authority->module->module_data['name'] ?? $certificate_authority->module->name,
                    ];
                })
                ->addColumn('days_emaining', function ($model) {
                    return $model->daysRemaining();
                })
                ->addColumn('urls', function ($model) {
                    $admin = app('admin');
                    $urls = [];

                    if ($admin->hasPermission('dns-manager-dns-servers')) {
                        $urls['edit'] = route('admin.web.ssl_certificate', $model->uuid);
                        $urls['delete'] = route('admin.api.ssl_certificate.delete', $model->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function postSslCertificate(Request $request): JsonResponse
    {
        $model = new SslCertificate();
        $cas = CertificateAuthority::query()->get()->pluck('uuid')->toArray();
        $domainRegex = '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i';

        $validator = Validator::make($request->all(), [
            'domain' => [
                'required',
                'regex:'.$domainRegex,
            ],
            'certificate_authority_uuid' => 'required|in:'.implode(',', $cas),
            'aliases' => 'nullable|string',
        ], [
            'domain.required' => __('error.The domain field is required'),
            'domain.regex' => __('error.The domain format is invalid'),
            'certificate_authority_uuid.in' => __('error.The selected Certificate Authority is invalid'),
            'certificate_authority_uuid.required' => __('error.The Certificate Authority field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $model->domain = $request->input('domain');
        $model->certificate_authority_uuid = $request->input('certificate_authority_uuid');

        $aliasesInput = $request->input('aliases', '');
        $aliases = collect(explode("\n", $aliasesInput))
            ->map(fn($alias) => trim($alias))
            ->filter(fn($alias) => $alias !== '' && strtolower($alias) !== strtolower($model->domain))
            ->unique()
            ->values()
            ->all();

        $invalidAliases = [];
        foreach ($aliases as $alias) {
            if (!preg_match($domainRegex, $alias)) {
                $invalidAliases[] = $alias;
            }
        }

        if (!empty($invalidAliases)) {
            return response()->json([
                'errors' => [__('error.Invalid aliases: :aliases', ['aliases' => implode(', ', $invalidAliases)])],
                'message' => [
                    'aliases' => [
                        __('error.Invalid aliases: :aliases', ['aliases' => implode(', ', $invalidAliases)]),
                    ],
                ],
            ], 422);
        }

        $model->aliases = $aliases;
        $model->configuration = json_encode([]);

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => $model,
            'redirect' => route('admin.web.ssl_certificate', $model->uuid),
        ]);
    }

    public function putSslCertificate(Request $request, $uuid): JsonResponse
    {
        $model = SslCertificate::find($uuid);

        if (!$model) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $status = $model->status;

        if ($status !== 'draft') {
            $validator = Validator::make($request->all(), [
                'auto_renew_days' => 'integer|min:0',
            ], [
                'auto_renew_days.integer' => __('error.Auto renew days must be an integer'),
                'auto_renew_days.min' => __('error.Auto renew days cannot be negative'),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            $model->auto_renew_days = $data['auto_renew_days'] ?? 0;

        } else {
            $ca = $model->certificateAuthority;

            $domainRegex = '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i';

            $validator = Validator::make($request->all(), [
                'domain' => ['required', 'regex:'.$domainRegex],
                'auto_renew_days' => 'integer|min:0',
                'aliases' => 'nullable|string',
                'wildcard' => 'nullable|string|in:yes,no,1,0',
                'email' => 'nullable|email|max:255',
                'organization' => 'nullable|string|max:255',
                'organizational_unit' => 'nullable|string|max:255',
                'country' => 'nullable|string|size:2', // ISO 3166-1 alpha-2
                'state' => 'nullable|string|max:255',
                'locality' => 'nullable|string|max:255',
            ], [
                'domain.required' => __('error.The domain field is required'),
                'domain.regex' => __('error.The domain format is invalid'),
                'auto_renew_days.integer' => __('error.Auto renew days must be an integer'),
                'auto_renew_days.min' => __('error.Auto renew days cannot be negative'),
                'email.email' => __('error.Invalid email address'),
                'email.max' => __('error.Email is too long'),
                'organization.max' => __('error.Organization is too long'),
                'organizational_unit.max' => __('error.Organizational Unit is too long'),
                'country.size' => __('error.Country must be a 2-letter ISO code'),
                'state.max' => __('error.State name is too long'),
                'locality.max' => __('error.Locality name is too long'),
                'wildcard.in' => __('error.Wildcard value must be yes or no'),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            $aliasesInput = $data['aliases'] ?? '';
            $aliases = collect(preg_split('/[\r\n,;]+/', $aliasesInput))
                ->map(fn($alias) => trim($alias))
                ->filter(fn($alias) => $alias !== '' && strtolower($alias) !== strtolower($data['domain']))
                ->unique()
                ->values()
                ->all();

            $invalidAliases = [];
            foreach ($aliases as $alias) {
                if (!preg_match($domainRegex, $alias)) {
                    $invalidAliases[] = $alias;
                }
            }

            if ($invalidAliases) {
                return response()->json([
                    'message' => [
                        'aliases' => [
                            __('error.Invalid aliases: :aliases', ['aliases' => implode(', ', $invalidAliases)]),
                        ],
                    ],
                ], 422);
            }

            if (empty($ca->configuration['allow_wildcard']) && ($data['wildcard'] ?? 'no') === 'yes') {
                return response()->json([
                    'message' => [
                        'wildcard' => [
                            __('error.Wildcard certificates are not allowed by the selected Certificate Authority'),
                        ],
                    ],
                ], 422);
            }

            $model->fill([
                'domain' => $data['domain'],
                'aliases' => $aliases ?? [],
                'wildcard' => in_array($data['wildcard'] ?? 'no', ['yes', '1']),
                'auto_renew_days' => $data['auto_renew_days'] ?? 0,
                'email' => $data['email'] ?? null,
                'organization' => $data['organization'] ?? null,
                'organizational_unit' => $data['organizational_unit'] ?? null,
                'country' => isset($data['country']) ? strtoupper($data['country']) : null,
                'state' => $data['state'] ?? null,
                'locality' => $data['locality'] ?? null,
            ]);

            $save_module_data = $model->saveModuleData($request->all());

            if ($save_module_data['status'] == 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => $save_module_data['message'],
                ], $save_module_data['code']);
            }
        }

        $model->save();
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }


    public function getSslCertificate(Request $request, $uuid): JsonResponse
    {
        $model = SslCertificate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $module_html = $model->getSettingsPage();
        $responseData = $model->toArray();
        $responseData['days_remaining'] = $model->daysRemaining();
        $responseData['private_key_pem'] = $model->private_key_pem;
        $responseData['csr_pem']= $model->csr_pem;
        $responseData['certificate_pem']= $model->certificate_pem;

        $responseData['module_html'] = $module_html;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

    public function deleteSslCertificate(Request $request, $uuid): JsonResponse
    {
        $model = SslCertificate::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $model->delete();
            if (!$deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [__('error.Deletion failed:').' '.$e->getMessage()],
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);

    }

    public function getSslCertificateGenerateCsr(Request $request, $uuid): JsonResponse
    {
        $model = SslCertificate::find($uuid);
        if (!$model) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $generate_csr = $model->generateCsr();
        if ($generate_csr['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'errors' => $generate_csr['errors'] ?? __('error.Unknown error'),
            ], $generate_csr['code'] ?? 500);
        }
        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    public function putSslCertificateStatus(Request $request, $uuid): JsonResponse
    {
        $model = SslCertificate::find($uuid);
        if (!$model) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $status = $request->input('status');

        $allowed = ['draft', 'pending', 'processing', 'active', 'expired', 'revoked', 'failed'];

        if (!in_array($status, $allowed, true)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Invalid status value')],
            ], 422);
        }

        $model->status = $status;
        $model->save();

        $model->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }


    public function getCertificateAuthoritiesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = CertificateAuthority::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $ca = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $ca->map(function ($model) {
            return [
                'id' => $model->uuid,
                'text' => $model->name,
            ];
        });

        return response()->json([
            'data' => [
                'results' => $results,
                'pagination' => [
                    'more' => ($page * $perPage) < $total,
                ],
            ],
        ]);
    }


}
