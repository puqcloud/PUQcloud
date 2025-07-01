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

use App\Models\Module;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PuqNextcloudServer extends Model
{
    protected $table = 'puq_nextcloud_servers';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'group_uuid',
        'host',
        'username',
        'password',
        'active',
        'max_accounts',
        'ssl',
        'port',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqNextcloudServerGroup(): BelongsTo
    {
        return $this->belongsTo(PuqNextcloudServerGroup::class, 'group_uuid', 'uuid');
    }

    public function getUseAccounts(): int
    {
        $module = Module::query()->where('name', 'puqNextcloud')->first();
        if (! $module) {
            return 0;
        }

        return Service::query()->whereIn('status', ['active', 'suspended'])->where('provision_data', 'like', "%$this->uuid%")->count();
    }
}
