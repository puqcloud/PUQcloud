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

use App\Models\Admin;
use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    protected $email;

    protected $password;

    protected $name;

    public function __construct($email = 'admin@example.com', $password = 'QWEqwe123', $name = 'Default Name')
    {
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_admin_group_name = 'Super Admin';

        $adminData = [
            'firstname' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'disable' => false,
            'dashboard' => '[]',
            'client_summary_dashboard' => '[]',
        ];

        $admin = Admin::where('email', $this->email)->first();

        if ($admin) {
            $admin->update($adminData);
        } else {
            $admin = Admin::create($adminData);
        }

        $admin->refresh();
        $super_admin_group = Group::where('name', $super_admin_group_name)->first();
        if ($super_admin_group) {
            $admin->addGroup($super_admin_group->uuid);
        }
    }
}
