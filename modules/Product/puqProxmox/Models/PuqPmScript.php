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
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PuqPmScript extends Model
{
    protected $table = 'puq_pm_scripts';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'type',
        'script',
        'model',
        'model_uuid',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmScriptLog(): HasMany
    {
        return $this->hasMany(PuqPmScriptLog::class, 'puq_pm_script_uuid', 'uuid');
    }

    public function puqPmLxcOsTemplate(): BelongsTo
    {
        return $this->belongsTo(
            PuqPmLxcOsTemplate::class,
            'model_uuid',
            'uuid'
        )->where('model', PuqPmLxcOsTemplate::class);
    }

    public function puqPmAppPreset(): BelongsTo
    {
        return $this->belongsTo(
            PuqPmAppPreset::class,
            'model_uuid',
            'uuid'
        )->where('model', PuqPmAppPreset::class);
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'model', 'model_uuid');
    }

    public function base64(): string
    {
        return base64_encode($this->script ?? '');
    }
}
