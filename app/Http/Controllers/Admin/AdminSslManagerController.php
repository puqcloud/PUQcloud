<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

/*
 * WEB Routes:
 *   GET  certificate_authorities                               -> certificateAuthorities()                  [admin.web.certificate_authorities]
 *   GET  certificate_authority/{uuid}                          -> certificateAuthority()                    [admin.web.certificate_authority]
 *   GET  ssl_certificates                                      -> sslCertificates()                         [admin.web.ssl_certificates]
 *   GET  ssl_certificate/{uuid}                                -> sslCertificate()                          [admin.web.ssl_certificate]
 *
 * API Routes:
 *   GET    certificate_authorities                             -> getCertificateAuthorities()               [admin.api.certificate_authorities.get]
 *   POST   certificate_authority                               -> postCertificateAuthority()                [admin.api.certificate_authority.post]
 *   GET    certificate_authority/{uuid}                        -> getCertificateAuthority()                 [admin.api.certificate_authority.get]
 *   PUT    certificate_authority/{uuid}                        -> putCertificateAuthority()                 [admin.api.certificate_authority.put]
 *   DELETE certificate_authority/{uuid}                        -> deleteCertificateAuthority()              [admin.api.certificate_authority.delete]
 *   GET    certificate_authority/{uuid}/test_connection        -> getCertificateAuthorityTestConnection()   [admin.api.certificate_authority.test_connection.get]
 *   GET    ssl_certificates                                    -> getSslCertificates()                      [admin.api.ssl_certificates.get]
 *   POST   ssl_certificate                                     -> postSslCertificate()                      [admin.api.ssl_certificate.post]
 *   GET    ssl_certificate/{uuid}                              -> getSslCertificate()                       [admin.api.ssl_certificate.get]
 *   PUT    ssl_certificate/{uuid}                              -> putSslCertificate()                       [admin.api.ssl_certificate.put]
 *   DELETE ssl_certificate/{uuid}                              -> deleteSslCertificate()                    [admin.api.ssl_certificate.delete]
 *   GET    ssl_certificate/{uuid}/generate_csr                 -> getSslCertificateGenerateCsr()            [admin.api.ssl_certificate.generate_csr.get]
 *   PUT    ssl_certificate/{uuid}/status                       -> putSslCertificateStatus()                 [admin.api.ssl_certificate.status.put]
 *   DELETE ssl_certificates/delete_expired                     -> deleteSslCertificatesExpired()            [admin.api.ssl_certificates.delete_expired.delete]
 *   DELETE ssl_certificates/delete_draft                       -> deleteSslCertificatesDraft()              [admin.api.ssl_certificates.delete_draft.delete]
 *   GET    certificate_authority_modules/select                -> getCertificateAuthorityModulesSelect()    [admin.api.certificate_authority_modules.select.get]
 *   GET    certificate_authorities/select                      -> getCertificateAuthoritiesSelect()         [admin.api.certificate_authorities.select.get]
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
use OpenApi\Attributes as OA;
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

    #[OA\Get(
        path: 'admin.api.certificate_authorities.get',
        description: 'Returns a DataTable list of certificate authorities.',
        summary: 'Get certificate authorities list',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate authorities list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: 'admin.api.certificate_authority.post',
        description: 'Creates a new certificate authority with specified name, module, and description.',
        summary: 'Create a new certificate authority',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name', 'module'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Certificate authority name (must be unique)', type: 'string'),
                        new OA\Property(property: 'module', description: 'UUID of the module', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'description', description: 'Certificate authority description', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['SSL Manager'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate authority created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.certificate_authority.get',
        description: 'Returns detailed information about a certificate authority.',
        summary: 'Get certificate authority by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the certificate authority',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate authority data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.certificate_authority.put',
        description: 'Updates an existing certificate authority by UUID.',
        summary: 'Update certificate authority by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', description: 'Certificate authority name (must be unique)', type: 'string'),
                        new OA\Property(property: 'description', description: 'Certificate authority description', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the certificate authority',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate authority updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.certificate_authority.delete',
        description: 'Deletes a certificate authority by UUID.',
        summary: 'Delete certificate authority by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the certificate authority to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate authority deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.certificate_authority_modules.select.get',
        description: 'Returns a list of certificate authority modules for select2 dropdown.',
        summary: 'Get certificate authority modules select list',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate authority modules select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.certificate_authority.test_connection.get',
        description: 'Tests the connection to a certificate authority.',
        summary: 'Test certificate authority connection',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the certificate authority',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection test result returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: 'admin.api.ssl_certificates.get',
        description: 'Returns a DataTable list of SSL certificates.',
        summary: 'Get SSL certificates list',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SSL certificates list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: 'admin.api.ssl_certificate.post',
        description: 'Creates a new SSL certificate with specified domain, certificate authority, and aliases.',
        summary: 'Create a new SSL certificate',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['domain', 'certificate_authority_uuid'],
                    properties: [
                        new OA\Property(property: 'domain', description: 'Domain name for the certificate', type: 'string', example: 'example.com'),
                        new OA\Property(property: 'certificate_authority_uuid', description: 'UUID of the certificate authority', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'aliases', description: 'Newline-separated list of domain aliases', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['SSL Manager'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SSL certificate created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'redirect', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.ssl_certificate.put',
        description: 'Updates an existing SSL certificate by UUID. Fields depend on certificate status (draft vs non-draft).',
        summary: 'Update SSL certificate by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'domain', description: 'Domain name (required for draft certificates)', type: 'string', example: 'example.com'),
                        new OA\Property(property: 'auto_renew_days', description: 'Number of days before expiry to auto-renew', type: 'integer', example: 30),
                        new OA\Property(property: 'aliases', description: 'Newline-separated list of domain aliases', type: 'string', nullable: true),
                        new OA\Property(property: 'wildcard', description: 'Whether to issue a wildcard certificate', type: 'string', enum: ['yes', 'no', '1', '0'], nullable: true),
                        new OA\Property(property: 'email', description: 'Contact email address', type: 'string', format: 'email', nullable: true),
                        new OA\Property(property: 'organization', description: 'Organization name', type: 'string', nullable: true),
                        new OA\Property(property: 'organizational_unit', description: 'Organizational unit name', type: 'string', nullable: true),
                        new OA\Property(property: 'country', description: 'ISO 3166-1 alpha-2 country code', type: 'string', nullable: true),
                        new OA\Property(property: 'state', description: 'State or province name', type: 'string', nullable: true),
                        new OA\Property(property: 'locality', description: 'Locality or city name', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the SSL certificate',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SSL certificate updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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


    #[OA\Get(
        path: 'admin.api.ssl_certificate.get',
        description: 'Returns detailed information about an SSL certificate including PEM data.',
        summary: 'Get SSL certificate by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the SSL certificate',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SSL certificate data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.ssl_certificate.delete',
        description: 'Deletes an SSL certificate by UUID.',
        summary: 'Delete SSL certificate by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the SSL certificate to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SSL certificate deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Delete(
        path: 'admin.api.ssl_certificates.delete_expired.delete',
        description: 'Deletes all expired SSL certificates.',
        summary: 'Delete all expired SSL certificates',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Expired SSL certificates deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteSslCertificatesExpired(): JsonResponse
    {
        $deleted = SslCertificate::where('status', 'expired')->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully') . ' (' . $deleted . ')',
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.ssl_certificates.delete_draft.delete',
        description: 'Deletes all draft SSL certificates.',
        summary: 'Delete all draft SSL certificates',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Draft SSL certificates deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function deleteSslCertificatesDraft(): JsonResponse
    {
        $deleted = SslCertificate::where('status', 'draft')->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully') . ' (' . $deleted . ')',
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ssl_certificate.generate_csr.get',
        description: 'Generates a CSR (Certificate Signing Request) for an SSL certificate.',
        summary: 'Generate CSR for SSL certificate',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the SSL certificate',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSR generated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Put(
        path: 'admin.api.ssl_certificate.status.put',
        description: 'Updates the status of an SSL certificate.',
        summary: 'Update SSL certificate status',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['status'],
                    properties: [
                        new OA\Property(property: 'status', description: 'New status value', type: 'string', enum: ['draft', 'pending', 'processing', 'active', 'expired', 'revoked', 'failed']),
                    ]
                )
            )
        ),
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the SSL certificate',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SSL certificate status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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


    #[OA\Get(
        path: 'admin.api.certificate_authorities.select.get',
        description: 'Returns a paginated list of certificate authorities for select2 dropdown.',
        summary: 'Get certificate authorities select list',
        security: [['apiKeyAuth' => []]],
        tags: ['SSL Manager'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificate authorities select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
