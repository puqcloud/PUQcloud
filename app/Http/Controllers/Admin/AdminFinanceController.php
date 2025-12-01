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
use App\Models\Country;
use App\Models\Currency;
use App\Models\HomeCompany;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Module;
use App\Models\PaymentGateway;
use App\Models\Region;
use App\Models\TaxRule;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AdminFinanceController extends Controller
{
    public function transactions(): View
    {
        $title = __('main.Transactions');

        return view_admin('finance.transactions.transactions', compact('title'));
    }

    public function getTransactions(Request $request): JsonResponse
    {
        $query = Transaction::query()->with('client');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('transaction_date', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('transaction_id', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%")
                                ->orWhere('amount_net', 'like', "%{$search}%")
                                ->orWhere('amount_gross', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($transaction) {
                    return $transaction->client->currency->toArray();
                })
                ->addColumn('payment_gateway', function ($transaction) {
                    $payment_gateway = $transaction->paymentGateway;
                    if (! empty($payment_gateway)) {
                        return ['uuid' => $payment_gateway->uuid, 'name' => $payment_gateway->name];
                    }

                    return [];
                })
                ->addColumn('urls', function ($transaction) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('finance-view')) {
                        $urls['view'] = ''; // route('admin.api.client.transaction.get', ['uuid' => $client->uuid, 't_uuid' => $transaction->uuid]);
                    }
                    $urls['client_web'] = route('admin.web.client.tab', ['uuid' => $transaction->client->uuid, 'tab' => 'summary']);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function homeCompanies(): View
    {
        $title = __('main.Home Companies');

        return view_admin('home_companies.home_companies', compact('title'));
    }

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

    public function getPaymentModulesSelect(Request $request): JsonResponse
    {
        $notificationModules = Module::all();
        $modules = [];
        foreach ($notificationModules as $module) {
            if ($module->type != 'Payment') {
                continue;
            }
            $module_name = ! empty($module->module_data['name']) ? $module->module_data['name'] : $module->name;
            $modules[] = [
                'id' => $module->uuid,
                'text' => $module_name.' ('.$module->status.')',
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredModules = array_filter($modules, function ($module) use ($searchTerm) {
            return empty($searchTerm) || stripos($module['text'], $searchTerm) !== false;
        });

        return response()->json(['data' => [
            'results' => array_values($filteredModules),
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function getHomeCompanyPaymentGateways(Request $request, $uuid): JsonResponse
    {
        $home_company = HomeCompany::find($uuid);

        if (empty($home_company)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $query = $home_company->paymentGateways()->with('currencies');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($payment_gateway) use ($home_company) {
                    $admin = app('admin');
                    $urls = [];
                    if ($admin->hasPermission('finance-view')) {
                        $urls['edit'] = route('admin.web.home_company.tab', ['uuid' => $home_company->uuid, 'tab' => 'payment_gateways', 'edit' => $payment_gateway->uuid]);
                        $urls['get'] = route('admin.api.payment_gateway.get', $payment_gateway->uuid);
                    }

                    if ($admin->hasPermission('finance-view')) {
                        $urls['delete'] = route('admin.api.payment_gateway.delete', $payment_gateway->uuid);
                    }

                    return $urls;
                })
                ->addColumn('module_data', function ($payment_gateway) {
                    return $payment_gateway->getModuleConfig();
                })
                ->make(true),
        ], 200);
    }

    public function postHomeCompanyPaymentGateway(Request $request, $uuid): JsonResponse
    {
        $home_company = HomeCompany::find($uuid);

        if (empty($home_company)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $modules = Module::query()->where('type', 'Payment')->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:payment_gateways,key',
            'module_uuid' => 'required|in:'.implode(',', $modules),
        ], [
            'key.required' => __('error.The key field is required'),
            'key.unique' => __('error.The key has already been taken'),
            'module.in' => __('error.The selected Module is invalid'),
            'module.required' => __('error.The Module field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $payment_gateway = new PaymentGateway;
        $payment_gateway->key = $request->get('key');
        $payment_gateway->module_uuid = $request->get('module_uuid');
        $payment_gateway->configuration = json_encode([]);
        $payment_gateway->home_company_uuid = $home_company->uuid;
        $order = PaymentGateway::query()
            ->where('home_company_uuid', $payment_gateway->home_company_uuid)
            ->max('order');
        $payment_gateway->order = ($order ?? 0) + 1;
        $payment_gateway->save();
        $payment_gateway->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
            'redirect' => route('admin.web.home_company.tab', ['uuid' => $home_company->uuid, 'tab' => 'payment_gateways', 'edit' => $payment_gateway->uuid]),
        ]);
    }

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

    public function taxRules(): View
    {
        $title = __('main.Tax Rules');

        return view_admin('finance.tax_rules.tax_rules', compact('title'));
    }

    public function getTaxRules(Request $request): JsonResponse
    {
        $query = TaxRule::with('country', 'region', 'homeCompany')
            ->select('tax_rules.*')
            ->leftJoin('countries', 'tax_rules.country_uuid', '=', 'countries.uuid')
            ->leftJoin('regions', 'tax_rules.region_uuid', '=', 'regions.uuid')
            ->leftJoin('home_companies', 'tax_rules.home_company_uuid', '=', 'home_companies.uuid')
            ->orderBy('tax_rules.order', 'asc');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('tax_rules.code', 'like', "%{$search}%")
                                ->orWhere('countries.name', 'like', "%{$search}%")
                                ->orWhere('regions.name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($tax_rule) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online->hasPermission('finance-view')) {
                        $urls['get'] = route('admin.api.tax_rule.get', $tax_rule->uuid);
                    }
                    if ($admin_online->hasPermission('finance-edit')) {
                        $urls['put'] = route('admin.api.tax_rule.put', $tax_rule->uuid);
                    }
                    if ($admin_online->hasPermission('finance-delete')) {
                        $urls['delete'] = route('admin.api.tax_rule.delete', $tax_rule->uuid);
                    }

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getTaxRule(Request $request, $uuid): JsonResponse
    {
        $tax_rule = TaxRule::find($uuid);

        if (empty($tax_rule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($tax_rule->country) {
            $tax_rule->country_data = ['id' => $tax_rule->country->uuid ?? null, 'text' => $tax_rule->country->name ?? null];
        } else {
            $tax_rule->country_data = ['id' => '0', 'text' => __('main.No choice')];
        }

        if ($tax_rule->region) {
            $tax_rule->region_data = ['id' => $tax_rule->region->uuid ?? null, 'text' => $tax_rule->region->name ?? null];
        } else {
            $tax_rule->region_data = ['id' => '0', 'text' => __('main.No choice')];
        }

        $tax_rule->home_company_data = ['id' => $tax_rule->homeCompany->uuid, 'text' => $tax_rule->homeCompany->company_name];

        return response()->json([
            'data' => $tax_rule,
        ]);
    }

    public function postTaxRule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $tax_rule = new TaxRule;

        $tax_rule->home_company_uuid = $request->get('home_company_uuid');

        if ($request->has('country_uuid')) {
            $tax_rule->country_uuid = null;
            if ($request->get('country_uuid') !== '0') {
                $tax_rule->country_uuid = $request->get('country_uuid');
            }
        }

        if ($request->has('region_uuid')) {
            $tax_rule->region_uuid = null;
            if ($request->get('region_uuid') !== '0') {
                $tax_rule->region_uuid = $request->get('region_uuid');
            }
        }

        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        if ($request->has('private_client')) {
            $tax_rule->private_client = false;
            if ($request->get('private_client') === 'yes') {
                $tax_rule->private_client = true;
            }
        }

        if ($request->has('company_without_tax_id')) {
            $tax_rule->company_without_tax_id = false;
            if ($request->get('company_without_tax_id') === 'yes') {
                $tax_rule->company_without_tax_id = true;
            }
        }

        if ($request->has('company_with_tax_id')) {
            $tax_rule->company_with_tax_id = false;
            if ($request->get('company_with_tax_id') === 'yes') {
                $tax_rule->company_with_tax_id = true;
            }
        }

        if ($request->has('individual_tax_rate')) {
            $tax_rule->individual_tax_rate = false;
            if ($request->get('individual_tax_rate') === 'yes') {
                $tax_rule->individual_tax_rate = true;
            }
        }

        if ($request->has('tax_1')) {
            $tax_rule->tax_1 = $request->get('tax_1');
        }
        if ($request->has('tax_1_name')) {
            $tax_rule->tax_1_name = $request->get('tax_1_name');
        }

        if ($request->has('tax_2')) {
            $tax_rule->tax_2 = $request->get('tax_2');
        }
        if ($request->has('tax_2_name')) {
            $tax_rule->tax_2_name = $request->get('tax_2_name');
        }

        if ($request->has('tax_3')) {
            $tax_rule->tax_3 = $request->get('tax_3');
        }
        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        $tax_rule->save();
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    public function putTaxRule(Request $request, $uuid): JsonResponse
    {
        $tax_rule = TaxRule::find($uuid);

        if (empty($tax_rule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $tax_rule->home_company_uuid = $request->get('home_company_uuid');

        if ($request->has('country_uuid')) {
            $tax_rule->country_uuid = null;
            if ($request->get('country_uuid') !== '0') {
                $tax_rule->country_uuid = $request->get('country_uuid');
            }
        }

        if ($request->has('region_uuid')) {
            $tax_rule->region_uuid = null;
            if ($request->get('region_uuid') !== '0') {
                $tax_rule->region_uuid = $request->get('region_uuid');
            }
        }

        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        if ($request->has('private_client')) {
            $tax_rule->private_client = false;
            if ($request->get('private_client') === 'yes') {
                $tax_rule->private_client = true;
            }
        }

        if ($request->has('company_without_tax_id')) {
            $tax_rule->company_without_tax_id = false;
            if ($request->get('company_without_tax_id') === 'yes') {
                $tax_rule->company_without_tax_id = true;
            }
        }

        if ($request->has('company_with_tax_id')) {
            $tax_rule->company_with_tax_id = false;
            if ($request->get('company_with_tax_id') === 'yes') {
                $tax_rule->company_with_tax_id = true;
            }
        }

        if ($request->has('individual_tax_rate')) {
            $tax_rule->individual_tax_rate = false;
            if ($request->get('individual_tax_rate') === 'yes') {
                $tax_rule->individual_tax_rate = true;
            }
        }

        if ($request->has('tax_1')) {
            $tax_rule->tax_1 = $request->get('tax_1');
        }
        if ($request->has('tax_1_name')) {
            $tax_rule->tax_1_name = $request->get('tax_1_name');
        }

        if ($request->has('tax_2')) {
            $tax_rule->tax_2 = $request->get('tax_2');
        }
        if ($request->has('tax_2_name')) {
            $tax_rule->tax_2_name = $request->get('tax_2_name');
        }

        if ($request->has('tax_3')) {
            $tax_rule->tax_3 = $request->get('tax_3');
        }
        if ($request->has('tax_3_name')) {
            $tax_rule->tax_3_name = $request->get('tax_3_name');
        }

        $tax_rule->save();
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);

    }

    public function deleteTaxRule(Request $request, $uuid): JsonResponse
    {
        $tax_rule = TaxRule::find($uuid);

        if (empty($tax_rule)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        try {
            $deleted = $tax_rule->delete();
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

        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Deleted successfully'),
        ]);
    }

    public function postTaxRulesUpdateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:tax_rules,uuid',
            'new_order' => 'required|integer',
        ], [
            'uuid.required' => __('error.The uuid field is required'),
            'uuid.exists' => __('error.The uuid does not exist in the tax_rules table'),
            'new_order.required' => __('error.The new_order field is required'),
            'new_order.integer' => __('error.The new_order must be an integer'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $taxRule = TaxRule::where('uuid', $request->input('uuid'))->first();

        if (! $taxRule) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Product group not found')],
            ], 404);
        }

        $taxRules = TaxRule::orderBy('order')->get();

        $minOrder = $taxRules->first()->order;
        $maxOrder = $taxRules->last()->order;

        if ($taxRule->order == $minOrder && $request->input('new_order') < $minOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The first item cannot be moved up')],
            ], 422);
        }

        if ($taxRule->order == $maxOrder && $request->input('new_order') > $maxOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The last item cannot be moved down')],
            ], 422);
        }

        $currentOrder = $taxRule->order;
        $newOrder = $request->input('new_order');

        if ($newOrder > $currentOrder) {
            TaxRule::where('order', '>', $currentOrder)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } else {
            TaxRule::where('order', '<', $currentOrder)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $taxRule->order = $newOrder;
        $taxRule->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

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

    public function postTaxEuRules(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $taxes = config('taxes.EU');

        foreach ($taxes as $country_code => $tax_data) {
            $tax_rule = new TaxRule;
            $tax_rule->home_company_uuid = $request->get('home_company_uuid');
            $country = Country::where('code', $country_code)->first();
            $tax_rule->country_uuid = $country->uuid;
            $tax_rule->region_uuid = null;
            $tax_rule->tax_1 = $tax_data['rate'];
            $tax_rule->tax_1_name = $tax_data['name'];
            $tax_rule->individual_tax_rate = true;
            $tax_rule->private_client = true;
            $tax_rule->company_without_tax_id = true;
            $tax_rule->company_with_tax_id = true;
            $tax_rule->save();
        }
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    public function postTaxCanadianRules(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'home_company_uuid' => 'required|exists:home_companies,uuid',
        ], [
            'home_company_uuid.required' => __('error.The Home Company field is required'),
            'home_company_uuid.exists' => __('error.Invalid Home Company UUID'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $taxes = config('taxes.Canada');
        $country = Country::where('code', 'CA')->first();
        foreach ($taxes as $province => $tax_data) {
            $tax_rule = new TaxRule;
            $tax_rule->home_company_uuid = $request->get('home_company_uuid');
            $tax_rule->country_uuid = $country->uuid;
            $province_record = Region::where('name', $province)->first();
            $tax_rule->region_uuid = $province_record->uuid;
            foreach ($tax_data as $index => $tax) {
                $tax_field = 'tax_'.($index + 1);
                $tax_name_field = 'tax_'.($index + 1).'_name';
                $tax_rule->$tax_field = $tax['rate'];
                $tax_rule->$tax_name_field = $tax['name'];
            }
            $tax_rule->individual_tax_rate = true;
            $tax_rule->private_client = true;
            $tax_rule->company_without_tax_id = true;
            $tax_rule->company_with_tax_id = true;
            $tax_rule->save();
        }
        TaxRule::reorder();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [],
        ]);
    }

    public function getInvoice(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => $invoice,
        ]);
    }

    public function deleteInvoice(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);
        $client_uuid = $invoice->client_uuid;

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($invoice->status != 'draft') {
            return response()->json([
                'errors' => [__('error.The status should be draft')],
            ], 404);
        }

        try {
            $deleted = $invoice->delete();
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
            'redirect' => route('admin.web.client.tab', ['uuid' => $client_uuid, 'tab' => 'invoices']),
            'message' => __('message.Deleted successfully'),
        ]);
    }

    public function getInvoiceItems(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::with(['invoiceItems'])->where('uuid', $uuid)->first();
        $currency_format = $invoice->client->currency->format;
        $query = InvoiceItem::query()->where('invoice_uuid', $uuid);

        $subtotal = $invoice->invoiceItems->sum('amount');
        $taxes = [
            'tax_1' => $invoice->tax_1,
            'tax_2' => $invoice->tax_2,
            'tax_3' => $invoice->tax_3,
        ];

        $data = DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && ! empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                            ->orWhere('amount', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('urls', function ($transaction) {
                $admin_online = app('admin');
                $urls = [];
                if ($admin_online && $admin_online->hasPermission('finance-edit')) {
                    $urls['edit'] = ''; // route('admin.api.client.transaction.get', ['uuid' => $client->uuid, 't_uuid' => $transaction->uuid]);
                }

                return $urls;
            })
            ->make(true);

        $additionalRows = [
            [
                'uuid' => null,
                'invoice_uuid' => $invoice->uuid,
                'description' => __('main.Subtotal'),
                'amount' => number_format_custom($subtotal, 2, $currency_format),
                'taxed' => null,
                'notes' => 'subtotal',
                'created_at' => null,
                'updated_at' => null,
                'urls' => [],
            ],
        ];

        foreach ($taxes as $key => $tax) {
            $name = $key.'_name';
            $amount = $key.'_amount';
            if (! empty($invoice->$name)) {
                $additionalRows[] = [
                    'invoice_uuid' => $invoice->uuid,
                    'description' => $invoice->$name." ({$tax}%)",
                    'amount' => number_format_custom($invoice->$amount, 2, $currency_format),
                    'taxed' => null,
                    'notes' => 'tax',
                    'urls' => [],
                ];
            }
        }

        $additionalRows[] = [
            'invoice_uuid' => $invoice->uuid,
            'description' => __('main.Tax'),
            'amount' => number_format_custom($invoice->tax, 2, $currency_format),
            'taxed' => null,
            'notes' => 'tax',
            'urls' => [],
        ];

        $additionalRows[] = [
            'invoice_uuid' => $invoice->uuid,
            'description' => __('main.Total'),
            'amount' => number_format_custom($invoice->total, 2, $currency_format),
            'taxed' => null,
            'notes' => 'total',
            'urls' => [],
        ];

        $data = $data->getData();
        $data->data = array_merge($data->data, $additionalRows);

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }

    public function getInvoiceTransactions(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::query()->where('uuid', $uuid)->first();

        if (! $invoice) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        $query = $invoice->transactions()->orderBy('transaction_date', 'desc');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('transaction_date', 'like', "%{$search}%")
                                ->orWhere('uuid', 'like', "%{$search}%")
                                ->orWhere('transaction_id', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%")
                                ->orWhere('amount', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($transaction) use ($invoice) {
                    return $invoice->client->currency->toArray();
                })
                ->addColumn('payment_gateway', function ($transaction) {
                    $payment_gateway = $transaction->paymentGateway;
                    if (! empty($payment_gateway)) {
                        return ['uuid' => $payment_gateway->uuid, 'name' => $payment_gateway->name];
                    }

                    return [];
                })
                ->addColumn('urls', function ($transaction) {
                    $admin_online = app('admin');
                    $urls = [];

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function putInvoicePublish(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $publish = $invoice->publish();

        if ($publish['status'] == 'error') {
            return response()->json([
                'errors' => $publish['errors'],
            ], 422);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $invoice,
        ]);
    }

    public function putInvoiceCancel(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $publish = $invoice->cancel();

        if ($publish['status'] == 'error') {
            return response()->json([
                'errors' => $publish['errors'],
            ], 422);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $invoice,
        ]);
    }

    public function postInvoiceAddPayment(Request $request, $uuid): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'payment_gateway_uuid' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'required',
        ], [
            'payment_gateway_uuid.required' => __('error.The Payment Gateway field is required'),
            'amount.required' => __('error.The Amount field is required'),
            'amount.numeric' => __('error.The Amount must be a number'),
            'amount.min' => __('error.The Amount must be greater than zero'),
            'transaction_id.required' => __('error.The Transaction ID field is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $add_payment = $invoice->addPayment($request->all());

        if ($add_payment['status'] == 'error') {
            return response()->json([
                'errors' => $add_payment['errors'],
            ], 422);
        }

        if (! empty($add_payment['message'])) {
            return response()->json([
                'status' => 'success',
                'message' => $add_payment['message'],
            ]);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('admin.web.client.tab', ['uuid' => $invoice->client->uuid, 'tab' => 'invoices', 'edit' => $invoice->reference_invoice_uuid]),
        ]);
    }

    public function postInvoiceMakeRefund(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $make_refund = $invoice->makeRefund($request->all());

        if ($make_refund['status'] == 'error') {
            return response()->json([
                'errors' => $make_refund['errors'],
            ], 422);
        }

        $invoice->refresh();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'redirect' => route('admin.web.client.tab', ['uuid' => $invoice->client->uuid, 'tab' => 'invoices', 'edit' => $make_refund['data']['uuid'] ?? $uuid]),
        ]);
    }

    public function getInvoicePdf(Request $request, $uuid): Response|JsonResponse
    {
        $invoice = Invoice::with('homeCompany')->find($uuid);

        if (! $invoice) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return $invoice->generatePdf()->download($invoice->getSafeFilename());
    }

    public function getInvoicePaymentGatewaysSelect(Request $request, $uuid): JsonResponse
    {
        $invoice = Invoice::find($uuid);

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $search = $request->input('q');

        if (! empty($search)) {
            $payment_gateways = $invoice->homeCompany->paymentGateways()->where('key', 'like', '%'.$search.'%')->get();
        } else {
            $payment_gateways = $invoice->homeCompany->paymentGateways()->orderBy('order')->get();
        }

        $results = [];
        foreach ($payment_gateways->toArray() as $payment_gateway) {
            $results[] = [
                'id' => $payment_gateway['uuid'],
                'text' => $payment_gateway['key'],
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function getPaymentGateway(Request $request, $uuid): JsonResponse
    {
        $payment_gateway = PaymentGateway::find($uuid);

        if (empty($payment_gateway)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (! empty($request->input('locale'))) {
            $payment_gateway->setLocale($request->input('locale'));
        }

        $module_html = $payment_gateway->getSettingsPage();
        $responseData = $payment_gateway->toArray();

        $responseData['currencies'] = $payment_gateway->currencies;

        $responseData['currencies_data'] = [];
        foreach ($payment_gateway->currencies as $currency) {
            $default = '';
            if ($currency->default) {
                $default = '*';
            }
            $responseData['currencies_data'][] = [
                'id' => $currency->uuid,
                'text' => $currency->code.$default,
            ];
        }

        $responseData['module_html'] = $module_html;

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

    public function putPaymentGateway(Request $request, $uuid): JsonResponse
    {
        $payment_gateway = PaymentGateway::find($uuid);

        if (empty($payment_gateway)) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'unique:payment_gateways,key,'.$payment_gateway->uuid.',uuid',
        ], [
            'name.unique' => __('error.The key is already in taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $payment_gateway->key = $request->input('key');

        if (! empty($request->input('locale'))) {
            $payment_gateway->setLocale($request->input('locale'));
        }

        if ($request->has('name')) {
            $payment_gateway->name = $request->input('name');
        }

        if ($request->has('description')) {
            $payment_gateway->description = $request->input('description');
        }

        $save_module_data = $payment_gateway->saveModuleData($request->all());

        if ($save_module_data['status'] == 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $save_module_data['message'],
            ], $save_module_data['code']);
        }

        $payment_gateway->save();
        $payment_gateway->refresh();

        if ($request->has('currencies')) {
            $currenciesUuids = $request->input('currencies');
            $existingCurrencies = Currency::whereIn('uuid', $currenciesUuids)->pluck('uuid')->toArray();
            $validCurrencies = array_intersect($currenciesUuids, $existingCurrencies);
            $payment_gateway->currencies()->detach();
            foreach ($validCurrencies as $currenciesUuid) {
                $payment_gateway->currencies()->attach($currenciesUuid);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $payment_gateway,
        ]);
    }

    public function deletePaymentGateway(Request $request, $uuid): JsonResponse
    {
        $payment_gateway = PaymentGateway::find($uuid);

        if (empty($payment_gateway)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $home_company_uuid = $payment_gateway->home_company_uuid;

        try {
            $deleted = $payment_gateway->delete();
            if (! $deleted) {
                return response()->json([
                    'errors' => [__('error.Deletion failed')],
                ], 500);
            }
            PaymentGateway::reorder($home_company_uuid);
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

    public function postPaymentGatewayUpdateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:payment_gateways,uuid',
            'new_order' => 'required|integer',
        ], [
            'uuid.required' => __('error.The uuid field is required'),
            'uuid.exists' => __('error.The uuid does not exist in the payment_gateways table'),
            'new_order.required' => __('error.The new_order field is required'),
            'new_order.integer' => __('error.The new_order must be an integer'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $paymentGateway = PaymentGateway::where('uuid', $request->input('uuid'))->first();

        if (! $paymentGateway) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $paymentGateways = PaymentGateway::orderBy('order')
            ->where('home_company_uuid', $paymentGateway->home_company_uuid)
            ->get();

        $minOrder = $paymentGateways->first()->order;
        $maxOrder = $paymentGateways->last()->order;

        if ($paymentGateway->order == $minOrder && $request->input('new_order') < $minOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The first item cannot be moved up')],
            ], 422);
        }

        if ($paymentGateway->order == $maxOrder && $request->input('new_order') > $maxOrder) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.The last item cannot be moved down')],
            ], 422);
        }

        $currentOrder = $paymentGateway->order;
        $newOrder = $request->input('new_order');

        if ($newOrder > $currentOrder) {
            PaymentGateway::where('order', '>', $currentOrder)
                ->where('home_company_uuid', $paymentGateway->home_company_uuid)
                ->where('order', '<=', $newOrder)
                ->decrement('order');
        } else {
            PaymentGateway::where('order', '<', $currentOrder)
                ->where('home_company_uuid', $paymentGateway->home_company_uuid)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }

        $paymentGateway->order = $newOrder;
        $paymentGateway->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }
}
