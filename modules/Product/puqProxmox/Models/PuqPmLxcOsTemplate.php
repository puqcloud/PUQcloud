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

namespace Modules\Product\puqProxmox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PuqPmLxcOsTemplate extends Model
{
    protected $table = 'puq_pm_lxc_os_templates';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'distribution',
        'version',
        'puq_pm_lxc_template_uuid',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmLxcTemplate(): BelongsTo
    {
        return $this->belongsTo(PuqPmLxcTemplate::class, 'puq_pm_lxc_template_uuid', 'uuid');
    }


    public function puqPmScripts(): HasMany
    {
        return $this->hasMany(PuqPmScript::class, 'puq_pm_lxc_os_template_uuid', 'uuid');
    }


}
