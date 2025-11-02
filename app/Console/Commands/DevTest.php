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

namespace App\Console\Commands;

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use phpseclib3\Net\SSH2;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DevTest extends Command
{
    protected $signature = 'Dev:test';
    protected $description = 'Count non-empty lines in files inside specified folders';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle()
    {
        $folders = [
            base_path('app'),
            base_path('database'),
            base_path('modules'),
            base_path('lang'),
            base_path('routes'),
            base_path('templates'),
        ];

        $results = [];

        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                $this->warn("Folder not found: $folder");

                continue;
            }
            $this->scanDirectory($folder, $results);
        }

        $this->line(str_pad('File Path', 80) . ' | Lines');
        $this->line(str_repeat('-', 90));

        $totalFiles = count($results);
        $totalLines = 0;

        foreach ($results as $item) {
            $totalLines += $item['lines'];
            $this->line(str_pad($item['path'], 80) . ' | ' . $item['lines']);
        }

        $this->line(str_repeat('-', 90));
        $this->info("Total files: $totalFiles");
        $this->info("Total non-empty lines: $totalLines");

        return 0;
    }

    protected function scanDirectory(string $path, array &$results)
    {
        $extensions = ['php', 'js', 'ts', 'vue', 'blade.php', 'css', 'scss'];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $ext = $file->getExtension();

            $filename = $file->getFilename();
            if ($filename === 'blade.php' || str_ends_with($filename, '.blade.php')) {
                $ext = 'blade.php';
            }

            if (!in_array($ext, $extensions)) {
                continue;
            }

            $fullPath = $file->getPathname();
            $lines = $this->countNonEmptyLines($fullPath);

            $results[] = [
                'path' => $fullPath,
                'lines' => $lines,
            ];
        }
    }

    protected function countNonEmptyLines(string $file): int
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $count++;
            }
        }

        return $count;
    }
}
