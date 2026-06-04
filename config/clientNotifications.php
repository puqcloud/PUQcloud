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
            'key' => 'clientAdministrative',
            'name' => 'Client Administrative',
            'description' => 'Personal notifications for individual client users related to their own actions, such as login, password reset, and profile changes.',
            'order' => '1',
            'notifications' => [
                [
                    'name' => 'Client Welcome Email',
                    'description' => 'A welcome email for a new user with instructions on setting up their account',
                ],
                [
                    'name' => 'Client One-time passcode',
                    'description' => 'One-time code to confirm email or phone number, two-step verification or changes',
                ],
                [
                    'name' => 'Client Reset Password',
                    'description' => 'A reset password email for a user with instructions and reset password URL',
                ],
            ],
        ],
        [
            'key' => 'clientOperational',
            'name' => 'Client Operational',
            'description' => 'Shared notifications visible to users of the same client account who have permission to view invoices, services, and general account activity.',
            'order' => '2',
            'notifications' => [
                [
                    'name' => 'Client Proforma Invoice Created',
                    'description' => 'Information about a proforma invoice',
                ],
                [
                    'name' => 'Client Invoice Created',
                    'description' => 'Information about a invoice',
                ],
                [
                    'name' => 'Client Invoice Payment Reminder',
                    'description' => 'Payment reminder sent before the invoice due date',
                ],
                [
                    'name' => 'Client Invoice First Overdue Notice',
                    'description' => 'First overdue notice sent after the invoice due date',
                ],
                [
                    'name' => 'Client Invoice Second Overdue Notice',
                    'description' => 'Second overdue notice sent after the invoice due date',
                ],
                [
                    'name' => 'Client Invoice Third Overdue Notice',
                    'description' => 'Third (final) overdue notice sent after the invoice due date',
                ],
                [
                    'name' => 'Client Billing Mode Changed',
                    'description' => 'Notification when service billing mode is switched between prepaid and invoice',
                ],
                [
                    'name' => 'Client Ticket Opened',
                    'description' => 'Confirmation when a new support ticket is submitted',
                ],
                [
                    'name' => 'Client Ticket Reply from Staff',
                    'description' => 'Notification when staff replies to a support ticket',
                ],
                [
                    'name' => 'Client Ticket Closed',
                    'description' => 'Notification when a support ticket is closed',
                ],
                [
                    'name' => 'Client Ticket Reopened',
                    'description' => 'Notification when a support ticket is reopened',
                ],
            ],
        ],
    ],
];
