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
return [
    'categories' => [
        [
            'key' => 'staffAdministrative',
            'name' => 'Staff Administrative',
            'description' => "Personal notifications related specifically to an administrator's actions and interaction with the system, such as welcome messages, password reset, and security alerts.",
            'order' => '1',
            'notifications' => [
                [
                    'name' => 'Role Change Notification',
                    'description' => 'Information about changing administrator access rights.',
                ],
            ],
        ],
        [
            'key' => 'staffOperational',
            'name' => 'Staff Operational',
            'description' => 'Notifications for administrators about system-level events involving clients, such as new orders, client registration, invoice payment, and other important system activities.',
            'order' => '2',
            'notifications' => [
                [
                    'name' => 'Admin Failed Login Attempt',
                    'description' => 'Alerts about a failed login attempt by an admin',
                ],
                [
                    'name' => 'New Order Notification',
                    'description' => 'Information about a new service order',
                ],
                [
                    'name' => 'Service Create Successful',
                    'description' => 'Confirms successful service creation',
                ],
                [
                    'name' => 'Service Termination Successful',
                    'description' => 'Confirms successful service termination',
                ],
                [
                    'name' => 'Service Cancellation Successful',
                    'description' => 'Confirms successful service cancellation',
                ],
                [
                    'name' => 'Admin Invoice Overdue Notification',
                    'description' => 'Alert when a service billing invoice becomes overdue',
                ],
                [
                    'name' => 'Admin Service Invoice Created',
                    'description' => 'Notification when a new service billing invoice is generated',
                ],
                [
                    'name' => 'New Ticket from Client',
                    'description' => 'Alert when a client submits a new support ticket',
                ],
                [
                    'name' => 'Client Reply on Ticket',
                    'description' => 'Alert when a client replies to a support ticket',
                ],
                [
                    'name' => 'Ticket Assigned to You',
                    'description' => 'Notification when a ticket is assigned to you',
                ],
                [
                    'name' => 'Ticket Escalated',
                    'description' => 'Alert when a ticket is escalated or transferred',
                ],
                [
                    'name' => 'Ticket SLA Breached',
                    'description' => 'Alert when a ticket SLA response time has been exceeded',
                ],
                [
                    'name' => 'Ticket Closed',
                    'description' => 'Alert when a ticket is closed',
                ],
                [
                    'name' => 'Ticket Reopened',
                    'description' => 'Alert when a ticket is reopened by a client',
                ],
            ],
        ],
    ],
];
