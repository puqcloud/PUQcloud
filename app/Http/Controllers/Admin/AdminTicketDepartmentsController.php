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
 *   GET  ticket_departments                    -> ticketDepartments()             [admin.web.ticket_departments]
 *   GET  ticket_department/{uuid}              -> ticketDepartment()              [admin.web.ticket_department]
 *
 * API Routes:
 *   GET    ticket_departments                  -> getTicketDepartments()          [admin.api.ticket_departments.get]
 *   POST   ticket_department                   -> postTicketDepartment()          [admin.api.ticket_department.post]
 *   GET    ticket_department/{uuid}            -> getTicketDepartment()           [admin.api.ticket_department.get]
 *   PUT    ticket_department/{uuid}            -> putTicketDepartment()           [admin.api.ticket_department.put]
 *   DELETE ticket_department/{uuid}            -> deleteTicketDepartment()        [admin.api.ticket_department.delete]
 *   GET    ticket_departments/select           -> getTicketDepartmentsSelect()    [admin.api.ticket_departments.select.get]
 *   POST   ticket_department/{uuid}/test_mail -> testMailConnection()            [admin.api.ticket_department.test_mail.post]
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketDepartment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Yajra\DataTables\DataTables;

class AdminTicketDepartmentsController extends Controller
{
    public function ticketDepartments(): View
    {
        $title = __('main.Ticket Departments');

        return view_admin('tickets.ticket_departments', compact('title'));
    }

    public function ticketDepartment(Request $request, $uuid): View
    {
        $title = __('main.Ticket Department');

        return view_admin('tickets.ticket_department', compact('title', 'uuid'));
    }

