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

use App\Models\NotificationLayout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class NotificationLayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $templatesPath = base_path('database/NotificationLayouts');

        $files = File::allFiles($templatesPath);
        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $this->importLayoutFromFile($templatesPath, $file->getFilenameWithoutExtension());
            }
        }
    }

    protected function importLayoutFromFile(string $path, string $fileName): void
    {
        $jsonFilePath = $path.'/'.$fileName.'.json';
        $htmlFilePath = $path.'/'.$fileName.'.blade.php';

        if (File::exists($jsonFilePath)) {
            $content = File::get($jsonFilePath);
            $data = json_decode($content, true);

            if (File::exists($htmlFilePath)) {
                $htmlContent = File::get($htmlFilePath);
                $data['layout'] = $htmlContent;
            }

            $layout = NotificationLayout::query()->where('name', $data['name'] ?? '')->first();

            if (! $layout) {
                $layout = new NotificationLayout;
            }
            $layout->name = $data['name'] ?? '';
            $layout->description = $data['description'] ?? '';
            $layout->save();

            foreach (explode(',', env('APP_AVAILABLE_LOCALE_CLIENT', 'en')) as $locale) {
                $layout->setLocale($locale);
                $layout->layout = $data['layout'] ?? '';
                $layout->save();
            }
        }
    }
}
