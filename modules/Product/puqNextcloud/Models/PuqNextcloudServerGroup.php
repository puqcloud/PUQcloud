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

namespace Modules\Product\puqNextcloud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PuqNextcloudServerGroup extends Model
{
    protected $table = 'puq_nextcloud_server_groups';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'fill_type',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqNextcloudServers(): HasMany
    {
        return $this->hasMany(PuqNextcloudServer::class, 'group_uuid', 'uuid');
    }

    public function getServer(): ?PuqNextcloudServer
    {
        $servers = $this->puqNextcloudServers->where('active', true);
        $fillType = $this->fill_type;

        if ($fillType === 'default') {
            $defaultServer = $servers->firstWhere('default', true);

            if ($defaultServer && $defaultServer->getUseAccounts() < $defaultServer->max_accounts) {
                return $defaultServer;
            }

            return $servers
                ->filter(fn ($s) => $s->getUseAccounts() < $s->max_accounts)
                ->sortBy(fn ($s) => $s->getUseAccounts())
                ->first();
        }

        if ($fillType === 'lowest') {
            return $servers
                ->filter(fn ($s) => $s->getUseAccounts() < $s->max_accounts)
                ->sortBy(fn ($s) => $s->getUseAccounts())
                ->first();
        }

        return null;
    }
}
