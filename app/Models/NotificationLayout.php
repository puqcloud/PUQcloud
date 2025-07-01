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

use App\Traits\AutoTranslatable;
use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 * @property string $locale
 * @property string $name
 * @property string $description
 * @property string $layout
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationLayout query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\NotificationLayout find(string)
 */
class NotificationLayout extends Model
{
    use AutoTranslatable;
    use ConvertsTimezone;
    use HasFactory;

    protected $table = 'notification_layouts';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    protected $fillable = [
        'name',
        'description',
    ];

    protected $translatable = ['layout'];
}
