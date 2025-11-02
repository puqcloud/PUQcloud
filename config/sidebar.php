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
    [
        'title' => 'Menu',
        'subItems' => [
            [
                'title' => 'Dashboard',
                'icon' => 'metismenu-icon fa fa-chart-line',
                'link' => 'admin.web.dashboard',
                'active_links' => ['admin.web.dashboard'],
                'permission' => '',
            ],
            [
                'title' => 'Clients',
                'icon' => 'metismenu-icon fas fa-users',
                'subItems' => [
                    [
                        'title' => 'Manage Clients',
                        'link' => 'admin.web.clients',
                        'active_links' => ['admin.web.clients'],
                        'permission' => 'clients-view',
                    ],
                    [
                        'title' => 'Manage Users',
                        'link' => 'admin.web.users',
                        'active_links' => ['admin.web.users'],
                        'permission' => 'users-view',
                    ],
                ],
            ],
            [
                'title' => 'Services',
                'icon' => 'metismenu-icon fas fa-cloud',
                'subItems' => [
                    [
                        'title' => 'Create New Service',
                        'link' => 'admin.web.service.create',
                        'active_links' => ['admin.web.service.create'],
                        'permission' => 'clients-view',
                    ],
                    [
                        'title' => 'All Services',
                        'link' => 'admin.web.services',
                        'active_links' => ['admin.web.services'],
                        'permission' => 'clients-view',
                    ],
                ],
            ],
            [
                'title' => 'Finance',
                'icon' => 'metismenu-icon fas fa-credit-card',
                'subItems' => [
                    [
                        'title' => 'Transactions',
                        'link' => 'admin.web.transactions',
                        'active_links' => ['admin.web.transactions'],
                        'permission' => 'finance-view',
                    ],
                    [
                        'title' => 'Home Companies',
                        'link' => 'admin.web.home_companies',
                        'active_links' => ['admin.web.home_companies'],
                        'permission' => 'finance-view',
                    ],
                    [
                        'title' => 'Tax Rules',
                        'link' => 'admin.web.tax_rules',
                        'active_links' => ['admin.web.tax_rules'],
                        'permission' => 'finance-view',
                    ],
                ],
            ],
            [
                'title' => 'Products',
                'icon' => 'metismenu-icon fa fa-box-open',
                'subItems' => [
                    [
                        'title' => 'Products',
                        'link' => 'admin.web.products',
                        'active_links' => ['admin.web.products', 'admin.web.product.tab'],
                        'permission' => 'products-management',
                    ],
                    [
                        'title' => 'Product Groups',
                        'link' => 'admin.web.product_groups',
                        'active_links' => ['admin.web.product_groups', 'admin.web.product_group.tab'],
                        'permission' => 'product-groups-management',
                    ],
                    [
                        'title' => 'Attribute Groups',
                        'link' => 'admin.web.product_attribute_groups',
                        'active_links' => [
                            'admin.web.product_attribute_groups', 'admin.web.product_attribute_group.tab',
                        ],
                        'permission' => 'product-attributes-management',
                    ],
                    [
                        'title' => 'Option Groups',
                        'link' => 'admin.web.product_option_groups',
                        'active_links' => ['admin.web.product_option_groups', 'admin.web.product_option_group.tab'],
                        'permission' => 'product-options-management',
                    ],
                ],
            ],

            [
                'title' => 'Monitoring',
                'icon' => 'metismenu-icon fas fa-tachometer-alt',
                'subItems' => [
                    [
                        'title' => 'Task queue',
                        'link' => 'admin.web.task_queue',
                        'active_links' => ['admin.web.task_queue'],
                        'permission' => 'task-queue-view',
                    ],
                    [
                        'title' => 'Admin Sessions',
                        'link' => 'admin.web.admin_session_logs',
                        'active_links' => ['admin.web.admin_session_logs'],
                        'permission' => 'admin-session-log-view',
                    ],
                    [
                        'title' => 'Client Sessions',
                        'link' => 'admin.web.client_session_logs',
                        'active_links' => ['admin.web.client_session_logs'],
                        'permission' => 'client-session-log-view',
                    ],
                    [
                        'title' => 'Activity Log',
                        'link' => 'admin.web.activity_logs',
                        'active_links' => ['admin.web.activity_logs'],
                        'permission' => 'activity-log-view',
                    ],
                    [
                        'title' => 'Module Log',
                        'link' => 'admin.web.module_logs',
                        'active_links' => ['admin.web.module_logs'],
                        'permission' => 'module-log-view',
                    ],
                    [
                        'title' => 'Notification History',
                        'link' => 'admin.web.notification_histories',
                        'active_links' => ['admin.web.notification_histories'],
                        'permission' => 'notification-history-view',
                    ],
                ],
            ],
        ],
    ],
    [
        'title' => 'Settings',
        'link' => '#',
        'subItems' => [
            [
                'title' => 'Staff',
                'icon' => 'metismenu-icon fas fa-user-friends',
                'subItems' => [
                    [
                        'title' => 'Admins',
                        'link' => 'admin.web.admins',
                        'active_links' => ['admin.web.admins', 'admin.web.admin'],
                        'permission' => 'admins-view',
                    ],
                    [
                        'title' => 'Groups',
                        'link' => 'admin.web.groups',
                        'active_links' => ['admin.web.groups', 'admin.web.group'],
                        'permission' => 'groups-view',
                    ],
                ],
            ],
            [
                'title' => 'Automation',
                'icon' => 'metismenu-icon fa fa-robot',
                'subItems' => [
                    [
                        'title' => 'Scheduler',
                        'link' => 'admin.web.scheduler',
                        'active_links' => ['admin.web.scheduler'],
                        'permission' => 'automation-scheduler-view',
                    ],
                    [
                        'title' => 'Horizon',
                        'link' => 'admin.web.automation.horizon',
                        'permission' => 'automation-horizon-management',
                        'blank' => true,
                    ],
                ],
            ],
            [
                'title' => 'Email & Notifications',
                'icon' => 'metismenu-icon fas fa-mail-bulk',
                'subItems' => [
                    [
                        'title' => 'Notification Senders',
                        'link' => 'admin.web.notification_senders',
                        'active_links' => ['admin.web.notification_senders', 'admin.web.notification_sender'],
                        'permission' => 'notification-senders-management',
                    ],
                    [
                        'title' => 'Notification Layouts',
                        'link' => 'admin.web.notification_layouts',
                        'active_links' => ['admin.web.notification_layouts', 'admin.web.notification_layout'],
                        'permission' => 'notification-layouts-management',
                    ],
                    [
                        'title' => 'Notification Templates',
                        'link' => 'admin.web.notification_templates',
                        'active_links' => ['admin.web.notification_templates', 'admin.web.notification_template'],
                        'permission' => 'notification-templates-management',
                    ],
                ],
            ],
            [
                'title' => 'DNS Manager',
                'icon' => 'metismenu-icon fas fa-globe',
                'subItems' => [
                    [
                        'title' => 'Server Groups',
                        'link' => 'admin.web.dns_server_groups',
                        'active_links' => ['admin.web.dns_server_groups', 'admin.web.dns_server_group'],
                        'permission' => 'dns-manager-dns-server-groups',
                    ],
                    [
                        'title' => 'Servers',
                        'link' => 'admin.web.dns_servers',
                        'active_links' => [
                            'admin.web.dns_servers', 'admin.web.dns_server', 'admin.web.dns_server.import',
                        ],
                        'permission' => 'dns-manager-dns-servers',
                    ],
                    [
                        'title' => 'Zones',
                        'link' => 'admin.web.dns_zones',
                        'active_links' => ['admin.web.dns_zones', 'admin.web.dns_zone'],
                        'permission' => 'dns-manager-dns-zones',
                    ],
                ],
            ],
            [
                'title' => 'SSL Manager',
                'icon' => 'metismenu-icon fas fa-shield-alt',
                'subItems' => [
                    [
                        'title' => 'Certificate Authorities',
                        'link' => 'admin.web.certificate_authorities',
                        'active_links' => [
                            'admin.web.certificate_authorities',
                            'admin.web.certificate_authority',
                        ],
                        'permission' => 'ssl-manager-certificate-authorities',
                    ],
                    [
                        'title' => 'SSL Certificates',
                        'link' => 'admin.web.ssl_certificates',
                        'active_links' => [
                            'admin.web.ssl_certificates',
                            'admin.web.ssl_certificate',
                        ],
                        'permission' => 'ssl-manager-ssl-certificates',
                    ],
                ],
            ],
            [
                'title' => 'General',
                'icon' => 'metismenu-icon fas fa-tools',
                'subItems' => [
                    [
                        'title' => 'General',
                        'link' => 'admin.web.general_settings',
                        'active_links' => ['admin.web.general_settings'],
                        'permission' => 'general-settings-management',
                    ],
                    [
                        'title' => 'Countries',
                        'link' => 'admin.web.countries',
                        'active_links' => ['admin.web.countries'],
                        'permission' => 'general-settings-management',
                    ],
                    [
                        'title' => 'Currencies',
                        'link' => 'admin.web.currencies',
                        'active_links' => ['admin.web.currencies'],
                        'permission' => 'currencies-management',
                    ],
                ],
            ],
            [
                'title' => 'Add-ons',
                'icon' => 'metismenu-icon fa fa-puzzle-piece',
                'subItems' => [
                    [
                        'title' => 'Marketplace',
                        'link' => 'admin.web.add_ons.marketplace',
                        'active_links' => ['admin.web.add_ons.marketplace'],
                        'permission' => 'add-ons-marketplace-management',
                    ],
                    [
                        'title' => 'Modules',
                        'link' => 'admin.web.add_ons.modules',
                        'active_links' => ['admin.web.add_ons.modules'],
                        'permission' => 'add-ons-modules-management',
                    ],
                ],
            ],

        ],

    ],
    [
        'title' => 'Customization',
        'subItems' => [],
    ],
];
