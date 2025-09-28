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

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;

class TranslationService
{
    protected static $guard;

    protected static $langPath;

    protected static $langFilesAddition = [];

    protected static $filesystem;

    public static function init($guard = null): void
    {
        if (! in_array($guard, ['admin', 'client'])) {
            return;
        }
        self::$guard = $guard;
        if ($guard === 'client') {
            self::$langPath = config('template.client.base_path').'/lang';
        }
        if ($guard === 'admin') {
            self::$langPath = base_path('lang/'.self::$guard);
        }

        self::$filesystem = new Filesystem;
        self::setAdditionFiles();
        self::setUpTranslator();
    }

    protected static function setUpTranslator(): void
    {
        app()->singleton('translation.loader', function () {
            $loader = new FileLoader(self::$filesystem, self::$langPath);

            foreach (self::$langFilesAddition as $prefix => $langFile) {
                if (self::$filesystem->exists($langFile)) {
                    $customTranslations = self::$filesystem->getRequire($langFile);
                    if (is_array($customTranslations)) {
                        app()->singleton($prefix, fn () => $customTranslations);
                    }
                }
            }

            return $loader;
        });

        app()->singleton('translator', function ($app) {
            /** @var \Illuminate\Translation\LoaderInterface $loader */
            $loader = $app['translation.loader'];
            $locale = $app->getLocale();
            $translator = new Translator($loader, $locale);

            foreach (self::$langFilesAddition as $prefix => $langFile) {
                if ($app->bound($prefix)) {
                    $customTranslations = $app->make($prefix);

                    if (is_array($customTranslations)) {
                        $prefixedTranslations = [];
                        foreach ($customTranslations as $key => $value) {
                            $prefixedTranslations["{$prefix}.{$key}"] = $value;
                        }

                        $translator->addLines($prefixedTranslations, $locale, '*');
                    }
                }
            }

            return $translator;
        });
    }

    protected static function setAdditionFiles(): void
    {
        // Modules
        $modules = app('Modules');
        $locale = app()->getLocale();

        foreach ($modules as $module) {
            $path = base_path("modules/{$module->type}/{$module->name}/lang/{$locale}.php");
            if (file_exists($path)) {
                self::$langFilesAddition[$module->type.'.'.$module->name] = $path;
            }
        }

        // Admin template
        if (self::$guard == 'admin') {
            $adminPath = config('template.admin.base_path')."/lang/{$locale}.php";
            if (file_exists($adminPath)) {
                self::$langFilesAddition['admin_template'] = $adminPath;
            }

            $clientPath = config('template.client.base_path')."/lang/{$locale}/admin_zone.php";
            if (file_exists($clientPath)) {
                self::$langFilesAddition['client_template'] = $clientPath;
            }
        }
    }
}