    #[OA\Get(
        path: 'admin.api.ticket_departments.get',
        description: 'Returns a DataTable list of ticket departments.',
        summary: 'Get ticket departments list',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket departments list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTicketDepartments(Request $request): JsonResponse
    {
        $query = TicketDepartment::query()->orderBy('sort_order', 'asc');

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($department) {
                    $admin_online = app('admin');
                    $urls = [];
                    if ($admin_online && $admin_online->hasPermission('tickets-departments')) {
                        $urls['edit'] = route('admin.web.ticket_department', $department->uuid);
                        $urls['delete'] = route('admin.api.ticket_department.delete', $department->uuid);
                    }

                    return $urls;
                })
                ->addColumn('admins_count', function ($department) {
                    return $department->admins()->count();
                })
                ->make(true),
        ], 200);
    }

    #[OA\Post(
        path: 'admin.api.ticket_department.post',
        description: 'Creates a new ticket department with a unique name.',
        summary: 'Create a new ticket department',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Unique name for the ticket department', type: 'string', example: 'General Support'),
                        new OA\Property(property: 'description', description: 'Department description', type: 'string', example: 'General support inquiries'),
                        new OA\Property(property: 'email', description: 'Department email address', type: 'string', format: 'email', example: 'support@example.com'),
                    ]
                )
            )
        ),
        tags: ['Support'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket department created successfully',
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
    public function postTicketDepartment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:ticket_departments,name',
            'description' => 'nullable|string',
            'email' => 'nullable|email',
        ], [
            'name.required' => __('error.The Name field is required'),
            'name.unique' => __('error.The Name has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $department = new TicketDepartment;
        $department->name = $request->get('name');
        $department->description = $request->get('description');
        $department->email = $request->get('email');
        $department->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Created successfully'),
            'data' => [
                'redirect' => route('admin.web.ticket_department', $department->uuid),
            ],
        ]);
    }

    #[OA\Get(
        path: 'admin.api.ticket_department.get',
        description: 'Returns detailed information about a ticket department, including assigned admins data.',
        summary: 'Get ticket department by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket department',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket department data returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket department not found'),
        ]
    )]
    public function getTicketDepartment(Request $request, $uuid): JsonResponse
    {
        $model = TicketDepartment::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $responseData = $model->toArray();
        $responseData['admins_data'] = $model->admins->map(fn ($a) => ['id' => $a->uuid, 'text' => $a->firstname.' '.$a->lastname]);
        $responseData['mail_import_password_set'] = ! empty($model->mail_import_password);
        unset($responseData['mail_import_password']);

        return response()->json([
            'data' => $responseData,
        ]);
    }

    #[OA\Put(
        path: 'admin.api.ticket_department.put',
        description: 'Updates a ticket department by UUID, including name, description, email, and mail import settings.',
        summary: 'Update ticket department by UUID',
        security: [['apiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', description: 'Department name', type: 'string', example: 'General Support'),
                        new OA\Property(property: 'description', description: 'Department description', type: 'string'),
                        new OA\Property(property: 'email', description: 'Department email', type: 'string', format: 'email'),
                        new OA\Property(property: 'hidden', description: 'Hidden from clients', type: 'boolean'),
                        new OA\Property(property: 'feedback_on_close', description: 'Request feedback on close', type: 'boolean'),
                        new OA\Property(property: 'auto_close_hours', description: 'Auto close after hours', type: 'integer'),
                        new OA\Property(property: 'sort_order', description: 'Sort order', type: 'integer'),
                        new OA\Property(property: 'mail_import_enabled', description: 'Enable mail import', type: 'boolean'),
                        new OA\Property(property: 'mail_import_type', description: 'Mail import type (imap/pop3)', type: 'string'),
                        new OA\Property(property: 'mail_import_host', description: 'Mail server host', type: 'string'),
                        new OA\Property(property: 'mail_import_port', description: 'Mail server port', type: 'integer'),
                        new OA\Property(property: 'mail_import_username', description: 'Mail username', type: 'string'),
                        new OA\Property(property: 'mail_import_password', description: 'Mail password', type: 'string'),
                        new OA\Property(property: 'mail_import_encryption', description: 'Mail encryption (ssl/tls/none)', type: 'string'),
                        new OA\Property(property: 'mail_import_delete_after', description: 'Delete mail after import', type: 'boolean'),
                        new OA\Property(property: 'admin_uuids', description: 'Array of admin UUIDs to assign', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            )
        ),
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket department',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket department updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Ticket department not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function putTicketDepartment(Request $request, $uuid): JsonResponse
    {
        $model = TicketDepartment::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $boolFields = ['hidden', 'feedback_on_close', 'allow_client_reopen', 'mail_import_enabled', 'mail_import_delete_after', 'mail_import_replies_only', 'mail_import_clients_only'];
        foreach ($boolFields as $field) {
            $request->merge([$field => $request->get($field) === 'yes' || $request->get($field) === true || $request->get($field) === '1']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:ticket_departments,name,'.$uuid.',uuid',
            'description' => 'nullable|string',
            'email' => 'nullable|email',
            'hidden' => 'nullable|boolean',
            'feedback_on_close' => 'nullable|boolean',
            'allow_client_reopen' => 'nullable|boolean',
            'auto_close_hours' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'mail_import_enabled' => 'nullable|boolean',
            'mail_import_type' => 'nullable|string',
            'mail_import_host' => 'nullable|string',
            'mail_import_port' => 'nullable|integer',
            'mail_import_username' => 'nullable|string',
            'mail_import_password' => 'nullable|string',
            'mail_import_encryption' => 'nullable|string',
            'mail_import_delete_after' => 'nullable|boolean',
            'mail_import_replies_only' => 'nullable|boolean',
            'mail_import_clients_only' => 'nullable|boolean',
            'sla_response_hours' => 'nullable|integer|min:0',
            'allowed_file_extensions' => 'nullable|string',
            'max_file_size_mb' => 'nullable|integer|min:0',
            'max_files_count' => 'nullable|integer|min:0',
        ], [
            'name.required' => __('error.The Name field is required'),
            'name.unique' => __('error.The Name has already been taken'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $fields = [
            'name', 'description', 'email', 'hidden',
            'feedback_on_close', 'allow_client_reopen', 'auto_close_hours',
            'mail_import_enabled', 'mail_import_type', 'mail_import_host',
            'mail_import_port', 'mail_import_username', 'mail_import_password',
            'mail_import_encryption', 'mail_import_delete_after',
            'mail_import_replies_only', 'mail_import_clients_only', 'sort_order',
            'sla_response_hours',
            'allowed_file_extensions', 'max_file_size_mb', 'max_files_count',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = $request->get($field);

                if ($field === 'mail_import_password' && ($value === '' || $value === null)) {
                    continue;
                }

                if (in_array($field, ['sla_response_hours', 'allowed_file_extensions', 'max_file_size_mb', 'max_files_count']) && $value === '') {
                    $value = null;
                }
                $model->$field = $value;
            }
        }

        $model->save();

        $model->admins()->sync($request->get('admin_uuids', []));

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
            'data' => $model,
        ]);
    }

    #[OA\Delete(
        path: 'admin.api.ticket_department.delete',
        description: 'Deletes a ticket department by UUID. Cannot delete if tickets exist in this department.',
        summary: 'Delete ticket department by UUID',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket department to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket department deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 403, description: 'Cannot delete department with existing tickets'),
            new OA\Response(response: 404, description: 'Ticket department not found'),
        ]
    )]
    public function deleteTicketDepartment(Request $request, $uuid): JsonResponse
    {
        $model = TicketDepartment::find($uuid);

        if (empty($model)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if ($model->tickets()->count() > 0) {
            return response()->json([
                'errors' => [__('error.Cannot delete department with existing tickets')],
            ], 403);
        }

        try {
            $deleted = $model->delete();
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
        path: 'admin.api.ticket_departments.select.get',
        description: 'Returns a select list of ticket departments.',
        summary: 'Get ticket departments select list',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
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
                description: 'Ticket departments select list returned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getTicketDepartmentsSelect(Request $request): JsonResponse
    {
        $search = $request->input('q');

        if (! empty($search)) {
            $departments = TicketDepartment::orderBy('sort_order', 'asc')->where('name', 'like', '%'.$search.'%')->get();
        } else {
            $departments = TicketDepartment::orderBy('sort_order', 'asc')->get();
        }

        $results = [];
        foreach ($departments->toArray() as $department) {
            $results[] = [
                'id' => $department['uuid'],
                'text' => $department['name'],
            ];
        }

        return response()->json(['data' => [
            'results' => $results,
            'pagination' => [
                'more' => false,
            ],
        ]], 200);
    }

    #[OA\Post(
        path: 'admin.api.ticket_department.test_mail.post',
        description: 'Tests the mail import connection for a ticket department.',
        summary: 'Test mail import connection',
        security: [['apiKeyAuth' => []]],
        tags: ['Support'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                description: 'UUID of the ticket department',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Connection test result',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ], type: 'object')),
            new OA\Response(response: 404, description: 'Department not found'),
        ]
    )]
    public function testMailConnection(Request $request, $uuid): JsonResponse
    {
        $department = TicketDepartment::find($uuid);

        if (empty($department)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        if (! $department->mail_import_host || ! $department->mail_import_username) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Mail import settings are not configured')],
            ], 422);
        }

        $encryption = $department->mail_import_encryption;
        if ($encryption === 'none' || ! $encryption) {
            $encryption = false;
        }

        $config = [
            'host' => $department->mail_import_host,
            'port' => (int) ($department->mail_import_port ?: 993),
            'encryption' => $encryption,
            'validate_cert' => false,
            'username' => $department->mail_import_username,
            'password' => $department->mail_import_password,
            'protocol' => $department->mail_import_type ?: 'imap',
        ];

        $logContext = [
            'host' => $config['host'],
            'port' => $config['port'],
            'protocol' => $config['protocol'],
            'encryption' => $config['encryption'] ?: 'none',
        ];

        try {
            $client = (new \Webklex\PHPIMAP\ClientManager())->make($config);
            $client->connect();

            $folder = $client->getFolder('INBOX');
            $messageCount = $folder ? $folder->query()->unseen()->count() : 0;

            $client->disconnect();

            logModule('TicketMailImport', $department->name, 'test_connection', 'info', $logContext,
                ['status' => 'success', 'unseen_messages' => $messageCount]
            );

            return response()->json([
                'status' => 'success',
                'message' => __('message.Connection successful'),
                'data' => [
                    'unseen_messages' => $messageCount,
                ],
            ]);
        } catch (\Throwable $e) {
            logModule('TicketMailImport', $department->name, 'test_connection', 'error', $logContext,
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return response()->json([
                'status' => 'error',
                'errors' => [
                    __('error.Mail connection failed').': '.$e->getMessage(),
                    'Config: '.$config['protocol'].'://'.$config['username'].'@'.$config['host'].':'.$config['port'].' (encryption: '.($config['encryption'] ?: 'none').')',
                ],
            ], 422);
        }
    }
}

