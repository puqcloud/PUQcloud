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
return [
    'time_based' => [
        'admin_session_log_history' => [
            'label' => 'Admin Session Log History',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 30,
            'description' => 'Number of days to keep logs of admin sessions',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'client_session_log_history' => [
            'label' => 'Client Session Log History',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 30,
            'description' => 'Number of days to keep logs of client sessions',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'activity_log_history' => [
            'label' => 'Activity Log History',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 30,
            'description' => 'Number of days to keep logs of system activities',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'module_log_history' => [
            'label' => 'Module Log History',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 30,
            'description' => 'Number of days to keep logs of module actions',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'notification_history' => [
            'label' => 'Notification History',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 30,
            'description' => 'Number of days to retain notification logs',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'completed_task_queue_history' => [
            'label' => 'Completed Task Queue History',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 30,
            'description' => 'Number of days to keep logs of completed tasks in the queue',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'task_queue_history' => [
            'label' => 'Task Queue History',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 30,
            'description' => 'Number of days to keep logs of tasks in the queue',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'user_verification_code_lifetime' => [
            'label' => 'User Verification Code Lifetime',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 10,
            'description' => 'The lifetime of the code when verified using email or phone number',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

    ],

    'finance' => [
        'default_invoice_due_days' => [
            'label' => 'Default Invoice Due Days',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 7,
            'description' => 'Number of days after issue date to set as invoice due date',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'min_add_funds_amount' => [
            'label' => 'Minimum Add Funds Amount',
            'type' => 'number',
            'validation' => 'required|numeric|min:0.01',
            'default' => 10.00,
            'description' => 'Minimum amount allowed for adding funds (in default currency)',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'max_add_funds_amount' => [
            'label' => 'Maximum Add Funds Amount',
            'type' => 'number',
            'validation' => 'required|numeric|min:0.01',
            'default' => 1000.00,
            'description' => 'Maximum amount allowed for adding funds (in default currency)',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],

        'max_client_balance' => [
            'label' => 'Maximum Client Balance',
            'type' => 'number',
            'validation' => 'required|numeric|min:0.01',
            'default' => 5000.00,
            'description' => "Maximum allowed total balance on client's account (in default currency)",
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],
    ],

    'client' => [
        'user_reset_password_url_expire' => [
            'label' => 'Reset Password URL Expire',
            'type' => 'number',
            'validation' => 'required|integer|min:1',
            'default' => 60,
            'description' => 'The number of minutes before the reset password link expires',
            'class' => 'col-12 col-md-6 col-lg-4 col-xl-3',
        ],
    ],

    'social' => [
        'facebook' => [
            'label' => 'Facebook',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Facebook profile or page URL',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your YouTube channel URL',
        ],
        'github' => [
            'label' => 'GitHub',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your GitHub username or URL',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Instagram username or URL',
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Requires a named company page - does not support individuals. Enter your LinkedIn page URL',
        ],

        'whatsapp' => [
            'label' => 'WhatsApp',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter the phone number registered for WhatsApp including country prefix',
        ],
        'twitter' => [
            'label' => 'Twitter',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Twitter handle or URL',
        ],
        'viber' => [
            'label' => 'Viber',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Viber username or phone number',
        ],
        'telegram' => [
            'label' => 'Telegram',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Telegram username or phone number',
        ],

        'bitbucket' => [
            'label' => 'BitBucket',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your BitBucket username or URL',
        ],
        'discord' => [
            'label' => 'Discord',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Since Discord is invite-based, generate a permanent invite URL and enter the part after https://discord.gg/ here',
        ],
        'flickr' => [
            'label' => 'Flickr',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Flickr profile URL',
        ],
        'gitter' => [
            'label' => 'Gitter',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Gitter chat room or profile URL',
        ],
        'reddit' => [
            'label' => 'Reddit',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Reddit profile or community URL',
        ],
        'skype' => [
            'label' => 'Skype',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Skype username',
        ],
        'slack' => [
            'label' => 'Slack',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter Slack workspace ID',
        ],
        'vimeo' => [
            'label' => 'Vimeo',
            'type' => 'text',
            'validation' => '',
            'default' => '',
            'description' => 'Enter your Vimeo profile URL',
        ],
    ],
];
