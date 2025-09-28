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
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AdminController extends Controller
{
    public function getLanguagesSelect(Request $request): JsonResponse
    {

        $languages = [];
        foreach (config('locale.admin.locales') as $key => $value) {
            $languages[] = [
                'id' => $key,
                'text' => $value['name'].' ('.$value['native'].')',
            ];
        }

        $searchTerm = $request->get('term', '');

        $filteredLanguages = array_filter($languages, function ($language) use ($searchTerm) {
            return empty($searchTerm) || stripos($language['text'], $searchTerm) !== false;
        });

        return response()->json(['data' => [
            'results' => array_values($filteredLanguages),
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    public function uuidRedirect($label, $uuid)
    {
        $label = ucfirst(strtolower($label));

        switch ($label) {
            case 'Service':
                $service = Service::findOrFail($uuid);
                $url = route('admin.web.client.tab', [
                    'uuid' => $service->client->uuid,
                    'tab' => 'services',
                    'edit' => $uuid,
                ]);
                break;

            case 'Invoice':
                $service = Invoice::findOrFail($uuid);
                $url = route('admin.web.client.tab', [
                    'uuid' => $service->client->uuid,
                    'tab' => 'invoices',
                    'edit' => $uuid,
                ]);
                break;

            case 'Product':
                $url = route('admin.web.product.tab', [
                    'uuid' => $uuid,
                    'tab' => 'general',
                ]);
                break;

            case 'Client':
                $url = route('admin.web.client.tab', [
                    'uuid' => $uuid,
                    'tab' => 'summary',
                ]);
                break;

            case 'Admin':
                $url = route('admin.web.admin', [
                    'uuid' => $uuid,
                ]);
                break;

            default:
                abort(404, 'Unknown reference type');
        }

        return Redirect::to($url);
    }
}
