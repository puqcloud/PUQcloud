<?php
/*
 * PUQcloud - Free Cloud Billing System
 * Development Utility - Source Code Line Counter
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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DevLineCounter extends Command
{
    protected $signature = 'Dev:count-lines';
    protected $description = 'Count non-empty lines in PUQcloud source directories';

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
        $totalLines = 0;

        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                $this->warn("Folder not found: $folder");
                continue;
            }
            $this->scanDirectory($folder, $results);
        }

        $this->line(str_pad('File Path', 80).' | Lines');
        $this->line(str_repeat('-', 90));

        foreach ($results as $item) {
            $totalLines += $item['lines'];
            $this->line(str_pad($item['path'], 80).' | '.$item['lines']);
        }

        $this->line(str_repeat('-', 90));
        $this->info('Total files: '.count($results));
        $this->info("Total non-empty lines: $totalLines");

        return Command::SUCCESS;
    }

    protected function scanDirectory(string $path, array &$results): void
    {
        $extensions = ['php', 'js', 'ts', 'vue', 'blade.php', 'css', 'scss'];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filename = $file->getFilename();
            $ext = $file->getExtension();

            if ($filename === 'blade.php' || str_ends_with($filename, '.blade.php')) {
                $ext = 'blade.php';
            }

            if (!in_array($ext, $extensions)) {
                continue;
            }

            $results[] = [
                'path' => $file->getPathname(),
                'lines' => $this->countNonEmptyLines($file->getPathname()),
            ];
        }
    }

    protected function countNonEmptyLines(string $file): int
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return count(array_filter($lines, fn($line) => trim($line) !== ''));
    }
}
