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

use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Group extends Model
{
    use ConvertsTimezone;

    protected $table = 'groups';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::saved(function ($model) {
            $tags = [
                'CheckingAdminPermissionsJob',
            ];
            Task::add('CheckingAdminPermissionsJob', 'System', [], $tags);
        });
    }

    protected $fillable = [
        'name',
        'description',
        'type',
        'related',
    ];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_x_group', 'group_uuid', 'admin_uuid');
    }

    public function addRelatedGroup(Group $group): void
    {
        if ($this->type == 'groups' && $group->type !== 'groups') {
            $exists = DB::table('group_x_group')
                ->where('group_uuid', $this->uuid)
                ->where('related_group_uuid', $group->uuid)
                ->exists();
            if (! $exists) {
                DB::table('group_x_group')->insert([
                    'group_uuid' => $this->uuid,
                    'related_group_uuid' => $group->uuid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function removeRelatedGroup(Group $group): void
    {
        if ($this->type == 'groups' && $group->type !== 'groups') {
            $exists = DB::table('group_x_group')
                ->where('group_uuid', $this->uuid)
                ->where('related_group_uuid', $group->uuid)
                ->exists();

            if ($exists) {
                DB::table('group_x_group')
                    ->where('group_uuid', $this->uuid)
                    ->where('related_group_uuid', $group->uuid)
                    ->delete();
            }
        }
    }

    // groups
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_x_group', 'group_uuid', 'related_group_uuid');
    }

    public function getAllGroups()
    {
        $allGroups = Group::where('type', '!=', 'groups')->get();
        $linkedGroups = $this->groups->pluck('uuid')->toArray() ?? [];
        $result = $allGroups->map(function ($group) use ($linkedGroups) {
            $to_result = $group->toArray() ?? [];
            $to_result['is_linked'] = in_array($group->uuid, $linkedGroups);

            return $to_result;
        });

        return $result;
    }

    // permissions
    public function adminPermissions(): array
    {
        $permissions = app('AdminPermission')->getAllPermissions();
        $groupPermissions = DB::table('group_permissions')
            ->where('group_uuid', $this->uuid)
            ->pluck('permission_key')
            ->toArray() ?? [];

        $result = [];
        foreach ($permissions as $permission) {
            if (in_array($permission['key'], $groupPermissions)) {
                $result[] = $permission;
            }
        }

        return $result;
    }

    public function getSystemPermissions(): array
    {
        $permissions = app('AdminPermission')->getSystemPermissions();
        $groupPermissions = DB::table('group_permissions')
            ->where('group_uuid', $this->uuid)
            ->pluck('permission_key')
            ->toArray() ?? [];

        foreach ($permissions as &$permission) {
            $permission['is_linked'] = in_array($permission['key'], $groupPermissions);
        }

        return $permissions;
    }

    public function getAdminTemplatePermissions(): array
    {
        $permissions = app('AdminPermission')->getAdminTemplatePermissions();
        $groupPermissions = DB::table('group_permissions')
            ->where('group_uuid', $this->uuid)
            ->pluck('permission_key')
            ->toArray() ?? [];

        foreach ($permissions as &$permission) {
            $permission['is_linked'] = in_array($permission['key'], $groupPermissions);
        }

        return $permissions;
    }

    public function getClientTemplatePermissions(): array
    {
        $permissions = app('AdminPermission')->getClientTemplatePermissions();
        $groupPermissions = DB::table('group_permissions')
            ->where('group_uuid', $this->uuid)
            ->pluck('permission_key')
            ->toArray() ?? [];

        foreach ($permissions as &$permission) {
            $permission['is_linked'] = in_array($permission['key'], $groupPermissions);
        }

        return $permissions;
    }

    public function getModulesPermissions(): array
    {
        $permissions = app('AdminPermission')->getModulesPermissions();
        $groupPermissions = DB::table('group_permissions')
            ->where('group_uuid', $this->uuid)
            ->pluck('permission_key')
            ->toArray() ?? [];

        foreach ($permissions as &$permission) {
            $permission['is_linked'] = in_array($permission['key'], $groupPermissions);
        }

        return $permissions;
    }

    public function addPermission($permissionKey): void
    {

        $permissions = collect(app('AdminPermission')->getAllPermissions())->pluck('key')->toArray() ?? [];
        if (! in_array($permissionKey, $permissions)) {
            return;
        }

        $exists = DB::table('group_permissions')
            ->where('group_uuid', $this->uuid)
            ->where('permission_key', $permissionKey)
            ->exists();

        if (! $exists) {
            DB::table('group_permissions')->insert([
                'group_uuid' => $this->uuid,
                'permission_key' => $permissionKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function removePermission($permissionKey): void
    {
        DB::table('group_permissions')
            ->where('group_uuid', $this->uuid)
            ->where('permission_key', $permissionKey)
            ->delete();
    }

    public function notificationRules(): HasMany
    {
        return $this->hasMany(NotificationRule::class, 'group_uuid', 'uuid');
    }
}
