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
 *   GET  home_companies                                     -> homeCompanies()       [admin.web.home_companies]
 *   GET  home_company/{uuid}/{tab}                          -> homeCompanyTab()      [admin.web.home_company.tab]
 *
 * API Routes:
 *   GET  home_companies                                     -> getHomeCompanies()                    [admin.api.home_companies.get]
 *   POST home_company                                       -> postHomeCompany()                     [admin.api.home_company.post]
 *   GET  home_company/{uuid}                                -> getHomeCompany()                      [admin.api.home_company.get]
 *   PUT  home_company/{uuid}                                -> putHomeCompany()                      [admin.api.home_company.put]
 *   DELETE home_company/{uuid}                              -> deleteHomeCompany()                   [admin.api.home_company.delete]
 *   GET  home_companies/select                              -> getHomeCompaniesSelect()              [admin.api.home_companies.select.get]
 *   GET  home_company/invoice_templates/{type}              -> getHomeCompanyInvoiceTemplates()      [admin.api.home_company.invoice_templates.get]
 *   GET  home_company/invoice_template/{type}               -> getHomeCompanyInvoiceTemplateContent() [admin.api.home_company.invoice_template.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\HomeCompany;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminHomeCompaniesController extends Controller
{
    public function homeCompanies(): View
    {
        $title = __('main.Home Companies');

        return view_admin('home_companies.home_companies', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.home_companies.get',
        description: 'Returns a DataTable list of home companies.',
        summary: 'Get home companies list',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Home companies list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getHomeCompanies(Request $request): JsonResponse
    {
        $query = HomeCompany::query();

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('images', function ($home_company) {
                    // $home_company->loadImagesField();
                    return $home_company->images;
                })
                ->addColumn('urls', function ($home_company) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('finance-view')) {
                        $urls['edit'] = route('admin.web.home_company.tab', ['uuid' => $home_company->uuid, 'tab' => 'general']);
                    }
                    if ($admin_online && $admin_online->hasPermission('finance-delete')) {
                        $urls['delete'] = route('admin.api.home_company.delete', $home_company->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.home_company.post',
        description: 'Creates a new home company with a unique name. Optionally sets it as default.',
        summary: 'Create a new home company',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Unique name for the home company', type: 'string', example: 'My Company'),
                        new OA\Property(property: 'default', description: 'Set as default home company (yes/no)', type: 'string', example: 'yes'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Home company created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function postHomeCompany(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:home_companies,name',
        ], [
            'name.required' => __('error.The Name field is required'),
            'name.unique' => __('error.The Name has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $home_company = new HomeCompany;
        $home_company->name = $request->get('name');

        foreach (['proforma', 'invoice', 'credit_note'] as $type) {
            $path = base_path("database/InvoiceTemplates/{$type}/default.blade.php");
            if (file_exists($path)) {
                $home_company->{$type.'_template'} = file_get_contents($path);
            }
        }

        $hasDefault = HomeCompany::where('default', true)->exists();

        if ($request->get('default') === 'yes') {
            HomeCompany::where('default', true)->update(['default' => false]);
            $home_company->default = true;
        } else {
            $home_company->default = $hasDefault ? false : true;
        }

        $home_company->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    public function homeCompanyTab(Request $request, $uuid, $tab): View|RedirectResponse
    {
        $validTabs = [
            'general', 'images', 'email_settings', 'tax_settings', 'invoice_settings', 'payment_gateways', 'invoice_template', 'credit_note_template', 'proforma_template',
        ];

        if (! in_array($tab, $validTabs)) {
            return redirect()->route('admin.web.home_company.tab', ['uuid' => $uuid, 'tab' => 'general']);
        }

        $title = __('main.Home Company');
        $locales = config('locale.client.locales');

        return view_admin('home_companies.home_company_'.$tab, compact('title', 'uuid', 'tab', 'locales'));
    }

    #[OA\Get(
        path: 'admin.api.home_company.get',
        description: 'Returns detailed information about a home company, including country, region, and group data.',
        summary: 'Get home company by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the home company',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Home company data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Home company not found'),
        ]
    )]
    public function getHomeCompany(Request $request, $uuid): JsonResponse
    {
        $home_company = HomeCompany::find($uuid);

        if (empty($home_company)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($home_company->country) {
            $home_company->country_data = ['id' => $home_company->country->uuid ?? null, 'text' => $home_company->country->name ?? null];
        }

        if ($home_company->region) {
            $home_company->region_data = ['id' => $home_company->region->uuid ?? null, 'text' => $home_company->region->name ?? null];
        }

        if ($home_company->group_uuid) {
            $home_company->group_data = ['id' => $home_company->group->uuid ?? null, 'text' => $home_company->group->name ?? null];
        }

        return response()->json([
            'data' => $home_company,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.home_company.put',
        description: 'Updates a home company by UUID, including name, address, tax settings, invoice settings, and templates.',
        summary: 'Update home company by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Home company name', type: 'string', example: 'My Company'),
                        new OA\Property(property: 'company_name', description: 'Legal company name', type: 'string', example: 'My Company LLC'),
                        new OA\Property(property: 'address_1', description: 'Address line 1', type: 'string'),
                        new OA\Property(property: 'city', description: 'City', type: 'string'),
                        new OA\Property(property: 'country_uuid', description: 'Country UUID', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'postcode', description: 'Postal code', type: 'string'),
                        new OA\Property(property: 'default', description: 'Set as default (yes/no)', type: 'string', example: 'yes'),
                    ]
                )
            )
        ),
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the home company',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Home company updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Home company not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putHomeCompany(Request $request, $uuid): JsonResponse
    {
        $home_company = HomeCompany::find($uuid);

        if (empty($home_company)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:home_companies,name,'.$uuid.',uuid',
        ], [
            'name.unique' => __('error.The name is already in taken'),
            'name.required' => __('error.The name field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $home_company->name = $request->get('name');

        $fields = [
            'company_name',
            'address_1', 'address_2', 'city', 'region_uuid', 'country_uuid', 'postcode',
            'tax_local_id', 'tax_local_id_name',
            'tax_eu_vat_id', 'tax_eu_vat_id_name',
            'registration_number', 'registration_number_name',
            'us_ein', 'us_state_tax_id', 'us_entity_type',
            'ca_business_number', 'ca_gst_hst_number', 'ca_pst_qst_number', 'ca_entity_type',
            'tax_1', 'tax_1_name',
            'tax_2', 'tax_2_name',
            'tax_3', 'tax_3_name',
            'proforma_invoice_number_format', 'proforma_invoice_number_next', 'proforma_invoice_number_reset',
            'invoice_number_format', 'invoice_number_next', 'invoice_number_reset',
            'credit_note_number_format', 'credit_note_number_next', 'credit_note_number_reset',
            'balance_credit_purchase_item_name', 'balance_credit_purchase_item_description',
            'refund_item_name', 'refund_item_description',
            'pay_to_text', 'invoice_footer_text', 'pdf_paper', 'pdf_font',
            'proforma_template', 'invoice_template', 'credit_note_template',
            'group_uuid', 'signature',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $home_company->$field = $request->get($field);
            }
        }

        if ($request->has('default')) {
            if ($request->get('default') === 'yes') {
                HomeCompany::where('uuid', '!=', $uuid)->update(['default' => false]);
                $home_company->default = true;
            } else {
                $home_company->default = false;

                $hasDefault = HomeCompany::where('uuid', '!=', $uuid)->where('default', true)->exists();
                if (! $hasDefault) {
                    $home_company->default = true;
                }
            }
        }

        $home_company->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $home_company,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.home_company.delete',
        description: 'Deletes a home company by UUID. Cannot delete the default home company.',
        summary: 'Delete home company by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the home company to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Home company deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 403, description: 'Cannot delete the default home company'),
            new OA\Response(response: 404, description: 'Home company not found'),
        ]
    )]
    public function deleteHomeCompany(Request $request, $uuid): JsonResponse
    {
        $home_company = HomeCompany::find($uuid);

        if (empty($home_company)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($home_company->default) {
            return response()->json([
                'errors' => [__('error.Cannot delete the default Home Company')],
            ], 403);
        }

        try {
            $deleted = $home_company->delete();
            if (! $deleted) {
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
        path: 'admin.api.home_companies.select.get',
        description: 'Returns a select list of home companies.',
        summary: 'Get home companies select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Home companies select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getHomeCompaniesSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $home_companies = HomeCompany::orderBy('default', 'desc')->where('code', 'like', '%'.$search.'%')->get();
        } else {
            $home_companies = HomeCompany::orderBy('default', 'desc')->get();
        }

        $results = [];
        foreach ($home_companies->toArray() as $home_company) {
            $results[] = [
                'id' => $home_company['uuid'],
                'text' => $home_company['name'].' ('.$home_company['company_name'].')',
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    #[OA\Get(
        path: 'admin.api.home_company.invoice_templates.get',
        description: 'Returns a list of available invoice template names for a given type (invoice, proforma, credit_note).',
        summary: 'Get invoice templates by type',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'type',
                description: 'Template type (invoice, proforma, credit_note)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'invoice')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Template folder not found'),
        ]
    )]
    public function getHomeCompanyInvoiceTemplates(string $type): JsonResponse
    {
        $path = base_path('database/InvoiceTemplates/'.$type);

        if (! File::exists($path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template folder not found: '.$path,
            ], 404);
        }

        $files = File::files($path);

        $templates = collect($files)
            ->map(function ($file) {
                $filename = $file->getFilename();

                return str_replace('.blade', '', pathinfo($filename, PATHINFO_FILENAME));
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $templates,
        ]);
    }

    #[OA\Get(
        path: 'admin.api.home_company.invoice_template.get',
        description: 'Returns the content of a specific invoice template file by type and name.',
        summary: 'Get invoice template content',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        parameters: [
            new OA\Parameter(
                name: 'type',
                description: 'Template type (invoice, proforma, credit_note)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'invoice')
            ),
            new OA\Parameter(
                name: 'name',
                description: 'Template name',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'default')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template content returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Template not found'),
        ]
    )]
    public function getHomeCompanyInvoiceTemplateContent(Request $request, $type): JsonResponse
    {
        $name = $request->get('name');
        $file = base_path("database/InvoiceTemplates/{$type}/{$name}.blade.php");

        if (! file_exists($file)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $content = file_get_contents($file);

        return response()->json([
            'status' => 'success',
            'data' => $content,
        ]);
    }
}
