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

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupService
{
    /**
     * Delete old records from a specified model based on a time-based key
     * and log the details.
     *
     * @param  string  $modelClass  The model class from which to delete records.
     * @param  string  $timeBasedKey  The key to retrieve the retention period from settings.
     * @param  array  $conditions  Additional where conditions to apply.
     */
    public static function deleteOldRecords(string $modelClass, string $timeBasedKey, array $conditions = []): void
    {
        // Check if the model class exists
        if (! class_exists($modelClass)) {
            Log::warning("Model class '$modelClass' does not exist. Skipping deletion.");

            return;
        }

        // Get the number of days to keep records based on the provided key
        $daysToKeep = SettingService::get($timeBasedKey);

        // Validate that $daysToKeep is set and greater than 0
        if (is_null($daysToKeep) || $daysToKeep <= 0) {
            Log::warning("Invalid retention period '$daysToKeep' for key '$timeBasedKey'. Skipping deletion.");

            return;
        }

        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        // Build the query with the cutoff date and additional conditions
        $query = $modelClass::query()->where('created_at', '<', $cutoffDate);

        // Apply additional conditions if provided
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        // Count the records to be deleted for logging purposes
        $recordsToDelete = $query->count();

        // Delete the records
        $deletedRecords = $query->delete();

        // Log the deletion details
        Log::info('Deleting old records', [
            'Model' => $modelClass,
            'Time-Based Key' => $timeBasedKey,
            'Deletion Date' => Carbon::now()->toDateTimeString(),
            'Cutoff Date' => $cutoffDate->toDateTimeString(),
            'Conditions' => $conditions,
            'Records Marked for Deletion' => $recordsToDelete,
            'Records Successfully Deleted' => $deletedRecords,
        ]);
    }
}
