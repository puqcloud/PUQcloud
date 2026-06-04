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
 *   GET  transactions                                       -> transactions()        [admin.web.transactions]
 *
 * API Routes:
 *   GET  transactions                                       -> getTransactions()     [admin.api.transactions.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTransactionsController extends Controller
{
    public function transactions(): View
    {
        $title = __('main.Transactions');

        return view_admin('finance.transactions.transactions', compact('title'));
    }

    #[OA\Get(
        path: 'admin.api.transactions.get',
        description: 'Returns a DataTable list of transactions.',
        summary: 'Get transactions list',
        security: [['apiKeyAuth' => []]],
        tags: ['Finance'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transactions list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTransactions(Request $request): JsonResponse
    {
        $locale = config('locale.admin.default');
        $query = Transaction::query()
            ->with('client.currency')
            ->leftJoin('translations', function ($join) use ($locale) {
                $join->on('translations.model_uuid', '=', 'transactions.payment_gateway_uuid')
                    ->where('translations.model_type', '=', 'App\\Models\\PaymentGateway')
                    ->where('translations.field', '=', 'name')
                    ->where('translations.locale', '=', $locale);
            })
            ->select('transactions.*', 'translations.value as payment_gateway_name');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('transactions.transaction_date', 'like', "%{$search}%")
                                ->orWhere('transactions.uuid', 'like', "%{$search}%")
                                ->orWhere('transactions.transaction_id', 'like', "%{$search}%")
                                ->orWhere('transactions.description', 'like', "%{$search}%")
                                ->orWhere('transactions.type', 'like', "%{$search}%")
                                ->orWhere('transactions.amount_net', 'like', "%{$search}%")
                                ->orWhere('transactions.amount_gross', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('currency', function ($transaction) {
                    return $transaction->client->currency->toArray();
                })
                ->addColumn('payment_gateway', function ($transaction) {
                    if ($transaction->payment_gateway_uuid) {
                        return ['uuid' => $transaction->payment_gateway_uuid, 'name' => $transaction->payment_gateway_name ?? ''];
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
}
