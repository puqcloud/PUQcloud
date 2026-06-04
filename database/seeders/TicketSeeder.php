<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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

use App\Models\TicketDepartment;
use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedStatuses();
        $this->seedDepartments();
    }

    private function seedStatuses(): void
    {
        $statuses = [
            [
                'title' => 'Draft',
                'color' => '#adb5bd',
                'include_in_active' => false,
                'include_in_awaiting_reply' => false,
                'sort_order' => 0,
                'is_default' => true,
            ],
            [
                'title' => 'Open',
                'color' => '#28a745',
                'include_in_active' => true,
                'include_in_awaiting_reply' => true,
                'sort_order' => 1,
                'is_default' => true,
            ],
            [
                'title' => 'Answered',
                'color' => '#007bff',
                'include_in_active' => true,
                'include_in_awaiting_reply' => false,
                'sort_order' => 2,
                'is_default' => true,
            ],
            [
                'title' => 'Customer-Reply',
                'color' => '#ffc107',
                'include_in_active' => true,
                'include_in_awaiting_reply' => true,
                'sort_order' => 3,
                'is_default' => true,
            ],
            [
                'title' => 'Closed',
                'color' => '#6c757d',
                'include_in_active' => false,
                'include_in_awaiting_reply' => false,
                'sort_order' => 4,
                'is_default' => true,
            ],
            [
                'title' => 'On Hold',
                'color' => '#fd7e14',
                'include_in_active' => true,
                'include_in_awaiting_reply' => false,
                'sort_order' => 5,
                'is_default' => true,
            ],
            [
                'title' => 'In Progress',
                'color' => '#17a2b8',
                'include_in_active' => true,
                'include_in_awaiting_reply' => false,
                'sort_order' => 6,
                'is_default' => true,
            ],
        ];

        foreach ($statuses as $status) {
            TicketStatus::updateOrCreate(
                ['title' => $status['title']],
                $status
            );

            dump("Ticket status seeded: {$status['title']}");
        }
    }

    private function seedDepartments(): void
    {
        $fileDefaults = [
            'allowed_file_extensions' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar',
            'max_file_size_mb' => 5,
            'max_files_count' => 5,
        ];

        $departments = [
            array_merge([
                'name' => 'General Support',
                'description' => 'General support inquiries',
                'email' => null,
                'hidden' => false,
                'sort_order' => 1,
            ], $fileDefaults),
            array_merge([
                'name' => 'Billing',
                'description' => 'Billing and payment inquiries',
                'email' => null,
                'hidden' => false,
                'sort_order' => 2,
            ], $fileDefaults),
            array_merge([
                'name' => 'Technical Support',
                'description' => 'Technical issues and service problems',
                'email' => null,
                'hidden' => false,
                'sort_order' => 3,
            ], $fileDefaults),
        ];

        foreach ($departments as $department) {
            $model = TicketDepartment::where('name', $department['name'])->first();

            if (! $model) {
                TicketDepartment::create($department);
                dump("Ticket department created: {$department['name']}");
            } else {
                $model->update($department);
                dump("Ticket department updated: {$department['name']}");
            }
        }
    }
}
