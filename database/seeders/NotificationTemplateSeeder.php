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

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templatesPath = base_path('database/AdminNotificationTemplates');

        $files = File::allFiles($templatesPath);
        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $this->importTemplateFromFile($templatesPath, $file->getFilenameWithoutExtension());
            }
        }

        $templatesPath = base_path('database/ClientNotificationTemplates');

        $files = File::allFiles($templatesPath);
        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $this->importTemplateFromFile($templatesPath, $file->getFilenameWithoutExtension());
            }
        }
    }

    protected function importTemplateFromFile(string $path, string $fileName): void
    {
        $jsonFilePath = $path.'/'.$fileName.'.json';
        $htmlFilePath = $path.'/'.$fileName.'.blade.php';

        if (! File::exists($jsonFilePath)) {
            dump("JSON file not found: $jsonFilePath");

            return;
        }

        $content = File::get($jsonFilePath);
        $data = json_decode($content, true);

        if (! isset($data['name']) || ! isset($data['category'])) {
            dump("Missing 'name' or 'category' in: $jsonFilePath");

            return;
        }

        if (File::exists($htmlFilePath)) {
            $htmlContent = File::get($htmlFilePath);
            $data['text'] = $htmlContent;
        } else {
            dump("HTML file not found, using text from JSON (if any): $htmlFilePath");
        }

        $template = NotificationTemplate::query()
            ->where('name', $data['name'])
            ->where('category', $data['category'])
            ->first();

        if (! $template) {
            dump("Creating new template: {$data['name']} / {$data['category']}");
            $template = new NotificationTemplate;
        } else {
            dump("Updating existing template: {$data['name']} / {$data['category']}");
        }

        $template->name = $data['name'];
        $template->category = $data['category'];
        if (! $template->save()) {
            dump("Failed to save base template: {$template->name}");
        }

        $locales = explode(',', env('APP_AVAILABLE_LOCALE_CLIENT', 'en'));
        foreach ($locales as $locale) {
            $locale = trim($locale);
            $template->setLocale($locale);
            $template->custom = $data['custom'] ?? true;
            $template->subject = $data['subject'] ?? '';
            $template->text = $data['text'] ?? '';
            $template->text_mini = $data['text_mini'] ?? '';

            if ($template->save()) {
                dump("Saved locale [$locale] for template: {$template->name}");
            } else {
                dump("Failed to save locale [$locale] for template: {$template->name}");
            }
        }
    }
}
