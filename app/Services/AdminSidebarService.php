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

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminSidebarService
{
    protected array $menu;

    public function getMenu(): array
    {
        $this->menu = config('sidebar');
        foreach ($this->menu as $itemKey => &$item) {
            if ($item['title'] == 'Customization') {
                $item['subItems'] = array_merge($item['subItems'], $this->getAdminTemplatesSidebar(), $this->getClientTemplatesSidebar(), $this->getModuleSidebars());
            }
        }

        $admin = app('admin');
        $this->checkPermissions($this->menu, $admin);
        $this->updateMenuLinksAndTitles($this->menu);

        return $this->menu;
    }

    protected function checkPermissions(array &$menu, $admin): void
    {
        foreach ($menu as $itemKey => &$item) {
            if (! empty($item['subItems'])) {
                $this->checkPermissions($item['subItems'], $admin);

                if (empty($item['subItems'])) {
                    unset($menu[$itemKey]);
                }
            }

            if (! empty($item['permission']) && ! $admin->hasPermission($item['permission'])) {
                unset($menu[$itemKey]);
            }
        }

        foreach ($menu as $itemKey => &$item) {
            if (! empty($item['subItems'])) {
                foreach ($item['subItems'] as $subItemKey => &$subItem) {
                    if (! empty($subItem['subItems'])) {
                        foreach ($subItem['subItems'] as $subSubItemKey => $subSubItem) {
                            if (! empty($subSubItem['permission']) && ! $admin->hasPermission($subSubItem['permission'])) {
                                unset($subItem['subItems'][$subSubItemKey]);
                            }
                        }
                    }

                    if (empty($subItem['subItems']) && ! empty($subItem['permission']) && ! $admin->hasPermission($subItem['permission'])) {
                        unset($item['subItems'][$subItemKey]);
                    }
                }

                if (empty($item['subItems'])) {
                    unset($menu[$itemKey]);
                }
            }
        }
    }

    protected function updateMenuLinksAndTitles(array &$menu): void
    {
        $currentRoute = Route::current();
        $currentRouteName = $currentRoute->getName();
        foreach ($menu as $itemKey => &$item) {
            if (! empty($item['subItems'])) {
                foreach ($item['subItems'] as $subItemKey => &$subItem) {
                    if (! empty($subItem['subItems'])) {
                        foreach ($subItem['subItems'] as $subSubItemKey => &$subSubItem) {
                            if (! empty($subSubItem['link']) && Route::has($subSubItem['link'])) {
                                $subSubItem['link'] = route($subSubItem['link']);

                                if (! empty($subSubItem['active_links']) and in_array($currentRouteName, $subSubItem['active_links'])) {
                                    $subSubItem['active'] = true;
                                }
                            }

                            if (! empty($subSubItem['title'])) {
                                if (empty($subSubItem['not_translate'])) {
                                    $subSubItem['title'] = __('navi.'.$subSubItem['title']);
                                }
                            }
                        }
                    }
                    if (! empty($subItem['link']) && Route::has($subItem['link'])) {
                        $subItem['link'] = route($subItem['link']);
                        if (! empty($subItem['active_links']) and in_array($currentRouteName, $subItem['active_links'])) {
                            $subItem['active'] = true;
                        }
                    }

                    if (! empty($subItem['title'])) {
                        if (empty($subItem['not_translate'])) {
                            $subItem['title'] = __('navi.'.$subItem['title']);
                        }
                    }
                }
            }

            if (! empty($item['title'])) {
                if (empty($item['not_translate'])) {
                    $item['title'] = __('navi.'.$item['title']);
                }
            }
        }
    }

    protected function getAdminTemplatesSidebar(): array
    {
        $menu = [];
        if (file_exists(config('template.admin.base_path').'/sidebar.php')) {
            $items = include config('template.admin.base_path').'/sidebar.php';
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (! isset($item['title'], $item['route'], $item['active_links'], $item['permission'])) {
                        continue;
                    }
                    $active_links = [];
                    foreach ($item['active_links'] as $active_link) {
                        $active_links[] = 'admin.web.admin_template.'.$active_link;
                    }
                    $menu[] = [
                        'title' => $item['title'],
                        'link' => 'admin.web.admin_template.'.$item['route'],
                        'active_links' => $active_links,
                        'permission' => 'adminTemplate-'.$item['permission'],
                        'not_translate' => true,
                    ];
                }
            }
        }
        if (empty($menu)) {
            return [];
        }

        return [[
            'title' => 'Admin Template',
            'icon' => 'metismenu-icon fa fa-columns',
            'subItems' => $menu,
        ]];
    }

    protected function getClientTemplatesSidebar(): array
    {
        $menu = [];
        if (file_exists(config('template.client.base_path').'/config/adminSidebar.php')) {
            $items = include config('template.client.base_path').'/config/adminSidebar.php';
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (! isset($item['title'], $item['route'], $item['active_links'], $item['permission'])) {
                        continue;
                    }
                    $active_links = [];
                    foreach ($item['active_links'] as $active_link) {
                        $active_links[] = 'admin.web.client_template.'.$active_link;
                    }
                    $menu[] = [
                        'title' => $item['title'],
                        'link' => 'admin.web.client_template.'.$item['route'],
                        'active_links' => $active_links,
                        'permission' => 'clientTemplate-'.$item['permission'],
                        'not_translate' => true,
                    ];
                }
            }
        }
        if (empty($menu)) {
            return [];
        }

        return [[
            'title' => 'Client Template',
            'icon' => 'metismenu-icon fa fa-columns',
            'subItems' => $menu,
        ]];
    }

    protected function getModuleSidebars(): array
    {
        $menu = [];
        $modules = app('Modules');

        foreach ($modules as $module) {
            if ($module->status != 'active') {
                continue;
            }
            $module_menu_items = [];
            $items = $module->moduleSidebar();

            foreach ($items as $item) {
                if (! isset($item['title'], $item['link'], $item['active_links'], $item['permission'])) {
                    continue;
                }
                $prefix = $module->type.'.'.$module->name.'.';
                $item_title = Str::replaceFirst($prefix, '', __($prefix.$item['title']));
                $active_links = [];
                foreach ($item['active_links'] as $active_link) {
                    $active_links[] = 'admin.web.'.$module->type.'.'.$module->name.'.'.$active_link;
                }
                $module_menu_items[] =
                    [
                        'title' => $item_title,
                        'link' => 'admin.web.'.$module->type.'.'.$module->name.'.'.$item['link'],
                        'active_links' => $active_links,
                        'permission' => $module->type.'-'.$module->name.'-'.$item['permission'],
                        'not_translate' => true,
                    ];
            }
            if (! empty($module_menu_items)) {
                $icon = $module->module_data['icon'] ?? 'fa fa-columns';
                $prefix = $module->type.'.'.$module->name.'.';
                $title = Str::replaceFirst($prefix, '', __($prefix.($module->module_data['name'] ?? $module->name)));

                $menu[] = [
                    'title' => $title,
                    'icon' => 'metismenu-icon '.$icon,
                    'subItems' => $module_menu_items,
                    'not_translate' => true,
                ];
            }
        }

        return $menu;
    }
}
