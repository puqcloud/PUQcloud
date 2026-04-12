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

/*
 * WEB Routes:
 *   GET  redirect/{label}:{uuid}                  -> uuidRedirect()             [admin.web.redirect]
 *
 * API Routes:
 *   GET  languages/select                         -> getLanguagesSelect()       [admin.api.languages.select.get]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use OpenApi\Attributes as OA;

class AdminController extends Controller
{
    #[OA\Get(
        path: 'admin.api.languages.select.get',
        summary: 'Get list of admin panel languages for select',
        description: 'Returns a list of available languages for admin panel select input. Supports optional search term filtering.',
        security: [['apiKeyAuth' => []]],
        tags: ['Language'],
        parameters: [
            new OA\Parameter(
                name: 'term',
                in: 'query',
                description: 'Optional search term to filter languages',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of admin panel languages returned successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'results',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', example: 'en'),
                                            new OA\Property(property: 'text', type: 'string', example: 'English (English)')
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: 'pagination',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'more', type: 'boolean', example: false)
                                    ]
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
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

        return response()->json([
            'data' => [
                'results' => array_values($filteredLanguages),
                'pagination' => [
                    'more' => false,
                ],
            ],
        ], 200);
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
