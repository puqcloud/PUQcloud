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

namespace Template\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\InvoiceItem;
use App\Models\Region;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class ClientController extends Controller
{
    public function profile(): View|Factory|Application
    {
        $title = __('main.Company Profile');

        return view_client('client.profile', compact('title'));
    }

    public function getProfile(): JsonResponse
    {
        $client = app('client');
        $allowedKeys = [
            'firstname',
            'lastname',
            'language',
            'company_name',
            'tax_id',
        ];

        $filteredUserData = collect($client->toArray())
            ->only($allowedKeys)
            ->toArray();

        $language = [];

        foreach (config('locale.client.locales') as $key => $value) {
            if ($key == $client->language) {
                $language = [
                    'id' => $key,
                    'text' => $value['name'].' ('.$value['native'].')',
                ];
            }
        }

        $filteredUserData['language_data'] = $language;

        $billing_address = $client->billingAddress();

        $region = Region::find($billing_address->region_uuid);

        if (! empty($region)) {
            $region = [
                'id' => $region->uuid,
                'text' => $region['code'].' - '.$region['name'].' ('.$region['native_name'].')',
            ];
        }

        $country = Country::find($billing_address->country_uuid);

        if (! empty($country)) {
            $country = [
                'id' => $country->uuid,
                'text' => "{$country->code} - {$country->name} ({$country->native_name})",
            ];
        }

        $filteredUserData['contact_email'] = $billing_address->contact_email;
        $filteredUserData['contact_phone'] = $billing_address->contact_phone;
        $filteredUserData['address_1'] = $billing_address->address_1;
        $filteredUserData['address_2'] = $billing_address->address_2;
        $filteredUserData['city'] = $billing_address->city;
        $filteredUserData['postcode'] = $billing_address->postcode;
        $filteredUserData['region_data'] = $region;
        $filteredUserData['country_data'] = $country;

        return response()->json([
            'data' => $filteredUserData,
        ], 200);
    }

    public function putProfile(Request $request): JsonResponse
    {
        $client = app('client');
        $billing_address = $client->billingAddress();
        $locales = array_keys(config('locale.client.locales'));
        $countries = Country::query()->get()->pluck('uuid')->toArray();
        $regions = Region::query()->where('country_uuid', $request->input('country_uuid'))->get()->pluck('uuid')->toArray();

        $validator = Validator::make($request->all(), [
            'firstname' => 'nullable|string',
            'lastname' => 'nullable|string',
            'company_name' => 'nullable|string|unique:clients,company_name,'.$client->uuid.',uuid',
            'tax_id' => 'nullable|string|unique:clients,tax_id,'.$client->uuid.',uuid',
            'language' => 'nullable|in:'.implode(',', $locales),
            'contact_email' => 'email',
            'address_1' => 'required',
            'address_2' => 'nullable',
            'city' => 'required',
            'postcode' => 'required',
            'country_uuid' => 'required|string|in:'.implode(',', $countries),
            'region_uuid' => 'required|string|in:'.implode(',', $regions),
        ], [
            'firstname.string' => 'The firstname must be a valid string',
            'lastname.string' => 'The lastname must be a valid string',
            'company_name.string' => 'The company name format is invalid',
            'company_name.unique' => 'The company name has already been taken',
            'tax_id.string' => 'The tax ID format is invalid',
            'tax_id.unique' => 'The tax ID has already been taken',
            'language.in' => 'The selected language is invalid',
            'contact_email.email' => __('error.The email must be a valid email address'),
            'address_1.required' => __('error.The address 1 field is required'),
            'city.required' => __('error.The city field is required'),
            'postcode.required' => __('error.The postcode field is required'),
            'country_uuid.string' => __('error.The country must be a valid string'),
            'country_uuid.required' => __('error.The country field is required'),
            'country_uuid.in' => __('error.The selected country is invalid'),
            'region_uuid.string' => __('error.The region must be a valid string'),
            'region_uuid.required' => __('error.The region field is required'),
            'region_uuid.in' => __('error.The selected region is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if (! empty($request->input('firstname'))) {
            $client->firstname = $request->input('firstname');
        }

        if (! empty($request->input('lastname'))) {
            $client->lastname = $request->input('lastname');
        }

        if ($request->has('company_name')) {
            $client->company_name = $request->input('company_name');
        }

        if ($request->has('tax_id')) {
            $client->tax_id = $request->input('tax_id');
        }

        if (! empty($request->input('language'))) {
            $client->language = $request->input('language');
        }

        $contact_phone = null;
        if (! empty($request->input('country_code')) and ! empty($request->input('phone_number'))) {
            $contact_phone = str_replace(' ', '', $request->input('country_code').$request->input('phone_number'));
        }

        $billing_address->contact_name = $request->input('firstname').' '.$request->input('lastname');
        $billing_address->contact_email = $request->input('contact_email');
        $billing_address->address_1 = $request->input('address_1');
        $billing_address->address_2 = $request->input('address_2');
        $billing_address->city = $request->input('city');
        $billing_address->postcode = $request->input('postcode');
        $billing_address->region_uuid = $request->input('region_uuid');
        $billing_address->country_uuid = $request->input('country_uuid');
        $billing_address->contact_phone = $contact_phone;

        if ($client->status === 'new') {
            $client->status = 'active';
        }

        $client->save();
        $billing_address->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function invoices(): View|Factory|Application
    {
        $title = __('main.Invoices');

        return view_client('client.invoices', compact('title'));
    }

    public function getInvoices(Request $request): JsonResponse
    {
        $client = app('client');

        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $allowedKeys = [
            'uuid',
            'type',
            'status',
            'number',
            'issue_date',
            'due_date',
            'total',
            'total_str',
            'currency_code',
            'urls',
        ];

        $query = $client->invoices()->whereIn('status', ['unpaid', 'paid', 'refunded']);

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('type', 'like', "%{$search}%")
                                ->orWhere('status', 'like', "%{$search}%")
                                ->orWhere('issue_date', 'like', "%{$search}%")
                                ->orWhere('due_date', 'like', "%{$search}%")
                                ->orWhere('total', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('total_str', function ($invoice) {
                    $currency = Currency::query()->where('code', $invoice->currency_code)->first();

                    return formatCurrency($invoice->total, $currency, $invoice->currency_code);
                })
                ->addColumn('urls', function ($invoice) {
                    $urls = [];

                    if ($invoice->status == 'unpaid') {
                        $urls['payment'] = route('client.web.panel.client.invoice.payment', $invoice->uuid);
                    }

                    $urls['pdf'] = route('client.api.client.invoice.pdf.get', $invoice->uuid);
                    $urls['details'] = route('client.web.panel.client.invoice.details', $invoice->uuid);

                    return $urls;
                })
                ->only($allowedKeys)
                ->make(true),
        ], 200);
    }

    public function getInvoicePdf(Request $request, $uuid): Response|JsonResponse
    {
        $client = app('client');

        $invoice = $client->invoices()->where('uuid', $uuid)->with('homeCompany')->find($uuid);

        if (! $invoice) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return $invoice->generatePdf()->download($invoice->getSafeFilename());
    }

    public function invoiceDetails(Request $request, $uuid): View|Factory|Application
    {
        $title = __('main.Invoice');

        return view_client('client.invoice_details', compact('title', 'uuid'));
    }

    public function getInvoice(Request $request, $uuid): JsonResponse
    {
        $client = app('client');
        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $invoice = $client->invoices()
            ->where('uuid', $uuid)
            ->first();

        if (empty($invoice)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $currency = Currency::query()->where('code', $invoice->currency_code)->first();

        $allowedKeys = [
            'uuid',
            'type',
            'number',
            'status',
            'currency_code',
            'subtotal',
            'tax',
            'total',
            'issue_date',
            'due_date',
            'paid_date',
            'refunded_date',
            'paid_net_amount',
            'paid_gross_amount',
            'due_amount',
        ];

        $filteredUserData = collect($invoice->toArray())
            ->only($allowedKeys)
            ->toArray();

        $filteredUserData['subtotal_str'] = formatCurrency($invoice->subtotal, $currency, $invoice->currency_code);
        $filteredUserData['tax_str'] = formatCurrency($invoice->tax, $currency, $invoice->currency_code);
        $filteredUserData['total_str'] = formatCurrency($invoice->total, $currency, $invoice->currency_code);
        $filteredUserData['paid_net_amount_str'] = formatCurrency($invoice->paid_net_amount, $currency, $invoice->currency_code);
        $filteredUserData['paid_gross_amount_str'] = formatCurrency($invoice->paid_gross_amount, $currency, $invoice->currency_code);
        $filteredUserData['due_amount_str'] = formatCurrency($invoice->due_amount, $currency, $invoice->currency_code);

        return response()->json([
            'data' => $filteredUserData,
        ]);
    }

    public function getInvoiceItems(Request $request, $uuid): JsonResponse
    {
        $client = app('client');
        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $invoice = $client->invoices()
            ->with(['invoiceItems'])
            ->where('uuid', $uuid)
            ->first();

        if (! $invoice) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $currency = Currency::query()->where('code', $invoice->currency_code)->first();
        $query = InvoiceItem::query()->where('invoice_uuid', $uuid);

        $subtotal = $invoice->invoiceItems->sum('amount');
        $taxes = [
            'tax_1' => $invoice->tax_1,
            'tax_2' => $invoice->tax_2,
            'tax_3' => $invoice->tax_3,
        ];

        $allowedKeys = [
            'description',
            'amount',
            'amount_str',
            'notes',
        ];
        $data = DataTables::of($query)
            ->addColumn('amount_str', function ($model) use ($currency, $invoice) {
                return formatCurrency($model->amount, $currency, $invoice->currency_code);
            })
            ->only($allowedKeys)
            ->make(true);

        $additionalRows = [
            [
                'description' => __('main.Subtotal'),
                'amount' => $subtotal,
                'amount_str' => formatCurrency($invoice->subtotal, $currency, $invoice->currency_code),
                'notes' => 'subtotal',
            ],
        ];

        foreach ($taxes as $key => $tax) {
            $name = $key.'_name';
            $amount = $key.'_amount';
            if (! empty($invoice->$name)) {
                $additionalRows[] = [
                    'description' => $invoice->$name." ({$tax}%)",
                    'amount' => $invoice->$amount,
                    'amount_str' => formatCurrency($invoice->$amount, $currency, $invoice->currency_code),
                    'notes' => 'tax',
                ];
            }
        }

        $additionalRows[] = [
            'description' => __('main.Tax'),
            'amount' => $invoice->tax,
            'amount_str' => formatCurrency($invoice->tax, $currency, $invoice->currency_code),
            'notes' => 'tax',
        ];

        $additionalRows[] = [
            'description' => __('main.Total'),
            'amount' => $invoice->total,
            'amount_str' => formatCurrency($invoice->total, $currency, $invoice->currency_code),
            'notes' => 'total',
        ];

        $data = $data->getData();
        $data->data = array_merge($data->data, $additionalRows);

        return response()->json([
            'data' => ['original' => $data],
        ], 200);
    }

    public function getInvoiceTransactions(Request $request, $uuid): JsonResponse
    {
        $client = app('client');
        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $invoice = $client->invoices()
            ->with(['invoiceItems'])
            ->where('uuid', $uuid)
            ->first();

        if (! $invoice) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $query = $invoice->transactions()->orderBy('transaction_date', 'desc');

        $allowedKeys = [
            'type',
            'amount_gross',
            'amount_gross_str',
            'amount_net',
            'amount_net_str',
            'currency_code',
            'transaction_id',
            'payment_gateway_name',
            'transaction_date',
        ];

        return response()->json([
            'data' => DataTables::of($query)
                ->addColumn('amount_gross_str', function ($model) {
                    $currency = Currency::query()->where('code', $model->currency_code)->first();

                    return formatCurrency($model->amount_gross, $currency, $model->currency_code);
                })
                ->addColumn('amount_net_str', function ($model) {
                    $currency = Currency::query()->where('code', $model->currency_code)->first();

                    return formatCurrency($model->amount_net, $currency, $model->currency_code);
                })
                ->addColumn('payment_gateway_name', function ($transaction) {
                    $payment_gateway = $transaction->paymentGateway;
                    if (! empty($payment_gateway)) {
                        return $payment_gateway->name;
                    }

                    return '';
                })
                ->only($allowedKeys)
                ->make(true),
        ], 200);
    }

    public function getInvoicePaymentGateways(Request $request, $uuid): JsonResponse
    {
        $client = app('client');
        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $invoice = $client->invoices()->where('uuid', $uuid)->where('status', 'unpaid')->first();
        if (! $invoice) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $home_company = $invoice->homeCompany;
        $currency = $client->currency;
        $payment_gateways = [];

        foreach ($home_company->paymentGateways()->orderBy('order')->get() as $payment_gateway) {
            if (in_array($currency->uuid, $payment_gateway->currencies()->pluck('uuid')->toArray())) {
                $payment_gateways[] = [
                    'uuid' => $payment_gateway->uuid,
                    'name' => $payment_gateway->name,
                    'description' => $payment_gateway->description,
                    'url' => route('client.api.client.invoice.payment_gateway.get', ['uuid' => $invoice->uuid, 'pg_uuid' => $payment_gateway->uuid]),
                    'img' => $payment_gateway->getImgData(),
                ];
            }
        }

        return response()->json([
            'data' => $payment_gateways,
        ], 200);
    }

    public function transactions(): View|Factory|Application
    {
        $title = __('main.Transactions');

        return view_client('client.transactions', compact('title'));
    }

    public function getTransactions(Request $request): JsonResponse
    {
        $client = app('client');

        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $allowedKeys = [
            'type',
            'amount_gross',
            'amount_gross_str',

            'amount_net',
            'amount_net_str',

            'balance_before',
            'balance_before_str',

            'balance_after',
            'balance_after_str',

            'currency_code',
            'description',
            'transaction_date',
            'period_start',
            'period_stop',
            'payment_gateway_name',
            'transaction_id',
        ];

        $query = $client->transactions();

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
                ->addColumn('amount_gross_str', function ($model) {
                    $currency = Currency::query()->where('code', $model->currency_code)->first();

                    return formatCurrency($model->amount_gross, $currency, $model->currency_code);
                })
                ->addColumn('amount_net_str', function ($model) {
                    $currency = Currency::query()->where('code', $model->currency_code)->first();

                    return formatCurrency($model->amount_net, $currency, $model->currency_code);
                })
                ->addColumn('balance_before_str', function ($model) {
                    $currency = Currency::query()->where('code', $model->currency_code)->first();

                    return formatCurrency($model->balance_before, $currency, $model->currency_code);
                })
                ->addColumn('balance_after_str', function ($model) {
                    $currency = Currency::query()->where('code', $model->currency_code)->first();

                    return formatCurrency($model->balance_after, $currency, $model->currency_code);
                })
                ->addColumn('payment_gateway_name', function ($transaction) {
                    $payment_gateway = $transaction->paymentGateway;
                    if (! empty($payment_gateway)) {
                        return $payment_gateway->name;
                    }

                    return '';
                })
                ->only($allowedKeys)
                ->make(true),
        ], 200);
    }

    public function invoicePayment(Request $request, $uuid): RedirectResponse|View|Factory|Application
    {
        $title = __('main.Payment');
        $client = app('client');
        $invoice = $client->invoices()->where('uuid', $uuid)->where('status', 'unpaid')->first();
        if (! $invoice) {
            return redirect()->route('client.web.panel.client.invoices');
        }

        return view_client('client.invoice_payment', compact('title', 'uuid', 'invoice'));
    }

    public function getInvoicePaymentGateway(Request $request, $uuid, $pg_uuid): JsonResponse
    {
        $client = app('client');
        if (! $client) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $invoice = $client->invoices()->where('uuid', $uuid)->where('status', 'unpaid')->first();
        if (! $invoice) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $home_company = $invoice->homeCompany;
        $currency = $client->currency;

        $pg = $home_company->paymentGateways()->where('uuid', $pg_uuid)->first();
        if (! $pg) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (in_array($currency->uuid, $pg->currencies()->pluck('uuid')->toArray())) {
            $payment_gateway = [
                'uuid' => $pg->uuid,
                'name' => $pg->name,
                'description' => $pg->description,
                'html' => $pg->getClientAreaModuleHtml($invoice),
            ];
        }

        if (empty($payment_gateway)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        return response()->json([
            'data' => $payment_gateway,
        ], 200);
    }

    public function addFunds(): View|Factory|Application
    {
        $title = __('main.Add Funds');
        $client = app('client');
        $funds_params = $client->getAddFundsParams();

        return view_client('client.add_funds', compact('title', 'funds_params'));
    }

    public function postAddFundsTopUp(Request $request): JsonResponse
    {
        $client = app('client');
        $funds_params = $client->getAddFundsParams();
        $amount = (float) $request->get('amount');

        if ($amount < $funds_params['min_add_funds_amount']) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Amount cannot be less than :amount', ['amount' => $funds_params['min_add_funds_amount']])],
                'message' => ['amount' => [__('error.Amount cannot be less than :amount', ['amount' => $funds_params['min_add_funds_amount']])]],
            ], 422);
        }

        if ($amount > $funds_params['max_add_funds_amount']) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Amount cannot be more than :amount', ['amount' => $funds_params['max_add_funds_amount']])],
                'message' => ['amount' => [__('error.Amount cannot be more than :amount', ['amount' => $funds_params['max_add_funds_amount']])]],
            ], 422);
        }

        if ($amount + $funds_params['balance'] > $funds_params['max_client_balance']) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Total balance cannot exceed :amount', ['amount' => $funds_params['max_client_balance']])],
                'message' => ['amount' => [__('error.Total balance cannot exceed :amount', ['amount' => $funds_params['max_client_balance']])]],
            ], 422);
        }

        $uuid = $client->createInvoiceProformaAddFunds(round($amount, 2));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Successfully'),
            'redirect' => route('client.web.panel.client.invoice.payment', ['uuid' => $uuid]),
        ]);
    }
}
