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
    // Permissions for admins
    [
        'name' => 'View admins',
        'key' => 'admins-view',
        'description' => 'Allows viewing of Administrator users',
        'group' => 'Permissions',
    ],
    [
        'name' => 'Edit admins',
        'key' => 'admins-edit',
        'description' => 'Allows editing of Administrator users',
        'group' => 'Permissions',
    ],
    [
        'name' => 'Create admins',
        'key' => 'admins-create',
        'description' => 'Allows creating Administrator users',
        'group' => 'Permissions',
    ],
    [
        'name' => 'Delete admins',
        'key' => 'admins-delete',
        'description' => 'Allows deleting Administrator users',
        'group' => 'Permissions',
    ],

    // Permissions for groups
    [
        'name' => 'View groups',
        'key' => 'groups-view',
        'description' => 'Allows viewing of groups',
        'group' => 'Permissions',
    ],
    [
        'name' => 'Edit groups',
        'key' => 'groups-edit',
        'description' => 'Allows editing of groups',
        'group' => 'Permissions',
    ],
    [
        'name' => 'Create groups',
        'key' => 'groups-create',
        'description' => 'Allows creating groups',
        'group' => 'Permissions',
    ],
    [
        'name' => 'Delete groups',
        'key' => 'groups-delete',
        'description' => 'Allows deleting groups',
        'group' => 'Permissions',
    ],

    // Automation
    [
        'name' => 'View automation settings',
        'key' => 'automation-settings-view',
        'description' => 'Allows viewing automation settings',
        'group' => 'Automation',
    ],
    [
        'name' => 'Edit automation settings',
        'key' => 'automation-settings-edit',
        'description' => 'Allows editing automation settings',
        'group' => 'Automation',
    ],
    [
        'name' => 'View automation scheduler',
        'key' => 'automation-scheduler-view',
        'description' => 'Allows viewing automation scheduler',
        'group' => 'Automation',
    ],
    [
        'name' => 'Edit automation scheduler',
        'key' => 'automation-scheduler-edit',
        'description' => 'Allows editing automation scheduler',
        'group' => 'Automation',
    ],
    [
        'name' => 'Horizon console',
        'key' => 'automation-horizon-management',
        'description' => 'Allows management of Horizon',
        'group' => 'Automation',
    ],

    // Monitoring
    [
        'name' => 'View task queue',
        'key' => 'task-queue-view',
        'description' => 'View the task queue and execution statuses in real time',
        'group' => 'Monitoring',
    ],
    [
        'name' => 'View queues summary',
        'key' => 'queues-summary-view',
        'description' => 'View the total number of tasks by status and by queue',
        'group' => 'Monitoring',
    ],
    [
        'name' => 'View admin sessions',
        'key' => 'admin-session-log-view',
        'description' => 'Allows viewing admin sessions',
        'group' => 'Monitoring',
    ],
    [
        'name' => 'View client sessions',
        'key' => 'client-session-log-view',
        'description' => 'Allows viewing client sessions',
        'group' => 'Monitoring',
    ],
    [
        'name' => 'View Activity Log',
        'key' => 'activity-log-view',
        'description' => 'Allows viewing Activity Log',
        'group' => 'Monitoring',
    ],
    [
        'name' => 'View Module Log',
        'key' => 'module-log-view',
        'description' => 'Allows viewing Module Log',
        'group' => 'Monitoring',
    ],
    [
        'name' => 'View Notification History',
        'key' => 'notification-history-view',
        'description' => 'Allows viewing Notification History',
        'group' => 'Monitoring',
    ],

    // Settings
    [
        'name' => 'Notification Senders',
        'key' => 'notification-senders-management',
        'description' => 'Allows management of Notification Senders',
        'group' => 'Settings',
    ],
    [
        'name' => 'Notification Layouts',
        'key' => 'notification-layouts-management',
        'description' => 'Allows management of Notification Layouts',
        'group' => 'Settings',
    ],
    [
        'name' => 'Notification Templates',
        'key' => 'notification-templates-management',
        'description' => 'Allows management of Notification Templates',
        'group' => 'Settings',
    ],
    [
        'name' => 'Notification rules',
        'key' => 'notification-rules-management',
        'description' => 'Allows management of Notification Rules',
        'group' => 'Settings',
    ],
    [
        'name' => 'General Settings',
        'key' => 'general-settings-management',
        'description' => 'Allows management of General Settings',
        'group' => 'Settings',
    ],
    [
        'name' => 'Currencies',
        'key' => 'currencies-management',
        'description' => 'Allows management of Currencies',
        'group' => 'Settings',
    ],
    [
        'name' => 'Add-ons Marketplace',
        'key' => 'add-ons-marketplace-management',
        'description' => 'Allows management of Add-ons Marketplace',
        'group' => 'Settings',
    ],
    [
        'name' => 'Add-ons Modules',
        'key' => 'add-ons-modules-management',
        'description' => 'Allows management of Add-ons Modules',
        'group' => 'Settings',
    ],
    [
        'name' => 'Add-ons Reports',
        'key' => 'add-ons-reports-management',
        'description' => 'Allows management of Add-ons Reports',
        'group' => 'Settings',
    ],

    // Clients
    [
        'name' => 'View clients',
        'key' => 'clients-view',
        'description' => 'Allows viewing of Clients',
        'group' => 'Clients',
    ],
    [
        'name' => 'Edit clients',
        'key' => 'clients-edit',
        'description' => 'Allows editing of Clients',
        'group' => 'Clients',
    ],
    [
        'name' => 'Create clients',
        'key' => 'clients-create',
        'description' => 'Allows creating Clients',
        'group' => 'Clients',
    ],
    [
        'name' => 'Delete clients',
        'key' => 'clients-delete',
        'description' => 'Allows deleting Clients',
        'group' => 'Clients',
    ],

    [
        'name' => 'View users',
        'key' => 'users-view',
        'description' => 'Allows viewing of Users',
        'group' => 'Clients',
    ],
    [
        'name' => 'Edit users',
        'key' => 'users-edit',
        'description' => 'Allows editing of Users',
        'group' => 'Clients',
    ],
    [
        'name' => 'Create users',
        'key' => 'users-create',
        'description' => 'Allows creating Users',
        'group' => 'Clients',
    ],
    [
        'name' => 'Delete users',
        'key' => 'users-delete',
        'description' => 'Allows deleting users',
        'group' => 'Clients',
    ],

    // Products
    [
        'name' => 'Products Management',
        'key' => 'products-management',
        'description' => 'Allows management of Products',
        'group' => 'Products',
    ],
    [
        'name' => 'Product Groups',
        'key' => 'product-groups-management',
        'description' => 'Allows management of Product Groups',
        'group' => 'Products',
    ],
    [
        'name' => 'Product Attributes',
        'key' => 'product-attributes-management',
        'description' => 'Allows management of Product Attributes',
        'group' => 'Products',
    ],
    [
        'name' => 'Product Options',
        'key' => 'product-options-management',
        'description' => 'Allows management of Product Options',
        'group' => 'Products',
    ],

    // Finance
    [
        'name' => 'View finance',
        'key' => 'finance-view',
        'description' => 'Allows viewing financial information',
        'group' => 'Finance',
    ],
    [
        'name' => 'Edit finance',
        'key' => 'finance-edit',
        'description' => 'Allows editing financial information',
        'group' => 'Finance',
    ],
    [
        'name' => 'Create finance',
        'key' => 'finance-create',
        'description' => 'Allows creating financial information',
        'group' => 'Finance',
    ],
    [
        'name' => 'Delete finance',
        'key' => 'finance-delete',
        'description' => 'Allows deleting financial information',
        'group' => 'Finance',
    ],

    // DNS Manager
    [
        'name' => 'DNS Server Groups',
        'key' => 'dns-manager-dns-server-groups',
        'description' => 'Management of DNS Server Groups',
        'group' => 'DNS Manager',
    ],
    [
        'name' => 'DNS Servers',
        'key' => 'dns-manager-dns-servers',
        'description' => 'Management of DNS Servers',
        'group' => 'DNS Manager',
    ],
    [
        'name' => 'DNS Zones',
        'key' => 'dns-manager-dns-zones',
        'description' => 'Management of DNS Zones',
        'group' => 'DNS Manager',
    ],
    [
        'name' => 'DNS Records',
        'key' => 'dns-manager-dns-records',
        'description' => 'Management of DNS Records within Zones',
        'group' => 'DNS Manager',
    ],
];
