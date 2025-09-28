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

namespace App\Models;

use App\Services\AdminSidebarService;
use App\Traits\ConvertsTimezone;
use App\Traits\ModelActivityLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Str;

/**
 * @property string $email
 * @property string $phone_number
 * @property bool $two_factor
 * @property bool $disable
 * @property bool $email_verified
 * @property string $firstname
 * @property string $lastname
 * @property string $admin_notes
 * @property string $language
 * @property string $dashboard
 * @property Group $groups
 */
class Admin extends Model implements Authenticatable, AuthorizableContract
{
    use Authorizable;
    use ConvertsTimezone;
    use \Illuminate\Auth\Authenticatable;
    use ModelActivityLogger;

    protected string $guard_name = 'admin';

    protected $table = 'admins';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $tags = [
                'CheckingAdminPermissionsJob',
            ];
            Task::add('CheckingAdminPermissionsJob', 'System', [], $tags);
        });

        static::saved(function ($model) {
            $tags = [
                'CheckingAdminPermissionsJob',
            ];
            Task::add('CheckingAdminPermissionsJob', 'System', [], $tags);
        });
    }

    protected $fillable = [
        'email',
        'phone_number',
        'password',
        'two_factor',
        'disable',
        'email_verified',
        'firstname',
        'lastname',
        'admin_notes',
        'language',
        'dashboard',
        'client_summary_dashboard',
    ];

    protected $casts = [
        'disable' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'admin_x_group', 'admin_uuid', 'group_uuid');
    }

    public function adminSessionLog(): HasMany
    {
        return $this->hasMany(AdminSessionLog::class, 'admin_uuid', 'uuid');
    }

    public function getLanguageData(): array
    {
        $language = [];
        foreach (config('locale.admin.locales') as $key => $value) {
            if ($key == $this->language) {
                $language = [
                    'id' => $key,
                    'text' => $value['name'].' ('.$value['native'].')',
                ];
            }
        }

        return $language;
    }

    public function getGroupsData(): array
    {
        $groups = [];
        foreach ($this->groups as $group) {
            $groups[] = [
                'id' => $group->uuid,
                'text' => $group->name,
            ];
        }

        return $groups;
    }

    public function addGroup($groupUuid): void
    {
        if (! $this->groups()->where('group_uuid', $groupUuid)->exists()) {
            $this->groups()->attach($groupUuid);
        }
    }

    public function removeGroup($groupUuid): void
    {
        if ($this->groups()->where('group_uuid', $groupUuid)->exists()) {
            $this->groups()->detach($groupUuid);
        }
    }

    public function getSidebar(): array
    {
        $sidebar = new AdminSidebarService;

        return $sidebar->getMenu();
    }

    public function hasPermission($permissionName): bool
    {
        foreach ($this->groups as $group) {
            if ($group->type == 'groups') {
                foreach ($group->groups as $group2) {
                    $permissionsArray = $group2->adminPermissions();
                    if ($this->permissionExistsInArray($permissionsArray, $permissionName)) {
                        return true;
                    }
                }
            } else {
                $permissionsArray = $group->permissions();
                if ($this->permissionExistsInArray($permissionsArray, $permissionName)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAllPermissions(): array
    {
        $permissions = [];
        foreach ($this->groups as $group) {
            if ($group->type == 'groups') {
                foreach ($group->groups as $group2) {
                    $permissions = array_merge($permissions, array_column($group2->adminPermissions(), 'key'));
                }
            } else {
                $permissions = array_merge($permissions, array_column($group->permissions(), 'key'));
            }
        }
        $permissions = array_unique($permissions);

        return array_values($permissions);
    }

    public function notificationRule(string $category, string $notification): bool|NotificationRule
    {
        if ($this->disable) {
            return false;
        }
        foreach ($this->groups as $group) {
            if ($group->type == 'groups') {
                foreach ($group->groups as $group2) {
                    foreach ($group2->notificationrules as $notificationrule) {
                        if ($notificationrule->category == $category and $notificationrule->notification == $notification) {
                            return $notificationrule;
                        }
                    }
                }
            } else {
                foreach ($group->notificationrules as $notificationrule) {
                    if ($notificationrule->category == $category and $notificationrule->notification == $notification) {
                        return $notificationrule;
                    }
                }
            }
        }

        return false;
    }

    private function permissionExistsInArray(array $permissionsArray, string $permissionName): bool
    {
        $permissionKeys = array_column($permissionsArray, 'key');

        return in_array($permissionName, $permissionKeys, true);
    }

    public function adminPermissions(): array
    {
        $permissions = [];
        foreach ($this->groups as $group) {
            if ($group->type == 'groups') {
                foreach ($group->groups as $group2) {
                    foreach ($group2->adminPermissions() as $permission2) {
                        $permissions[$permission2['key']] = $permission2;
                    }
                }
            } else {
                foreach ($group->permissions() as $permission) {
                    $permissions[$permission['key']] = $permission;
                }
            }
        }

        return $permissions;
    }

    public function ips(): HasMany
    {
        return $this->hasMany(AdminIP::class, 'admin_uuid', 'uuid');
    }

    public function updateIpAddress($ip): void
    {
        $latestIp = $this->ips()->orderBy('created_at', 'desc')->first();

        $now = Carbon::now();

        if ($latestIp && $latestIp->ip_address === $ip) {
            $latestIp->update([
                'stop_use' => $now,
            ]);
        } else {
            $this->ips()->create([
                'ip_address' => $ip,
                'start_use' => $now,
                'stop_use' => $now,
            ]);
        }
    }

    public function getHomeCompany(): HomeCompany
    {
        $home_company = HomeCompany::query()->where('default', true)->first();

        return $home_company;
    }
}
