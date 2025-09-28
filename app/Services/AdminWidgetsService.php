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

class AdminWidgetsService
{
    public static function getAdminDashboardWidgets(): array
    {
        $widgets = self::getAdminSystemDashboardWidgets();

        return $widgets;
    }

    private static function getAdminSystemDashboardWidgets(): array
    {
        $admin = app('admin');
        $admin_widgets = config('adminDashboardWidgets');
        $widgets = [];

        foreach ($admin_widgets as $key => $value) {

            $value['name'] = __('main.'.$value['name']);
            $value['description'] = __('main.'.$value['description']);

            if (! empty($value['permission']) and $admin->hasPermission($value['permission'])) {
                $widgets[] = $value;
            }
            if (empty($value['permission'])) {
                $widgets[] = $value;
            }
        }

        return $widgets;
    }

    public static function getAdminClientSummaryWidgets(): array
    {
        $widgets = self::getAdminSystemClientSummaryWidgets();

        return $widgets;
    }

    private static function getAdminSystemClientSummaryWidgets(): array
    {
        $admin = app('admin');
        $admin_widgets = config('adminClientSummaryWidgets');
        $widgets = [];

        foreach ($admin_widgets as $key => $value) {

            $value['name'] = __('main.'.$value['name']);
            $value['description'] = __('main.'.$value['description']);

            if (! empty($value['permission']) and $admin->hasPermission($value['permission'])) {
                $widgets[] = $value;
            }
            if (empty($value['permission'])) {
                $widgets[] = $value;
            }
        }

        return $widgets;
    }
}
