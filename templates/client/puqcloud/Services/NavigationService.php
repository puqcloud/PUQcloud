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

namespace Template\Client\Services;

use AllowDynamicProperties;
use App\Models\Client;
use App\Models\ProductGroup;
use App\Models\Service;
use App\Models\User;

#[AllowDynamicProperties] class NavigationService
{
    protected User $user;

    protected Client $client;

    public array $incidents;

    public function __construct()
    {
        $this->user = app('user');
        $this->client = app('client');
        $this->incidents = $this->getIncidents();
    }

    public function getSidebar(): array
    {
        $menu = [
            [
                'title' => '',
                'link' => '#',
                'subItems' => [
                    [
                        'title' => __('main.Dashboard'),
                        'icon' => 'metismenu-icon fa fa-chart-line',
                        'link' => route('client.web.panel.dashboard'),
                        'active_links' => [],
                        'permission' => '',
                    ],
                ],
            ],
            [
                'title' => __('main.Cloud'),
                'link' => '#',
                'subItems' => $this->getServices(),

            ],
        ];

        return $menu;
    }

    protected function getServices(): array
    {
        $client = app('client');
        $items = [];
        $product_groups = ProductGroup::query()
            ->where('hidden', false)
            ->orderBy('order')
            ->select('key', 'uuid', 'icon')
            ->get();

        foreach ($product_groups as $product_group) {
            $product_group->setLocale(session('locale'));
            $icon = (! empty($product_group) && ! empty($product_group->icon)) ? $product_group->icon : 'fa fa-cloud-sun';
            $link = route('client.web.panel.cloud.group', $product_group->uuid);
            $title = empty($product_group->name) ? $product_group->key : $product_group->name;

            $service_count = Service::clientServicesByGroup($client, $product_group->uuid)->whereIn('status', ['pending', 'active', 'suspended'])->count();
            $items[] = [
                'title' => $title,
                'icon' => $icon.' metismenu-icon',
                'link' => $link,
                'active' => request()->url() === $link,
                'service_count' => $service_count,
                'order' => $product_group->order - $service_count,
            ];
        }

        usort($items, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $items;
    }

    public function getClientMenu(): array
    {
        $menu = [];
        $configPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud').'/config/clientMenu.php');

        if (file_exists($configPath)) {
            $menuFromFile = include $configPath;

            foreach ($menuFromFile as $item) {
                $menu[] = [
                    'icon' => $item['icon'] ?? '',
                    'label' => $item['label'] ?? '',
                    'url' => $item['url'] ?? '#',
                ];
            }
        }

        return $menu;
    }

    public function getUserMenu(): array
    {
        $menu = [];
        $configPath = base_path('templates/client/'.env('TEMPLATE_CLIENT', 'puqcloud').'/config/userMenu.php');

        if (file_exists($configPath)) {
            $menuFromFile = include $configPath;

            foreach ($menuFromFile as $item) {
                if (! empty($item['divider'])) {
                    $menu[] = ['divider' => true];
                } else {
                    $menu[] = [
                        'icon' => $item['icon'] ?? '',
                        'label' => $item['label'] ?? '',
                        'url' => $item['url'] ?? '#',
                    ];
                }
            }
        }

        return $menu;
    }

    public function getIncidents(): array
    {
        //        $incidents =  [
        //            [
        //                'id' => 1,
        //                'type' => 'info',
        //                'title' => 'Scheduled maintenance on Database Server',
        //                'description' => 'We will be performing scheduled maintenance on our database servers to improve performance and security.',
        //                'created' => '2025-05-15 22:00 GMT',
        //                'updated' => '1 day ago',
        //                'url' => 'https://status.example.com/incident/1',
        //            ],
        //            [
        //                'id' => 2,
        //                'type' => 'success',
        //                'title' => 'Issue resolved: API Latency',
        //                'description' => 'The API latency issues have been successfully resolved. All services are now operating normally.',
        //                'created' => '2025-05-10 10:30 GMT',
        //                'updated' => '3 days ago',
        //                'url' => 'https://status.example.com/incident/2',
        //            ],
        //            [
        //                'id' => 3,
        //                'type' => 'warning',
        //                'title' => 'Degraded performance in EU zone',
        //                'description' => 'Users in the EU zone may experience slower response times. Our team is actively working on a solution.',
        //                'created' => '2025-05-17 18:00 GMT',
        //                'updated' => '2 hours ago',
        //                'url' => 'https://status.example.com/incident/3',
        //            ],
        //            [
        //                'id' => 4,
        //                'type' => 'danger',
        //                'title' => 'Outage: Cloud plans unavailable',
        //                'description' => 'All cloud plans are currently unavailable due to a critical system failure. Emergency maintenance is in progress.',
        //                'created' => '2025-05-18 04:00 GMT',
        //                'updated' => 'Just now',
        //                'url' => 'https://status.example.com/incident/4',
        //            ],
        //        ];
        $id = 0;
        $incidents = [];
        $verifications = $this->user->verifications()->where('type', 'email')->where('verified', false)->get();
        foreach ($verifications as $verification) {
            $incidents[] = [
                'id' => $id + 1,
                'type' => 'danger',
                'title' => __('main.Email Verification Pending'),
                'description' => __('main.Your email ":email" has not been verified yet. Please verify it to unlock full access', [
                    'email' => $verification->value,
                ]),
                'created' => $verification->created_at,
                'updated' => $verification->updated_at,
                'url' => route('client.web.panel.user.verification_center'),
            ];
        }
        $client = app('client');

        if ($client->status === 'new') {
            $incidents[] = [
                'id' => $id + 1,
                'type' => 'warning',
                'title' => __('main.Complete Your Profile'),
                'description' => __('main.We need some additional information to activate your account'),
                'created' => $client->created_at,
                'updated' => now(),
                'url' => route('client.web.panel.client.profile'),
            ];
        }

        return $incidents;
    }
}
